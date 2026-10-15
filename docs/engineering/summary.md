# Solution Summary

**Branch:** gifts-tracker/no_architecture  **Base HEAD:** bab7ba4b0499ec829e33f944893ffdd493b69a7c  **Date:** 2026-10-15
**Architecture docs:** not present (no `docs/architecture/` directory). The work follows the existing codebase conventions.

## Overview
Gift tracking for Monica, in three parts:
- A Gifts section on every contact page. Users can add, edit and delete gifts, and the section has an open list and a gift history.
- A vault-wide Gifts page that groups open gifts by occasion.
- A vault setting that switches that page on or off.

The feature copies the existing Loans module throughout. That means a domain folder with services (`app/Domains/Contact/ManageGifts`), a view helper, a JSON controller, a Vue module in `resources/js/Shared/Modules`, and a template module type so the section shows on contact pages.

Gift stages and occasions reuse the existing Settings lists. The "given" stage is not stored: it is the last stage by position, worked out each time data is read. Renames and reorders in Settings therefore show on gifts straight away.

## Ticket status
| Ticket | Title | Status | Key files | Tests |
|---|---|---|---|---|
| 1 | Record a gift idea on a contact's page | Done | `app/Models/Gift.php`, `ManageGifts/Services/CreateGift.php`, `ContactModuleGiftController.php`, `ModuleGiftsViewHelper.php`, `Shared/Modules/Gifts.vue`, migration `2026_10_15_000000_create_gift_tracking_tables.php`, `SetupAccount.php` | `CreateGiftTest`, `ModuleGiftsViewHelperTest`, `GiftControllersTest`, `AddGiftsModuleToExistingAccountsTest`, `SetupAccountTest` |
| 2 | Edit and delete a gift from a contact's page | Done | `UpdateGift.php`, `DestroyGift.php`, `Gifts.vue` | `UpdateGiftTest`, `DestroyGiftTest`, `GiftControllersTest` |
| 3 | Add occasion, price, link, and note to a gift | Done | `CreateGift.php` and `UpdateGift.php` (rules), controller messages, `ModuleGiftsViewHelper::details`, `Gifts.vue` | `CreateGiftTest`, `ModuleGiftsViewHelperTest`, `GiftControllersTest` |
| 4 | Move a gift through gift stages and keep a gift history | Done | `GiftHelper.php`, `CreateGift.php` and `UpdateGift.php` (date given), `ModuleGiftsViewHelper::data` (open list vs history) | `UpdateGiftTest`, `CreateGiftTest`, `GiftHelperTest`, `GiftControllersTest` |
| 5 | Give one gift to several recipients | Done | `contact_gift` pivot, `Gift::recipients`, `Contact::gifts`, `DestroyContact.php` | `CreateGiftTest`, `UpdateGiftTest`, `DestroyGiftTest`, `DestroyContactTest`, `ModuleGiftsViewHelperTest` |
| 6 | Keep gifts correct when stages or occasions change in Settings | Done | `DestroyGiftState.php` (blocks deletion), `DestroyGiftOccasion.php`, `GiftStates/Index.vue` (explanation and error) | `DestroyGiftStateTest`, `DestroyGiftOccasionTest`, `ModuleGiftsViewHelperTest` (rename, reorder) |
| 7 | See all open gifts in a vault on the Gifts page | Done | `Vault/ManageGifts/Web/Controllers/VaultGiftController.php`, `VaultGiftsIndexViewHelper.php`, `Pages/Vault/Gifts/Index.vue`, `Layout.vue`, `routes/web.php` | `VaultGiftsIndexViewHelperTest`, `GiftControllersTest` |
| 8 | Switch the vault Gifts page on or off | Done | `vaults.show_gifts_tab` column, `UpdateVaultTabVisibility.php`, `VaultSettingsTabVisibilityController.php`, `VaultIndexViewHelper.php`, `VaultSettingsIndexViewHelper.php`, `TabVisibility.vue` | `UpdateVaultTabVisibilityTest`, `VaultIndexViewHelperTest`, `VaultSettingsIndexViewHelperTest`, `GiftControllersTest` |

All tickets are Done at the code and unit/feature-test level. The Vue screens compile (`vite build`) and pass lint. Nobody has used them in a browser, because the brief did not allow starting servers.

