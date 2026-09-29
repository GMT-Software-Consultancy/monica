# Plan: full contact export as JSON

## Goal

Add a "Download all data (JSON)" link to the contact page, next to "Download as vCard". It downloads a single JSON file holding everything recorded about that contact. It includes the address-book details the vCard carries, plus notes, relationships, reminders, important dates and every other contact module. The vCard download is not changed.

## Decisions from the requester

| # | Decision |
|---|---|
| Format | One JSON file. A human-readable version may come later. Used for personal backups and for other tools or scripts. |
| Scope | Everything recorded about the contact, including the vCard's address-book details. |
| Photos / documents | Listed with name and date only. The files themselves are not included. |
| Other contacts | Show who they are and how they connect to this contact. Their own details are not included. |
| Import | Not needed. Export only. |
| Placement | On the contact page, next to the vCard download. The vCard download stays as it is. |
| Access | Anyone who can view the contact. |
| Bulk | One contact only. |

## Assumptions I made (requester had no strong view)

1. **No audit logging.** The export only puts into a file what the same user can already see on screen. The vCard download isn't logged either. The only per-contact log is the contact feed (`ContactFeedItem`), which other vault members can see, so logging exports there would show up for everyone. Adding a new audit table would be a separate feature. I'm leaving logging out and noting it here as a possible follow-up.
2. **Modules turned off in the template are still exported.** The template only controls what the page shows; the data is still stored against the contact. The requester asked for a full copy of everything recorded. Data behind a hidden module (for example, loans recorded before a template change) is still "recorded about this person", so it goes in.
3. **Language and dates: stable machine keys plus readable labels.**
   - JSON keys are fixed English `snake_case` and never translated.
   - Enumerations keep their stored constant (for example `"type": "recurring_year"`, `"type": "debt"`). Where a user-facing name exists, a `label` sits next to it. The label uses the downloading user's locale, the same text they see on screen (for example relationship type names, pet categories, gender).
   - Full timestamps are ISO 8601 in UTC (`2026-09-29T14:03:00Z`).
   - Partial dates (important dates and reminders, where year/month/day may be missing) are exported as `{ "year": 1985|null, "month": 4|null, "day": 12|null }`. They are not squeezed into a fake full date.
   - The contact's name and related contacts' names follow the user's name-order preference, as they do in the UI. The raw name parts are also given separately.
4. **Related contacts that are soft-deleted are left out** of relationships. This matches `ModuleRelationshipViewHelper`.
5. **Journal posts that mention the contact are listed as references only** (title, date, journal name). A post is its own record and often covers several people, much like Q4's "it's this contact's copy, not theirs". The posts module on the contact page only lists them too.
6. **The contact feed (activity history) is left out.** It is a log of edits, not data about the person.
7. **A `format_version` field is included** (`1`), even though import is out of scope. Scripts can then detect future schema changes.

## Design

### Why a GET download rather than the vCard's flash approach

`ContactVCardController::download` returns the file contents through `Redirect::back()->with('flash', ...)`, and `Show.vue` turns that into a Blob. That puts the whole payload into the session. `SESSION_DRIVER` is `database` by default, but self-hosters can use `cookie`, where a full contact export would go over the ~4 KB limit. A full export can also be large (years of notes).

The new endpoint is a plain `GET` that returns `application/json` with `Content-Disposition: attachment`. The frontend is a normal `<a href download>`. Nothing in the frontend needs to be decoded, the browser handles the download, and the export has no side effects, so GET is correct.

### Backend

**1. Service `app/Domains/Contact/ManageContact/Services/ExportContactData.php`**

- Extends `BaseService` and implements `ServiceInterface`, like `ExportVCard`. That way it goes through the same validation and permission checks.
- `rules()`: `account_id`, `author_id`, `vault_id`, `contact_id`, the same as `ExportVCard` minus `group_id`.
- `permissions()`: `author_must_belong_to_account`, `vault_must_belong_to_account`, `author_must_be_in_vault` (viewer is enough, per decision 7), `contact_must_belong_to_vault`.
- `execute(array $data): array` calls `validateRules`, eager-loads the relations listed below, and returns the export array.
- One private method per section, so the class stays readable and each section can be tested on its own.
- A private `contactReference(Contact $c): array` helper returns `{ id, name, url }`. `url` is `route('contact.show')` when the contact is `listed`, otherwise `null`, matching `ModuleRelationshipViewHelper::getContact`. It is used for every other contact that appears in the export (relationships, loans, life events and timeline participants). That covers Q4: identity and how they're connected, nothing more.
- Timestamps are formatted by one private `timestamp(?Carbon)` helper that returns `->toIso8601ZuluString()` or null.

