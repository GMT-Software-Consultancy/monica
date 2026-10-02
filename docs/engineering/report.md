# Branch quality review: `no_architecture_ticket_prompt`

| | |
|---|---|
| Branch | `no_architecture_ticket_prompt` |
| Base | `main` |
| Merge-base | `b8ab5f0ad563d23baa64778432bce07e2cd50041` |
| Reviewed head | `75db1bc70cc833f1f9a26470151deb8fc47fc719` |
| Date | 2026-10-02 |
| Requirements | **Inferred** from commit `d527ed6c9` ("Add JSON export for contacts") and the diff. No spec was supplied, so correctness confidence is capped at medium. |

## Blockers

None.

## Weighted total: **79 / 100 (Good)**

## Scorecard

| # | Criterion | Weight | Score | Confidence | Justification |
|---|---|---|---|---|---|
| 1 | Functional correctness & completeness | 20 | 4 | medium | The JSON download works end to end and is tested. The exported fields are a deliberate subset (no notes, relationships, pets etc.) and that choice isn't documented. |
| 2 | Test quality | 15 | 3 | high | Service permission paths are well covered. The JSON shape of `ContactResource` is barely asserted (only first and last name). |
| 3 | Maintainability & readability | 15 | 4 | high | Small, clear units that mirror the vCard export. The controller is a near copy of `ContactVCardController`, and the two new tests use different annotation styles. |
| 4 | Design & architecture fit | 15 | 4 | high | Follows the Service → Controller → ViewHelper → Vue pattern. An API `JsonResource` is reused as the web export format. |
| 5 | Reliability & error handling | 10 | 4 | medium | Nullsafe access on optional relations, and authorization failures surface correctly. Inherits the vCard flow's weak spots (empty-name filename, payload in session flash). |
| 6 | Security | 10 | 5 | high | Two layers of authorization (`can:contact-owner` route middleware plus service permissions), VIEW level as for vCard, POST with CSRF, no new dependencies. |
| 7 | Performance efficiency | 5 | 5 | medium | Eager-loads all nested relations in one `load()`, so the resource has no N+1. Single-record scope. |
| 8 | Scope discipline & change hygiene | 5 | 4 | high | The net diff is tightly focused. The feature commit also contains an unrelated `.husky/pre-commit` change, and the branch carries a duplicate port-fix commit plus a merge. |
| 9 | Operability & documentation | 5 | 3 | medium | No config or migrations needed, and the commit message is thorough. The export format isn't documented anywhere in the repo, and only `en.json` got the new string. |

`total = (20·4 + 15·3 + 15·4 + 15·4 + 10·4 + 10·5 + 5·5 + 5·4 + 5·3) / 5 = 395 / 5 = 79`

## Evidence

**Diff stats** (`git diff --stat b8ab5f0ad...no_architecture_ticket_prompt`, excluding review files): 10 files changed, +348 / −4. Five new PHP files (service, controller, resource and two tests) and five modified (route, view helper, view helper test, `Show.vue`, `lang/en.json`).

### 1. Functional correctness
- Route: `routes/web.php:259`, `POST .../contacts/{contact}/json` → `ContactJsonExportController@download`, registered next to the vCard route and also without `HandleInertiaRequests`.
- `ContactJsonExportController.php:18-27` returns `Redirect::back()->with('flash', ['data' => ..., 'filename' => "$name.json"])`, which the refactored `downloadFile()` in `Show.vue:135-154` turns into a blob download.
- `ContactResource.php:20-55` exports names, gender, pronoun, company, job, contact information, addresses, important dates and labels. It leaves out notes, relationships, pets, goals, tasks, religion and the avatar. The commit message lists the fields on purpose, so this looks like a scoping decision, but without a spec it can't be confirmed.
- `ContactInformationType`, `AddressType`, `Gender` and `Pronoun` all have a `name` accessor that falls back to `name_translation_key` (e.g. `app/Models/Gender.php:80`), so seeded types export readable names rather than null.
- Targeted run (Sail image, SQLite):
  `php vendor/bin/phpunit --filter 'ContactJsonExportControllerTest|ExportContactAsJsonTest|ContactShowViewHelperTest|...'` → **12 tests, 39 assertions, OK**.
