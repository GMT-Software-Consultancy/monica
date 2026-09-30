# Branch review: `with_architecture`

|               |                                                                                                                                                                                                                               |
| ------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Branch        | `with_architecture`                                                                                                                                                                                                           |
| Base          | `main`                                                                                                                                                                                                                        |
| Merge-base    | `c2557ba5690af2bd126b2cdd3e47a08446ffa316`                                                                                                                                                                                    |
| Reviewed head | `c68cb6ec0dc25c1b0f922179a017cfa54164e3e1`                                                                                                                                                                                    |
| Date          | 2026-09-29                                                                                                                                                                                                                    |
| Requirements  | **provided**: the branch's own `docs/product/specs/spec.md` and `docs/product/specs/tickets.md` (7 tickets, "Full contact export"), `docs/product/strategy.md`, and the architecture docs in `docs/architecture/` (10 files). |

> **Moved on 2026-09-30.** This report was written to `branch-review/with_architecture.md` outside the repo, then moved here when the branch-quality-review skill changed its output location. The scores and evidence are unchanged, and the machine-readable JSON scorecard is no longer produced. The branch has two commits after the reviewed head, neither of which changes the solution: `f5942585f` (merged the pre-commit hook change from `main`) and `414530fe1` (moved the solution summary to `docs/engineering/summary.md`).

> **Reviewer disclosure:** the same Claude session that wrote this branch also reviewed it. Every score below rests on commands re-run in a clean worktree, but the design and readability judgements may still carry author bias.

## Blockers

None.

## Weighted total

**90 / 100 (Excellent)**

## Scorecard

| Criterion                             | Weight | Score | Confidence | Justification                                                                                                                                                                                                                                                                              |
| ------------------------------------- | ------ | ----- | ---------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Functional correctness & completeness | 20     | 4     | medium     | 6 of 7 tickets are complete and tested. In ticket 4, the gift's other party (giver/receiver) isn't exported, because the `gifts` schema can't represent it (documented). The client download wasn't browser-tested.                                                                        |
| Test quality                          | 15     | 5     | high       | 44 new tests with 100% line coverage of both new classes. They include status-code parity with vCard, a mutation-checked relation guard, `Http::preventStrayRequests`, a no-DB-writes check, and fixed clocks.                                                                             |
| Maintainability & readability         | 15     | 4     | high       | 31 small functions, average CCN 1.9, maximum 10. `timelineEvents()` is 46 NLOC with nested closures, and the relationship logic duplicates the ViewHelper's. The explicit relation registry helps future changes.                                                                          |
| Design & architecture fit             | 15     | 5     | high       | Matches the architecture docs and the spec's technical notes exactly: a `BaseService` called as `(new X)`, a POST route next to vCard bypassing Inertia, the gates, and a real `application/json` attachment. The one raw `DB::table('gifts')` query is justified because no model exists. |
| Reliability & error handling          | 10     | 4     | medium     | All-or-nothing by construction, `JSON_THROW_ON_ERROR`, and a client error toast. No test injects a failure into a section or the controller (the spec had no failure AC).                                                                                                                  |
| Security                              | 10     | 5     | high       | POST with CSRF, gates plus service permissions, and parity tests covering outsiders, other vaults and deleted contacts. Data minimisation is tested, there are no URLs or external calls (tested), `no-store` is set, and there are no dependency or secret changes.                       |
| Performance efficiency                | 5      | 5     | medium     | Eager loading for loans, timeline and life events, and groups (a single roles query). No N+1 found in review. No benchmarks.                                                                                                                                                               |
| Scope discipline & change hygiene     | 5      | 5     | high       | One feature commit touching 10 code and test files, with no drive-by edits. The docs commits are the task's inputs.                                                                                                                                                                        |
| Operability & documentation           | 5      | 4     | medium     | No config or migrations. Decisions, the gift schema conflict and follow-ups (including the missing route in `04-external-interfaces.md`) are recorded in `docs/engineering/summary.md`. No export-specific logging.                                                                        |

`total = (20×4 + 15×5 + 15×4 + 15×5 + 10×4 + 10×5 + 5×5 + 5×5 + 5×4) / 5 = 450 / 5 = 90`

## Evidence

All commands ran in the project's Sail image (`sail-8.4/app`, PHP 8.4.22, Node 22) against a detached worktree at `../.branch-review/with_architecture`, with `vendor/` and `node_modules/` mounted from the main checkout.

**Diff** (`git diff --stat c2557ba5...with_architecture`): 23 files, +4668/−0.

- Code and tests: 10 files, 2,017 lines.
- Docs (inputs): 2,459 lines.
- Solution summary (`output/summary.md` at the reviewed head, now `docs/engineering/summary.md`): 192 lines.

**Build:** `yarn build` (Vite client + SSR) on a copy of the worktree gave exit 0 with 2× "built in".

**Tests:** full suite after `migrate:fresh` + `db:seed` on the testing DB (`vendor/bin/phpunit`): 2101 tests, 3972 assertions, 0 failures, 1 skipped, 1 deprecation. The deprecation is `app/Helpers/helpers.php:129` (`trim(null)`), code that predates this branch, triggered via a factory in `ExportContactTest::every_exported_relation_points_to_a_part_of_the_export`.

**Coverage:** pcov, `phpunit --coverage-text` on the new tests:

- `ExportContactTest` alone: `ExportContact` methods 100% (31/31), lines 100% (400/400).
- The controller tests alone: `ContactExportController` lines 100% (15/15).

**Complexity:** `lizard -l php` on the two new classes.

