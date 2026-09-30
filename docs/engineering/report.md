# Branch review: `no_architecture`

|               |                                                                                                                                                                                                          |
| ------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Branch        | `no_architecture`                                                                                                                                                                                        |
| Base          | `main`                                                                                                                                                                                                   |
| Merge-base    | `c2557ba5690af2bd126b2cdd3e47a08446ffa316`                                                                                                                                                               |
| Reviewed head | `a2db444b5733668b740647059edcda548da66144`                                                                                                                                                               |
| Date          | 2026-09-29                                                                                                                                                                                               |
| Requirements  | **provided**: the branch's own `docs/product/specs/spec.md` and `docs/product/specs/tickets.md` (4 tickets, "Full contact export"), with `docs/product/strategy.md` as context. No `docs/architecture/`. |

> **Moved on 2026-09-30.** This report was written to `branch-review/no_architecture.md` outside the repo, then moved here when the branch-quality-review skill changed its output location. The scores and evidence are unchanged, and the machine-readable JSON scorecard is no longer produced. The branch has two commits after the reviewed head, neither of which changes the solution: `037c0c9e8` (merged the pre-commit hook change from `main`) and `f9103fb5a` (moved the solution summary to `docs/engineering/summary.md`).

> **Reviewer disclosure:** the same Claude session that wrote this branch also reviewed it. Every score below rests on commands re-run in a clean worktree, but the design and readability judgements may still carry author bias.

## Blockers

None.

## Weighted total

**92 / 100 (Excellent)**

## Scorecard

| Criterion                             | Weight | Score | Confidence | Justification                                                                                                                                                                                                                    |
| ------------------------------------- | ------ | ----- | ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Functional correctness & completeness | 20     | 5     | medium     | All 4 tickets and AC-1 to AC-6 are implemented and verified at service and HTTP level. The client-side download wasn't exercised in a browser.                                                                                   |
| Test quality                          | 15     | 5     | high       | 31 new tests with 100% line coverage of both new classes. They include failure injection per section, privacy negative assertions, a no-DB-writes check, and fixed clocks.                                                       |
| Maintainability & readability         | 15     | 4     | high       | 29 small methods, average CCN 1.9, maximum 10. One 585-line service, with relationship logic duplicated from `ModuleRelationshipViewHelper`.                                                                                     |
| Design & architecture fit             | 15     | 4     | medium     | Follows the BaseService, domain and ViewHelper conventions. Adds a second download mechanism (GET + JSON envelope with a pre-encoded string) alongside the vCard flash pattern; the privacy reason for it is documented.         |
| Reliability & error handling          | 10     | 5     | medium     | All-or-nothing export, `JSON_THROW_ON_ERROR`, a 500 with no body data on failure, and a client error toast. Failure paths are tested for notes, relationships, files and the controller.                                         |
| Security                              | 10     | 5     | high       | Gates plus service permissions, with tests for outsider and cross-vault 403s and view-only access. Linked-contact data is minimised and tested, `Cache-Control: no-store` is set, and there are no dependency or secret changes. |
| Performance efficiency                | 5      | 4     | medium     | Lazy-loaded N+1 in `loans()` (currency, lenders and borrowers per loan) and in `groups()` (a members query and a roles query per group). Bounded by one contact's data.                                                          |
| Scope discipline & change hygiene     | 5      | 5     | high       | One feature commit touching 10 code and test files, with no drive-by edits. The history also carries docs and handoff commits, which are the task's inputs.                                                                      |
| Operability & documentation           | 5      | 4     | medium     | No config or migrations. Decisions are recorded in `docs/engineering/summary.md` and in docblocks. There's no export-specific logging or metrics (analytics are out of scope in the spec).                                       |

`total = (20×5 + 15×5 + 15×4 + 15×4 + 10×5 + 10×5 + 5×4 + 5×5 + 5×4) / 5 = 460 / 5 = 92`

## Evidence

All commands ran in the project's Sail image (`sail-8.4/app`, PHP 8.4.22, Node 22) against a detached worktree at `../.branch-review/no_architecture`, with `vendor/` and `node_modules/` mounted from the main checkout.

**Diff** (`git diff --stat c2557ba5...no_architecture`): 13 files, +2573/−0.

- Code and tests: 10 files, 1,773 lines.
- Docs (inputs): 648 lines.
- Solution summary (`output/summary.md` at the reviewed head, now `docs/engineering/summary.md`): 148 lines.

**Build:** `yarn build` (Vite client + SSR) on a copy of the worktree gave exit 0 with 2× "built in".

**Tests:** full suite after `migrate:fresh` + `db:seed` on the testing DB (`vendor/bin/phpunit`): 2088 tests, 3880 assertions, 0 failures, 1 skipped, 1 deprecation. The deprecation is `app/Helpers/helpers.php:129` (`trim(null)`), code that predates this branch, triggered by a factory path in `ExportContactTest::it_describes_the_relationship_from_the_point_of_view_of_the_exported_contact`.

**Coverage:** pcov, `phpunit --coverage-text` on the new tests:

- `ExportContactTest` alone: `ExportContact` methods 100% (29/29), lines 100% (369/369).
- `ContactFullExportControllerTest` alone: `ContactFullExportController` lines 100% (16/16).

**Complexity:** `lizard -l php` on the two new classes.

