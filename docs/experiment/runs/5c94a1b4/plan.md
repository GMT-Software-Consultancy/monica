# Plan: full contact export (JSON) from the contact page

## Goal

Add a "Download all data" action to the contact page (`resources/js/Pages/Vault/Contact/Show.vue`). It downloads one JSON file with what the user has recorded about that contact: profile basics, notes, relationships, reminders and important dates. The file is export only (answer 5). It covers one contact, from the contact page only (answers 8 and 10).

## Assumptions (where the requester had no strong view)

| # | Topic | Decision | Why |
|---|-------|----------|-----|
| 1 | Format | One pretty-printed UTF-8 **JSON** file, `{contact-name-slug}.json`, with a top-level `schema_version`. | JSON is the easiest format for "other tools" and can still be read by a person. A ZIP or PDF adds dependencies and work for little gain. Nothing needs to import it back, but a version number lets us change the format later without surprising anyone. |
| 2 | Scope | **v1 sections:** `contact` (profile basics), `notes`, `relationships`, `reminders`, `important_dates`. Calls, tasks, loans, goals, pets, life events, mood tracking, quick facts, posts, groups, labels, contact information, addresses, documents and photos are **not in v1** (see Follow-ups). | These are the four items the requester named, plus enough identity to make the file make sense on its own. The address-book fields (contact info, addresses, labels, job, gender) are already in the vCard. Each section is its own method, so adding more later is a small, separate PR. |
| 3 | Files/photos | Not included in v1, neither the binaries nor their metadata. The avatar is not included either. | Including files would force a ZIP and bring up size and storage-driver questions (Uploadcare/local). That fits better in a follow-up if people ask for it. |
| 4 | Relationships | For each relationship: related contact `id` and `name`, the relationship type name **from this contact's point of view**, and the relationship group type name. The related contact's other data is not included. | This matches what the Relationships module shows and avoids leaking other contacts' data into one person's export. |
| 6 | Who can export | Anyone who can view the contact: vault viewers, editors and managers. This is the same rule as the vCard download. | This data is already visible to them on the page. Enforced by the existing `can:vault-viewer,vault` + `can:contact-owner,vault,contact` route middleware, and by the service permission `author_must_be_in_vault`. |
| 7 | Per-user data | Notes include `author_name` (nullable) and `created_at`/`updated_at`. Reminders include only what describes the reminder (label, date parts, type, frequency). Per-user notification channels and `last_triggered_at`/`number_times_triggered` are **excluded**. | The author and timestamps belong to the record. Notification settings belong to users, not to the person being exported. |
| 8 | Placement | A new link, "Download all data", in the left-hand action list, directly under "Download as vCard". The vCard link stays. It is shown for archived contacts too. | Downloading needs no extra rights, and the two downloads do different jobs (sync with an address book vs keeping a full copy). Archived contacts are the most likely to be exported before cleanup. |
| 9 | Delivery | A synchronous `GET` that returns the file with `Content-Disposition: attachment`. No queue or email. | Four text sections for a single contact are small. A plain `GET` link with the `download` attribute is simpler than the vCard's Inertia-flash/Blob approach, and the request changes nothing. |
| 10 | API | **No API endpoint.** | `routes/api.php` has no contact routes today. The logic lives in a service, so an API controller could reuse it later. |

## Design

### Why a service and not only a ViewHelper

`CLAUDE.md` says services are for writes. However, `App\Domains\Contact\Dav\Services\ExportVCard` is an existing read/export service, and it gets the account/vault/contact permission checks and the standard `ValidationException` / `ModelNotFoundException` test pattern for free. We follow that precedent. Unlike `ExportVCard`, the new service **must not write** anything; in particular it must not touch `contacts.vcard`. That is also why a `GET` route is safe.

We deliberately **do not embed** the vCard. Calling `ExportVCard` saves `contacts.vcard` as a side effect, and the vCard is already available separately.

### JSON shape (schema_version 1)

