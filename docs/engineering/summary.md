# Solution Summary

**Branch:** gifts-tracker/fenced_architecture  **Base HEAD:** 3ab768441cdf74328ac6bdb65867e5d994c5ed3f  **Date:** 15 October 2026
**Architecture docs:** present (`docs/architecture/README.md`, `00-overview.md`, `01-components.md`, `02-data-model.md`, `03-data-flow.md`, `04-external-interfaces.md`, `05-configuration.md`, `06-build-deploy.md`, `07-cross-cutting.md`, `08-glossary.md`)

The working tree was clean at the start.

## Overview

This adds gift tracking. Users can add, edit and delete gifts from a new "Gifts" section on a contact's page. A gift can have several recipients, and the section lists open gifts apart from given gifts. A new "Gifts" vault tab lists every open gift in the vault, grouped by gift occasion, and vault managers can hide it in "Tabs visibility". Gifts use the account's existing gift states and gift occasions. Existing accounts get the feature through migrations, with no setup. The work follows the architecture docs: a domain subfolder with services, a controller and a ViewHelper, `BaseService` rules and permissions, Gates on routes, and Inertia/Vue pages.

## Ticket status

| Ticket | Title | Status | Key files | Tests |
|---|---|---|---|---|
| 1 | Record a gift idea from a contact's page | Done | `app/Domains/Contact/ManageGifts/Services/CreateGift.php`, `.../Web/Controllers/ContactModuleGiftController.php`, `.../Web/ViewHelpers/ModuleGiftsViewHelper.php`, `resources/js/Shared/Modules/Gifts.vue`, `app/Models/Gift.php`, both new migrations, `SetupAccount.php`, `ContactShowViewHelper.php` | `CreateGiftTest`, `ModuleGiftsViewHelperTest`, `GiftControllersTest`, `AddGiftsModuleToExistingAccountsTest`, `SetupAccountTest` |
| 2 | Edit and delete a gift | Done | `UpdateGift.php`, `DestroyGift.php`, `Gifts.vue` | `UpdateGiftTest`, `DestroyGiftTest`, `GiftControllersTest` |
| 3 | Show given gifts as a contact's gift history | Done | `app/Helpers/GiftHelper.php`, `ModuleGiftsViewHelper.php`, `Gifts.vue` (Date given field and Given gifts list) | `GiftHelperTest`, `ModuleGiftsViewHelperTest`, `CreateGiftTest`, `UpdateGiftTest` |
| 4 | Add a rough price and a link to a gift | Done | `ResolvesGiftAttributes.php`, `ModuleGiftsViewHelper.php`, `Gifts.vue` | `CreateGiftTest`, `ModuleGiftsViewHelperTest`, `GiftControllersTest` |
| 5 | Give one gift to several recipients | Done | `ResolvesGiftAttributes.php`, `UpdateGift.php`, `DestroyContact.php`, `Contact::gifts()`, `Gift::recipients()` | `CreateGiftTest`, `UpdateGiftTest`, `DeleteGiftRecipientTest`, `GiftTest`, `ModuleGiftsViewHelperTest`, `VaultGiftsIndexViewHelperTest` |
| 6 | List all open gifts in a Gifts vault tab | Done | `app/Domains/Vault/ManageGifts/Web/{Controllers/VaultGiftController.php,ViewHelpers/VaultGiftsIndexViewHelper.php}`, `resources/js/Pages/Vault/Gifts/Index.vue`, `Layout.vue`, `VaultIndexViewHelper.php`, `routes/web.php` | `VaultGiftsIndexViewHelperTest`, `GiftControllersTest`, `VaultIndexViewHelperTest` |
| 7 | Let vault managers show or hide the Gifts vault tab | Done | `vaults.show_gifts_tab` column, `UpdateVaultTabVisibility.php`, `VaultSettingsTabVisibilityController.php`, `VaultSettingsIndexViewHelper.php`, `TabVisibility.vue`, `Layout.vue` | `UpdateVaultTabVisibilityTest`, `VaultSettingsIndexViewHelperTest`, `GiftControllersTest` |
| 8 | Keep gifts when someone deletes a gift state or gift occasion | Done | `DestroyGiftState.php`, `DestroyGiftOccasion.php`, nullable FKs with `nullOnDelete` | `DestroyGiftStateTest`, `DestroyGiftOccasionTest`, `ModuleGiftsViewHelperTest`, `VaultGiftsIndexViewHelperTest` |
| 9 | Count how many active hosted vaults have gifts | Done (code). The DoD's last item is a PM decision, not code. | `app/Console/Commands/ReportGiftUsage.php` (`php artisan monica:gift-usage`) | `ReportGiftUsageTest` |

