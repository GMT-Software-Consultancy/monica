#!/usr/bin/env bash
# Run the document stages for one arm: seed -> (questions -> stand-in PM -> spec -> tickets) or plan.
# Usage: ./run-docs.sh <arm: 0|A|B|C> <feature: pilot|test>
# Output: runs/<run-id>/output (blind: no arm name) and runs/<run-id>/private (transcripts, manifest).
set -euo pipefail
ARM="${1:?arm 0|A|B|C}"; FEATURE="${2:?feature pilot|test}"
H="$(cd "$(dirname "$0")" && pwd)"; REPO="$(cd "$H/../../.." && pwd)"
MODEL="${MODEL:-claude-opus-5-5}"; BASE_TAG="${BASE_TAG:-exp-base-v1}"
CLAUDE="$H/node_modules/.bin/claude"
SHEET="${SHEET_FILE:-$H/../${FEATURE}-pm-oracle.md}"; SEED="$H/seeds/$FEATURE.md"
[[ -s "$SHEET" && -s "$SEED" ]] || { echo "missing seed or answer sheet for $FEATURE" >&2; exit 1; }
# Use the long-lived token if the file holds one; otherwise fall back to the existing login.
TOKEN_FILE="${CCBL_TOKEN_FILE:-$HOME/.config/ccbl/oauth-token}"
if [[ -s "$TOKEN_FILE" ]] && grep -q '^sk-ant-oat01-' "$TOKEN_FILE"; then
  export CLAUDE_CODE_OAUTH_TOKEN="$(cat "$TOKEN_FILE")"
else
  unset CLAUDE_CODE_OAUTH_TOKEN
fi
FLAGS=(--model "$MODEL" --strict-mcp-config --setting-sources "" --permission-mode acceptEdits --output-format stream-json --verbose)
DOC_TOOLS="Read,Write,Edit,Glob,Grep"

RID=$(python3 -c 'import secrets; print(secrets.token_hex(4))')
RUN="$H/runs/$RID"; mkdir -p "$RUN/output" "$RUN/private"
SB="${SANDBOX_ROOT:-${TMPDIR:-/tmp}}/exp-$RID"; rm -rf "$SB"
log(){ printf '%s [%s] %s\n' "$(date -u +%H:%M:%S)" "$RID" "$*" | tee -a "$RUN/private/log.txt"; }

# --- sandbox: base commit only, no history, no other branches ---
git clone -q --depth 1 --branch "$BASE_TAG" "file://$REPO" "$SB" 2>/dev/null
BASE_SHA=$(git -C "$SB" rev-parse HEAD); rm -rf "$SB/.git"
mkdir -p "$SB/output"
ARCH_LINE=""
case "$ARM" in
  A|B|C)
    mkdir -p "$SB/.claude"
    if [[ "$ARM" == B ]]; then KIT="$H/arms/skills-unfenced"; else KIT="$H/arms/skills-fenced"; fi
    cp -R "$KIT" "$SB/.claude/skills"; cp "$H/arms/skill-config.md" "$SB/.claude/skill-config.md"
    cp "$SEED" "$SB/seed.md"
    if [[ "$ARM" != A ]]; then
      mkdir -p "$SB/docs/architecture"; cp -R "$H/arms/arch/." "$SB/docs/architecture/"
      ARCH_LINE=' The architecture doc is in `docs/architecture/` (start with `README.md`).'
    fi ;;
  0) cp "$H/arms/arm0/CLAUDE.md" "$SB/CLAUDE.md" ;;
  *) echo "unknown arm $ARM" >&2; exit 1 ;;
esac
kit_hash(){ (cd "$SB" && { find .claude CLAUDE.md docs/architecture seed.md -type f 2>/dev/null || true; } | LC_ALL=C sort | xargs shasum -a 256 | shasum -a 256 | awk '{print $1}'); }
KIT_HASH=$(kit_hash)

render(){ # render <template> ; substitutes {{ARCH_LINE}} {{SEED}} {{ANSWERS}} {{SHEET}} {{QUESTIONS}} from env files
  python3 - "$1" "$ARCH_LINE" "$SEED" "${ANSWERS_FILE:-/dev/null}" "$SHEET" "${QUESTIONS_FILE:-/dev/null}" <<'PY'
import sys
t=open(sys.argv[1]).read()
for k,v in {"ARCH_LINE":sys.argv[2],"SEED":open(sys.argv[3]).read().strip(),"ANSWERS":open(sys.argv[4]).read().strip(),
            "SHEET":open(sys.argv[5]).read().strip(),"QUESTIONS":open(sys.argv[6]).read().strip()}.items():
    t=t.replace("{{"+k+"}}",v)
print(t)
PY
}
result_field(){ python3 - "$1" "$2" <<'PY'
import json,sys
last=None
for line in open(sys.argv[1]):
    try: e=json.loads(line)
    except Exception: continue
    if e.get("type")=="result": last=e
v=(last or {}).get(sys.argv[2],"")
print(v if isinstance(v,str) else json.dumps(v))
PY
}
stage(){ # stage <name> <tools> <prompt-file> [resume-session]
  local name="$1" tools="$2" prompt="$3" resume="${4:-}" extra=()
  [[ -n "$resume" ]] && extra=(--resume "$resume")
  log "stage $name start"
  ( cd "$SB" && "$CLAUDE" -p "${FLAGS[@]}" --tools "$tools" ${extra[@]+"${extra[@]}"} < "$prompt" > "$RUN/private/$name.jsonl" 2> "$RUN/private/$name.stderr" ) || log "stage $name exited non-zero"
  log "stage $name done: $(result_field "$RUN/private/$name.jsonl" duration_ms)ms, \$$(result_field "$RUN/private/$name.jsonl" total_cost_usd)"
}
stand_in_pm(){ # answers output/questions.md from the sheet, in an empty box with no tools
  local box; box=$(mktemp -d); QUESTIONS_FILE="$SB/output/questions.md" render "$H/prompts/stand-in-pm.md" > "$RUN/private/pm-prompt.md"
  log "stage pm start"
  ( cd "$box" && "$CLAUDE" -p "${FLAGS[@]}" --tools "" < "$RUN/private/pm-prompt.md" > "$RUN/private/pm.jsonl" 2> "$RUN/private/pm.stderr" ) || true
  result_field "$RUN/private/pm.jsonl" result > "$RUN/private/answers.md"; rm -rf "$box"
  { echo "# Questions"; echo; cat "$SB/output/questions.md"; echo; echo "# Stand-in PM answers"; echo; cat "$RUN/private/answers.md"; } > "$RUN/output/qa.md"
  log "stage pm done"
}

