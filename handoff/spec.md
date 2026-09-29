# Download a contact as JSON

Last updated: 29 September 2026
Status: Ready
Tickets: none yet. There is no ticket tracker for this project, so tickets go to `output/tickets.md`.

---

## Problem statement

Users who want a contact's details in scripts or other tools must parse a vCard file, which is hard to work with.

## Why now

The product strategy names "provable trust" as a capability Monica needs. This means users can see and take their own data. A JSON download is a small, visible step toward that promise.

The cost is low. The feature reuses the data the vCard export already collects, and it adds one link to an existing page. That matters because the project has two core maintainers. The strategy also asks us to avoid features that are expensive to maintain.

This "why now" is our judgement, not a measured trigger. The PM had no strong view (see Assumptions).

## Users affected

There is no `personas.md` file for this product, so this spec defines its own persona name.

- **Data-owning power user** (primary): a person who keeps their relationship data in Monica and wants to reuse it elsewhere. Some are developers who write scripts. This includes both self-hosters and hosted-plan users. Everyone gets the feature because it is not tied to a paid plan.
- **Vault viewer**: a user whose vault permission is "viewer" (read-only access to a vault). They can download a contact's vCard today, so they can also download the JSON file.
- **Everyday Monica user** (non-user of this feature): a user who never exports data. The only change they see is one extra link on the contact page.

## Background

Today a user can open a contact page and click **Download as vCard**. Monica then downloads a `.vcf` file. vCard (RFC 6350) is a standard text format for contact cards. Address books such as Apple Contacts and Google Contacts read it well.

Scripts and general tools handle vCard poorly. A user who wants a contact's email address in a script must parse vCard lines, escaping rules and parameters. JSON (JavaScript Object Notation) is a plain data format that almost every language and tool can read in one line of code.

---

## Scope

### In scope

- Add a **Download as JSON** link on the contact page, directly below the **Download as vCard** link.
- The link downloads one `.json` file for the contact the user is viewing.
- The file contains the same contact details as the vCard download, and nothing more (see "What the file contains" below).
- Anyone who can download the contact's vCard can download the JSON file.

### What the file contains

The file is one JSON object. It holds only the details the vCard export includes today. Build each field from Monica's own data, as listed here:

| JSON key | Type | Source in Monica | Matching vCard property |
|---|---|---|---|
| `schema_version` | number | Always `1` | none (file metadata) |
| `id` | string | Contact UUID, the contact's unique ID (`contacts.id`) | `UID` |
| `full_name` | string | `Contact::name` (the display name) | `FN` |
| `first_name` | string or null | `contacts.first_name` | `N` |
| `middle_name` | string or null | `contacts.middle_name` | `N` |
| `last_name` | string or null | `contacts.last_name` | `N` |
| `nickname` | string or null | `contacts.nickname` | `NICKNAME` |
| `gender` | string or null | Name of the contact's gender (e.g. "Woman") | `GENDER` |
| `birthday` | object or null | The birthdate important date: `{ "year", "month", "day" }`. Each part is a number or null. | `BDAY` |
| `company` | string or null | Name of the contact's company | `ORG` |
| `job_position` | string or null | `contacts.job_position` | `TITLE` |
| `labels` | array of strings | Names of the contact's labels | `CATEGORIES` |
| `contact_information` | array of objects | Each contact information entry the vCard includes: `{ "type", "name", "value", "kind", "preferred" }` | `EMAIL`, `TEL`, `IMPP`, `X-SOCIAL-PROFILE`, `URL` |
| `addresses` | array of objects | Current addresses only (not past addresses): `{ "type", "line_1", "line_2", "city", "province", "postal_code", "country" }` | `ADR` |
| `updated_at` | string | `contacts.updated_at` in ISO 8601 format, UTC (e.g. `2026-09-29T10:15:00Z`) | `REV` |

Rules for the content:

- **Every key is always present.** Use `null` for a missing single value. Use `[]` for an empty list. Never leave a key out.
- **Birthday without a year:** set `year` to `null` and keep the known `month` and `day`. Never invent a year.
- **Contact information:** include the same entries the vCard export includes. The vCard export skips entries whose contact information type has an empty `type`, so the JSON skips them too.
- **Readable values, not internal IDs:** use names (e.g. the gender name, the label name). Do not use lookup table IDs. The contact UUID is the only ID in the file.
- **Characters:** encode the file as UTF-8. Write non-Latin characters as they are, not as `\u` escapes. Indent the JSON so a person can read it.

Example of a file for a contact with some details:

```json
{
    "schema_version": 1,
    "id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "full_name": "Ada Lovelace",
    "first_name": "Ada",
    "middle_name": null,
    "last_name": "Lovelace",
    "nickname": null,
    "gender": "Woman",
    "birthday": { "year": null, "month": 12, "day": 10 },
    "company": "Analytical Engines Ltd",
    "job_position": "Mathematician",
    "labels": ["Friends"],
    "contact_information": [
        { "type": "email", "name": "Email address", "value": "ada@example.com", "kind": "work", "preferred": true }
    ],
    "addresses": [
        { "type": "home", "line_1": "12 St James's Square", "line_2": null, "city": "London", "province": null, "postal_code": "SW1Y 4JH", "country": "United Kingdom" }
    ],
    "updated_at": "2026-09-29T10:15:00Z"
}
```

### Out of scope

- **Several contacts at once.** We are not building a download for a whole vault, a group, or a selection of contacts.
- **Import.** We are not building a way to create or update contacts from a JSON file.
- **An API endpoint.** We are not adding a route under `/api`. The web download is enough.
- **Any Monica data the vCard does not include.** This covers notes, relationships, reminders, tasks, calls, pets, loans, goals, life events, mood tracking, documents and files. It also covers name prefix, suffix, maiden name and pronouns.
- **Photo and avatar.** The file contains no image data and no image URLs.
- **Other formats.** We are not adding CSV or any other format.
- **Changes to the vCard download.** The vCard link, file and behaviour stay exactly as they are.
- **Activity feed entry.** Downloading the JSON file does not add an entry to the contact's activity feed. The vCard download does not add one either.
- **Usage tracking.** We are not adding metrics or tracking for this feature.
- **Published documentation of the JSON shape.** This is nice to have later. This spec does not include it.

### Phasing

- Phase 1 (this spec): download one contact as JSON, with the vCard details only.
- Possible later work, not planned: more Monica data in the file (for example notes and relationships), documentation of the JSON shape, and an API endpoint.

### Vertical slice check

If this feature shipped alone, a user could download any contact they can view as a JSON file and read it directly in a script or tool. They cannot do this today. This is a user-facing slice, not an enabler story.

---

## Figma

No designs needed. The new link copies the existing **Download as vCard** link exactly: the same list, the same styles, and the same position in the contact page's left column, placed directly below it.

---

## Acceptance criteria

**AC-1**
GIVEN a user who can view a contact named "Ada Lovelace"
WHEN the user opens the contact page
THEN the page shows a **Download as JSON** link directly below the **Download as vCard** link
AND clicking the link downloads a file named `ada-lovelace.json`, using the same file-name rule as the vCard file (`ada-lovelace.vcf`).

**AC-2**
GIVEN a contact with a first name, last name, nickname, gender, company, job position, one label, one email address, one phone number, one current address, one past address and a full birthday
WHEN the user downloads the contact as JSON
THEN the file is valid JSON with exactly the keys listed in "What the file contains"
AND each value matches the contact's data
AND `addresses` contains only the current address
AND the file contains no notes, photo, avatar or other data outside that table.

**AC-3**
GIVEN a contact whose birthday has a month and day but no year
WHEN the user downloads the contact as JSON
THEN `birthday` is `{ "year": null, "month": <month>, "day": <day> }` with the stored month and day.

**AC-4 (unhappy path: empty fields)**
GIVEN a contact that has only a first name and no other details
WHEN the user downloads the contact as JSON
THEN the download succeeds with valid JSON
AND every key from "What the file contains" is present
AND each missing single value is `null` and each missing list is `[]`.

**AC-5 (unhappy path: no access)**
GIVEN a user who is not a member of the contact's vault, or a contact ID that belongs to a different vault
WHEN the user sends the request for the JSON download
THEN Monica returns no file and denies the request in the same way it denies the vCard download for that case.

**AC-6**
GIVEN a user whose permission in the vault is "viewer"
WHEN the user downloads a contact as JSON
THEN the download succeeds
AND the contact's `updated_at` value, its stored `vcard` value and its activity feed are unchanged.

---

## Assumptions

