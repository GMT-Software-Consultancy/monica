# Runs

Document-stage outputs for every run, by blind run ID. Arms are not shown here.

- **qa.md**: the agent's questions and the stand-in PM's answers (the decision log).
- **spec.md** and **tickets.md**, or **plan.md**: what the agent wrote.
- **intent-diff.md** or **decision-inventory.md**: the blind scorer's report.

Runs with a code-stage branch hold their handoff on `run/<run-id>`, cut from tag `exp-base-v1`. For the straight-to-code arm, use `run/seed-only-pilot` or `run/seed-only-test`.

## Pilot feature (JSON export)

Answer sheet: `pilot-pm-oracle.md`.

| Run | Files | Code-stage branch |
|---|---|---|
| `3ede8faf` | [intent-diff](3ede8faf/intent-diff.md) · [qa](3ede8faf/qa.md) · [spec](3ede8faf/spec.md) · [tickets](3ede8faf/tickets.md) | `run/3ede8faf` |
| `4fa3f2e6` | [intent-diff](4fa3f2e6/intent-diff.md) · [qa](4fa3f2e6/qa.md) · [spec](4fa3f2e6/spec.md) · [tickets](4fa3f2e6/tickets.md) | `run/4fa3f2e6` |
| `aaebbf5b` | [intent-diff](aaebbf5b/intent-diff.md) · [qa](aaebbf5b/qa.md) · [spec](aaebbf5b/spec.md) · [tickets](aaebbf5b/tickets.md) | `run/aaebbf5b` |
| `b6ac0744` | [intent-diff](b6ac0744/intent-diff.md) · [plan](b6ac0744/plan.md) · [qa](b6ac0744/qa.md) | `run/b6ac0744` |

## Test feature (full record), realistic answer sheet

Answer sheet: `test-pm-oracle-realistic.md`.

| Run | Files | Code-stage branch |
|---|---|---|
| `653d41a6` | [decision-inventory](653d41a6/decision-inventory.md) · [qa](653d41a6/qa.md) · [spec](653d41a6/spec.md) · [tickets](653d41a6/tickets.md) | `run/653d41a6` |
| `6b5381fa` | [decision-inventory](6b5381fa/decision-inventory.md) · [qa](6b5381fa/qa.md) · [spec](6b5381fa/spec.md) · [tickets](6b5381fa/tickets.md) | `run/6b5381fa` |
| `9e1a61e7` | [decision-inventory](9e1a61e7/decision-inventory.md) · [plan](9e1a61e7/plan.md) · [qa](9e1a61e7/qa.md) | `run/9e1a61e7` |
| `f3057262` | [decision-inventory](f3057262/decision-inventory.md) · [qa](f3057262/qa.md) · [spec](f3057262/spec.md) · [tickets](f3057262/tickets.md) | `run/f3057262` |

## Test feature (full record), thin answer sheet

Answer sheet: `test-pm-oracle.md`. Exploratory only; no code stage.

| Run | Files | Code-stage branch |
|---|---|---|
| `393d9d26` | [decision-inventory](393d9d26/decision-inventory.md) · [qa](393d9d26/qa.md) · [spec](393d9d26/spec.md) · [tickets](393d9d26/tickets.md) | — |
| `3c6ea7f6` | [decision-inventory](3c6ea7f6/decision-inventory.md) · [qa](3c6ea7f6/qa.md) · [spec](3c6ea7f6/spec.md) · [tickets](3c6ea7f6/tickets.md) | — |
| `5c94a1b4` | [decision-inventory](5c94a1b4/decision-inventory.md) · [plan](5c94a1b4/plan.md) · [qa](5c94a1b4/qa.md) | — |
| `f50ed908` | [decision-inventory](f50ed908/decision-inventory.md) · [qa](f50ed908/qa.md) · [spec](f50ed908/spec.md) · [tickets](f50ed908/tickets.md) | — |