**2. vCard section.** Address-book details appear in two forms:
- **Structured fields.** Names, gender, pronoun, contact information, addresses, company and job position, labels and important dates are covered by the sections below.
- **`address_book.vcard`.** The serialized string from `app(ExportVCard::class)->execute(...)`, which is exactly what the vCard download produces. This keeps any properties that came in through a CardDAV import but aren't modelled in Monica (they're kept in `contacts.vcard`), so the JSON is a true superset of the vCard.
  - *Side effect:* `ExportVCard::execute` writes the regenerated vCard back to `contacts.vcard` without touching timestamps. The existing vCard download already does this, so it adds no new behaviour. It is wrapped in `withoutTimestamps`, so `updated_at` isn't bumped. During implementation, confirm that no model observer on `Contact` bumps DAV sync tokens on this save. If one does, build the vCard with the exporters directly instead of calling `execute`.

**3. Controller `app/Domains/Contact/ManageContact/Web/Controllers/ContactExportController.php`**

```php
public function download(Request $request, Vault $vault, Contact $contact)
{
    $data = (new ExportContactData)->execute([
        'account_id' => Auth::user()->account_id,
        'author_id' => Auth::id(),
        'vault_id' => $vault->id,
        'contact_id' => $contact->id,
    ]);
    $name = Str::of($contact->name)->slug(language: App::getLocale());

    return response()->json($data, 200, [
        'Content-Disposition' => "attachment; filename=\"$name.json\"",
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
```

The filename slug follows `ContactVCardController`. If the slug is empty (a contact with no Latin-script name), fall back to `contact-{id}.json`.

**4. Route in `routes/web.php`**, inside the `can:contact-owner,vault,contact` group, right after the vCard route (line 257):

```php
Route::get('export', [ContactExportController::class, 'download'])->name('contact.export.download');
```

The outer vault group already enforces vault membership. Add the `use` import at the top.

**5. `ContactShowViewHelper`**: add `'download_json' => route('contact.export.download', [...])` to the `url` array in both `data()` and `dataForTemplatePage()` (lines 91 and 145).

### Frontend

**6. `resources/js/Pages/Vault/Contact/Show.vue`**: add a list item directly after the "Download as vCard" `<li>` (line 272–277):

```vue
<!-- download all data as json -->
<li class="mb-2">
  <a :href="data.url.download_json" download class="cursor-pointer text-blue-500 hover:underline">
    {{ $t('Download all data (JSON)') }}
  </a>
</li>
```

This uses a plain `<a>`, not Inertia's `Link`, so the browser downloads the file directly.

**7. Translations**: add the string `Download all data (JSON)` and run `php artisan monica:localize` to regenerate `lang/*.json`.

## JSON schema (format_version 1)

