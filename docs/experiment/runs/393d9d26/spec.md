# Full contact export

Last updated: 29 September 2026
Status: Ticketed
Tickets: `output/tickets.md`, tickets 1–7 (no tracker IDs; ticket_tool is none)

---

## Problem statement

Monica users cannot export everything they have recorded about a contact, because the vCard download only includes address-book details.

## Why now

The product strategy names "provable trust" as a required capability. It says transparent data handling must be a marketed feature, not only an engineering fact. The promise "your data is yours" is only credible if users can take their data out in a form they control. Today, hosted users have no way to get notes, reminders or relationships out of Monica. Self-hosters can only get them by querying the database directly.

## Users affected

`docs/product/personas.md` does not exist, so this spec cannot use persona names. The users are described instead:

- **Users who want a personal backup.** They keep detailed relationship data in Monica and want a copy they control, outside Monica. This includes hosted users and self-hosters.
- **Users who want to reuse their data.** They want to load what they recorded into another tool, such as a notes app, a script or another personal relationship manager.
- **Other members of a shared vault (edge case).** A vault is a workspace that several users can share. Any member who can see a contact can export it. That export includes notes that other members wrote.
- **The contact themselves (non-user).** The contact is the person described in the file. They do not use Monica and do not see the export.

## Background

A user can already download a vCard from a contact's page. A vCard is a standard address-book file (`.vcf`) that holds names, phone numbers, emails and addresses. It does not hold notes, reminders, important dates, relationships or the other records the user has logged. To keep those records, users today must copy them by hand from the contact page. Self-hosters can also query the database directly.

---

## Scope

### In scope

- A new "Export all data" action on the contact page. It sits next to the existing vCard download action.
- Clicking it downloads one JSON file for that one contact. JSON is a standard text format for structured data that most tools and programming languages can read.
- The file contains every record a user has entered about the contact, in these sections:
  - `contact`: name parts (first, middle, last, nickname, maiden name, prefix, suffix), gender, pronoun, religion, job position, company
  - `contact_information`: phone numbers, emails, social profiles, with their type labels
  - `addresses`
  - `important_dates`: birthdays, anniversaries and other dates, with their type labels
  - `notes`: title, body, author name, created and updated times
  - `reminders`
  - `relationships`
  - `calls`
  - `tasks`
  - `loans`
  - `goals`
  - `pets`
  - `life_events`, with the timeline event each one belongs to
  - `mood_tracking_events`
  - `quick_facts`
  - `labels`
  - `groups`
  - `photos` and `documents`: metadata only (see "Decisions made")
- The file shows readable labels, not internal database IDs, for lookup values. For example, it shows `"gender": "Woman"`, not `"gender_id": 3`. This makes the file usable outside Monica.
- Every vault member who can view the contact can use the action. This covers the manager, editor and viewer permission levels.
- All Monica users get the action, on both hosted and self-hosted instances. The paid plan does not gate it.

### Out of scope

- Exporting more than one contact at a time. This includes vault-wide export and bulk export from the contact list.
- Importing an exported file back into Monica.
- A human-readable format such as PDF, HTML or Markdown.
- The photo and document files themselves. The file lists them but does not contain them.
- Data about related contacts beyond their name and Monica ID.
- The contact's activity feed. The activity feed is Monica's automatic history of changes to the contact, not data the user entered.
- Journal posts that mention the contact. Journals belong to the vault, not to the contact.
- Any change to the existing vCard download.
- Usage tracking or success metrics for the export. The PM ruled these out.
- Recording exports in the contact's activity feed.

### Phasing

- Phase 1: this spec.
- Later work is possible but not planned. Candidates are a human-readable format, a ZIP archive with photos and documents, and export of a whole vault.

### Vertical slice check

If this shipped alone, a user could download everything they have recorded about one contact as one file they keep. They cannot do that today. This is a user-facing feature, not an enabler story.

---

## Figma

No designs needed. The PM confirmed that no designs or mock-ups exist. The only UI change is one extra action next to the existing vCard download action, and it uses the same style.

---

## Acceptance criteria

**AC-1**
GIVEN a user is a member of a vault with any permission level (manager, editor or viewer)
AND the user is on the page of a contact in that vault
WHEN the user selects "Export all data"
THEN the browser downloads one file with the extension `.json` and the content type `application/json; charset=utf-8`
AND the file name matches `monica-contact-{name}-{YYYY-MM-DD}.json`. `{name}` is the contact's first and last name in lowercase ASCII letters, digits and hyphens. `{YYYY-MM-DD}` is the export date in UTC.
AND if `{name}` would be empty after conversion, the file uses the contact's ID in its place.

**AC-2**
GIVEN a contact has at least one record in every section listed under "In scope"
WHEN a vault member exports the contact
THEN the file is valid JSON with the top-level keys `format` (value `"monica-contact-export"`), `format_version` (value `1`), `exported_at` (ISO 8601 time in UTC) and one key per section
AND each section holds exactly as many entries as the contact has records of that type, not counting soft-deleted records. A soft-deleted record is one that is marked deleted but still stored in the database.
AND every lookup value (gender, pronoun, religion, relationship type, contact information type, address type, important date type, label, group) appears as its readable name, not as a numeric ID.

**AC-3**
GIVEN contact A has a relationship with contact B
WHEN a vault member exports contact A
THEN each entry in `relationships` contains only these fields: the relationship type name, contact B's full name and contact B's Monica ID
AND the file contains no other data about contact B, such as B's notes, dates or contact information.