```json
{
  "schema_version": 1,
  "exported_at": "2026-09-29T12:00:00+00:00",
  "source": { "application": "Monica", "url": "https://…/vaults/{vault}/contacts/{contact}" },
  "contact": {
    "id": "uuid",
    "name": "Display name",
    "first_name": "…", "middle_name": null, "last_name": "…",
    "nickname": null, "maiden_name": null, "prefix": null, "suffix": null,
    "gender": "Male|null", "pronoun": "he/him|null",
    "archived": false,
    "created_at": "ISO-8601", "updated_at": "ISO-8601"
  },
  "notes": [
    { "id": 1, "title": null, "body": "…", "emotion": "Happy|null",
      "author_name": "Jane Doe|null", "created_at": "…", "updated_at": "…" }
  ],
  "relationships": [
    { "relationship_group": "Family", "relationship_type": "parent",
      "contact": { "id": "uuid", "name": "…" } }
  ],
  "reminders": [
    { "id": 1, "label": "…", "day": 3, "month": 4, "year": null,
      "type": "recurring_year", "frequency_number": 1 }
  ],
  "important_dates": [
    { "id": 1, "label": "Birthdate", "type": "Birthdate|null",
      "day": 3, "month": 4, "year": 1980 }
  ]
}
```

Rules:
- All timestamps are ISO-8601 in UTC (`->toIso8601String()`). Date parts stay as raw integers or null, because day, month or year may be unknown.
- Sections are always present, as `[]` when empty.
- Names use the model accessors (`Contact::name`, `RelationshipType::name` / `name_reverse_relationship`, `RelationshipGroupType::name`, `Emotion::name`), which already handle translation keys. The file is therefore in the exporting user's locale. We accept this as a known trade-off.
- Ordering: notes by `created_at` desc (same as the module). Reminders and important dates by month, then day (same as `ModuleRemindersViewHelper`). Relationships by group type, then type.
- Relationships use the same direction logic as `ModuleRelationshipViewHelper::data()`. A row where `contact_id === $contact->id` uses `name_reverse_relationship` and the related contact `related_contact_id`. Otherwise it uses `name` and `contact_id`. Rows where either contact is soft-deleted are skipped.

## Changes

### Backend

1. **New service** `app/Domains/Contact/ManageContact/Services/ExportContactData.php`
   - Extends `BaseService` and implements `ServiceInterface`.
   - `rules()`: `account_id`, `author_id`, `vault_id` (uuid, exists), `contact_id` (required, uuid, exists).
   - `permissions()`: `author_must_belong_to_account`, `vault_must_belong_to_account`, `author_must_be_in_vault`, `contact_must_belong_to_vault` (the same set as `ExportVCard`, without the group permission).
   - `execute(array $data): array` → `validateRules`, then return the structure above. It is built by private methods `contact()`, `notes()`, `relationships()`, `reminders()`, `importantDates()`. Eager-load `notes.author`, `notes.emotion` and `importantDates.contactImportantDateType` to avoid N+1 queries. The relationships query loads all related contacts in one `whereIn`, not with `Contact::find` in a loop.

2. **New controller** `app/Domains/Contact/ManageContact/Web/Controllers/ContactExportController.php`
   - `download(Request $request, Vault $vault, Contact $contact)` builds `$data` (`account_id`, `author_id`, `vault_id`, `contact_id`) from `Auth` and the route params, then calls the service.
   - It returns `response()->json($export, 200, ['Content-Disposition' => 'attachment; filename="'.$name.'.json"'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)`.
   - `$name` is built as in `ContactVCardController` (`Str::of($contact->name)->slug(language: App::getLocale())`), falling back to `'contact'` when the slug is empty (for example, names written only in non-Latin scripts).

3. **Route** in `routes/web.php`, inside the `{contact}` group (next to line 257):
   `Route::get('export', [ContactExportController::class, 'download'])->name('contact.export.download');`
   Add the `use` import at the top.

