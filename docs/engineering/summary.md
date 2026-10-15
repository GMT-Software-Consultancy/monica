# Solution Summary

**Branch:** gifts-tracker/with_architecture  **Base HEAD:** a6621ae2cc1c8da142bd4eda7a70fe86ed2da808  **Date:** 2026-10-15
**Architecture docs:** present (`docs/architecture/README.md`, `00-overview.md`, `01-components.md`, `02-data-model.md`, `03-data-flow.md`, `04-external-interfaces.md`, `05-configuration.md`, `06-build-deploy.md`, `07-cross-cutting.md`, `08-glossary.md`)

The working tree was clean at the start.

## Overview
This change adds gift tracking. Each gift is one record in a vault, with one or more recipient contacts. A new Gifts module on the contact page splits gifts into "Ideas" and "Given", and lets editors add, edit and delete them. A new read-only vault Gifts page lists open gifts grouped by gift occasion, and vault managers can hide its tab. The build follows the layering in the architecture docs (route → gate → domain controller → `BaseService` service → Eloquent; ViewHelper → Inertia/Vue) and copies the Loans module (`app/Domains/Contact/ManageLoans`) and the vault Tasks page (`app/Domains/Vault/ManageTasks`), as the spec asks.

## Ticket status
| Ticket | Title | Status | Key files | Tests |
|---|---|---|---|---|
| 1 | Prepare gift storage for gifts with several recipients | Done | `database/migrations/2026_10_15_000001_recreate_gifts_tables.php`, `app/Models/Gift.php`, `database/factories/GiftFactory.php`, `app/Models/Contact.php`, `app/Models/Vault.php` | `tests/Unit/Migrations/GiftsMigrationsTest.php`, `tests/Unit/Models/GiftTest.php` |
| 2 | Add gifts from a contact page and list them in a Gifts module | Done | `app/Domains/Contact/ManageGifts/**`, `app/Models/Module.php`, `SetupAccount.php`, `ContactShowViewHelper.php`, `routes/web.php`, `resources/js/Shared/Modules/Gifts.vue`, `resources/js/Shared/Modules/GiftItems/GiftForm.vue`, `resources/js/Pages/Vault/Contact/Show.vue` | `CreateGiftTest`, `ModuleGiftViewHelperTest`, `SetupAccountTest`, `GiftTrackingTest` |
| 3 | Edit a gift from the Gifts module | Done | `UpdateGift.php`, `ContactModuleGiftController::update`, `GiftForm.vue`, `Gifts.vue` | `UpdateGiftTest`, `ModuleGiftViewHelperTest`, `GiftTrackingTest::ac2…` |
| 4 | Delete a gift from the Gifts module | Done | `DestroyGift.php`, `ContactModuleGiftController::destroy`, `Gifts.vue` (with a `confirm()` step, as in Loans) | `DestroyGiftTest` |
| 5 | Fill in the given date when the user picks the last gift state | Done | `GiftForm.vue` (watcher), `ModuleGiftViewHelper` (`last_gift_state_id`, `current_date`) | `ModuleGiftViewHelperTest::the_last_gift_state_follows_the_position_not_the_name` (server side only; see Testing) |
| 6 | Give existing accounts the Gifts module on their contact templates | Done | `database/migrations/2026_10_15_000003_add_gifts_module_to_existing_accounts.php` | `GiftsMigrationsTest` (three data-migration tests) |
| 7 | Show open gifts on a vault Gifts page, grouped by gift occasion | Done | `app/Domains/Vault/ManageGifts/**`, `resources/js/Pages/Vault/Gifts/Index.vue`, `resources/js/Layouts/Layout.vue`, `VaultIndexViewHelper.php` | `VaultGiftsIndexViewHelperTest`, `GiftTrackingTest` (AC-1, 3, 4, viewer, stranger) |
| 8 | Let vault managers hide the Gifts tab | Done | `2026_10_15_000002_add_show_gifts_tab_to_vaults.php`, `Vault.php`, `UpdateVaultTabVisibility.php`, `VaultSettingsTabVisibilityController.php`, `VaultSettingsIndexViewHelper.php`, `TabVisibility.vue` | `UpdateVaultTabVisibilityTest`, `VaultSettingsIndexViewHelperTest`, `VaultIndexViewHelperTest`, `GiftTrackingTest::ac5…`, `::an_editor_cannot_change_the_tab_visibility` |
| 9 | Keep gifts correct when a user deletes a recipient contact | Done | `DestroyContact.php` (`detachGifts()`) | `DestroyContactTest`, `GiftTrackingTest::ac6…`, `VaultGiftsIndexViewHelperTest` (soft-deleted recipients) |
| 10 | Keep gifts in the right vault when a contact moves to another vault | Done | `MoveContactToAnotherVault.php` (`moveGifts()`) | `MoveContactToAnotherVaultTest` |

