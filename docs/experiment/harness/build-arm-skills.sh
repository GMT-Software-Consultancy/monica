#!/usr/bin/env bash
# Build the arm skill kits from a CCBL skills folder (not stored in this repo).
# Usage: ./build-arm-skills.sh <path-to-ccbl>/.claude/skills
#   arms/skills-fenced/   create-spec, create-tickets and _shared, unchanged (arms A and C)
#   arms/skills-unfenced/ the same, with the create-spec air-gap (fence) rule removed (arm B)
set -euo pipefail
SRC="${1:?path to a CCBL .claude/skills folder}"
H="$(cd "$(dirname "$0")" && pwd)"
for kit in skills-fenced skills-unfenced; do
  rm -rf "$H/arms/$kit"; mkdir -p "$H/arms/$kit"
  for d in create-spec create-tickets _shared; do cp -R "$SRC/$d" "$H/arms/$kit/"; done
done
python3 - "$H/arms/skills-unfenced/create-spec/SKILL.md" <<'PY'
import sys
p = sys.argv[1]; s = open(p).read()
a = s.index('### How to use the technical context (air-gap rule)')
b = s.index('Then ask the PM: **"What are we speccing up?"**')
s = s[:a] + s[b:]
s = s.replace(' (product-observable, not a mechanism — see the air-gap rule in Step 1)', '')
start = s.index('State only product-observable constraints')
end = s.index('plan-review.]', start) + len('plan-review.]')
s = s[:start] + 'State the technical constraints that apply.]' + s[end:]
assert 'air-gap' not in s.lower(), 'fence text still present'
open(p, 'w').write(s)
PY
echo "built arms/skills-fenced and arms/skills-unfenced from $SRC"