- `ExportContact.php`: 491 NLOC, 29 functions, average CCN 1.9, maximum CCN 10 (`partialDate`, a `match` expression).
- `ContactFullExportController.php`: 1 function, CCN 2.
- No function exceeds CCN 15. Longest functions: `relationships()` 37 NLOC, `lifeEvents()` 36 NLOC.

**Static analysis:** `phpstan analyse` on the new classes gave 34 findings. All are in the categories untouched code shows in the same environment (larastan not resolving Eloquent generics: "undefined static method", `Collection<int,stdClass>::map()`, "unresolvable type"). None fall outside those categories. Pint, ESLint and Prettier passed in the committing pre-commit hook.

**Dependencies:** no changes to `composer.json`, `composer.lock`, `package.json` or `yarn.lock`.

**Security:** pattern grep of the diff for secrets matched test fixture strings only ("…-secret" markers used in privacy assertions). `gitleaks` isn't installed.

### 1. Correctness

- Every AC in the branch's `spec.md` maps to at least one passing test. The mapping is in `docs/engineering/summary.md` under "Acceptance-criteria coverage".
- `app/Domains/Contact/ManageContact/Web/Controllers/ContactFullExportController.php:27-45`: file name, pretty JSON and no-store response.
- The link and blob download in `resources/js/Pages/Vault/Contact/Show.vue` weren't run in a browser (medium confidence).

### 2. Tests

- `tests/Unit/Domains/Contact/ManageContact/Services/ExportContactTest.php:881`: a Mockery partial mock makes one section throw, proving the export fails as a whole. It's used for notes, relationships and files.
- Same file, line 252: a `DB::listen` check that the export performs no writes. Lines 720 and 753 assert linked-contact and group-member details are absent.
- `ContactFullExportControllerTest.php:88`: HTTP 500 with no `data` when the export fails. The partial mocks of protected methods couple the tests to internal method names (a minor point).

### 3. Maintainability

- The lizard metrics are above.
- `ExportContact.php:360`: the relationship query duplicates `ModuleRelationshipViewHelper.php:30` rather than sharing it.

### 4. Design

- The service follows `BaseService` with `rules()`/`permissions()`, lives in `ManageContact`, and the URL is exposed through `ContactShowViewHelper`.
- `routes/web.php:259` uses `Route::get('export', …)`. The response is a JSON envelope whose `content` is itself a JSON string (`ContactFullExportController.php:34`), which the client turns into a Blob. That's a second download pattern next to the vCard's session-flash approach. It's justified in the summary: it avoids storing the export in the session.

### 5. Reliability

- `JSON_THROW_ON_ERROR` (`ContactFullExportController.php:34`). No section catches exceptions, so a partial export is impossible.
- The client creates the Blob only after a successful response, and `flash(…,'error')` runs on failure.

### 6. Security

- The route sits inside the `can:vault-viewer` and `can:contact-owner` groups, and the service repeats `author_must_be_in_vault` and `contact_must_belong_to_vault`.
- Other contacts appear only as `{name, connection}`. URLs and uuids are stripped from files.
- `Cache-Control: no-store, private` (line 44). The file name is slug-safe, so there's no header injection.

### 7. Performance

- `ExportContact.php:439`: `loansAsLoaner()->get()` has no eager loading, so lines 447 onward lazy-load `currency`, `loaners` and `loanees` per loan.
- `ExportContact.php:411-416`: a members query and a `GroupTypeRole::findMany` per group. Other sections eager-load.

### 8. Scope

- Feature commit `a2db444b5`: 10 files, 1,925 insertions.
- Branch history also contains `f47abadc8` (handoff files, cherry-picked) and the docs commits. They are task inputs, and the handoff files are net-zero after being moved into `docs/`.

### 9. Operability

- `docs/engineering/summary.md` records the decisions, assumptions and limitations. There's no new config, env var or migration.
- The file-format contract is `format_version` (`ExportContact::FORMAT_VERSION`). No published format docs (out of scope in the spec).

## Top 3 strengths

1. **Failure behaviour is proven, not just designed.** Section-level fault injection plus an HTTP-level 500 test show there's never a partial file (AC-6 and ticket failure scenarios).
2. **Privacy is enforced and tested.** Linked contacts, group members and file URLs are asserted absent, the export performs no DB writes, and caches are told `no-store`.
3. **High test depth for the size of the change:** 100% line coverage of the new classes, with fixed clocks and explicit expected payloads.

## Top 3 improvements

1. **Eager-load loans:** `->with(['currency','loaners','loanees'])` at `ExportContact.php:439-440`. Also batch the group member and role queries (`:411-416`) into one query each.
2. **Share the relationship-direction logic** with `ModuleRelationshipViewHelper` (for example, a small helper returning `[otherContactId, typeName]`), so the page and the export can't drift.
3. **Return the file directly** (`Content-Disposition: attachment`, `application/json`) instead of a JSON envelope holding a JSON string. That keeps one encoding step, and the privacy property still holds.

## Not measured

- **Browser behaviour** of the link, download and toast (there's no JS test runner, and the app wasn't run end to end).
- **Secret scanning with gitleaks** (not installed); a grep was used instead.
- **Coverage against base** (not needed: both classes are new).
- **Dependency audit** (no dependency changes).