Ticket 9 note: the codebase has no hosted-analytics pipeline (see Assumptions 13 and 14). The command gives maintainers the number and the share on demand, from release day. The DoD item "if hosted analytics cannot produce the count… the PM and maintainers record that decision" is a process step that code cannot do.

## Design decisions

- **Decision:** Replace the unused 2022 `gifts` and `contact_gift` tables with a new structure. `gifts` has `vault_id`, nullable `gift_occasion_id`, `gift_state_id` and `currency_id`, `name`, `description` (the note), `estimated_price` (integer, minor units), `url`, `given_at` (date) and timestamps. `contact_gift` is a pivot (`gift_id`, `contact_id`).
  - **Why:** `02-data-model.md` lists the 2022 `gifts` table, but no code reads or writes it. Its `contact_gift` table points to `loans` (a copy-paste bug). It also has received/bought fields, and gifts received are out of scope. One shared record plus a pivot matches the spec ("A gift is one shared record") and the `Loan` precedent (`vault_id` plus contact pivot).
  - **Alternatives considered:** Altering the old tables. This was rejected because `contact_gift` would need every column replaced, and `type` and `received_at` have no use in Phase 1.
- **Decision:** "Given" is computed when the page is read, never stored. A gift is given when its `gift_state_id` equals the account's last gift state by `position` (`GiftHelper::finalGiftStateId`).
  - **Why:** The spec's technical notes say reordering states moves gifts between open and given. Computing it on read gives that behaviour with no sync work.
  - **Alternatives considered:** An `is_given` flag kept in sync when states are reordered or deleted. This was rejected as more complex and error-prone.
- **Decision:** Gift states and occasions are nulled explicitly in `DestroyGiftState` and `DestroyGiftOccasion`, in addition to `nullOnDelete` FKs.
  - **Why:** The `testing` SQLite connection does not enable foreign keys (`config/database.php`), and the architecture supports several databases. The explicit update makes the behaviour the same on every database.
- **Decision:** When a recipient is deleted, `DestroyContact` deletes only the gifts that have no recipient left.
  - **Why:** Contacts are soft-deleted (`02-data-model.md`), so pivot cascades never fire. `Gift::recipients()` hides soft-deleted contacts through the SoftDeletes scope. The vault tab also only lists gifts that still have a recipient (`whereHas('recipients')`), as a safety net.
- **Decision:** The add and edit services share their rules and their attribute and recipient resolution through a trait, `ManageGifts/Services/ResolvesGiftAttributes.php`.
  - **Why:** Create and update take the same ten fields. `CreateLoan` and `UpdateLoan` duplicate this instead. A trait keeps the `BaseService` and `ServiceInterface` pattern and avoids about 60 duplicated lines. This is a small deviation from the existing duplication habit, recorded here.
- **Decision:** Price conversion and currency checks happen in the service, not in the controller as `ContactModuleLoanController` does.
  - **Why:** Validation (`numeric`) must run before parsing, so "about eighty" gives a field error and not a parser exception.
- **Decision:** Validation errors are shown next to each field in `Gifts.vue`, from Laravel's 422 `errors` bag. They are not shown through the shared `Errors.vue` box.
  - **Why:** AC-6 and the ticket 1, 2, 3, 4 and 5 scenarios say the message appears next to the field. The shared component only shows one box for the whole form.
- **Decision:** Controls to add, edit and delete are hidden for viewers with `layoutData.vault.permission.at_least_editor`. The server enforces the same rule with the `author_must_be_vault_editor` permission.
  - **Why:** This is the existing pattern (`Contact/Index.vue`, `Journal/Index.vue`) and `07-cross-cutting.md` (two-layer authorisation).
- **Decision:** The vault tab is `GET /vaults/{vault}/gifts` (`vault.gifts.index`), behind `can:vault-viewer`. A hidden tab works like the others: it only disappears from the navigation, and the URL still loads.
  - **Why:** That is how every other hidden vault tab behaves today (for example `VaultTaskController` has no visibility check), and ticket 7 asks for "the same result as for any other hidden vault tab".