## Design decisions
- **Decision:** Copy the Loans module structure: a `Gift` model with `belongsToMany` recipients through `contact_gift`; `CreateGift`, `UpdateGift` and `DestroyGift` extend `BaseService`; one `ContactModuleGiftController` returns JSON with a `dtoGift` DTO; a `ModuleGiftViewHelper` is wired into `ContactShowViewHelper::modules()`.
  **Why:** The spec's Technical notes require it, and it matches the domain pattern in `01-components.md` and the request flow in `03-data-flow.md`.
  **Alternatives considered:** Form Request classes. Rejected, because `01-components.md` notes that domain controllers use `$request->input()` and services do the validation.
- **Decision:** Shared gift validation lives in a trait, `app/Domains/Contact/ManageGifts/Services/GiftValidation.php`. It runs a first `Validator` pass with readable messages ("The name is required.", "The gift needs at least one recipient.", "The link must be a valid web address."), then `BaseService::validateRules()` runs as usual.
  **Why:** The tickets want these exact errors. `BaseService::validateRules()` cannot take custom messages, and changing it would be a wider refactor.
  **Alternatives considered:** Changing `BaseService`, or adding global attribute names in `lang/en/validation.php`. Both would affect the whole app.
- **Decision:** The services require `contact_id` and declare `contact_must_belong_to_vault` on top of the permissions the spec lists. They also update the contact's `last_updated_at`.
  **Why:** This matches `CreateLoan`, `UpdateLoan` and `DestroyLoan`. The calls always come from a contact page, and the extra check only makes the services stricter.
- **Decision:** "Open" and "given" are decided only by `given_at`, through the `Gift::open()` and `Gift::given()` scopes.
  **Why:** Spec, "Decisions made".
- **Decision:** The vault Gifts page is protected only by the existing `can:vault-viewer` route gate. It has no service, the same as `VaultTaskController`.
  **Why:** The page is read-only. The gate gives the same guarantee as `author_must_be_in_vault` (permission ≤ 300, see `07-cross-cutting.md`).
- **Decision:** The data migration uses the query builder only. It does not use services, because no author exists during a migration. It can run more than once safely: it skips accounts and templates that already have the Gifts module. It places the module directly after Loans by shifting later positions by one.
  **Why:** Ticket 6 and the spec, plus the precedent of `2025_07_10_200000_fix_contact_information.php`.
- **Decision:** `DestroyGift` detaches the pivot rows before it deletes the gift. The vault Gifts page puts any gift whose `gift_occasion_id` is not one of the account's occasions into "No occasion".
  **Why:** The `testing` connection in `config/database.php` does not turn on SQLite foreign keys, so `ON DELETE` rules do not run there. These two steps keep behaviour the same with or without FK enforcement. In production the FKs (cascade and set null) do the work.
- **Decision:** One `GiftForm.vue` child component serves both the add form and the edit form. It owns its own Inertia form and posts with axios.
  **Why:** `Loans.vue` copies the whole form twice. One component avoids the copy and avoids mutating props.
- **Decision:** The Gifts tab sits after Tasks in the vault navigation, and the Gifts switch sits after the Tasks switch in vault settings.

## Assumptions
1. (tickets.md, ticket 1) Ticket 1 has a Definition of Done but no Given/When/Then scenarios. I used the DoD items as its acceptance criteria.
2. (spec, Service rules) The service checks only that the currency exists, not that it is active. The form's currency list comes from `currencies.index`, which shows active currencies only. Without this, a gift could not be edited after its currency was turned off.
3. (spec, "`url` must be a valid web address") I read "web address" as `http` or `https` only (`url:http,https`), with a maximum of 2048 characters.
4. (spec, estimated price) An estimated price of 0 is stored as empty, because `valueOrNull` treats 0 as empty, the same as Loans. Negative prices are rejected.
5. (ticket 6) "On the template page that holds Loans" means directly after Loans on that page. The other modules on the page move down by one position.
6. (ticket 6) "Last page by position" includes pages of any type. A tie in position is broken by the highest id. The account gets the module even when it has no templates or its templates have no pages, so an administrator can add it later.
7. (ticket 2, recipients only from this vault) The recipient picker uses the existing vault-scoped `search_contacts_only` endpoint. The service also rejects any recipient from another vault, with a 404 / `ModelNotFoundException`, as Loans does.
8. (ticket 5) The auto-fill compares the picked state with `last_gift_state_id`, which is the account's state with the highest `position`. It runs only when the user changes the state, so opening an edit form never changes a date by itself.
9. (ticket 5, "clears the given date") I added a "Clear" link next to the date picker, because clearing the v-calendar input is not reliable.
10. (spec, Translations) New strings are added to `lang/en.json` only. Other locales fall back to the English key until the maintainers run `monica:localize`.
11. (ticket 2, row details) Each row shows the occasion, state and price as small badges, and "Also for: …" lists the other recipients. "Given" rows also show the given date.
12. (spec, open question on existing gift rows) I assumed, as the spec does, that the old `gifts` and `contact_gift` tables hold no data. The migration drops them without copying anything.
13. (ticket 10) `CopyContactToAnotherVault` does not copy gifts. The tickets cover moving a contact, not copying one.

