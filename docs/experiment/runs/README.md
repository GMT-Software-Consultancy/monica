# Runs

Document-stage outputs for every run. The arm names follow the Notion "Our Test Case" branches.

| Arm | Steps | Architecture doc | Fence rule |
|---|---|---|---|
| `prompt_only` | Seed → code | No | – |
| `prompt_plan` | Seed → questions → plan → code | No | – |
| `no_architecture` | Seed → questions → spec → tickets → code | No | – |
| `with_architecture` | Seed → questions → spec → tickets → code | Yes (`Arch_1`) | Off |
| `fenced_architecture` | Seed → questions → spec → tickets → code | Yes (`Arch_1`) | On |

Each run folder holds:

- **qa.md**: the agent's questions and the stand-in PM's answers (the decision log).
- **spec.md** and **tickets.md**, or **plan.md**: what the agent wrote.
- **intent-diff.md** or **decision-inventory.md**: the blind scorer's report.

## Code stage

Each code-stage branch is `exp/<feature>/<arm>`, cut from tag `exp-base-v1`, and adds only a `handoff/` folder with what the coding agent gets. Check out the branch, run the coding agent, and commit the code there. Later repeats get a suffix, such as `exp/full-record/fenced_architecture-2`.

## Pilot feature (JSON export)

Answer sheet: `pilot-pm-oracle.md`.

| Arm | Run | Files | Code-stage branch |
|---|---|---|---|
| `prompt_only` | – | seed only | `exp/pilot/prompt_only` |
| `prompt_plan` | `b6ac0744` | [intent-diff](b6ac0744/intent-diff.md) · [plan](b6ac0744/plan.md) · [qa](b6ac0744/qa.md) | `exp/pilot/prompt_plan` |
| `no_architecture` | `aaebbf5b` | [intent-diff](aaebbf5b/intent-diff.md) · [qa](aaebbf5b/qa.md) · [spec](aaebbf5b/spec.md) · [tickets](aaebbf5b/tickets.md) | `exp/pilot/no_architecture` |
| `with_architecture` | `4fa3f2e6` | [intent-diff](4fa3f2e6/intent-diff.md) · [qa](4fa3f2e6/qa.md) · [spec](4fa3f2e6/spec.md) · [tickets](4fa3f2e6/tickets.md) | `exp/pilot/with_architecture` |
| `fenced_architecture` | `3ede8faf` | [intent-diff](3ede8faf/intent-diff.md) · [qa](3ede8faf/qa.md) · [spec](3ede8faf/spec.md) · [tickets](3ede8faf/tickets.md) | `exp/pilot/fenced_architecture` |

## Test feature (full record), realistic answer sheet

Answer sheet: `test-pm-oracle-realistic.md`.

| Arm | Run | Files | Code-stage branch |
|---|---|---|---|
| `prompt_only` | – | seed only | `exp/full-record/prompt_only` |
| `prompt_plan` | `9e1a61e7` | [decision-inventory](9e1a61e7/decision-inventory.md) · [plan](9e1a61e7/plan.md) · [qa](9e1a61e7/qa.md) | `exp/full-record/prompt_plan` |
| `no_architecture` | `6b5381fa` | [decision-inventory](6b5381fa/decision-inventory.md) · [qa](6b5381fa/qa.md) · [spec](6b5381fa/spec.md) · [tickets](6b5381fa/tickets.md) | `exp/full-record/no_architecture` |
| `with_architecture` | `f3057262` | [decision-inventory](f3057262/decision-inventory.md) · [qa](f3057262/qa.md) · [spec](f3057262/spec.md) · [tickets](f3057262/tickets.md) | `exp/full-record/with_architecture` |
| `fenced_architecture` | `653d41a6` | [decision-inventory](653d41a6/decision-inventory.md) · [qa](653d41a6/qa.md) · [spec](653d41a6/spec.md) · [tickets](653d41a6/tickets.md) | `exp/full-record/fenced_architecture` |

## Test feature (full record), thin answer sheet

Answer sheet: `test-pm-oracle.md`. Exploratory only; no code stage.

| Arm | Run | Files | Code-stage branch |
|---|---|---|---|
| `prompt_plan` | `5c94a1b4` | [decision-inventory](5c94a1b4/decision-inventory.md) · [plan](5c94a1b4/plan.md) · [qa](5c94a1b4/qa.md) | – |
| `no_architecture` | `f50ed908` | [decision-inventory](f50ed908/decision-inventory.md) · [qa](f50ed908/qa.md) · [spec](f50ed908/spec.md) · [tickets](f50ed908/tickets.md) | – |
| `with_architecture` | `393d9d26` | [decision-inventory](393d9d26/decision-inventory.md) · [qa](393d9d26/qa.md) · [spec](393d9d26/spec.md) · [tickets](393d9d26/tickets.md) | – |
| `fenced_architecture` | `3c6ea7f6` | [decision-inventory](3c6ea7f6/decision-inventory.md) · [qa](3c6ea7f6/qa.md) · [spec](3c6ea7f6/spec.md) · [tickets](3c6ea7f6/tickets.md) | – |
