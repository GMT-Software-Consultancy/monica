# Full contact export

Last updated: 29 September 2026
Status: Ticketed
Tickets: 1–7 in `output/tickets.md` (no tracker IDs, because `ticket_tool` is `none`)

---

## Problem statement

People who keep their relationship data in Monica cannot download everything they have recorded about one contact, because the vCard download holds only address-book details.

## Why now

Data ownership is the core of Monica's promise: "your data is yours." The product strategy (`docs/product/strategy.md`) names "provable trust" as a required capability. It asks for data handling that users can see and check, not only an engineering fact. Today a user can see their data in Monica but cannot take a full copy of it out. This feature turns the promise into something a user can do in one click. The PM gave no specific trigger, such as a count of requests. See the Assumptions section.

## Users affected

`docs/product/personas.md` does not exist, so these groups come from the strategy and the PM's answers:

- **People who keep their relationship data in Monica, on the hosted plan or self-hosted.** These are the primary users. Some want a personal backup of one contact. Others want to reuse the data in other tools or in their own scripts.
- **Vault members with viewer permission.** A vault is a shared workspace that holds contacts. Each vault member has one of three permission levels: manager, editor or viewer. Viewers can see every detail of a contact, so they can also export it.
- **Other contacts linked to the exported contact.** They do not use the feature, but the export mentions them. Examples are a sister in a relationship or a friend in a shared life event. The export shows only who they are and how they are connected, never their own details.

## Background

Today, the contact page has a vCard download. A vCard is the standard address-book file format. It holds names, phone numbers, email addresses and postal addresses. It does not hold notes, relationships, reminders, important dates or any other Monica data. The only other way to get this data out is to copy it by hand from the contact page. That is slow, and the result is not a file that other tools or scripts can read.

---

## Scope

### In scope

- An "Export" link on the contact page, next to the existing vCard download link, with the same look.
- One download of one file per click. The file uses JSON (JavaScript Object Notation), a text format that most programming languages can read.
- A full copy of the contact: every record Monica stores about this contact. See "What the export file contains" below.
- Photos and documents listed by file name and upload date only. The file does not contain the files themselves.
- Other contacts appear only as their ID, their display name and how they are connected to this contact.
- Every vault member who can view the contact can export it: managers, editors and viewers.

### What the export file contains

The export file has these top-level parts:

- `format_version`: the number `1`. This lets a future version change the file's shape without breaking scripts that read version 1.
- `exported_at`: the date and time of the export, in ISO 8601 format (for example `2026-09-29T14:05:00Z`).
- `contact`: the contact's own details. These are the ID, all name fields, gender, pronoun, religion, job position and company. The name fields are prefix, first, middle, last, nickname, maiden name and suffix.
- One list for each type of data that belongs to the contact:
  - contact information (for example phone numbers, email addresses, social profiles), with its type
  - addresses, with their address type
  - important dates (for example birthdays), with their date type
  - notes, with title, body, author name and dates
  - reminders, with their date and how often they repeat
  - relationships, with the other contact and the relationship type
  - calls, with their call reason
  - tasks
  - loans where this contact lends or borrows, with the other party
  - pets, with their pet category
  - goals, with their streaks
  - timeline events and the life events inside them, with other contacts who share them
  - mood tracking events, with their mood label
  - gifts
  - quick facts
  - labels and groups the contact belongs to, by name
  - photos, documents and the avatar, by file name and upload date

A list that has no records appears as an empty list. It is never left out.

### Out of scope

- **We are not exporting more than one contact at a time.** There is no export for a whole vault or a whole account.
- **We are not building import.** Monica cannot read the export file back in, and no import is planned.
- **We are not including the photo and document files.** The export lists them but does not contain them or links to them.
- **We are not changing the vCard download.** It stays exactly as it is.
- **We are not adding an API endpoint.** The download from the contact page is the only way to get the file.
- **We are not adding scheduled or automatic exports.**
- **We are not adding a human-readable format** such as PDF or HTML. This can come later.
- **We are not including the contact's activity feed.** The activity feed is Monica's log of changes to the contact, such as "contact created". It records actions in Monica, not facts about the person.
- **We are not including journal posts or life metric values** that mention the contact. These belong to vault-level features, not to the contact.
- **We are not publishing documentation of the file's shape.** It would be useful but is not needed now.
- **We are not adding tracking or success metrics** for this feature.

### Phasing (if applicable)

- Phase 1: this spec. One contact, one JSON file, download from the contact page.
- Possible later work, not planned: a human-readable format, a written description of the file's shape, and a whole-vault export.

### Vertical slice check

If this shipped alone, a user could download a complete copy of everything they recorded about a contact, which they cannot do today.

---

## Figma

No designs needed. The "Export" link matches the existing vCard download link in style and position.

---

## Acceptance criteria

**AC-1**
GIVEN a user who is a vault member with manager, editor or viewer permission
WHEN the user opens a contact's page in that vault
THEN the page shows an "Export" link next to the vCard download link, in the same style
AND the vCard download link still works and produces the same vCard file as before this change.

**AC-2**
GIVEN a contact that has at least one record of each data type listed in "What the export file contains"
WHEN a vault member clicks the "Export" link
THEN the browser downloads one file with the `.json` extension
AND the file is valid JSON
AND the file contains `format_version` with the value `1`, `exported_at`, and `contact`
AND the file contains every record of every listed data type for that contact.