## Conflicts and deviations
1. **Path mismatch (spec header vs repo).** The spec says its tickets are in `output/tickets.md`, and the tickets file says the spec is `output/spec.md`. Both files are actually in `docs/product/specs/`. This has no effect on the work.
2. **Spec permission list vs Loans precedent.** The spec lists three permissions. The services also declare `contact_must_belong_to_vault`, following codebase precedent; see Design decisions. This is stricter than the spec and does not conflict with it.
3. **"Reuse the Loans module" vs viewer rules in tickets 2–4.** `Loans.vue` shows its add, edit and delete actions to everyone. The tickets need these hidden from viewers, so `Gifts.vue` checks `layoutData.vault.permission.at_least_editor`. Where the two disagreed, I followed the tickets.
4. **Data migration for templates (spec vs tickets).** The spec's Technical notes only say "attach it". Ticket 6 asks for the same page as Loans, falling back to the last page. I implemented the ticket.
5. **FK behaviour in tests vs `06-build-deploy.md`.** The architecture docs describe the SQLite test DB. That connection does not enforce foreign keys, so tests check the cascade and set-null rules by reading the schema (`Schema::getForeignKeys`) instead of deleting rows.
6. **Ticket 1 DoD lists MySQL/MariaDB, PostgreSQL and SQLite.** I ran the migrations on SQLite only (fresh `migrate:fresh`). MySQL and PostgreSQL were not available here. The migrations use only portable Blueprint calls; `->after()` is ignored where the database does not support it.
7. **Deviation: new trait inside a domain `Services/` folder.** No other domain does this. It is kept small and private to `ManageGifts`.

## Changes
**Ticket 1**
- Created `database/migrations/2026_10_15_000001_recreate_gifts_tables.php`. It drops the old `contact_gift` and `gifts` tables and recreates them as in the spec. `down()` restores the original shape.
- Created `app/Models/Gift.php` and `database/factories/GiftFactory.php`.
- Modified `app/Models/Contact.php` (`gifts()`) and `app/Models/Vault.php` (`gifts()`, plus `show_gifts_tab` in fillable and casts).

**Ticket 2–5**
- Created `app/Domains/Contact/ManageGifts/Services/{GiftValidation,CreateGift,UpdateGift,DestroyGift}.php`.
- Created `app/Domains/Contact/ManageGifts/Web/Controllers/ContactModuleGiftController.php` and `app/Domains/Contact/ManageGifts/Web/ViewHelpers/ModuleGiftViewHelper.php`.
- Modified `app/Models/Module.php` (`TYPE_GIFTS`), `app/Domains/Settings/CreateAccount/Jobs/SetupAccount.php` (Gifts after Loans) and `app/Domains/Contact/ManageContact/Web/ViewHelpers/ContactShowViewHelper.php`.
- Modified `routes/web.php`: added `contact.gift.store`, `contact.gift.update` and `contact.gift.destroy`.
- Created `resources/js/Shared/Modules/Gifts.vue` and `resources/js/Shared/Modules/GiftItems/GiftForm.vue`. Modified `resources/js/Pages/Vault/Contact/Show.vue`.

**Ticket 6**
- Created `database/migrations/2026_10_15_000003_add_gifts_module_to_existing_accounts.php`.

**Ticket 7–8**
- Created `database/migrations/2026_10_15_000002_add_show_gifts_tab_to_vaults.php` (`show_gifts_tab` boolean, default true).
- Created `app/Domains/Vault/ManageGifts/Web/Controllers/VaultGiftController.php` and `app/Domains/Vault/ManageGifts/Web/ViewHelpers/VaultGiftsIndexViewHelper.php`.
- Created `resources/js/Pages/Vault/Gifts/Index.vue`.
- Modified `routes/web.php` (`vault.gifts.index`), `app/Domains/Vault/ManageVault/Services/UpdateVaultTabVisibility.php`, `app/Domains/Vault/ManageVaultSettings/Web/Controllers/VaultSettingsTabVisibilityController.php`, `app/Domains/Vault/ManageVaultSettings/Web/ViewHelpers/VaultSettingsIndexViewHelper.php`, `app/Domains/Vault/ManageVault/Web/ViewHelpers/VaultIndexViewHelper.php`, `resources/js/Layouts/Layout.vue` and `resources/js/Pages/Vault/Settings/Partials/TabVisibility.vue`.

