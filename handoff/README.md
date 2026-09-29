# Handoff: 6b5381fa

**Feature:** test (full record), realistic answer sheet
**Base:** this branch (`main` plus the local dev port fixes). The handoff commit adds only the `handoff/` folder. The code matches tag `exp-base-v1`, which also carries `docs/product/strategy.md`; the coding stage doesn't use it.

## Code stage

1. Give the coding agent: `handoff/tickets.md`: the approved tickets. `handoff/spec.md` is the approved spec they came from.
2. Use the same model and coding prompt as every other run branch (see `docs/experiment/test-plan-v4.md` on `product-strategy-creation`).
3. Let it work until its own tests pass, or for 60 minutes at most. Save the transcript, token count and time.
4. Commit the code to this branch. The build is the diff from the handoff commit.

Don't give the agent anything outside this folder and the codebase. The question log for this run lives on `product-strategy-creation` under `docs/experiment/runs/6b5381fa/`, not here, so the agent can't read it.