```jsonc
{
  "format_version": 1,
  "exported_at": "2026-09-29T14:03:00Z",
  "source": { "application": "Monica", "version": "<app version, same source that app/Console/Commands/GetVersion.php reads>", "url": "<contact.show url>" },
  "vault": { "id": "...", "name": "..." },

  "contact": {
    "id": "...", "name": "...",
    "prefix": null, "first_name": "...", "middle_name": null, "last_name": "...",
    "suffix": null, "nickname": null, "maiden_name": null,
    "gender": { "type": "...", "label": "..." } | null,
    "pronoun": { "label": "..." } | null,
    "religion": { "label": "..." } | null,
    "company": { "name": "...", "type": "..." } | null,
    "job_position": null,
    "listed": true,
    "age": 41 | null,
    "created_at": "...", "updated_at": "...", "last_updated_at": "..."
  },

  "address_book": { "vcard": "BEGIN:VCARD\r\n..." },

  "contact_information": [ { "type": "email", "label": "Email address", "protocol": "mailto:", "value": "...", "kind": null } ],
  "addresses": [ { "type": { "label": "Home" } | null, "line_1": "...", "line_2": null, "city": "...",
                   "province": null, "postal_code": "...", "country": "...",
                   "latitude": null, "longitude": null, "is_past_address": false } ],
  "labels": [ { "name": "...", "description": null } ],
  "groups": [ { "name": "...", "type": "...", "role": "..." | null } ],

  "important_dates": [ { "label": "Birthdate", "type": "birthdate" | null,
                         "date": { "year": 1985, "month": 4, "day": 12 } } ],
  "reminders": [ { "label": "...", "type": "recurring_year", "frequency_number": 1,
                   "date": { "year": null, "month": 4, "day": 12 },
                   "last_triggered_at": null, "number_times_triggered": 0 } ],

  "notes": [ { "title": null, "body": "...", "emotion": { "type": "...", "label": "..." } | null,
               "author": "Name" | null, "created_at": "...", "updated_at": "..." } ],

  "relationships": [ { "group": "Family", "relationship": "father",
                       "contact": { "id": "...", "name": "...", "url": "..." | null } } ],

  "tasks": [ { "label": "...", "description": null, "completed": false, "completed_at": null,
               "due_at": null, "author": "Name" | null, "created_at": "..." } ],
  "calls": [ { "called_at": "...", "duration": 15 | null, "type": "audio", "answered": true,
               "who_initiated": "contact", "reason": "..." | null,
               "emotion": { ... } | null, "description": null, "author": "Name" | null } ],
  "loans": [ { "type": "debt", "name": "...", "description": null,
               "amount_lent": 5000 | null, "currency": "USD" | null,
               "loaned_at": "..." | null, "settled": false, "settled_at": null,
               "loaners": [ <contactReference> ], "loanees": [ <contactReference> ] } ],
  "goals": [ { "name": "...", "active": true, "streaks": [ "2026-09-01T00:00:00Z", ... ] } ],
  "pets": [ { "name": "...", "category": "Dog" | null } ],
  "quick_facts": [ { "template": "Hobbies", "content": "..." } ],

  "timeline_events": [ { "label": null, "started_at": "...",
                         "participants": [ <contactReference> ],
                         "life_events": [ { "type": "...", "summary": null, "description": null,
                                            "happened_at": "...", "emotion": { ... } | null,
                                            "costs": null, "currency": null, "paid_by": <contactReference> | null,
                                            "duration_in_minutes": null, "distance": null, "distance_unit": null,
                                            "from_place": null, "to_place": null, "place": null,
                                            "participants": [ <contactReference> ] } ] } ],
  "mood_tracking_events": [ { "rated_at": "...", "parameter": "...", "note": null, "number_of_hours_slept": null } ],
  "life_metrics": [ { "label": "..." } ],
  "journal_posts": [ { "title": "...", "written_at": "...", "journal": "..." } ],

  "files": {
    "avatar": { "name": "...", "mime_type": "...", "size": 12345, "created_at": "..." } | null,
    "photos":    [ { "name": "...", "mime_type": "...", "size": 12345, "created_at": "..." } ],
    "documents": [ { "name": "...", "mime_type": "...", "size": 12345, "created_at": "..." } ]
  }
}
```

Section and data-source notes:

- **Relationships.** There is no `Relationship` model. Query the `relationships` table in both directions, as `ModuleRelationshipViewHelper::data` does. When this contact is `contact_id`, use `name_reverse_relationship`; otherwise use `name`. Join `relationship_types` and `relationship_group_types` for the labels. Skip rows where either contact is soft-deleted.
- **Loans.** Merge `loansAsLoaner` and `loansAsLoanee`, deduplicate by loan id, and load `loaners` and `loanees` for the counterparty references.
- **Groups.** `contact_group.group_type_role_id` gives the role. Load it through the pivot or a small join.
- **Files.** `$contact->files` is split by `File::TYPE_PHOTO` / `TYPE_DOCUMENT`, and the avatar is `$contact->file`. Only metadata is exported. `original_url`/`cdn_url` are left out on purpose: they are Uploadcare links that can expire, and including them would half-contradict "don't include the files".
- **Important dates.** `type` comes from `contactImportantDateType.internal_type` (for example `birthdate`), and `label` from the type label or the date's own `label`.
- **Currency** is exported as its ISO `code`.
- **Emotion, gender, pet category, call reason, relationship type and similar values** use each model's existing translated-name accessor, the same one the UI uses.
- **Ordering.** Lists are sorted chronologically, oldest first, where a date exists, and by name otherwise. This keeps the file stable between exports so people can diff it.
- **Performance.** Eager-load with `$contact->load([...])` for every relation above (with nested `timelineEvents.lifeEvents.participants`, `loansAsLoaner.loaners`, and so on) to avoid N+1 queries. One contact's data is small enough to build in memory, so no streaming or queued job is needed.

