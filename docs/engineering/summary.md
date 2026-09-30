# Solution Summary

**Branch:** with_architecture **Base HEAD:** ff2d5aadfa6dda7bcab69054b60da7afebeb943c **Date:** 2026-09-29
**Architecture docs:** present (`docs/architecture/README.md`, `00-overview.md`, `01-components.md`, `02-data-model.md`, `03-data-flow.md`, `04-external-interfaces.md`, `05-configuration.md`, `06-build-deploy.md`, `07-cross-cutting.md`, `08-glossary.md`)

## Overview

Added an "Export" link next to the vCard download on the contact page. It downloads one JSON file with everything Monica stores about the contact. The layout follows the architecture docs:

- `ExportContact`, a read-only `BaseService` in the `ManageContact` sub-domain, builds the file.
- `ContactExportController` returns it as an `application/json` attachment from a POST route next to the vCard route, which also bypasses the Inertia middleware (as the spec's technical notes require).
- The `vault-viewer` and `contact-owner` gates guard the route, and the service repeats the check.
- A test (ticket 7) fails whenever a `Contact` model relation is neither exported nor explicitly excluded.

## Ticket status

| Ticket | Title                                                      | Status      | Key files                                                                                                                                                  | Tests                                                                                                              |
| ------ | ---------------------------------------------------------- | ----------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| 1      | Download a contact export from the contact page            | Done        | `ExportContact.php` (file shape, `contact` part), `ContactExportController.php`, `routes/web.php`, `ContactShowViewHelper.php`, `Show.vue`, `lang/en.json` | `ContactExportControllerTest` (10), `ExportContactTest` ticket-1 block (10), `ContactShowViewHelperTest` (updated) |
| 2      | Include contact information, addresses and important dates | Done        | `ExportContact.php`                                                                                                                                        | `ExportContactTest` ticket-2 block (5)                                                                             |
| 3      | Include notes, reminders, calls and tasks                  | Done        | `ExportContact.php`                                                                                                                                        | `ExportContactTest` ticket-3 block (3)                                                                             |
| 4      | Include relationships, loans, gifts and shared events      | **Partial** | `ExportContact.php`                                                                                                                                        | `ExportContactTest` ticket-4 block (6)                                                                             |
| 5      | Include pets, goals, moods, quick facts, labels and groups | Done        | `ExportContact.php`                                                                                                                                        | `ExportContactTest` ticket-5 block (3)                                                                             |
| 6      | List photos, documents and the avatar                      | Done        | `ExportContact.php`                                                                                                                                        | `ExportContactTest` ticket-6 block (3)                                                                             |
| 7      | Warn developers when a new type of contact data is missing | Done        | `ExportContact::EXPORTED_RELATIONS`, `ExportContact::EXCLUDED_RELATIONS`                                                                                   | `ExportContactTest` ticket-7 block (3)                                                                             |

**Ticket 4 is partial.** Relationships, loans (both directions), timeline events and life events, and the gifts list are all exported. What's missing is one scenario, "The export lists gifts with the other party": the gift can't show who gave it or Tom Smith as the receiver. The database can't represent a gift's other party. `gifts` has a single `contact_id`, and the `contact_gift` pivot in `2022_06_09_173049_create_gifts_table.php` has `loan_id`, `loaner_id` and `loanee_id` columns, with no `gift_id`. There's no gift model, service or screen. The export lists the contact's gifts (`gifts.contact_id`) with their `type`, details, amount and dates. See Conflicts.

The front-end behaviour (the link's position and style, the browser download, the error toast) has no automated test, because the repo has no JavaScript test runner.

## Design decisions

- **Decision:** New service `App\Domains\Contact\ManageContact\Services\ExportContact`. It extends `BaseService`, implements `ServiceInterface` with `rules()` and `permissions()`, and is called as `(new ExportContact)->execute($data)`.
  - **Why:** `01-components.md` and `08-glossary.md` describe this as the service-object pattern, and `03-data-flow.md` step 6 shows the `(new X)->execute()` call style. The permissions are `author_must_be_in_vault` (the viewer level, ≤ 300) and `contact_must_belong_to_vault`. This is the second authorization layer `07-cross-cutting.md` describes, matching the spec's rule that the export must not require `vault-editor`.
  - **Alternatives considered:** Building the data in a ViewHelper. Rejected, because ViewHelpers shape Inertia props (`08-glossary.md`) and don't check permissions.

- **Decision:** `ContactExportController::download` sits on `POST /vaults/{vault}/contacts/{contact}/export` (`contact.export.download`), next to the vCard route, with `->withoutMiddleware([HandleInertiaRequests::class])`.
  - **Why:** The spec's Technical notes specify this, citing `04-external-interfaces.md`. The route sits inside the existing `can:vault-viewer,vault` and `can:contact-owner,vault,contact` groups, so a non-member, a contact from another vault, and a soft-deleted contact get exactly what the vCard route returns. Tests compare the two routes directly.

- **Decision:** The response is the JSON file itself, with `Content-Type: application/json; charset=UTF-8`, `Content-Disposition: attachment; filename="jane-doe-2026-09-29.json"` and `Cache-Control: no-store, private`. The page requests it with `axios` (`responseType: 'blob'`), then saves it under the name from `Content-Disposition`. The user stays on the page, and a failed request shows the existing `flash()` error toast.
  - **Why:** The spec says the browser receives a file, not a page, with content type `application/json`. `no-store` supports the strategy's privacy promise that no copy of the export is kept in caches. An `axios` request keeps the user on the contact page, which a plain form POST wouldn't do on error. The other actions in `Show.vue` use `axios` + `flash()` the same way.
  - **Alternatives considered:** Copying the vCard's session-flash round trip. Rejected, because the architecture note says the route must bypass Inertia so the browser receives a file, and flashing would put the export in the session store.

- **Decision:** The top-level layout is `format_version`, `exported_at`, `contact`, then one key per data type (`contact_information`, `addresses`, `important_dates`, `notes`, `reminders`, `calls`, `tasks`, `relationships`, `loans`, `gifts`, `timeline_events`, `pets`, `goals`, `mood_tracking_events`, `quick_facts`, `labels`, `groups`, `photos`, `documents`) and `avatar`.
  - **Why:** This follows the spec's "What the export file contains" list and its order. Every list is always present, and `[]` when empty (AC-5). The `contact` part always holds all 13 fields, `null` where empty (ticket 1).

- **Decision:** Type fields use the models' accessors (`Gender::name`, `RelationshipType::name`, `ContactInformationType::name`, `AddressType::name`, `CallReason::label`, `PetCategory::name`, `MoodTrackingParameter::label`, `LifeEventType::label`, and so on).
  - **Why:** These accessors return the user-set name, or else `__($translation_key)` in the request locale. That is exactly the name the contact page shows, in the user's language (spec Technical notes; `it_exports_default_type_names_in_the_users_language` checks this in French). Record IDs are kept on every record.

- **Decision:** Formats:
  - timestamps use ISO 8601 UTC (`2026-09-29T14:05:00Z`)
  - calendar dates the user enters use `YYYY-MM-DD`
  - partial important dates and reminders use ISO 8601 partial forms (`--05-03` with no year, `1990-05`, `1990`), plus the raw `day`, `month` and `year`
  - money is the raw amount in the main unit, as a JSON number (`50` for 5000 cents of EUR), with the currency code
  - distance is raw, with its unit
  - **Why:** The spec's Formats note asks for ISO 8601, raw values and currency codes, ignoring the user's date, number and distance preferences. The no-year birthday keeps `year: null` (ticket 2: "does not invent one").

- **Decision:** Other contacts are always the object `{id, name}`. The connection is given by where the object sits and the fields next to it:
  - relationships: `relationship_type` / `relationship_group` with `contact`
  - loans: `contact_role` (`lender`/`borrower`) with `lenders` / `borrowers`
  - timeline events and life events: `other_participants`, `paid_by`
  - The display name comes from `NameHelper::formatContactName()` with the exporting user's name order, as on the contact page.
  - **Why:** AC-3 and ticket 4 require ID, display name and connection only. Relationships reuse `ModuleRelationshipViewHelper`'s logic (`name_reverse_relationship` when the exported contact is `contact_id`), so "sister" matches the page. Relationships to soft-deleted contacts are skipped, as on the page.

- **Decision:** Timeline events are the ones the contact takes part in (`Contact::timelineEvents`), each with all of its life events.
  - **Why:** This mirrors the contact page (`ModuleLifeEventViewHelper`, `08-glossary.md` "TimelineEvent").

- **Decision:** Gifts are read with `DB::table('gifts')`, joined to `currencies`, where `gifts.contact_id` is the contact.
  - **Why:** There's no `Gift` model. Adding one would be an unrequested model addition, so the query stays inside the service, reading the database only. See Conflicts.

- **Decision:** Groups are exported as `{id, name, type, role}`, with no members.
  - **Why:** Ticket 5 puts "The members of a group other than the exported contact" out of scope. Labels are `{id, name}` only.

- **Decision:** Files are `{id, name, mime_type, size, uploaded_at}`, with no `uuid`, `original_url` or `cdn_url`. `avatar` is one such object, or `null`.
  - **Why:** AC-4 and ticket 6 require no file content and no web address. Everything is read from the `files` table, and Uploadcare is never called (the spec's no-third-party-calls note).

- **Decision (ticket 7):** `ExportContact::EXPORTED_RELATIONS` maps each exported `Contact` relation to its part of the file. `ExportContact::EXCLUDED_RELATIONS` lists `vault`, `template`, `posts` and `lifeMetrics`, each with a reason. A test reflects over `Contact`'s public methods that return an Eloquent `Relation` and fails, naming the relation, when one is in neither list.
  - **Why:** This is the spec's suggested "test that fails when a contact relation is missing". I checked that it fires by temporarily removing `pets`. The test failed with: `The Contact relation "pets" is not in the full contact export. Add it to the export and to ExportContact::EXPORTED_RELATIONS, or to ExportContact::EXCLUDED_RELATIONS with the reason.` A second test checks that every mapped part exists in the export.

- **Decision:** The file name is `Str::slug(contact name)` + `-` + the export date + `.json`, falling back to `contact-YYYY-MM-DD.json` when the slug is empty. The date is the export date in the user's timezone (UTC when none is set).
  - **Why:** The spec's File name note. The slug rule is the one the vCard download already uses (`ContactVCardController`). `Zoë / O'Brien?` becomes `zoe-obrien-2026-09-29.json`.

## Assumptions

1. **Link text** (ticket 1, "Export"): reused the existing `"Export"` translation key. One new string, "The export failed. Please try again.", was added to `lang/en.json` only. Other locales fall back to the key.
2. **Money as a number** (spec Formats; ticket 4 "the amount `50`"): amounts are exported in the currency's main unit via `MonetaryNumberHelper::inputValue`, as a JSON number. Whole amounts encode as `50`.
3. **Life-event distance** is exported as stored, with its `distance_unit`, ignoring `distance_format`.
4. **Address latitude and longitude** (ticket 2 "all its address fields", and out of scope: "map or geocoding data from external services"): the stored `latitude` and `longitude` are exported as address fields. No geocoding service is called.
5. **Authors**: note `author_name` is the author user's name (`User::name`), `null` if that user was deleted. Calls and tasks export their stored `author_name` column.
6. **Tasks**: both open and completed tasks are exported. Soft-deleted tasks and dates are excluded, because the user deleted them.
7. **Loans**: settled loans are exported too, although the contact page shows only unsettled ones, because the spec asks for every record. A loan where the contact is both lender and borrower is shown once, with `contact_role: lender`.
8. **Gift fields**: the raw `type` column is exported as stored. Its values aren't documented, and nothing in the codebase writes it.
9. **Groups**: the contact's role in the group is exported as the role label, or `null`.
10. **Files**: `mime_type` and `size` are included as well as name and upload date, to help identify the originals. "Upload date" is the `files.created_at` timestamp.
11. **Filename date** uses the user's timezone, so the date matches the user's own calendar. `exported_at` stays in UTC.
12. **Excluded relations**: `vault` and `template` aren't data about the person. `posts` and `lifeMetrics` are out of scope in the spec. The activity feed isn't a `Contact` relation, and it isn't exported.

## Conflicts and deviations

- **Ticket 4 gifts vs the database schema (not resolvable within scope).** Ticket 4 expects a gift to show the giver and the receiver by ID and name. The tickets' own decomposition assumed "the gifts table links a gift to a giver contact and a receiver contact", but the migration doesn't: `gifts.contact_id` is a single contact, and `contact_gift` has loan columns (`loan_id`, `loaner_id`, `loanee_id`) and no `gift_id`. Following the precedence rules (scope from the tickets, but implementation constrained by the architecture and codebase), the export includes the gifts list built from `gifts.contact_id`. It can't show the other party, so ticket 4 is marked Partial. Fixing the gift schema would be a migration plus a new domain feature, which is outside this spec (ticket 4's out-of-scope list: "A way to record gifts in Monica"). The PM should decide whether to drop gifts, as the tickets already suggest, or ticket the schema fix.
- **Spec "contact information … with its type" vs `kind`**: the export also includes the stored `kind` column, alongside the account type name. No conflict, just an extra stored field.
- **`04-external-interfaces.md`** lists the contact routes. Adding a new route there would mean editing `docs/`, which the task forbids. The new route is recorded here instead.
- No deviations from the architecture docs.

## Changes

**Ticket 1**

- Created `app/Domains/Contact/ManageContact/Services/ExportContact.php`.
- Created `app/Domains/Contact/ManageContact/Web/Controllers/ContactExportController.php`.
- Modified `routes/web.php`: the `contact.export.download` route and its import.
- Modified `app/Domains/Contact/ManageContact/Web/ViewHelpers/ContactShowViewHelper.php`: `url.export` in `data()` and `dataForTemplatePage()`.
- Modified `resources/js/Pages/Vault/Contact/Show.vue`: the "Export" link after the vCard link, and `exportContact()`.
- Modified `lang/en.json`: 1 string.
- Created `tests/Unit/Domains/Contact/ManageContact/Web/Controllers/ContactExportControllerTest.php`.
- Created `tests/Unit/Domains/Contact/ManageContact/Services/ExportContactTest.php`.
- Modified `tests/Unit/Domains/Contact/ManageContact/Web/ViewHelpers/ContactShowViewHelperTest.php`: one assertion added for `url.export`. No existing assertion changed.

**Tickets 2–6:** sections in `ExportContact.php`, tested in `ExportContactTest.php`.
**Ticket 7:** the `EXPORTED_RELATIONS` / `EXCLUDED_RELATIONS` constants in `ExportContact.php`, tested in `ExportContactTest.php`.

**Not changed:** anything in `app/Domains/Contact/Dav/` (`ExportVCardResource`, `VCardResource`) or `ContactVCardController.php`, as the spec requires.
**Dependencies added:** none. **Migrations, config, environment variables:** none.

## Testing

All commands ran inside the project's Sail container (`sail-8.4/app`: PHP 8.4.22, Node 22, yarn 4.6.0) via `docker compose run --rm --no-deps laravel.test`. The testing DB was prepared with `php artisan migrate:fresh --database=testing` and `db:seed --database=testing`.

| Command                                                                                              | Result                                                                                                                                     |
| ---------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| Baseline `vendor/bin/phpunit` (before changes)                                                       | 2057 tests, 3724 assertions, **8 failures**, 1 skipped                                                                                     |
| Final `vendor/bin/phpunit`                                                                           | 2101 tests (+44 new), 3972 assertions, **8 failures** (the identical set to the baseline, checked by diffing the failure lists), 1 skipped |
| `phpunit` on `ExportContactTest` + `ContactExportControllerTest` (re-run after the last code change) | 44 tests, 248 assertions, all pass                                                                                                         |
| `phpunit` on `ContactExportControllerTest` + `ContactShowViewHelperTest`                             | 12 tests, 50 assertions, all pass                                                                                                          |
| `vendor/bin/pint --test` (7 changed or new PHP files)                                                | pass                                                                                                                                       |
| `yarn eslint resources/js/Pages/Vault/Contact/Show.vue`                                              | pass                                                                                                                                       |
| `yarn prettier --check Show.vue lang/en.json`                                                        | pass                                                                                                                                       |
| `vendor/bin/phpstan analyse` (whole project)                                                         | See below                                                                                                                                  |
| `vendor/bin/psalm`                                                                                   | Not run. `vimeo/psalm` isn't in `composer.json` or `vendor`, although `psalm.xml` and `yarn test` still reference it                       |

**Pre-existing failures (left untouched):** `CalDAVDatesTest` (2), `CalDAVTasksTest` (2), `CardDAVTest` (3) and `VCardContactTest::test_carddav_update_existing_contact_if_unmodified_error`. These are DAV sync and XML response assertions, and no DAV code was changed.

**PHPStan:** even in the container, the project reports about 700 errors in untouched code (for example "Call to an undefined static method App\Models\Contact::where()", and `Collection<int,stdClass>::map()` mismatches such as `ModuleDocumentsViewHelper.php:18`). Larastan isn't resolving Eloquent generics in this setup. The findings in `ExportContact.php` fall into those same categories. The one genuine finding (`amount()` "never returns int") was fixed. `ContactExportController.php` has no findings. I planned a line-by-line diff against a PHPStan run on a pristine HEAD export, but couldn't finish it, because the session's command-safety check kept failing (a tooling issue, not a code issue). CI's `static_analysis.yml` should be checked.

**Acceptance-criteria coverage**

- **T1:**
  - Link next to the vCard link, same style: `Show.vue` uses the same `<li class="mb-2"><Link class="cursor-pointer text-blue-500 hover:underline">` markup. The URL is covered by `ContactShowViewHelperTest`. Visual placement isn't automated.
  - Viewer download (file name with date, valid JSON, `format_version`, `exported_at`, `contact`): `a_vault_viewer_downloads_the_export_file`, `managers_and_editors_download_the_export_file_too`, `it_exports_the_file_shape`.
  - Own details, including translated type names: `it_exports_the_contacts_own_details`, `it_exports_default_type_names_in_the_users_language`.
  - vCard unchanged: `the_vcard_download_still_gives_the_vcard_file`, plus no changes to vCard or Dav code. CardDAV is covered by the existing DAV suite, whose failure set is unchanged.
  - Non-member, other vault, deleted contact: `a_user_outside_the_vault…`, `a_contact_from_another_vault…`, `a_deleted_contact…`. Each asserts no file and the same status code as the vCard route. I didn't record the exact codes, only their equality.
  - Only a first name: `it_exports_a_contact_with_only_a_first_name`.
  - Unsafe characters: `the_file_name_is_safe_when_the_name_has_unusual_characters`, `the_file_name_falls_back_when_the_name_has_no_safe_characters`.
  - Display preferences: `display_preferences_do_not_change_the_file`, `it_writes_money_as_a_raw_number`.
  - No raw vCard: `it_does_not_export_the_raw_vcard`.
  - No writes and no external calls: `it_does_not_write_anything_or_call_any_external_service`.
- **T2:**
  - `it_exports_contact_information_addresses_and_important_dates_with_their_types`
  - `it_keeps_another_contacts_information_and_addresses_out`
  - `it_exports_an_address_shared_with_another_contact_without_the_other_contact`
  - `it_exports_an_important_date_without_a_year_without_inventing_one`
  - `it_formats_partial_dates_in_iso_8601`
  - Empty lists: `it_exports_a_contact_with_only_a_first_name`
- **T3:** `it_exports_notes_reminders_calls_and_tasks`, `it_keeps_another_contacts_notes_and_reminders_out`, `it_lets_a_viewer_export_notes_that_an_editor_wrote`, and the empty-lists test.
- **T4:**
  - `it_exports_relationships_loans_and_shared_events`
  - `it_describes_the_relationship_from_the_exported_contacts_point_of_view`
  - `it_keeps_other_contacts_private_details_out`
  - `it_exports_loans_where_the_contact_lends_and_borrows`
  - `it_ignores_relationships_with_deleted_contacts`
  - `it_exports_the_gifts_of_the_contact` (gift list only; the other-party part isn't possible, see above)
  - Empty lists: the empty-lists test
- **T5:** `it_exports_pets_goals_moods_quick_facts_labels_and_groups`, `it_keeps_another_contacts_pets_goals_and_moods_out`, `it_does_not_name_other_contacts_that_share_a_label_or_group`, and the empty-lists test.
- **T6:**
  - `it_lists_photos_documents_and_the_avatar_by_name_and_upload_date`
  - `it_exports_no_file_content_and_no_web_address`
  - `it_lists_files_when_the_file_storage_service_is_unreachable` (Uploadcare keys set, `Http::preventStrayRequests()`)
  - No uploaded files: the empty-lists test (`photos`/`documents` are `[]`, `avatar` is `null`)
- **T7:**
  - `every_relation_of_the_contact_model_is_exported_or_excluded_on_purpose` (mutation-checked, see Design decisions)
  - `every_exported_relation_points_to_a_part_of_the_export`
  - `the_journal_posts_life_metrics_and_activity_feed_are_excluded_on_purpose`

## Known limitations and follow-ups

- **Gifts:** the schema can't link a gift to its other party (see Conflicts). The PM should decide whether to drop gifts or ticket a schema fix.
- **Ticket 7 covers `Contact` relations only.** A new table that points at contacts without a relation on the `Contact` model (like `gifts` today) wouldn't trip the check.
- **Front-end:** there's no automated test for the click → download → toast flow. It needs a manual browser check.
- **Translations:** the new error string exists only in `lang/en.json`.
- **Architecture doc update:** `04-external-interfaces.md` should list `POST /vaults/{vault}/contacts/{contact}/export` (not edited here, because `docs/` is read-only for this task).
- **Riskiest assumption (spec):** the data-type list is complete. The ticket 7 check shows every current `Contact` relation is either exported or deliberately excluded.