- Full suite, in a clean checkout of `75db1bc70` (Sail image, SQLite): **2064 tests, 3739 assertions, 1 error, 1 skipped**.
  - The one error is `ZiggyVersionCheckTest`. It shells out to `yarn info`, and yarn isn't available to the container user. It fails identically on a `main` worktree, so it's environmental and predates this branch.
  - A first full run in the developer working tree also showed 8 DAV failures (`CardDAVTest`, `CalDAVDatesTest`, `CalDAVTasksTest`, `VCardContactTest`). That working tree has CRLF line endings, while the committed blobs are LF. Those tests compare exact XML output, so CRLF breaks them, and in a clean LF checkout of the same commit all of them pass. This is a local checkout problem, not a branch regression.

### 2. Test quality
- `ExportContactAsJsonTest.php` has 5 tests: the happy path (checks eager-loaded relations), validation failure, user not in account, contact not in vault, and no vault permission.
- `ContactJsonExportControllerTest.php` has 2 tests: download (filename slug plus first and last name in the payload) and 403 for a contact from another vault.
- Gaps:
  - Nothing asserts the nested JSON (`contact_information`, `addresses`, `important_dates`, `labels`, `gender`) or the null paths (no company, no gender). A typo in a resource key would go unnoticed.
  - `ExportContactAsJsonTest.php:66-76` (`it_fails_if_contact_doesnt_belong_to_vault`) uses a vault from another account, so it throws at `vault_must_belong_to_account` before the contact check runs. The test name overstates what it covers. It copies the same pattern as `ExportVCardTest`.
  - The controller 403 test (`ContactJsonExportControllerTest.php:42-51`) is answered by the `can:contact-owner` route middleware (`routes/web.php` contact group), not by the service.
  - There's no controller test for a vault member with no access, or for an unauthenticated user.
- Style: the feature test uses `#[Test]` and the unit test uses `/** @test */`. Both appear in the repo (135 vs 1880 uses), but PHPUnit 11 reports doc-comment annotations as deprecations (1912 in the targeted run).