## Design decisions
- **Decision:** Copy the Loans module structure:
  - a `ManageGifts` domain with `CreateGift`, `UpdateGift` and `DestroyGift` services extending `BaseService`
  - a `ModuleGiftsViewHelper` and a JSON `ContactModuleGiftController`
  - routes nested under the contact
  - a `Gifts.vue` module rendered by `Pages/Vault/Contact/Show.vue`

  **Why:** The tickets say "follow the existing contact page sections, such as Loans", and every contact module in the codebase is built this way. **Alternatives considered:** resource controllers with form requests. Nothing in the codebase works that way, so I did not use them.
- **Decision:** Work out the given stage when data is read: `GiftHelper::givenStage` returns the highest `position` in the account's `gift_states`. There is no stored `is_given` flag. **Why:** Ticket 6 says a reorder must change which gifts are given right away, and renames must show on existing gifts. Working it out on read gives both for free. **Alternatives considered:** storing a flag and recomputing it on every reorder. That is more code and the flag could drift.
- **Decision:** Replace the unused placeholder `gifts` and `contact_gift` tables. They were created in 2022 and have no model, and `contact_gift` pointed at `loans`. The new migration drops and recreates both:
  - `gifts` belongs to a vault, as `loans` does. It has nullable foreign keys to occasion, stage (`nullOnDelete`) and currency.
  - `contact_gift` is a plain gift-to-contact pivot.

  **Why:** The old schema could not model one gift with many recipients. It was never used, so no data is lost. **Alternatives considered:** altering the old tables. Their columns (`contact_id`, `type`, `received_at`, `bought_at`) and the broken pivot do not fit the spec.
- **Decision:** The same migration adds the Gifts module to existing accounts (`addGiftsModuleToExistingAccounts`, safe to run twice). For each account it creates one `gifts` module and attaches it to every template, on the page that holds the Loans module, or else on the first page that is not the contact page. New accounts get the module from `SetupAccount::addTemplatePageInformation`, after Loans, with `can_be_deleted => false` like Loans. **Why:** Ticket 1 says existing accounts must see the section without editing their templates (spec open question 3, working assumption "yes"). **Alternatives considered:** a console command. A migration runs on upgrade automatically, for both hosted and self-hosted installs.
- **Decision:** Validate each field in the controller (`$request->validate` with custom messages), and keep the full rules in the services as well. **Why:** AC-5 and ticket 3 need a message next to each field that names the field. Service `validateRules` only gives generic messages keyed by internal names such as `recipient_ids`. This is the smallest way to get per-field messages without changing `BaseService`. **Alternatives considered:** extending `BaseService` to take custom messages. That is a change across the whole codebase.
- **Decision:** `DestroyContact::updateGifts` detaches the contact from shared gifts and deletes gifts where it was the only recipient. Contacts are soft-deleted, so database cascades never fire. The vault page also skips gifts with no live recipient (`whereHas('recipients')`). **Why:** AC-2, tickets 5 and 7, and the spec assumption "if a user deletes the only recipient, Monica deletes the gift".
- **Decision:** `DestroyGift` and `DestroyContact` detach pivot rows explicitly. `DestroyGiftOccasion` nulls `gift_occasion_id` explicitly. **Why:** The test sqlite connection does not enforce foreign keys, and doing it explicitly is correct whatever the database does.
- **Decision:** Hide the add, edit and delete controls with a `can_edit` flag in the module data (vault permission <= EDIT). The services enforce the same rule with `author_must_be_vault_editor`. **Why:** This follows the vault permission model, and the flag can be tested in the view helper.
- **Decision:** Store the price as a whole amount in `estimated_price` (for example 40) and format it for display with `MonetaryNumberHelper`. The currency choices come from `CurrencyIndexViewHelper::data`, which lists the account's active currencies. The services also reject a currency the account has not enabled. **Why:** The spec says "a whole amount in one of the account's enabled currencies", and the existing helpers already do this.

## Assumptions
1. **Given stage** (spec, Terms): the stage with the highest `position`, with ties broken by the highest id. If an account has no stages, every gift is open and new gifts have no stage.
2. **Default stage** (AC-1): if no stage is sent, the gift gets the first stage by position. The form preselects that stage.
3. **Date given when a gift moves back** (decomposition notes leave this undefined): moving a given gift back to an earlier stage keeps its date given, and the user can clear it in the form. Open gifts may have a date given, since ticket 4 has a scenario with one.
4. **Date given on reorder** (ticket 6, out of scope): a reorder that makes a gift given does not set a date given.
5. **Deleting a stage that gifts use** (spec open question 1): Monica blocks the deletion with a validation message: "Some gifts are at this stage. Move those gifts to another stage first." Unused stages delete as before.
6. **Contact page templates** (spec open question 3): the module goes into every template of every existing account, placed as described above. It has `can_be_deleted = false`, like Loans and the other built-in modules.
7. **Link** (ticket 3): a "web address" means an `http` or `https` URL (`url:http,https`) of at most 2048 characters. It is shown as a plain link with `rel="noopener noreferrer nofollow"`. Monica never fetches it.
8. **Price** (ticket 3): a whole number of zero or more. The currency is optional. A price with no currency shows as a bare number.
9. **Recipients** (ticket 5):
   - The add form starts with the current contact as the only recipient.
   - The user can remove the current contact if another recipient remains. The gift then leaves that contact's page.
   - Recipient search uses the existing contact search, which only covers the current vault. The server also checks that each recipient is in the vault.
