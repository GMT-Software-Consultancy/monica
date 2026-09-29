# Solution Summary

**Branch:** no_architecture **Base HEAD:** a1cffb18b253912b01c181a662576e8088537eea **Date:** 2026-09-29
**Architecture docs:** not present (followed existing codebase conventions)

## Overview

Added a "Download full export (JSON)" link next to "Download as vCard" on the contact page. It downloads one JSON file with everything recorded about the contact. A new read-only domain service, `ExportContact`, builds the file. It follows the same `BaseService` rules and permissions pattern as the other contact services. A new web controller returns the file in memory, with no server-side copy. The page builds the download in the browser only after the whole export has arrived. If the export fails, the page shows an error toast and no file is created.

## Ticket status

| Ticket | Title                                                               | Status | Key files                                                                                                                                                                      | Tests                                                                                                                |
| ------ | ------------------------------------------------------------------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------- |
| 1      | Download a contact's full export file from the contact page         | Done   | `ContactFullExportController.php`, `ExportContact.php` (address-book + labels sections, file shape), `routes/web.php`, `ContactShowViewHelper.php`, `Show.vue`, `lang/en.json` | `ContactFullExportControllerTest` (8), `ExportContactTest` ticket-1 block (9), `ContactShowViewHelperTest` (updated) |
| 2      | Include the contact's own recorded entries in the full export       | Done   | `ExportContact.php` (quick facts, notes, important dates, reminders, tasks, calls, goals + streaks, pets, mood tracking)                                                       | `ExportContactTest` ticket-2 block (5) + empty-contact test                                                          |
| 3      | Show linked contacts in the full export by name and connection only | Done   | `ExportContact.php` (relationships, groups, loans, life events; `linkedContact()`)                                                                                             | `ExportContactTest` ticket-3 block (6) + empty-contact test                                                          |
| 4      | List the contact's photos, documents and avatar in the full export  | Done   | `ExportContact.php` (`avatar`, `photos`, `documents`; `fileEntry()`)                                                                                                           | `ExportContactTest` ticket-4 block (3) + empty-contact test                                                          |

The front-end behaviour (link placement, the browser download, the error toast) has no automated test. The project has no JavaScript test framework. See Testing.

## Design decisions

- **Decision:** Put the export logic in a new service, `App\Domains\Contact\ManageContact\Services\ExportContact`, which extends `BaseService`. It declares `rules()` and `permissions()` (`author_must_be_in_vault`, `contact_must_belong_to_vault`) and returns a plain array.
  - **Why:** This is how every contact action in the codebase works, including `ExportVCard`. `author_must_be_in_vault` checks the lowest level, view permission, so view-only members can export (T1). The contact and vault checks mean nothing outside the member's vault can leak.
  - **Alternatives considered:** Building the array in a ViewHelper. That was rejected because ViewHelpers here shape page props, while services own permission checks.

- **Decision:** The controller returns the file inside a JSON envelope, `{"data": {"filename": "...", "content": "<json string>"}}`, sent with `Cache-Control: no-store, private`. The front end fetches it with `axios` and builds a Blob.
  - **Why:** The vCard download passes its file through a session flash (`Redirect::back()->with('flash', ...)`). That would write the export into the session store on the server, which breaks the privacy rule: "Monica keeps no copy of the export after the download". The export is instead built in memory and sent only in the response, and `no-store` stops caches from keeping it. The `{data: ...}` envelope and the `axios` + `flash()` error pattern match the other actions on `Show.vue`, such as `destroy` and `toggleArchive`. The file is created in the browser only after a successful response, so a failed export never produces a file, not even a partial one (T1–T4 failure scenarios).
  - **Alternatives considered:** Copying the vCard flash approach (rejected for privacy). A streamed `Content-Disposition` download (rejected: when a plain link download fails, the browser navigates away or saves an error page instead of keeping the user on the contact page with a message).

- **Decision:** The route is `GET vaults/{vault}/contacts/{contact}/export` (`contact.export.download`). It sits inside the existing `can:vault-viewer` and `can:contact-owner` route groups.
  - **Why:** It reuses the same authorization as "Download as vCard", as the spec requires. GET fits because the export is read-only. The vCard route uses POST because `ExportVCard` writes to the contact.
  - **Alternatives considered:** POST, to mirror the vCard route. There's no side effect that would justify it.

- **Decision:** Any failure in any section fails the whole export. No section catches exceptions. JSON encoding uses `JSON_THROW_ON_ERROR`.
  - **Why:** Tickets 2–4 say a missing section must fail the export rather than silently drop data. Uncaught exceptions give Laravel's standard 500 response, which is also logged, and the page then shows the error message.

