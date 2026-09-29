# Experiment harness

Runs the document stages of the test (seed to spec and tickets, or seed to plan) for one arm,
in a clean sandbox, and records everything needed to score it blind.

## What a run does

1. Clones the tag `exp-base-v1` into a sandbox, with no git history or other branches.
   The base is `main`, plus the local dev port fixes, plus `docs/product/strategy.md`.
2. Installs only that arm's kit:
   - **Arm 0:** `arms/arm0/CLAUDE.md`, generated once by `/init` and then frozen.
   - **Arms A to C:** the CCBL skills from `arms/skills-fenced/`, or `arms/skills-unfenced/`
     for arm B, plus the seed.
   - **Arms B and C:** also get Garry's architecture docs from `arms/arch/`.
3. Runs each stage with the pinned Claude Code in `node_modules/` (2.1.284) and
   `--model claude-opus-5-5`. Personal settings, skills, memory, MCP servers and web tools
   are all off.
4. The agent writes its questions. A stand-in PM, in an empty box with no tools, answers
   them from the answer sheet only. The agent then finishes.
5. Saves blind outputs to `runs/<id>/output/`. Saves transcripts, token counts, cost and
   time to `runs/<id>/private/`. The mapping from run ID to arm goes in `runs/sealed.json`.

## Use

The CCBL skills aren't stored in this repo. Build the arm kits from a CCBL checkout first:

```bash
npm install
./build-arm-skills.sh <path-to-ccbl>/.claude/skills
./run-docs.sh C pilot
```

It needs a long-lived token in `~/.config/ccbl/oauth-token`, from `claude setup-token`.

## Not built yet

- **The code stage.** It needs Monica's tests running, either in Garry's environment or in
  Docker.
- **Scoring against the intent reference.** The document-stage scoring is done by hand for
  now.