**Ticket 9–10**
- Modified `app/Domains/Contact/ManageContact/Services/DestroyContact.php` and `app/Domains/Contact/ManageContact/Services/MoveContactToAnotherVault.php`.

**All tickets**
- Modified `lang/en.json`: 28 new keys, inserted in alphabetical position.

**Tests**
- Created:
  - `tests/Unit/Domains/Contact/ManageGifts/Services/{CreateGiftTest,UpdateGiftTest,DestroyGiftTest}.php`
  - `tests/Unit/Domains/Contact/ManageGifts/Web/ViewHelpers/ModuleGiftViewHelperTest.php`
  - `tests/Unit/Domains/Vault/ManageGifts/Web/ViewHelpers/VaultGiftsIndexViewHelperTest.php`
  - `tests/Unit/Migrations/GiftsMigrationsTest.php`
  - `tests/Unit/Models/GiftTest.php`
  - `tests/Feature/Gifts/GiftTrackingTest.php` (HTTP and Inertia tests for AC-1 to AC-6)
- Updated these existing tests, because their behaviour now includes the Gifts tab, switch or module. Nothing was weakened; I only added the new key or assertion:
  - `VaultIndexViewHelperTest` (new `show_gifts_tab` and `gifts` URL)
  - `VaultSettingsIndexViewHelperTest` (`show_gifts_tab`)
  - `UpdateVaultTabVisibilityTest` (new required field, plus two new tests)
  - `SetupAccountTest` (Gifts module assertion, plus one ordering test)
  - `DestroyContactTest` and `MoveContactToAnotherVaultTest` (one new test each)

**Dependencies:** none added.
**Configuration / env vars:** none. **Migrations:** the three listed above.

## Testing
- Baseline, before any change (`php artisan test --compact`): 2057 tests, 2055 passed, 2 failed.
  - `Tests\Feature\ZiggyVersionCheckTest` fails because it runs `yarn info`, and yarn is not installed in the container.
  - `Tests\Unit\Domains\Contact\DAV\Services\CalDAVDatesTest` failed once on a timing comparison of `deleted_at`.
- Final, after `php artisan migrate:fresh --database=testing --force`: 2116 tests, 2115 passed, 1 failed. The failure is `ZiggyVersionCheckTest`, which was already failing. The CalDAV test passed this time, so it is flaky.
- 59 new tests. The "deprecated" count in the output comes from PHPUnit warnings about `/** @test */` doc-comments, which the existing tests also use.
- `vendor/bin/pint` fixed 3 style issues, all in files touched by this change.
- Prettier and ESLint on the changed and new Vue files: clean.
- `npx vite build` succeeded. The build output was removed afterwards.
- `phpstan` on the new files reports the same kind of errors as existing code such as `ManageLoans` (for example "undefined static method Model::find()"), because larastan's model extension is not active with the current `phpstan.neon`. The only gift-specific finding was the scope return types, and I fixed it.
- Acceptance criteria: AC-1 to AC-6 from the spec are each covered by a test in `GiftTrackingTest`. Every ticket scenario has a service, view-helper or HTTP test, with two exceptions. These are client-side Vue behaviours, and the repo has no JS test runner:
  - the ticket 5 auto-fill;
  - the cancel step of the ticket 4 delete confirmation.
  For ticket 5, the server data it relies on (`last_gift_state_id` follows position, and `current_date`) is tested.
- Pre-existing failure left untouched: `ZiggyVersionCheckTest`.

## Known limitations and follow-ups
- A viewer who calls the gift endpoints directly gets `NotEnoughPermissionException`. That is a 500 response, because `bootstrap/app.php` does not map it to 403. Every existing module behaves the same way; a follow-up could map it to 403 across the app.
- The ticket 5 auto-fill and the gift-list sorting in `Gifts.vue` have no automated tests, because there is no JS test framework.
- The migrations were not run against MySQL or PostgreSQL in this environment. CI covers both (`06-build-deploy.md`).
- Translations exist in English only.
- Phase 2 items are out of scope: sorting by upcoming dates, private gifts, reminders, totals and budgets, photos, the API, DAV export, search results and feed entries.