- **Decision:** Each section is a `protected` method on the service.
  - **Why:** The code reads as one section per data type. Tests can also make a single section fail (notes, relationships, files) through a Mockery partial mock, which proves the "fails entirely" behaviour for each one.

- **Decision:** Relationships are read with the same query as `ModuleRelationshipViewHelper`, in both directions, and labelled from the exported contact's point of view. The label is `name_reverse_relationship` when the contact is `contact_id`, otherwise `name`, exactly as on the contact page.
  - **Why:** "sister: Jane Doe" has to match what the user sees on the page (T3). Relationships whose other contact is soft-deleted are skipped, as on the contact page.

- **Decision:** Linked contacts appear as `{"name": ..., "connection": ...}` only. Names use `NameHelper::formatContactName()` with the exporting user's name order, like the rest of the UI. Connection values:
  - relationships: the relationship type
  - groups: the other member's group role, or `"group member"`
  - loans: `"lender"` or `"borrower"`
  - life events: `"participant"`, or `"paid for the life event"` for `paid_by`
  - **Why:** This covers T3 and AC-3: name and connection only. Contact IDs aren't exported either.

- **Decision:** Files are listed as `{name, mime_type, size, added_at}`. `uuid`, `original_url` and `cdn_url` are left out.
  - **Why:** T4 excludes file content and "download links to the original files". The CDN URL is exactly such a link. `mime_type` and `size` are kept because they help identify the original file.

- **Decision:** Dates use two ISO 8601 forms:
  - calendar dates the user enters (due date, call date, loan date, life event date, streak date, mood date): `YYYY-MM-DD`
  - system timestamps (`created_at`, `updated_at`, `completed_at`, `settled_at`, `added_at`): `YYYY-MM-DDThh:mm:ssZ` in UTC
  - important dates and reminders, which can be partial: ISO 8601 partial forms `YYYY-MM-DD`, `--MM-DD` (no year), `YYYY-MM` and `YYYY`, with the raw `day`, `month` and `year` values too
  - **Why:** The spec requires ISO 8601 everywhere. Monica stores important dates with an optional year, and `--MM-DD` is the ISO 8601 form for a date without a year. vCard uses the same form.

- **Decision:** The file name is `Str::slug(contact name).json`, as the vCard uses. If the slug is empty, the name falls back to `contact.json`.
  - **Why:** AC-2 says the file is "named after the contact". The fallback stops a name made only of symbols from producing a bare `.json` file. This is a minimal guard, not a change to the vCard.

## Assumptions

1. **Link text** (tickets.md, "The spec does not give the text of the full export link"): used "Download full export (JSON)". The error text is "The export failed. Please try again." Both were added to `lang/en.json` only. Other locales fall back to the English key through laravel-vue-i18n, and translations are left to the normal translation workflow.
2. **Format version** (spec, "format version number"): an integer `format_version: 1` at the top level, exposed as `ExportContact::FORMAT_VERSION`.
3. **File layout** (not specified): `{ "format_version": 1, "contact": { ...sections } }`, with every section always present.
4. **"Present but empty"** (AC-5, T1–T4 empty scenarios): list sections are `[]`. Single-value sections (`gender`, `pronoun`, `religion`, `job_information`, `avatar`) are `null`. `names` is always an object.
5. **Template-hidden data** (spec open question 🟡, T1/T2 scenarios): the export ignores the contact template entirely and includes all recorded data.
6. **Author and user data**: authors of notes, tasks and calls (vault users, not the contact) are left out. They are not "recorded about the contact", and leaving them out keeps the file to the contact's data.
7. **Tasks**: the export includes both open and completed tasks. Soft-deleted rows are excluded, because the user deleted them.
8. **Loans**: "loaner" is shown as `lender` and "loanee" as `borrower`, matching the UI labels "Who makes the loan?" and "Who the loan is for?". Settled loans are included. The contact page shows only unsettled ones, but the spec asks for everything recorded.
9. **Linked contact names** follow the exporting user's name-order preference. If that preference includes, say, `%nickname%`, the linked contact's nickname appears as part of their name, as it does on the contact page. This isn't treated as a detail leak.
10. **Groups**: besides the group name and this contact's role (the ticket requirement), other group members are listed by name and role. The spec's scope says contacts in groups "appear by name and by how they connect". No other member details are included.
11. **Life events**: exported for the contact as a participant (`life_event_participants`). The event's timeline label is included as context.
12. **Monetary amounts** (loans, life-event costs): exported as decimal strings via `MonetaryNumberHelper::inputValue`, with the currency code.
13. **Activity feed, journal posts, life metrics**: excluded. The first two are explicitly out of scope. Life metrics aren't in the spec's in-scope list.