- `ExportContact.php`: 562 NLOC, 31 functions, average CCN 1.9, maximum CCN 10 (`partialDate`).
- `ContactExportController.php`: 2 functions, maximum CCN 2.
- No function exceeds CCN 15. Longest functions: `timelineEvents()` 46 NLOC, `relationships()` 39 NLOC.

**Static analysis:** `phpstan analyse` on the new classes gave 33 findings. All are in the categories untouched code shows in the same environment (larastan not resolving Eloquent generics). None fall outside those categories. Pint, ESLint and Prettier passed in the committing pre-commit hook.

**Dependencies:** no changes to `composer.json`, `composer.lock`, `package.json` or `yarn.lock`.

**Security:** pattern grep of the diff for secrets matched test fixture strings only. `gitleaks` isn't installed.

### 1. Correctness

- Tickets 1–3 and 5–7 have every scenario covered by passing tests. The mapping is in `docs/engineering/summary.md` under "Acceptance-criteria coverage".
- Ticket 4's gift scenario "gift shows Jane gave it … Tom Smith … as the receiver" isn't met. `ExportContact.php:406` reads `gifts.contact_id` only, and the migration `2022_06_09_173049_create_gifts_table.php` has no gift-to-other-contact link (the `contact_gift` pivot holds loan columns). The limitation is recorded as a conflict.
- Timeline events come from `Contact::timelineEvents`. Life events whose contact isn't a timeline participant would be skipped, but the app's services always attach both (`CreateLifeEvent.php:116-117`, `UpdateLifeEvent.php:143-144`), so no app-created data is lost.

### 2. Tests

- `ContactExportControllerTest.php:165` `assertSameResponseAsVCard`: the export's status equals the vCard route's for outsiders, other vaults and deleted contacts, with no file.
- `ExportContactTest.php`: `every_relation_of_the_contact_model_is_exported_or_excluded_on_purpose`, which fails naming the relation (verified by removing `pets`). Also `Http::preventStrayRequests` (lines 231, 885), `DB::listen` for no writes (236), and a translated type name check (`fr` → "Homme").
- There's no fault-injection test for partial failure (not required by this spec).

### 3. Maintainability

- The lizard metrics are above.
- `ExportContact.php:58` `EXPORTED_RELATIONS` and `:91` `EXCLUDED_RELATIONS` make the scope explicit and checkable.
- `ExportContact.php:328`: the relationship query duplicates `ModuleRelationshipViewHelper.php:30`.

### 4. Design

- `routes/web.php:259` `Route::post('export', …)->withoutMiddleware([HandleInertiaRequests::class])` sits next to the vCard route, as `spec.md` Technical notes and `04-external-interfaces.md` specify.
- `ContactExportController.php:24` calls `(new ExportContact)->execute(...)`, following the `03-data-flow.md` step 6 style. The response is a real attachment with `application/json` (lines 33-37).
- `ExportContact.php:406`: `DB::table('gifts')` inside the service, a pragmatic read because there's no `Gift` model.

### 5. Reliability

- `JSON_THROW_ON_ERROR` (`ContactExportController.php:31`). No section catches exceptions.
- `Show.vue:159`: `axios.post(..., { responseType: 'blob' })`, with a Blob download only on success and `flash(…,'error')` in `catch`. The failure path isn't covered by a test.

### 6. Security

- POST routes go through the web middleware's CSRF protection, and `axios` sends the XSRF token.
- Gates plus `author_must_be_in_vault` and `contact_must_belong_to_vault` in the service.
- Other contacts appear as `{id, name}` only. The tests assert other contacts' notes, dates, phones and label co-members are absent. No file URLs or uuids appear.
- `Cache-Control: no-store, private` (line 36). The file name is slug-safe.

### 7. Performance

- `ExportContact.php:375-376`: loans eager-load `currency`, `loaners` and `loanees`.
- `timelineEvents()` eager-loads participants and life events with their relations.
- `groups()` runs a single `GroupTypeRole::findMany` (line 572).

### 8. Scope

- Feature commit `c68cb6ec0`: 10 files, 2,207 insertions. The other commits add the input docs.

### 9. Operability

- `docs/engineering/summary.md` covers decisions, 12 assumptions, conflicts and follow-ups. It notes that `docs/architecture/04-external-interfaces.md` needs the new route, which wasn't edited because `docs/` was read-only for the task.

## Top 3 strengths

1. **Faithful to the documented architecture and technical notes:** same layer, same call style, same route placement and middleware bypass as the vCard route, and a true file response.
2. **A guard for future completeness:** the relation-registry test fails with a clear message when someone adds a contact relation without exporting it (verified by mutation).
3. **Access-control parity is proven, not assumed:** tests compare the export's status with the vCard route's for three unhappy paths, including soft-deleted contacts.

## Top 3 improvements

1. **Resolve the gift gap with the PM:** drop gifts from the export, or ticket a schema fix (a gift-to-contact pivot with a role). Until then, ticket 4 stays partial.
2. **Add a fault-injection test** (for example, a section throwing) at controller level, asserting a 5xx and no `Content-Disposition`, so the no-partial-file behaviour stays protected.
3. **Split `timelineEvents()`** (46 NLOC) by extracting the life-event mapper, and share the relationship-direction logic with `ModuleRelationshipViewHelper`.

## Not measured

- **Browser behaviour** of the link, blob download and toast (there's no JS test runner, and the app wasn't run end to end).
- **The exact HTTP status codes** for the unhappy paths: only their equality with the vCard route is asserted.
- **Secret scanning with gitleaks** (not installed); a grep was used instead.
- **Dependency audit** (no dependency changes).
