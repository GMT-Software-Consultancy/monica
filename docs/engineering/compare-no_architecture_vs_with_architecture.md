# Branch comparison: `no_architecture` vs `with_architecture`

> **Moved on 2026-09-30** from `branch-review/comparison.md` outside the repo, when the branch-quality-review skill changed its output location. The content is unchanged, and the same file is committed to both compared branches.

Base `main`; both branches share the merge-base `c2557ba5`. Reviewed 2026-09-29.

> **Read this first: the two branches were not built to the same spec.** Each was scored against its own `docs/product/specs/spec.md` and `tickets.md`.
>
> - `no_architecture`: 4 tickets, no architecture docs.
> - `with_architecture`: 7 tickets plus 10 architecture docs. This spec asks for more: record IDs, gifts, `exported_at`, a POST attachment route, status parity with the vCard route, and a completeness guard.
>
> So the totals measure how well each branch met _its_ brief. They don't measure which feature is better. The same Claude session also authored both branches (see each report's disclosure).

## Scorecard

| Criterion (weight)                         | `no_architecture` | `with_architecture` |
| ------------------------------------------ | ----------------- | ------------------- |
| Functional correctness & completeness (20) | 5                 | 4                   |
| Test quality (15)                          | 5                 | 5                   |
| Maintainability & readability (15)         | 4                 | 4                   |
| Design & architecture fit (15)             | 4                 | 5                   |
| Reliability & error handling (10)          | 5                 | 4                   |
| Security (10)                              | 5                 | 5                   |
| Performance efficiency (5)                 | 4                 | 5                   |
| Scope discipline & change hygiene (5)      | 5                 | 5                   |
| Operability & documentation (5)            | 4                 | 4                   |
| **Total**                                  | **92**            | **90**              |
| **Band**                                   | Excellent         | Excellent           |
| **Blockers**                               | None              | None                |

| Metric                            | `no_architecture`             | `with_architecture`           |
| --------------------------------- | ----------------------------- | ----------------------------- |
| Full suite (Sail, clean worktree) | 2088 run, 0 failed, 1 skipped | 2101 run, 0 failed, 1 skipped |
| New tests                         | 31                            | 44                            |
| Line coverage of new classes      | 100%                          | 100%                          |
| Max / average CCN (new service)   | 10 / 1.9                      | 10 / 1.9                      |
| `yarn build` (client + SSR)       | pass                          | pass                          |
| New dependencies                  | none                          | none                          |
| Code and test lines added         | 1,773                         | 2,017                         |

No calibration changes were needed. Every criterion where the scores differ has different evidence behind it.

## Key trade-offs

The two branches share a core: a read-only `BaseService` in `ManageContact`, gate plus service-level authorization, privacy-minimised linked contacts, file lists without URLs, ISO 8601 dates, and 100% coverage of the new code. The differences come from their briefs and designs:

- **`no_architecture` is stronger on failure-mode proof.**
  - Section-level fault injection plus an HTTP 500 test prove there's never a partial file.
  - It has no unmet requirement.
  - Its download path is a GET returning a JSON envelope that holds a pre-encoded JSON string, which is a second download pattern next to the vCard route.
  - `loans()` and `groups()` have small N+1 query patterns.
- **`with_architecture` is stronger on architectural fidelity and future-proofing.**
  - It follows the documented route, middleware bypass and call style exactly, and returns a true `application/json` attachment.
  - It eager-loads throughout.
  - It adds a relation-registry test that catches future data types missing from the export.
  - It proves status-code parity with the vCard route, including for deleted contacts.
  - Its gaps: ticket 4's gift giver and receiver can't be exported, because the `gifts` schema can't represent them (a spec-versus-schema conflict, documented), and it has no fault-injection test.

## Recommendation

**Take `with_architecture` as the base,** despite its 2-point lower total, and port two small items from `no_architecture`:

1. A controller-level fault-injection test asserting a 5xx and no `Content-Disposition`.
2. The per-section "fails entirely" tests.

Why: the gap is within one point on two criteria, and the lower correctness score comes from a schema limitation that no implementation could meet without new product work. It would still be open whichever branch shipped. Its design matches the documented architecture, it has no N+1 queries, and its completeness guard reduces long-term risk for the feature's core promise ("the export is complete").

**Caveats:**

- The briefs differ, so this recommends a codebase, not a spec.
- The PM must decide on gifts: drop them, or ticket a schema fix.
- Neither branch's client-side download was verified in a browser; do that before release.
- The reviewer is also the author of both branches.