## Conflicts and deviations

- **vCard pattern vs privacy rule**: the closest precedent, the vCard download, passes its file through the session flash. Copying it would store the export server-side, if only briefly, which conflicts with the spec's "Monica must not keep a copy of the export after the download". Resolved in favour of the spec: the export goes back directly in the response. The vCard download itself is unchanged. `it_still_downloads_the_contact_as_a_vcard` covers this.
- **"An API or endpoint for exports" is out of scope** vs the need for a server route: this was read as "no public or REST API". The only way in is a session-authenticated web route used by the contact page, the same kind of route the vCard link uses. Nothing was added to `routes/api.php`.
- **Groups placement**: the spec lists groups under address-book-adjacent data. The tickets put groups in ticket 3. Scope follows the tickets.
- No deviations from existing conventions beyond the above.

## Changes

**Ticket 1**

- Created `app/Domains/Contact/ManageContact/Services/ExportContact.php`: the service, file shape, address-book and label sections.
- Created `app/Domains/Contact/ManageContact/Web/Controllers/ContactFullExportController.php`.
- Modified `routes/web.php`: the `contact.export.download` route and its import.
- Modified `app/Domains/Contact/ManageContact/Web/ViewHelpers/ContactShowViewHelper.php`: `url.download_full_export` in both `data()` and `dataForTemplatePage()`.
- Modified `resources/js/Pages/Vault/Contact/Show.vue`: the link and `downloadFullExport()`.
- Modified `lang/en.json`: 2 strings.
- Created `tests/Unit/Domains/Contact/ManageContact/Web/Controllers/ContactFullExportControllerTest.php`.
- Created `tests/Unit/Domains/Contact/ManageContact/Services/ExportContactTest.php`.
- Modified `tests/Unit/Domains/Contact/ManageContact/Web/ViewHelpers/ContactShowViewHelperTest.php`: one assertion added for the new URL. No existing assertion changed.

**Tickets 2, 3, 4**: added sections to `ExportContact.php`, with tests in `ExportContactTest.php`.

**Dependencies added:** none.
**Configuration, migrations, environment variables:** none. Self-hosted instances need no setup.

## Testing

**Environment note:** this WSL distro has no PHP, Node or running Docker. For the PHP checks, PHP 8.4.26 CLI was extracted from the Ondřej Surý PPA `.deb` packages into the session scratchpad, not installed system-wide. It had the extensions the project needs (`intl`, `pdo_sqlite`, `sodium`, `mbstring`, …). The testing DB was prepared with `php artisan migrate:fresh --database=testing` and `db:seed --database=testing`, with the env vars from `phpunit.xml`, as `yarn test` does. No Node checks could run.