10. **Changing a gift's stage** (ticket 4): the stage is a dropdown in the edit form. There is no separate inline control, which matches the tickets' "the gift form".
11. **Selecting a gift on the vault page** (ticket 7 says "the design decides"): there is no design, so selecting a gift expands its details in place: stage, price, link, note and date given. It also links to each recipient's contact page, which is where editors change the gift. Viewers see the details only.
12. **Ordering:**
    - Open gifts: newest first.
    - Gift history: by date given, newest first.
    - Vault page: occasion groups follow the occasions list, and "No occasion" comes last.
13. **Response when the page is switched off:** 404. See Conflicts.
14. **Missing inputs:** there are no personas, no Figma designs and no `docs/iterations`. The UI follows the existing Loans and Tasks styling.
15. **Translations:** new strings are in `lang/en.json` only. Other locales fall back to English.
16. **On by default:** `show_gifts_tab` defaults to true in the database, so existing and new vaults have the page on (spec assumption, ticket 8). `UpdateVaultTabVisibility` accepts `show_gifts_tab` as optional (`sometimes|boolean`), so existing callers keep working.

## Conflicts and deviations
1. **Ticket 8: "responds the same way as a switched-off Tasks or Journal page" vs "the member sees no gifts list".** Today a switched-off Tasks or Journal page only leaves the navigation, and its URL still renders the full page. Copying that would break the second half of the scenario. **Resolution:** `VaultGiftController` returns 404 when `show_gifts_tab` is false, so no gifts list is shown. Tasks and Journal are out of scope and left as they are.
2. **The spec and tickets refer to `output/spec.md` and `output/tickets.md`,** but the documents are in `docs/product/specs/`. This has no effect on the work.
3. **Deviation from the Loans pattern:** the gift controller validates the request, with per-field messages, before calling the service. The Loans controller relies only on the service. This was needed for AC-5 and ticket 3, as set out in Design decisions.
4. **Existing tests updated because ticket 8 changes behaviour:**
   - `VaultIndexViewHelperTest` and `VaultSettingsIndexViewHelperTest` assert the exact visibility and URL arrays, so they now include `show_gifts_tab` and the `gifts` URL.
   - New assertions were added to `SetupAccountTest`, `DestroyContactTest`, `DestroyGiftStateTest`, `DestroyGiftOccasionTest` and `UpdateVaultTabVisibilityTest`.
   - No existing assertion was weakened or removed.

## Changes
**Shared and data (tickets 1, 5, 8)**
- Created `database/migrations/2026_10_15_000000_create_gift_tracking_tables.php`. It recreates `gifts` and `contact_gift`, adds `vaults.show_gifts_tab` (default true), and adds the Gifts module to existing accounts' templates.
- Created `app/Models/Gift.php`, `database/factories/GiftFactory.php` and `app/Helpers/GiftHelper.php`.
- Modified:
  - `app/Models/Vault.php`: `gifts()`, and `show_gifts_tab` as fillable and cast
  - `app/Models/Contact.php`: `gifts()`
  - `app/Models/Module.php`: `TYPE_GIFTS`

**Tickets 1–5 (contact page)**
- Created:
  - `app/Domains/Contact/ManageGifts/Services/{CreateGift,UpdateGift,DestroyGift}.php`
  - `.../Web/Controllers/ContactModuleGiftController.php`
  - `.../Web/ViewHelpers/ModuleGiftsViewHelper.php`
  - `resources/js/Shared/Modules/Gifts.vue`
- Modified:
  - `routes/web.php`: `contact.gift.store`, `contact.gift.update` and `contact.gift.destroy`
  - `ContactShowViewHelper.php` and `Pages/Vault/Contact/Show.vue`
  - `SetupAccount.php` and `DestroyContact.php`

