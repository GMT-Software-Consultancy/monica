# Handoff: seed-only-pilot

**Feature:** pilot (JSON export)
**Base:** tag `exp-base-v1`. This branch adds only the `handoff/` folder.

## Code stage

1. Give the coding agent: `handoff/seed.md`: the stakeholder request only, with no spec, tickets or plan (straight-to-code arm).
2. Use the same model and coding prompt as every other run branch (see `docs/experiment/test-plan-v4.md` on `product-strategy-creation`).
3. Let it work until its own tests pass, or for 60 minutes at most. Save the transcript, token count and time.
4. Commit the code to this branch. The build is the diff from `exp-base-v1`.

Don't give the agent anything outside this folder and the codebase. The question log for this run lives on `product-strategy-creation` under `docs/experiment/runs/seed-only-pilot/`, not here, so the agent can't read it.
