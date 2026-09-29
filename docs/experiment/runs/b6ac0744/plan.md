# Plan: Download a contact as JSON

## Goal

Add a "Download as JSON" link next to "Download as vCard" on the contact page. It downloads one contact as a readable JSON file named after the contact. The file holds the same details the vCard download holds and nothing more (no photo, no notes, relationships, reminders, etc.). It is export only, with no API endpoint and no import.

## How the vCard download works today

- **Link:** `resources/js/Pages/Vault/Contact/Show.vue:272-277` calls `download()` (`Show.vue:135-154`). That function does `router.post(props.data.url.download_vcard)`, reads `response.props.jetstream.flash.{data,filename}`, and saves it through a Blob and a temporary `<a download>`.
- **URL:** `download_vcard` is built in `ContactShowViewHelper::data()` and `::dataForTemplatePage()` (`app/Domains/Contact/ManageContact/Web/ViewHelpers/ContactShowViewHelper.php:91` and `:145`).
- **Route:** `routes/web.php:257`, `POST vaults/{vault}/contacts/{contact}/vcard`, named `contact.vcard.download`, `withoutMiddleware(HandleInertiaRequests)`. It sits inside the `can:vault-viewer,vault` group (`:199`) and the `can:contact-owner,vault,contact` group (`:250`). So any vault viewer can download, which is the permission level the requester asked for.
- **Controller:** `ContactVCardController::download()` runs `ExportVCard`, slugs the contact name, and returns `Redirect::back()->with('flash', ['data' => ..., 'filename' => "$name.vcf"])`.
- **Content:** the contact exporters decide what goes in the vCard (Monica's own data):

  | vCard property | Source | Exporter |
  |---|---|---|
  | `FN`, `N`, `NICKNAME` | `name`, `first_name`, `middle_name`, `last_name`, `nickname` | `ManageContact/Dav/ExportNames` |
  | `GENDER` | `gender->type`, or a M/F/O fallback from the gender name | `ManageContact/Dav/ExportGender` |
  | `ADR` | current addresses only (`is_past_address = false`): line_1, line_2, city, province, postal_code, country, addressType->type | `ManageContact/Dav/ExportAddress` |
  | `EMAIL`, `TEL`, `IMPP`, `X-SOCIAL-PROFILE`, `URL` | `contactInformations`, split by `contactInformationType->type`. Email/phone carry `kind` and `pref`. Types with an empty `type` are skipped | `ManageContactInformation/Dav/ExportContactInformation` |
  | `BDAY` | important date whose type `internal_type` is `birthdate`, partial dates allowed; if there are several, the last one wins | `ManageContactImportantDates/Dav/ExportImportantDates` |
  | `ORG`, `TITLE` | `company->name`, `job_position` | `ManageContact/Dav/ExportWorkInformation` |
  | `CATEGORIES` | label names | `ManageContact/Dav/ExportLabels` |
  | `UID`, `REV` | contact id, `updated_at` | `ExportVCard`, `ManageContact/Dav/ExportTimestamp` |

## Key design decision: build the JSON from the models, not from the vCard

`ExportVCard` starts from `$contact->vcard` when one is stored. For contacts synced from a remote CardDAV address book, that stored vCard can carry extra properties from the remote side, such as `PHOTO` or `NOTE`, that Monica's exporters never touch. `ExportVCard` also has a side effect: it writes the serialized vCard back to `contacts.vcard`.

So the JSON will be built directly from the `Contact` model, field by field, following the exporter table above. This:
- keeps out data the requester excluded (photo, notes) even when a remote vCard contains it;
- makes the download a pure read with no database write;
- produces a readable structure instead of vCard's arrays of components.

The cost is that the JSON and vCard field lists are maintained separately. The view helper will say so in a comment, and its test covers each field, so a future vCard exporter change has an obvious counterpart to update.

## Assumptions (where the requester had no strong view)

1. **Versioning:** include a top-level `"version": 1`. It costs nothing and lets scripts detect a future shape change. It is **not** yet a documented, stable public contract. No docs are written now, since the requester called docs nice-to-have. If the shape changes incompatibly later, bump the number.
2. **IDs:** include `id` (the contact's UUID, like vCard `UID`). No vault ID, no IDs of addresses, labels or contact-information rows, because they have no meaning outside Monica and the file isn't for import.
3. **Timestamps:** include `updated_at` only, as an ISO 8601 UTC string (`2026-09-29T10:15:00Z`), because vCard carries the same value as `REV`. No `created_at`. This is record metadata, not a contact date, so it fits "birthday is the only date".
4. **Birthday:** an object `{ "year": 1985|null, "month": 3|null, "day": 14|null }`, or `null` when there's no birthday. Nothing is invented for missing parts. A single ISO string can't express "no year" in plain ISO 8601 (vCard uses `--0314`, which scripts handle poorly), so a string field is left out. When there are several birthdate entries, use the same one the vCard uses: the last in `importantDates` order.
5. **Key style:** `snake_case` like the rest of Monica. Every documented key is always present. Missing single values are `null` and missing lists are `[]`, so scripts don't need `isset` checks.
6. **Encoding:** pretty-printed UTF-8 (`JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR`), because the file is meant for people as well as scripts.
7. **Values are taken as stored.** vCard's `escape()` trims values and turns empty strings into omissions. JSON will trim strings and turn empty ones into `null`, matching that.

## JSON shape

```json
{
  "version": 1,
  "id": "9a1b…-uuid",
  "updated_at": "2026-09-29T10:15:00Z",
  "name": "Jane Q. Doe",
  "first_name": "Jane",
  "middle_name": "Q.",
  "last_name": "Doe",
  "nickname": "JD",
  "gender": { "name": "Female", "code": "F" },
  "birthday": { "year": null, "month": 3, "day": 14 },
  "company": "Acme",
  "job_position": "Engineer",
  "labels": ["Friends", "Work"],
  "emails": [{ "value": "jane@example.com", "kind": "work", "preferred": true }],
  "phones": [{ "value": "+1 555 0100", "kind": "cell", "preferred": false }],
  "instant_messaging": [{ "service": "Matrix", "value": "@jane:example.org" }],
  "social_profiles": [{ "service": "Mastodon", "username": "jane" }],
  "urls": [{ "type": "https://", "value": "https://example.com" }],
  "addresses": [{
    "type": "home",
    "line_1": "1 Main St", "line_2": null,
    "city": "Springfield", "province": "IL",
    "postal_code": "62701", "country": "US"
  }]
}
```

Field rules, matching the exporters:
- `name` = `$contact->name` (vCard `FN`).
- `gender`: `null` if there is no gender. Otherwise `name` is `gender->name` and `code` is computed exactly as `ExportGender` does (`gender->type`, else M/F/O from the translated name).
- `company` = `company?->name`.
- `emails` / `phones` / `instant_messaging` / `social_profiles` / `urls`: split `contactInformations` using the same case-insensitive `Str::is` checks on `contactInformationType->type` as `ExportContactInformation`. `kind` keeps its stored lowercase value (vCard upper-cases it). `service` is `contactInformationType->name`. A `urls` value is `type . data`, as in vCard. Rows whose type has an empty `type` are skipped, as in vCard.
- `addresses`: only rows with pivot `is_past_address = false`. `type` = `addressType?->type`.
- The avatar/photo is never included.

## Changes

### 1. JSON builder: `app/Domains/Contact/ManageContact/Web/ViewHelpers/ContactJsonViewHelper.php` (new)

A static `data(Contact $contact): array` that returns the structure above. Put it with the ViewHelpers because, per CLAUDE.md, these are the static "model → array" classes, and this is a read with no mutation. A Service would be wrong: services are for writes and would pull in permission plumbing that the route gates already handle. Eager-load `gender`, `company`, `labels`, `contactInformations.contactInformationType`, `importantDates.contactImportantDateType`, and current `addresses.addressType` to avoid N+1 queries.

Add a short docblock comment saying the fields mirror the contact vCard exporters in `app/Domains/Contact/*/Dav/Export*.php`.

### 2. Controller: `app/Domains/Contact/ManageContact/Web/Controllers/ContactJsonController.php` (new)

Mirrors `ContactVCardController`:

```php
public function download(Request $request, Vault $vault, Contact $contact)
{
    $name = Str::of($contact->name)->slug(language: App::getLocale());

    return Redirect::back()->with('flash', [
        'data' => json_encode(ContactJsonViewHelper::data($contact), JSON_PRETTY_PRINT | ...),
        'filename' => "$name.json",
    ]);
}
```

It adds no permission checks of its own. The route gates (`vault-viewer`, `contact-owner`) are the same ones that protect the vCard download, which is what the requester asked for.

### 3. Route: `routes/web.php`

Next to line 257:

```php
Route::post('json', [ContactJsonController::class, 'download'])->name('contact.json.download')->withoutMiddleware([HandleInertiaRequests::class]);
```

Add the matching `use` line next to `ContactVCardController` (line 14). POST matches the vCard route and the flash-based download that the frontend expects.

### 4. View helper URL: `ContactShowViewHelper.php`

Add `'download_json' => route('contact.json.download', [...])` next to `download_vcard` in **both** `data()` (`:91`) and `dataForTemplatePage()` (`:145`).

### 5. Frontend: `resources/js/Pages/Vault/Contact/Show.vue`

- Change `download()` to `download(url)` and pass the URL from the template, so both links share the Blob/anchor logic. Update the existing vCard call to `download(data.url.download_vcard)`.
- After the vCard `<li>` (`:272-277`), add a matching `<li class="mb-2">` with the same `Link`, classes and `@click.prevent="download(data.url.download_json)"`, labelled `{{ $t('Download as JSON') }}` with the comment `<!-- download as json -->`.

### 6. Translations

Add `"Download as JSON": "Download as JSON"` to `lang/en.json`. Other locales fall back to the key through laravel-vue-i18n. `php artisan monica:localize` would fill the other 28 locale files, but it sends strings to Google Translate. Whether to run it before merge is the maintainers' call, and it isn't part of this change.

### 7. Tests

- `tests/Unit/Domains/Contact/ManageContact/Web/ViewHelpers/ContactJsonViewHelperTest.php` (new), using `DatabaseTransactions` like its neighbours:
  - a fully populated contact produces the exact expected array (`assertEquals` on the whole structure);
  - a bare contact gives `null` for single values and `[]` for lists, and every key is present;
  - partial birthdays: month+day without a year gives `year: null`; year-only works; no birthday gives `null`; non-birthday important dates are ignored;
  - past addresses are excluded;
  - gender falls back to the M/F/O code when `type` is empty;
  - contact-information routing: email, phone, IMPP, social profile and URL each land in the right list, and empty-`type` rows are skipped;
  - no photo/avatar key appears, even when `$contact->vcard` contains a `PHOTO` (this guards the "build from models" decision).
- `ContactShowViewHelperTest.php:86-93`: add the `download_json` URL to the expected `url` array (and to the template-page test if it asserts URLs).
- `tests/Feature/Controllers/Contact/ContactJsonControllerTest.php` (new): a feature test in the style of `UserTokenControllerTest`:
  - a vault viewer gets a redirect with a session flash `filename` of `{slug}.json` and `data` that decodes to the expected array;
  - a user without access to the vault gets 403;
  - a contact from another vault gets 403;
  - `contacts.vcard` is unchanged afterwards (no side effect).

  There is no vCard controller test to copy, so this is also the first test of that download flow.

### 8. Checks before PR

`vendor/bin/pint`, `vendor/bin/phpstan`, `yarn lint`, `yarn format`, and `vendor/bin/phpunit` on the new and changed tests. PR title: `feat: download a contact as JSON`.

## Out of scope

Photo/avatar, notes, relationships, reminders, other important dates, multi-contact or vault export, an API endpoint, JSON import, schema docs, and non-English translations (see step 6).

## Known limitations carried over from vCard

- If a contact's name slugs to an empty string, the file is named `.json`, just as the vCard is named `.vcf`. Both would be fixed together if needed; this change doesn't touch it.
- The whole file is passed through the session flash, as with vCard. That's fine for one contact's details.