## Tests

The layout follows CLAUDE.md (tests mirror `app/Domains`, `DatabaseTransactions`).

**`tests/Unit/Domains/Contact/ManageContact/Services/ExportContactDataTest.php`**
- `it_exports_a_contact`: builds a contact with at least one record in every section (factories exist for each model) and asserts the top-level keys, `format_version`, and a representative value per section.
- `it_exports_relationships_in_both_directions`: checks that the reverse name is used when the contact is `contact_id`, and the forward name when it is `related_contact_id`.
- `it_only_includes_a_reference_for_related_contacts`: asserts the related contact entry has exactly `id`, `name` and `url`, with none of their notes, dates or contact information.
- `it_skips_soft_deleted_related_contacts`.
- `it_exports_data_for_modules_not_in_the_template`: removes the loans module from the template and asserts the loans still appear (assumption 2).
- `it_exports_partial_dates_with_nulls`: an important date with no year.
- `it_lists_files_without_urls`: photos and documents have name, date, MIME type and size, and no URL keys.
- `it_includes_the_vcard`: `address_book.vcard` starts with `BEGIN:VCARD`.
- `it_allows_a_vault_viewer_to_export`: `setPermissionInVault(..., Vault::PERMISSION_VIEW)` succeeds.
- `it_fails_if_wrong_parameters_are_given` → `ValidationException`.
- `it_fails_if_user_doesnt_belong_to_account`, `it_fails_if_contact_doesnt_belong_to_vault` → `ModelNotFoundException`.
- `it_fails_if_user_is_not_in_the_vault` → `NotEnoughPermissionException`.

**`tests/Unit/Domains/Contact/ManageContact/Web/ViewHelpers/ContactShowViewHelperTest.php`**: add the `download_json` URL to the expected `url` array next to `download_vcard` (line 92).

**Controller / route feature test (new file `tests/Feature/Controllers/Contact/ContactExportControllerTest.php`)**
- A logged-in vault viewer gets `GET .../contacts/{id}/export` with status 200, `Content-Type: application/json`, a `Content-Disposition` attachment with a `.json` filename, and a body that parses as JSON with `format_version` = 1.
- A user from another account gets 403 or 404.
- A contact in a different vault from the one in the URL gets a failing `contact-owner` gate.

**Verification before PR**
- `vendor/bin/phpunit tests/Unit/Domains/Contact/ManageContact tests/Feature/Controllers/Contact`
- `vendor/bin/phpstan` (CI gate)
- `vendor/bin/pint`
- `yarn lint`
- Manual: download from a contact with data in every module, open the file, check it parses, and confirm the vCard download still works unchanged.
- PR title: `feat: export a contact's full data as JSON`

## Files touched

| File | Change |
|---|---|
| `app/Domains/Contact/ManageContact/Services/ExportContactData.php` | new |
| `app/Domains/Contact/ManageContact/Web/Controllers/ContactExportController.php` | new |
| `routes/web.php` | add `use` and one route after line 257 |
| `app/Domains/Contact/ManageContact/Web/ViewHelpers/ContactShowViewHelper.php` | add `download_json` URL (×2) |
| `resources/js/Pages/Vault/Contact/Show.vue` | add link after the vCard `<li>` |
| `lang/*.json` | new string through `monica:localize` |
| `tests/Unit/Domains/Contact/ManageContact/Services/ExportContactDataTest.php` | new |
| `tests/Unit/Domains/Contact/ManageContact/Web/ViewHelpers/ContactShowViewHelperTest.php` | expect new URL |
| `tests/Feature/Controllers/Contact/ContactExportControllerTest.php` | new |

## Out of scope / follow-ups

- A human-readable export (HTML/PDF/Markdown). The service's array output can feed it later.
- Including the actual photo and document files (would need a ZIP).
- Import of this format.
- Bulk and vault-wide export.
- Audit logging of exports (assumption 1).
- Moving the vCard download off the session-flash mechanism. That's a separate cleanup and was explicitly left as it is.