**Ticket 6 (Settings)**
- Modified:
  - `DestroyGiftState.php`: blocks deleting a stage that gifts use
  - `DestroyGiftOccasion.php`: clears the occasion on its gifts
  - `resources/js/Pages/Settings/Personalize/GiftStates/Index.vue`: explains that the last stage means given, and shows the deletion error

**Ticket 7 (vault Gifts page)**
- Created `app/Domains/Vault/ManageGifts/Web/Controllers/VaultGiftController.php`, `.../ViewHelpers/VaultGiftsIndexViewHelper.php` and `resources/js/Pages/Vault/Gifts/Index.vue`.
- Modified:
  - `routes/web.php`: `vault.gifts.index`
  - `VaultIndexViewHelper.php`: `url.gifts` and `visibility.show_gifts_tab`
  - `resources/js/Layouts/Layout.vue`: navigation link

**Ticket 8 (switch)**
- Modified `UpdateVaultTabVisibility.php`, `VaultSettingsTabVisibilityController.php`, `VaultSettingsIndexViewHelper.php` and `resources/js/Pages/Vault/Settings/Partials/TabVisibility.vue`.

**Other**
- `lang/en.json`: 32 new strings, inserted in the file's existing order.
- No dependencies added. No environment variables or configuration added. One new migration, listed above.

## Testing
**New tests:**
- `tests/Unit/Domains/Contact/ManageGifts/Services/{CreateGiftTest,UpdateGiftTest,DestroyGiftTest}.php`
- `tests/Unit/Domains/Contact/ManageGifts/Web/ViewHelpers/ModuleGiftsViewHelperTest.php`
- `tests/Unit/Domains/Vault/ManageGifts/Web/ViewHelpers/VaultGiftsIndexViewHelperTest.php`
- `tests/Unit/Helpers/GiftHelperTest.php`
- `tests/Unit/Migrations/AddGiftsModuleToExistingAccountsTest.php`
- `tests/Feature/Controllers/Gifts/GiftControllersTest.php`. This covers the per-field validation messages, blocking users with view permission, the vault page grouping, the 404 when the page is off, a non-member getting the same response as for Tasks, and the settings switch.

**Commands** (all run in the `sail-8.4/app` container):
- `php artisan migrate:fresh --database=testing --force`
- `php artisan test --compact`:
  - **Baseline:** 2057 tests, 2 failed. `ZiggyVersionCheckTest` fails because `yarn` is not in the container. `CalDAVDatesTest` fails on the time precision of `deleted_at`, which looks flaky.
  - **Final:** 2116 tests (59 new), 1 failed: `ZiggyVersionCheckTest`, the same failure as the baseline. `CalDAVDatesTest` passed this time. The rest pass, with PHPUnit deprecation notices for `/** @test */` doc-comments, as in the baseline.
- `vendor/bin/pint` on all changed PHP paths: passed.
- `npx prettier --write` and `npx eslint` on the changed Vue files: clean.
- `npx vite build --outDir /tmp/buildcheck`: builds. The output stays outside the repository.
- `vendor/bin/phpstan`, run only on the changed paths: reports Larastan resolution errors such as "undefined static method `Gift::create()`". The same errors appear on untouched existing files such as `CreateGiftState.php`, so they come from running on part of the codebase, not from this change. I did not run phpstan on the whole codebase.

**Acceptance-criteria coverage:** each ticket scenario maps to at least one service, view-helper or feature test, with two exceptions:
- Purely visual parts, such as a message sitting "next to" a field or the navigation link appearing, are built in Vue and backed by per-field error keys tested in `GiftControllersTest`. The project has no JavaScript test framework, so there are no automated UI tests.
- "User cancels an edit" happens only in the browser: the edit form works on a copy and never calls the server.

**Failures that were already there:** `ZiggyVersionCheckTest` is environmental and was left alone.

## Known limitations and follow-ups
- `MoveContactToAnotherVault` and `CopyContactToAnotherVault` do not move or adjust gifts. A moved contact stays a recipient of gifts in its old vault. This needs a follow-up ticket.
- Gifts are not in vCard/DAV exports or any data export.
- The vault Gifts page has no design yet, though ticket 7 asks for one. The current layout copies the Tasks page and needs a design review.
- Switched-off Tasks and Journal pages still render when opened by URL (Conflict 1). Making them behave like Gifts is a separate change.
- Translations for languages other than English are pending.
- Phase 2 items are out of scope: the dashboard widget, sorting by next occasion, gifts received and private gifts.