4. **ViewHelper** `app/Domains/Contact/ManageContact/Web/ViewHelpers/ContactShowViewHelper.php`: add
   `'download_export' => route('contact.export.download', ['vault' => $contact->vault, 'contact' => $contact])`
   next to **both** existing `download_vcard` entries (around lines 91 and 145).

### Frontend

5. `resources/js/Pages/Vault/Contact/Show.vue`: under the "Download as vCard" `<li>` (lines 272–277), add:
   ```vue
   <!-- download all data -->
   <li class="mb-2">
     <a :href="data.url.download_export" download class="cursor-pointer text-blue-500 hover:underline">
       {{ $t('Download all data') }}
     </a>
   </li>
   ```
   Use a plain `<a>`, **not** Inertia `<Link>`, so the browser handles the attachment. No script changes are needed.

6. **Translations:** add `"Download all data"` to `lang/en.json`, then run `php artisan monica:localize` to update the other locales.

### Tests

7. `tests/Unit/Domains/Contact/ManageContact/Services/ExportContactDataTest.php`, following the existing service test conventions (`DatabaseTransactions`, `createUser()`, `createVault()`, `setPermissionInVault()`):
   - `it_exports_a_contact`: seed a note with an author and emotion, a reminder, an important date with a type, and a relationship in each direction. Assert the full structure, including the relationship names from each side.
   - `it_exports_a_contact_as_a_vault_viewer`: works with `PERMISSION_VIEW`.
   - `it_skips_relationships_with_deleted_contacts`.
   - `it_returns_empty_sections` for a bare contact.
   - `it_does_not_modify_the_contact`: `vcard` and `updated_at` are unchanged.
   - `it_fails_if_wrong_parameters_are_given` → `ValidationException`.
   - `it_fails_if_user_doesnt_belong_to_account`, `it_fails_if_vault_doesnt_belong_to_account`, `it_fails_if_contact_doesnt_belong_to_vault` → `ModelNotFoundException`.
   - `it_fails_if_user_is_not_in_the_vault` → the exception that `author_must_be_in_vault` throws (check in `BaseService`).
8. `tests/Unit/Domains/Contact/ManageContact/Web/ViewHelpers/ContactShowViewHelperTest.php`: add `download_export` to the expected URL arrays.
9. New HTTP test (for example `tests/Feature/Controllers/Contact/ContactExportControllerTest.php`), since there's no controller test for the vCard to copy:
   - A signed-in vault member gets 200, `Content-Type: application/json`, and `Content-Disposition` containing `attachment` and `.json`.
   - A user not in the vault gets 403.
   - A contact from another vault gets 403/404 through `contact-owner`.

### Verification

- `vendor/bin/phpunit` for the new and updated tests, then the full suite.
- `vendor/bin/phpstan` and `vendor/bin/pint`.
- `yarn lint`, `yarn format`, `yarn build`.
- Manual check: download from an active contact and from an archived one. Open the file and check it with `jq`.
- PR title: `feat: export all contact data as JSON`.

## Out of scope / follow-ups

- More sections, in suggested order: contact information, addresses, labels, groups, job and religion (makes the file self-contained); then calls, tasks, loans, goals, pets, life events/timeline, mood tracking, quick facts, posts. Each is a new method plus a test, with `schema_version` staying at 1 because keys are only added.
- Documents, photos and avatar (would change delivery to a ZIP, probably a queued job).
- API endpoint reusing `ExportContactData`.
- Bulk or whole-vault export (explicitly not wanted now).
- Export in a fixed locale instead of the user's locale.

## Risks

- **Privacy:** notes can be sensitive, and any vault viewer can download them. This matches what viewers can already see on screen and what the vCard allows, so no new exposure.
- **Large note bodies:** still small for a single contact. If this becomes a problem, switch to `response()->streamDownload`.
- **Relationship direction bugs:** covered by the two-direction test in step 7.