| Command                                                                                                       | Baseline (before changes)                                                                                                  | Final                                                                                                                        |
| ------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `vendor/bin/phpunit` (full suite)                                                                             | 2057 tests, 3723 assertions, **1 error, 8 failures**, 1 skipped                                                            | 2088 tests, 3879 assertions, **1 error, 8 failures**, 1 skipped                                                              |
| `vendor/bin/phpunit --filter 'ExportContactTest\|ContactFullExportControllerTest\|ContactShowViewHelperTest'` | n/a                                                                                                                        | 33 tests, 173 assertions, all pass                                                                                           |
| `vendor/bin/pint --test` (changed PHP files)                                                                  | n/a                                                                                                                        | pass                                                                                                                         |
| `vendor/bin/phpstan analyse` (whole project)                                                                  | 669 errors in this environment                                                                                             | Only new errors are in `ExportContact.php`, and all are the same environmental categories that fill the baseline (see below) |
| `vendor/bin/psalm`                                                                                            | not installed (`vimeo/psalm` isn't in `composer.json`, although `psalm.xml` and the `yarn test` script still reference it) | not run                                                                                                                      |
| ESLint / Prettier                                                                                             | Node not available                                                                                                         | not run. The Vue changes were checked by hand against `.prettierrc` (120 columns, single quotes, trailing commas)            |

The failing set is identical before and after, checked by diffing the failure list. These 9 failures pre-existed and were left untouched:

- `ZiggyVersionCheckTest::it_ckecks_ziggy_version_are_same`: "Attempt to read property "versions" on null", because this environment has no resolvable node package metadata.
- `CalDAVDatesTest` (2), `CalDAVTasksTest` (2), `CardDAVTest` (3) and `VCardContactTest::test_carddav_update_existing_contact_if_unmodified_error`: DAV sync and XML response assertions unrelated to this change.

**PHPStan:** in this environment larastan doesn't resolve Eloquent generics. The untouched baseline has 411 "undefined static method" errors on models and 26 errors where a collection is typed `Collection<int,stdClass>`, and `ExportContact.php` shows the same patterns. The one real finding (an unnecessary `?->` before `??`) was fixed. CI (`monicahq/workflows` laravel.yml) should be checked for a clean PHPStan run in a proper environment.

**Acceptance-criteria coverage**

- T1 link next to vCard, same style: `Show.vue` (same `<li class="mb-2"><Link class="cursor-pointer text-blue-500 hover:underline">` markup). The URL is covered by `ContactShowViewHelperTest`. Visual placement isn't automated.
- T1 vCard unchanged: `it_still_downloads_the_contact_as_a_vcard`.
- T1 download, `.json` named after the contact, valid JSON, format version, address-book details and labels: `it_downloads_the_full_export_file_of_a_contact`, `it_exports_the_address_book_details_and_labels_of_a_contact`, `it_names_the_file_contact_when_the_name_gives_no_file_name`.
- T1 ISO 8601 dates: `it_formats_every_date_in_the_iso_8601_format`, `it_formats_partial_dates_in_the_iso_8601_format`.
- T1 failure, no file: `it_returns_an_error_and_no_file_when_the_export_cannot_be_created` (500, no `data`). The client-side "no Blob on error, toast shown" path isn't automated.
- T1 only-name contact: `it_exports_a_contact_with_only_a_name_with_every_section_present_but_empty`, `it_downloads_a_contact_with_only_a_name`.
- T1 view-only member: `it_lets_a_vault_member_with_view_permission_export_a_contact`, `it_lets_a_vault_member_with_view_permission_download_the_export`. Access control: `it_forbids_the_export_to_users_outside_the_vault`, `it_forbids_the_export_of_a_contact_from_another_vault`, `it_fails_if_user_has_no_access_to_the_vault`, `it_fails_if_contact_doesnt_belong_to_vault`.
- T1 template-hidden job information: `it_exports_job_information_even_when_the_contact_template_hides_it`.
- T1 self-hosted, no setup: no config or env was introduced. The tests run on a stock testing instance.
- Privacy, no stored copy: `it_does_not_write_anything_to_the_database`, plus the `Cache-Control: no-store` assertion.
- T2 every entry, open and completed tasks, goal streaks, ISO dates: `it_exports_every_recorded_entry_of_the_contact`. Notes failure: `it_fails_entirely_when_the_notes_cannot_be_included`. Empty sections: the only-name test. Hidden notes: `it_exports_notes_even_when_the_contact_template_hides_them`.
- T3 all four types, group name and role, name and connection: `it_exports_relationships_groups_loans_and_life_events_with_linked_contacts_by_name_and_connection`, `it_describes_the_relationship_from_the_point_of_view_of_the_exported_contact`. Linked contact's phone and note excluded: `it_keeps_the_details_of_a_linked_contact_out_of_the_export`. Other group members' emails excluded: `it_keeps_the_details_of_other_group_members_out_of_the_export`. Relationships failure: `it_fails_entirely_when_the_relationships_cannot_be_included`. Empty sections: the only-name test.
- T4 2 photos + 1 document gives 3 entries with no content: `it_lists_photos_and_documents_by_name_and_date_added_without_their_content`. Avatar: `it_lists_the_avatar_by_name_and_date_added_without_its_content`. Photo-list failure: `it_fails_entirely_when_the_photo_list_cannot_be_included`. Empty sections: the only-name test.

## Known limitations and follow-ups

- **Front-end not covered by automated tests**: the repo has no JS test runner. The click → download → toast flow should get a manual check in a browser (and ESLint/Prettier via `yarn lint` in an environment with Node).
- **Translations**: the two new strings exist only in `lang/en.json`.
- **Riskiest assumption still open** (spec): engineering should confirm the in-scope data-type list against the product. Candidates noticed but not included, because they're outside the listed scope: life metrics, journal posts that mention the contact, the activity feed.
- **Structure documentation**: out of scope for Phase 1 (per the spec). `format_version` is the contract hook for later changes.
- **Large contacts**: the export is built in one request, in memory. That's fine for single contacts. A whole-vault export (out of scope) would need a queued job.
- **Exposure before T2–T4**: all four tickets ship together here, so the product manager's question about showing the link after ticket 1 alone no longer applies.