- **Decision:** Existing accounts get the feature through two migrations. The schema migration's `show_gifts_tab` column defaults to `true`. A data migration (`2026_10_15_100001_…`) adds a `gifts` Module to each account and attaches it to the default template, the one with `can_be_deleted = false`. It goes on the `information` page, or on the first non-contact page if that page is gone, after the existing modules. It can be run again without effect.
  - **Why:** Precedent for migrations that change data (`2023_06_16_103430_fix_call_reasons.php`). New accounts get the module through `SetupAccount`. The module is listed in Settings → Templates like any other module, so users can add it to other layouts.
- **Decision:** Ticket 9 is an artisan command, `monica:gift-usage` (`--days=30`, `--json`), that counts rows only.
  - **Why:** No analytics pipeline exists (`05-configuration.md` and `config/monica.php` have no hosted or telemetry setting). The privacy review gate in the strategy rules out sending anything, so the command reads only `contacts.vault_id`, `contacts.last_updated_at` and `gifts.vault_id`.
- **Decision:** Links are shown as plain text inside an `<a target="_blank" rel="noopener noreferrer nofollow">`, and must be `http` or `https` (`url:http,https`).
  - **Why:** This follows the privacy gate (no fetch and no preview). The scheme limit stops `javascript:` links from becoming an XSS vector.

## Assumptions

1. **Default gift state (ticket 1, spec "New gifts start in the first gift state").** When the add request has no gift state, the gift starts in the account's first state. The add form preselects it. Choosing "No gift state" on the add form also falls back to the first state. On the edit form, "No gift state" clears the state.
2. **Default contact page layout (ticket 1).** This means each account's template with `can_be_deleted = false`, which is the "Default template" made by `SetupAccount`. Templates the user made are not changed.
3. **Where the section goes (ticket 1).** On the default template's "Information" page (slug `information`), after the existing sections. New accounts get it between Loans and Tasks. If the Information page was deleted, it goes on the first non-contact page.
4. **"Final gift state" ordering.** The highest `position` wins, with the highest `id` breaking ties. The "first" state is the lowest `position`, with the lowest `id` breaking ties.
5. **Removing the current contact from recipients.** An editor may remove the contact whose page they are on, as long as one recipient stays. The gift then leaves that page's section. The spec only requires at least one recipient.
6. **Rough price.** The price is a number of 0 or more, stored in the currency's minor units (cents), like loans. A price of `0` is stored as no price, because of `BaseService::valueOrNull` (it uses `empty()`). A price without a currency is formatted with no currency symbol. The currency must be enabled (`account_currency.active = true`) for the account.
7. **Link validation.** The tickets only say "paste a web address". The link must be `http`/`https` and at most 2,048 characters. Monica never fetches it.
8. **Date given.** It is a date only (`Y-m-d`), with no time. A date that does not exist (31 February) is rejected by `date_format:Y-m-d`. The form uses the browser's date input, which also blocks impossible dates.
9. **Order within lists.** Open gifts are newest first. Given gifts are by date given, latest first, with undated gifts last. Vault tab groups follow the account's occasion order, then "No occasion" last. Empty groups are not shown.
10. **Name length.** At most 255 characters. The note can be up to 65,535.
11. **Empty state in the contact section.** One message explains how to add the first gift. Viewers see the same message, though they have no "Add a gift" button.
12. **Contact last updated.** Adding, editing or deleting a gift updates `last_updated_at` on the contact whose page was used, as loans do.
13. **"Active hosted vault" (ticket 9).** No definition exists. A vault is active when at least one of its non-deleted contacts has `last_updated_at` within the last `--days` days (default 30).
14. **"Hosted plan only" (ticket 9).** The command counts the instance it runs on. Maintainers run it on the hosted instance. It is never scheduled and sends nothing, so self-hosted instances report nothing new.
15. **Viewer permission errors.** A viewer calling the write endpoints directly gets the app's existing `NotEnoughPermissionException` response (HTTP 500 today, as for loans and the other modules). No new error handling was added.
16. **Translations.** New strings were added to `lang/en.json` only. Other locales fall back to English until they are translated.

## Conflicts and deviations