T0=$(date -u +%Y-%m-%dT%H:%M:%SZ)
if [[ "$ARM" == 0 ]]; then
  render "$H/prompts/plan-questions.md" > "$RUN/private/p1.md"; stage plan-questions "$DOC_TOOLS" "$RUN/private/p1.md"
  SID=$(result_field "$RUN/private/plan-questions.jsonl" session_id)
  [[ -s "$SB/output/questions.md" ]] && stand_in_pm || echo "(no questions asked)" > "$RUN/private/answers.md"
  ANSWERS_FILE="$RUN/private/answers.md" render "$H/prompts/plan-answers.md" > "$RUN/private/p2.md"; stage plan "$DOC_TOOLS" "$RUN/private/p2.md" "$SID"
else
  render "$H/prompts/spec-questions.md" > "$RUN/private/p1.md"; stage spec-questions "$DOC_TOOLS" "$RUN/private/p1.md"
  SID=$(result_field "$RUN/private/spec-questions.jsonl" session_id)
  [[ -s "$SB/output/questions.md" ]] && stand_in_pm || echo "(no questions asked)" > "$RUN/private/answers.md"
  ANSWERS_FILE="$RUN/private/answers.md" render "$H/prompts/spec-answers.md" > "$RUN/private/p2.md"; stage spec "$DOC_TOOLS" "$RUN/private/p2.md" "$SID"
  render "$H/prompts/tickets.md" > "$RUN/private/p3.md"; stage tickets "$DOC_TOOLS" "$RUN/private/p3.md"
fi
T1=$(date -u +%Y-%m-%dT%H:%M:%SZ)
for f in spec.md tickets.md plan.md; do [[ -f "$SB/output/$f" ]] && cp "$SB/output/$f" "$RUN/output/"; done

python3 - "$RUN" "$RID" "$ARM" "$FEATURE" "$MODEL" "$("$CLAUDE" --version | head -1)" "$BASE_TAG" "$BASE_SHA" "$KIT_HASH" "$T0" "$T1" "$SB" "$(basename "$SHEET")" <<'PY'
import json,sys,glob,os
run,rid,arm,feat,model,cli,tag,sha,kit,t0,t1,sb,sheet=sys.argv[1:]
stages={}
for p in sorted(glob.glob(os.path.join(run,"private","*.jsonl"))):
    r=None
    for line in open(p):
        try: e=json.loads(line)
        except Exception: continue
        if e.get("type")=="result": r=e
    if r: stages[os.path.basename(p)[:-6]]={k:r.get(k) for k in ("duration_ms","total_cost_usd","num_turns","is_error")}|{"usage":r.get("usage"),"models":list((r.get("modelUsage") or {}).keys())}
json.dump({"run_id":rid,"arm":arm,"feature":feat,"model":model,"cli":cli,"base_tag":tag,"base_sha":sha,"kit_sha256":kit,
           "started":t0,"finished":t1,"sandbox":sb,"answer_sheet":sheet,"stages":stages,"outputs":sorted(os.listdir(os.path.join(run,"output")))},
          open(os.path.join(run,"private","manifest.json"),"w"),indent=2)
PY
# seal the run-id -> arm mapping outside the run folder
python3 - "$H/runs/sealed.json" "$RID" "$ARM" "$FEATURE" "$(basename "$SHEET")" <<'PY'
import json,sys,os,fcntl
p=sys.argv[1]; open(p,"a").close()
with open(p,"r+") as f:
    fcntl.flock(f,fcntl.LOCK_EX); t=f.read(); s=json.loads(t) if t.strip() else {}
    s[sys.argv[2]]={"arm":sys.argv[3],"feature":sys.argv[4],"answer_sheet":sys.argv[5]}; f.seek(0); f.truncate(); json.dump(s,f,indent=2,sort_keys=True)
PY
log "finished: outputs $(ls "$RUN/output" | tr '\n' ' ')"