**AC-3**
GIVEN a contact that has a relationship, a shared timeline event or a loan with another contact
WHEN a vault member exports the first contact
THEN each other contact appears in the file only as its ID, its display name and how it is connected
AND the file contains no other fields about that other contact, such as its notes, dates or contact information.

**AC-4**
GIVEN a contact with at least one photo, one document and an avatar
WHEN a vault member exports the contact
THEN the file lists each photo, document and avatar with its file name and upload date
AND the file contains no file content and no web address for any file.

**AC-5 (empty state)**
GIVEN a contact that has only a first name and no other data
WHEN a vault member exports the contact
THEN the browser downloads a valid JSON file
AND every data type list from "What the export file contains" is present as an empty list.

**AC-6 (unhappy path)**
GIVEN a user who is not a member of the vault, or a contact that does not belong to the vault, or a contact that has been deleted
WHEN the user requests the export for that contact
THEN the server returns no export file
AND the server returns the same HTTP status code that the vCard download returns for the same user and contact.

---

## Assumptions

- **Users who want to leave Monica are a side effect, not the target.** The export makes leaving easier. We believe this builds trust more than it raises churn, because data ownership is Monica's promise.
- **No specific trigger exists, such as a count of requests or churn feedback.** The case for this feature is the strategy's "provable trust" capability, not measured demand.
- **The activity feed is not part of "everything recorded about this person."** It is a log of changes in Monica, and it would add noise for users who reuse the data in scripts.
- **One request can build the whole file.** A single contact's data is small enough to generate while the user waits. It does not need a background job.
- **No work in the current iteration touches the contact page or the vCard download.** The iteration file (`docs/iterations/current.md`) does not exist, so nobody checked this.
- **Scheduled exports are not needed now.** Users who want regular backups can click "Export" again.
- **Riskiest:** the list of data types in "What the export file contains" is complete. The architecture docs name about 25 contact sub-domains, and some links (for example gifts) are not fully documented. If the real list is longer, the work grows.

---

## Technical notes

- **Permissions.** Use the same checks as the contact page. The user must pass the `vault-viewer` gate: they are a vault member with any permission level. The contact must pass the `contact-owner` gate: its `vault_id` matches the vault. Do not require `vault-editor`. Source: `docs/architecture/07-cross-cutting.md`.
- **Route.** Add the route next to the vCard route, `POST /vaults/{vault}/contacts/{contact}/vcard`. Like the vCard route, it must bypass the Inertia middleware so the browser receives a file, not a page. Inertia is the library that connects the Laravel backend to the Vue frontend. Source: `docs/architecture/04-external-interfaces.md`.
- **Do not change the vCard code.** `ExportVCardResource` and `VCardResource` in `app/Domains/Contact/Dav/` also serve CardDAV sync. CardDAV is the protocol that address-book apps use to sync contacts with Monica. Changes there can break sync clients.
- **No third-party calls.** Build the file only from Monica's database. Do not call Uploadcare, LocationIQ, Mapbox or any other external service while exporting. Read photo and document names and dates from the database, not from the Uploadcare API. This keeps the privacy promise in `docs/product/strategy.md`.
- **Readable values, not internal IDs, for types.** Many fields point to account-level lookup tables, such as gender, pronoun, relationship type, address type and call reason. Export the name the user sees on the contact page, in the user's language. Keep record IDs, because scripts can use them to match records across exports.
- **Formats.** Write every date and time in ISO 8601. Ignore the user's display preferences for date format, number format and distance unit. Write numbers and money amounts as raw values, and write currency as its code (for example `EUR`).
- **File name.** Include the contact's name and the export date, for example `jane-doe-2026-09-29.json`. Replace characters that are not safe in file names. Send the response with content type `application/json`.
- **Do not export the raw `vcard` column** on the `contacts` table. It holds DAV sync data. The `contact` part of the file already holds the address-book fields in structured form.
- **Deleted contacts.** Contacts are soft-deleted: Monica marks them as deleted but keeps the database row. The export must treat a soft-deleted contact as missing (see AC-6).
- **Future data types.** When a developer later adds a new type of contact data, they must also add it to the export. Consider a test that fails when a contact relation is missing from the export.

---

## Open questions

- 🟡 Does any work in progress touch the contact page or the vCard download? We are proceeding on the assumption that nothing does. — PM
- 🟡 How do gifts link to a contact? The architecture docs list a `gifts` table but no contact sub-domain for it. We are proceeding on the assumption that gifts link directly to the contact. — developer
- 🟡 Should the export include the activity feed? We are proceeding without it. — PM
- 🟢 Should a later phase include journal posts and life metric values that mention the contact? Not in Phase 1. — PM

---

## Before you build

**Decisions made:**

- The export is one JSON file for one contact, downloaded from the contact page.
- The "Export" link sits next to the vCard download link, and the vCard download does not change.
- Any vault member who can view the contact can export it.
- Other contacts appear only as ID, display name and connection.
- Photos, documents and the avatar appear by file name and upload date only, with no file content or links.
- The file includes `format_version: 1` so its shape can change later without breaking scripts.
- There is no import, no bulk export, no API endpoint, no scheduled export and no tracking.

**Riskiest assumption:**
The list of contact data types in this spec is complete; if the codebase holds more, the export will be missing data or the work will grow.

**Next step:**
Before creating tickets, a developer checks the list of data types against `app/Models/Contact.php`. After that, the spec is ready for ticket generation, with no blockers.