- **Architecture docs vs. the tickets on the old `gifts` table.** `02-data-model.md` lists `gifts` (migration `2022_06_09_173049`), and the tickets' run notes say "Engineering decides whether to reuse it". It was replaced, not reused (see Design decisions). This drops two empty, unused tables. Nothing in the architecture docs depends on them.
- **Ticket 9 vs. the codebase.** The ticket assumes "hosted analytics" exist. They do not. The closest solution that fits the architecture is a read-only artisan command, which follows the existing `app/Console/Commands` pattern. See the Ticket status note.
- **Deviation from loan code duplication.** The shared trait `ResolvesGiftAttributes` (see Design decisions).
- **Deviation from `Loans.vue` permissions.** `Loans.vue` shows edit and delete controls to everyone. `Gifts.vue` hides them for viewers, because AC-5 requires it.
- No conflicts were found between the strategy, the spec and the tickets.

## Changes

**Ticket 1 (with shared infrastructure)**
- Created: `app/Models/Gift.php`, `database/factories/GiftFactory.php`, `database/migrations/2026_10_15_100000_create_gift_tracking_tables.php`, `database/migrations/2026_10_15_100001_add_gifts_module_to_existing_accounts.php`, `app/Helpers/GiftHelper.php`, `app/Domains/Contact/ManageGifts/Services/{CreateGift,ResolvesGiftAttributes}.php`, `app/Domains/Contact/ManageGifts/Web/Controllers/ContactModuleGiftController.php`, `app/Domains/Contact/ManageGifts/Web/ViewHelpers/ModuleGiftsViewHelper.php`, `resources/js/Shared/Modules/Gifts.vue`
- Modified: `app/Models/Module.php` (`TYPE_GIFTS`), `app/Models/Contact.php` (`gifts()`), `app/Models/Vault.php` (`gifts()`, `show_gifts_tab`), `app/Domains/Contact/ManageContact/Web/ViewHelpers/ContactShowViewHelper.php`, `app/Domains/Settings/CreateAccount/Jobs/SetupAccount.php`, `resources/js/Pages/Vault/Contact/Show.vue`, `routes/web.php`, `lang/en.json`

**Ticket 2:** `app/Domains/Contact/ManageGifts/Services/{UpdateGift,DestroyGift}.php`. Routes and UI are in the files above.

**Ticket 3:** `GiftHelper.php`, the given and open split in `ModuleGiftsViewHelper.php`, and the Date given field in `Gifts.vue`.

**Ticket 4:** the price, currency and link rules in `ResolvesGiftAttributes.php`, formatting in `ModuleGiftsViewHelper.php`, and the fields in `Gifts.vue`.

**Ticket 5:** recipient sync in `CreateGift` and `UpdateGift`, and `app/Domains/Contact/ManageContact/Services/DestroyContact.php` (deletes gifts with no recipients left).

**Ticket 6:** `app/Domains/Vault/ManageGifts/Web/Controllers/VaultGiftController.php`, `app/Domains/Vault/ManageGifts/Web/ViewHelpers/VaultGiftsIndexViewHelper.php`, `resources/js/Pages/Vault/Gifts/Index.vue`, `resources/js/Layouts/Layout.vue`, `app/Domains/Vault/ManageVault/Web/ViewHelpers/VaultIndexViewHelper.php`

**Ticket 7:** `app/Domains/Vault/ManageVault/Services/UpdateVaultTabVisibility.php`, `app/Domains/Vault/ManageVaultSettings/Web/Controllers/VaultSettingsTabVisibilityController.php`, `app/Domains/Vault/ManageVaultSettings/Web/ViewHelpers/VaultSettingsIndexViewHelper.php`, `resources/js/Pages/Vault/Settings/Partials/TabVisibility.vue`

**Ticket 8:** `app/Domains/Settings/ManageGiftStates/Services/DestroyGiftState.php`, `app/Domains/Settings/ManageGiftOccasions/Services/DestroyGiftOccasion.php`

**Ticket 9:** `app/Console/Commands/ReportGiftUsage.php`