- **Persona.** The main user is the "Data-owning power user" this spec defines. The product has no `personas.md` to confirm this.
- **Why now.** A small portability feature supports the strategy's "provable trust" capability and is cheap enough to justify. No specific metric or event triggered it.
- **vCard fields are enough (riskiest).** Script users will find the vCard-equivalent fields useful. If most users actually want notes or relationships, they may see this first version as too thin.
- **Permission.** Using the vCard download's permissions is right. Those are the `vault-viewer` gate (any vault member) plus the `contact-owner` gate (the contact belongs to that vault). Nobody needs a higher role for a JSON file with the same content.
- **Format stability.** A `schema_version` field is enough to signal changes to scripts. No published documentation is needed yet.
- **Archived contacts.** Archived (unlisted) contacts can be downloaded as JSON, because they can be downloaded as a vCard today.

---

## Technical notes

- **Follow the vCard download pattern.** `ContactVCardController::download` (`app/Domains/Contact/ManageContact/Web/Controllers/ContactVCardController.php`) handles the vCard download. It runs on `POST /vaults/{vault}/contacts/{contact}/vcard` inside the `can:vault-viewer` and `can:contact-owner` route groups (`routes/web.php`). It returns the file content and file name as flash data. The `download()` function in `resources/js/Pages/Vault/Contact/Show.vue` turns that data into a browser download. Add a JSON route in the same route group, and use the same mechanism.
- **Put the logic in a service.** Build the JSON in a new service class that extends `BaseService`. Use the same permission checks as `ExportVCard`: `author_must_belong_to_account`, `vault_must_belong_to_account`, `author_must_be_in_vault` and `contact_must_belong_to_vault`.
- **Do not reuse `ExportVCard` directly.** It has two behaviours the JSON export must not copy:
  - It saves the generated vCard back to the contact's `vcard` column. The JSON export must not write anything.
  - For contacts synced over CardDAV (a protocol that syncs contacts with external address books), it starts from the stored raw vCard. That raw vCard can hold extra properties, such as a photo. The JSON export must read only Monica's own fields.
- **Match the vCard field rules.** Use the per-field vCard exporters as the reference for which data to include:
  - `ExportNames`, `ExportGender`, `ExportLabels`, `ExportWorkInformation`, `ExportAddress` and `ExportTimestamp` in `app/Domains/Contact/ManageContact/Dav/`
  - `ExportContactInformation` in `app/Domains/Contact/ManageContactInformation/Dav/`
  - `ExportImportantDates` in `app/Domains/Contact/ManageContactImportantDates/Dav/`
- **Birthday source.** Take the birthday from the contact's important date whose type has `internal_type` equal to `ContactImportantDate::TYPE_BIRTHDATE`. Use the stored `year`, `month` and `day` directly, not the vCard date string.
- **More than one birthday.** If a contact has more than one birthdate, the vCard export keeps the last one it processes. Use the same one.
- **File name.** Build the file name with `Str::of($contact->name)->slug(language: App::getLocale())` plus `.json`, as the vCard controller does.
- **Translations.** Add the link text "Download as JSON" through the `$t()` translation helper, like the other links on the page. Add the new string to the language files.
- **Existing flows.** The vCard download and the rest of the contact page stay unchanged.
- **In-flight work.** There is no `docs/iterations/current.md` file, so we do not know of any conflicting work. Check for open changes to `Show.vue` or `ContactVCardController` before starting.

---

## Open questions

- 🟡 **Empty file names.** Some contact names produce an empty slug (the URL-safe version of a name). Should the file then be called `contact.json`? The vCard download has the same gap and produces `.vcf`. Working assumption: fall back to `contact.json` for JSON only, and leave the vCard behaviour as it is. Owner: developer, confirm in code review.
- 🟡 **Documenting the JSON shape.** Should the JSON shape appear in the user help documentation later? Working assumption: not in this spec. Owner: PM.
- 🟢 **Contacts synced over CardDAV.** Raw properties from the stored vCard (for example a photo from an external address book) do not appear in the JSON. This spec already decides that. It is listed here so reviewers see it.

---

## Before you build

**Decisions made:**

- The file contains only the details the vCard download includes. Notes, relationships, photos and other Monica data are not included.
- A birthday without a year keeps `year` as `null`. Monica never invents a year.
- Every key is always present, with `null` or `[]` for empty values.
- The file uses readable names, not internal lookup IDs. The contact UUID is the only ID.
- The file includes `schema_version: 1`.
- Permissions match the vCard download, so vault viewers can download.
- Downloading changes nothing: no activity feed entry, no update to the contact's `vcard` or `updated_at` values.
- One contact at a time, from the web contact page only. No API, no import, no other formats, and no changes to the vCard download.

**Riskiest assumption:**
Script users will find the vCard-equivalent fields useful without notes, relationships or other Monica data.

**Next step:**
Ready for ticket generation. Nothing blocks it.