**AC-4**
GIVEN a contact has at least one photo and at least one document
WHEN a vault member exports the contact
THEN each entry in `photos` and `documents` contains only the file name, MIME type, size in bytes and upload time
AND the file contains no file contents and no download URLs
AND Monica sends no request to local file storage or to Uploadcare during the export. Uploadcare is the external file-hosting service that some instances use.

**AC-5 (unhappy path: empty contact)**
GIVEN a contact has only a name and no other records
WHEN a vault member exports the contact
THEN the download succeeds
AND every section key is present, and each list section holds an empty array `[]`
AND fields with no value hold `null`, not an empty string.

**AC-6 (unhappy path: no access)**
GIVEN a user sends the export request for a contact
AND at least one of these is true: the user is not a member of the contact's vault, the contact belongs to a different vault than the one in the URL, or the contact is soft-deleted
WHEN Monica handles the request
THEN Monica returns an HTTP 403 or 404 response, matching what the existing vCard route returns in the same case
AND the response contains no contact data.

---

## Assumptions

- **Riskiest:** One JSON file serves both user groups. We believe users who want a backup accept a structured text file, and do not need a PDF or a readable document.
- Every vault member who can see a contact should be able to export it, including viewers. A viewer can already read every note on the contact page, so the export shows nothing new to them. This matches the existing vCard route.
- Notes written by other vault members belong in the export, with the author's name. The user asked for "everything recorded about that person", and those notes are visible to them on the page.
- Name, type and ID are enough to describe a relationship. Including the related contact's data would turn a one-contact export into a multi-contact export.
- A metadata list of photos and documents is enough for phase 1. Packing the files would mean a ZIP archive, larger downloads and requests to Uploadcare.
- Users do not expect exports to appear in the contact's activity feed. An export changes nothing about the contact, and the feed records changes.
- The export sits beside the vCard download and does not replace it. vCard is still the right format for address-book apps and for CardDAV sync. CardDAV is the protocol that address-book apps use to sync contacts.
- One contact's data is small enough to build during the request, without a background job.
- The concrete "why now" is the strategy's "provable trust" capability plus the gap for hosted users. The PM gave no more specific trigger, such as a support-ticket count or a compliance request.

---

## Technical notes

- **Route pattern.** Follow the existing vCard download. That route is `POST /vaults/{vault}/contacts/{contact}/vcard`, and it bypasses the Inertia middleware. Inertia is the library that returns page data to the Vue frontend, and it cannot return a file download. Add a sibling route, `POST /vaults/{vault}/contacts/{contact}/export`, behind the same `contact-owner` gate and the `vault-viewer` gate.
- **Service layer.** Build the export in a service object that extends `BaseService`. Declare these permissions: `author_must_belong_to_account`, `vault_must_belong_to_account`, `author_must_be_in_vault` and `contact_must_belong_to_vault`. The service only reads data. It must not create a `ContactFeedItem`.
- **Where the data lives.** Each section maps to a subdomain under `app/Domains/Contact/`, for example `ManageNotes`, `ManageReminders`, `ManageContactImportantDates` and `ManageRelationships`. The architecture docs do not list the columns for most of these tables. Confirm each section's fields from its migration file before writing the serializer. A serializer is the code that turns a database record into JSON.
- **Soft deletes.** Contacts are soft-deleted. Laravel route model binding already excludes them, so AC-6 needs no custom check. Confirm this for sub-records that also use soft deletes.
- **Dates.** Write timestamps as ISO 8601 in UTC, for example `2026-09-29T14:05:00Z`. Write date-only values as `YYYY-MM-DD`. Important dates can lack a year or a month. Write the stored parts as separate fields and use `null` for missing parts. Do not invent a placeholder year.
- **Encoding.** Write UTF-8 without escaping non-ASCII characters, so names such as "Zoë" or "李" stay readable.
- **Privacy review gate.** Build the file in memory and stream it in the response. Do not store it on the server. Do not call any third-party service. Do not add analytics or tracking events; the PM ruled these out.
- **Performance target.** A contact with 1,000 notes should export in under 5 seconds on the standard Docker Compose setup. Load each section with eager loading, which fetches related records in a few queries instead of one query per record.
- **Missing context.** `CLAUDE.md`, `docs/ticket-creation-context.md` and `docs/iterations/current.md` do not exist. Nobody has checked this spec for conflicts with work in progress.

---

## Open questions

- 🟡 Is JSON acceptable to users who only want a personal backup? Working assumption: yes, and a readable format can follow later if users ask. Owner: PM.
- 🟡 In a shared vault, should other members see that someone exported a contact? Working assumption: no. Exports are not recorded in the activity feed. Owner: PM.
- 🟡 Does the `gifts` table hold per-contact data that users expect in the export? It appears in the migrations but has no `Manage` subdomain in the architecture docs. Working assumption: leave it out of phase 1. Owner: developer confirms from the code.
- 🟢 Should the export add usage tracking? No. The PM ruled out metrics and tracking for this work.

---

## Before you build

**Decisions made:**

- The export is one JSON file per contact, started from the contact page. There is no import and no multi-contact export.
- The export adds to the vCard download; it does not replace it. The vCard download stays unchanged.
- Every vault member who can view the contact can export it, on every plan.
- Relationships include only the related contact's name, ID and relationship type.
- Photos and documents appear as metadata only. The file never contains file contents or download URLs.
- Monica does not record exports in the activity feed and does not track them.

**Riskiest assumption:**
One JSON file meets the needs of users who want a personal backup, not only those who want to reuse data in other tools.

**Next step:**
Ready for ticket generation. First, the developer confirms each section's fields from its migration file.