**Tests created:** `tests/Unit/Models/GiftTest.php`, `tests/Unit/Helpers/GiftHelperTest.php`, `tests/Unit/Domains/Contact/ManageGifts/Services/{CreateGiftTest,UpdateGiftTest,DestroyGiftTest,DeleteGiftRecipientTest}.php`, `tests/Unit/Domains/Contact/ManageGifts/Web/ViewHelpers/ModuleGiftsViewHelperTest.php`, `tests/Unit/Domains/Vault/ManageGifts/Web/ViewHelpers/VaultGiftsIndexViewHelperTest.php`, `tests/Feature/Controllers/Gifts/GiftControllersTest.php`, `tests/Unit/Commands/ReportGiftUsageTest.php`, `tests/Unit/Migrations/AddGiftsModuleToExistingAccountsTest.php`

**Existing tests updated (the tickets change this behaviour):**
- `VaultIndexViewHelperTest`: layout data now has `show_gifts_tab` and the `gifts` URL.
- `VaultSettingsIndexViewHelperTest`: visibility now has `show_gifts_tab`.
- `UpdateVaultTabVisibilityTest`: `show_gifts_tab` is now a required field, and a test was added that hides and shows the tab.
- `SetupAccountTest`: asserts the Gifts module exists.
- `DestroyGiftStateTest` and `DestroyGiftOccasionTest`: new tests check that gifts are kept.

No existing assertion was weakened or removed.

**Dependencies added:** none.

**Migrations:** the two above. The first one also adds `vaults.show_gifts_tab` (boolean, default `true`).

**Configuration / environment variables:** none.

**New routes:** `GET vaults/{vault}/gifts` (`vault.gifts.index`), `POST|PUT|DELETE vaults/{vault}/contacts/{contact}/gifts[/{gift}]` (`contact.gift.store|update|destroy`).

**New command:** `php artisan monica:gift-usage [--days=30] [--json]`.

## Testing

All commands ran in the `sail-8.4/app` container.

- **Baseline** (`php artisan test --compact`, before any change): 2,057 tests, 2 failed, 3,722 assertions. The failures:
  - `Tests\Feature\ZiggyVersionCheckTest`: it runs `yarn info` and needs network access, so it fails in this environment.
  - `Tests\Unit\Domains\Contact\DAV\Services\CalDAVDatesTest`: a timing flake, where `deleted_at` was one second off.
- **Final** (`php artisan migrate:fresh --database=testing --force`, then `php artisan test --compact`): 2,121 tests, 1 failed (`ZiggyVersionCheckTest`, the same environment issue), 3,880 assertions. The CalDAV flake passed on this run. There are 64 new tests and no new failures.
- `vendor/bin/pint --test`: pass (1,669 files).
- `eslint` on every changed or new Vue file: no errors. `prettier --write` was applied to them.
- `vite build` (client): succeeds.
- `vendor/bin/phpstan`: the tool is not usable in this environment. It reports 681 errors across the whole codebase, for example "Call to an undefined static method App\Models\Vault::find()" in existing factories, which means the Larastan model extension is not working here. The messages for the new files are of the same environmental kinds (`Model::create()` or `findOrFail()` undefined, untyped collection callbacks). One real docblock issue in `GiftFactory` was fixed. Psalm was not run.

**Acceptance criteria coverage:**
- AC-1 and ticket 1:
  - `CreateGiftTest::it_creates_a_gift_with_only_a_name_in_the_first_gift_state`, `…with_an_occasion_a_state_and_a_note`, `…without_gift_state_when_the_account_has_none`, `it_fails_without_a_name`
  - `GiftControllersTest::an_editor_adds_a_gift_with_only_a_name`, `the_errors_are_returned_for_each_field`
  - `AddGiftsModuleToExistingAccountsTest` (existing accounts), `SetupAccountTest` (new accounts)
  - Viewer: `CreateGiftTest::it_fails_if_the_user_is_a_viewer`, plus the hidden UI controls.
- AC-2 and ticket 5:
  - `UpdateGiftTest::it_changes_the_gift_state_for_every_recipient`, `it_removes_one_recipient`, `it_does_not_save_the_change_when_every_recipient_is_removed`
  - `CreateGiftTest::it_creates_a_gift_for_several_recipients`, `it_fails_if_a_recipient_is_not_in_the_vault`
  - `ModuleGiftsViewHelperTest::it_shows_the_same_gift_on_every_recipient_page`
  - `DeleteGiftRecipientTest`, `VaultGiftsIndexViewHelperTest::it_hides_gifts_whose_recipients_were_all_deleted`