### 3. Maintainability
- Every new method is 1–15 lines with straight-line logic and a cyclomatic complexity of about 1–2 by inspection (lizard wasn't available).
- `ContactJsonExportController` matches `ContactVCardController` line for line except the service call and extension. That duplication is acceptable at two copies, but a third format should get a shared helper.
- `Show.vue`: extracting `downloadFile(url)` removes duplicate blob-download logic. The inner `url` was renamed to `fileUrl` to avoid shadowing the parameter.
- Pint on the committed (LF) content: `pint --test` → **PASS, 7 files** (the base copies also pass).
  - On the working tree, Pint reports `line_ending` on every file, including the untouched `ContactVCardController.php`.
  - The working-tree files are CRLF while the git blobs are LF, so this is a local WSL checkout artifact, not a branch defect.
- PHPStan on the changed files reports one error, at `ContactShowViewHelper.php:167` (`argument.unresolvableType` in `getTemplatePagesList`). That line isn't touched by the diff, so it predates this branch. No new PHPStan errors.

### 4. Design & architecture fit
- Authorization goes through `BaseService::permissions()`, the same as every other domain service (`ExportContactAsJson.php:30-38`). Its rules and permissions mirror `ExportVCard` minus the group branch.
- The URL is exposed through `ContactShowViewHelper` in both `dto()` and the second URL block (`:95-98`, `:153-156`), as the vCard URL is. The view helper test is updated.
- `ContactResource` lives in `App\Http\Resources` next to `VaultResource`, the public-API resources.
  - Reusing it for a web download is pragmatic and leaves it ready for a future `api.contacts.show`.
  - But it now does two jobs: if API needs change the shape, the download format changes silently.
  - Unlike `VaultResource`, it has no `links` block.
- The service is thin: it validates and eager-loads, then returns the `Contact` rather than the export payload, so turning it into JSON happens in the controller. That's reasonable, but the class name `ExportContactAsJson` promises more than it does.

### 5. Reliability
- The resource uses nullsafe access (`gender?->name`, `company?->name`, `contactInformationType?->name`, `addressType?->name`), so optional relations can't fatal.
- Inherited from the vCard pattern:
  - A contact whose name slugs to an empty string downloads as `.json`.
  - The whole payload travels through the session flash, which is fine for a single contact.
- Failure modes (403, validation, not found) surface as exceptions or HTTP errors. Nothing is silently swallowed.

### 6. Security
- Route group middleware `can:vault-viewer,vault` → `can:contact-owner,vault,contact`, then service permissions `author_must_be_in_vault` (VIEW) and `contact_must_belong_to_vault`. Cross-vault access is tested.
- The export holds the same PII a vault viewer can already see on the contact page and in the vCard. No extra exposure.
- No secrets in the diff (a manual scan of the 348 added lines; gitleaks not run). No dependency or lockfile changes.

### 7. Performance
- `ExportContactAsJson.php:47-55` loads 7 relation paths, including nested ones (`contactInformations.contactInformationType`, `addresses.addressType`), in a single `load()`, which is roughly 8 queries no matter how many child records there are.
- One contact per request, so there's no unbounded input.

### 8. Scope & hygiene
- `git log b8ab5f0ad..no_architecture_ticket_prompt`:
  - `72330edac` is a docker-compose port fix, which duplicates `c2557ba56` already on main.
  - `d527ed6c9` is the feature.
  - `75db1bc70` merges main.
- `d527ed6c9` also edits `.husky/pre-commit`, which has nothing to do with JSON export. The same change has since landed on main (`b8ab5f0ad`), so the net diff against main is clean (`git diff b8ab5f0ad 75db1bc70 -- .husky docker-compose.yml` is empty). The branch's own history is not.
- The branch name (`no_architecture_ticket_prompt`) doesn't describe the change.

### 9. Operability & documentation
- No migrations, config or env changes.
- The commit message documents every file well, but nothing in the repo describes the JSON export format (no docs or changelog entry).
- `"Download as JSON"` was added only to `lang/en.json`, so other locales fall back to the English key. That's acceptable under Laravel's fallback, but differs from how fully localized strings are handled.
- No logging, which matches the vCard export.

## Top 3 strengths
1. **Faithful reuse of established patterns.** The service, controller, route, view helper and Vue wiring all mirror the vCard export, so a maintainer already knows where everything lives.
2. **Layered authorization with good permission tests.** Route-level gates plus service-level permissions, with 5 service tests covering each failure mode.
3. **Small helpful refactor in `Show.vue`.** `downloadFile(url)` removes what would have been duplicated blob-download code, and also fixes the inner `url` shadowing.

## Top 3 improvements
1. **Test the JSON shape.** Add a `ContactResource` test, or extend the feature test, that creates a contact with a gender, company, contact information, address, important date and label, then asserts the exact nested keys and values, including the null paths.
2. **Make the scope explicit.** Either document which contact data the JSON export includes and why (e.g. a short note in `docs/` or the PR description), or extend it to notes, relationships and pets if the goal is a full backup. Consider a `ContactExportResource` separate from any future API resource so the two formats can diverge safely.
3. **Clean up history and test style before merging.** Squash or drop the duplicate port-fix commit and split the `.husky/pre-commit` change out of the feature commit. Use `#[Test]` consistently in `ExportContactAsJsonTest`, and fix `it_fails_if_contact_doesnt_belong_to_vault` so it puts the vault in the user's account and actually reaches the contact-ownership check.

## Not measured
- **Browser behaviour.** The Vue download wasn't run in a browser: no app containers were running and Node isn't installed on the host, so `eslint` and `prettier` couldn't run on `Show.vue` either.
- **Coverage.** No coverage driver was configured in the test run.
- **Complexity tooling.** `lizard` isn't installed, so complexity was judged by inspection (every function is trivially small).
- **Secret scanning.** `gitleaks` isn't installed; the diff was scanned manually.
- **Ziggy version check.** `ZiggyVersionCheckTest` can't run in this environment (yarn is unavailable to the container user). It fails the same way on `main`.
- **Requirements.** None were supplied. Correctness is judged against intent inferred from the commit message.