- AC-3 and ticket 3:
  - `ModuleGiftsViewHelperTest::it_separates_open_gifts_from_given_gifts`, `it_moves_gifts_between_open_and_given_when_gift_states_are_reordered`
  - `UpdateGiftTest::it_sets_the_date_given`, `CreateGiftTest::it_fails_if_the_date_given_does_not_exist`
  - `GiftHelperTest`, `VaultGiftsIndexViewHelperTest::it_returns_no_groups_when_there_are_no_open_gifts` (given gifts are excluded)
- AC-4 and ticket 6:
  - `VaultGiftsIndexViewHelperTest::it_lists_open_gifts_grouped_by_occasion`, `…_when_the_account_has_none`, `it_returns_no_groups_when_there_are_no_open_gifts` (the Vue page shows the empty-state message)
  - `GiftControllersTest::a_viewer_opens_the_gifts_vault_tab`, `someone_outside_the_vault_cannot_open_the_gifts_vault_tab`
- AC-5:
  - `GiftControllersTest::a_viewer_cannot_add_change_or_delete_a_gift`
  - `UpdateGiftTest::it_fails_if_the_user_is_a_viewer`, `DestroyGiftTest::it_fails_if_the_user_is_a_viewer`
  - The UI uses `at_least_editor`.
- AC-6:
  - The name and recipient validation tests in `CreateGiftTest`, `UpdateGiftTest` and `GiftControllersTest` (422 with per-field errors).
- Ticket 4:
  - `CreateGiftTest::it_creates_a_gift_with_a_price_a_link_and_a_date_given`, `it_fails_if_the_price_is_not_a_number`, `it_fails_if_the_link_is_not_a_web_address`, `it_fails_if_the_currency_is_not_enabled_in_the_account`
  - `ModuleGiftsViewHelperTest::it_gets_the_data_transfer_object` (the link is returned exactly as pasted)
- Ticket 7:
  - `UpdateVaultTabVisibilityTest::it_hides_and_shows_the_gifts_tab`, `GiftControllersTest::a_hidden_gifts_tab_behaves_like_other_hidden_tabs`
  - `VaultSettingsIndexViewHelperTest` and the DB default `true` (existing vaults)
  - The contact section does not depend on the tab setting.
- Ticket 8:
  - `DestroyGiftStateTest::it_keeps_the_gifts_that_used_the_gift_state`, `DestroyGiftOccasionTest::it_keeps_the_gifts_that_used_the_gift_occasion`
  - `ModuleGiftsViewHelperTest::it_keeps_gifts_when_the_final_gift_state_is_deleted`, `it_shows_the_renamed_gift_occasion`
  - `VaultGiftsIndexViewHelperTest::it_moves_gifts_to_no_occasion_when_their_occasion_is_deleted`
- Ticket 9: `ReportGiftUsageTest` (counts, share, output with no gift content, an instance with no active vaults, invalid input).

**Not covered by automated tests:** the project has no JS test framework, so the Vue behaviour is checked by build and lint only. That includes:
- whether controls show or hide
- the link opening in a new tab
- the per-field messages
- the currency dropdown being limited to enabled currencies, which comes from the existing `currencies.index` endpoint, which already filters on `active`

**Pre-existing failures left untouched:** `ZiggyVersionCheckTest` (environment), and the flaky `CalDAVDatesTest::caldav…` (timing).

## Known limitations and follow-ups

- Moving a contact to another vault (`contact.move`) does not detach it from gifts in the old vault. Such a recipient would still show on the old vault's gift. Recipients from other vaults are out of scope, but a follow-up could detach them on move.
- Hiding the Gifts tab only hides the navigation entry, the same as the other tabs. Blocking direct URLs to hidden tabs would be a separate, app-wide change.
- Write endpoints called by a viewer return HTTP 500 (`NotEnoughPermissionException`), the same as other modules. Mapping it to 403 app-wide would be a separate fix.
- Ticket 9 runs only on demand. If maintainers want a recurring number, a scheduled run that logs aggregate counts could be added after a privacy review.
- Design review (Figma) for the add/edit form and the vault tab has not happened yet. The UI follows the existing `Loans` and `Tasks` patterns and may need changes afterwards.
- Translations into locales other than English are needed for the new strings.
- PHPStan cannot be checked here. It should be re-run in CI, where Larastan loads properly.
