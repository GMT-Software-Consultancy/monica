# Full contact export

Last updated: 29 September 2026
Status: Ready
Tickets: (none yet — ticket_tool is "none", so tickets will go to `output/tickets.md`)

---

## Problem statement

People who keep relationship data in Monica cannot download everything they recorded about one contact, because the only download (vCard) holds address-book details alone.

## Why now

The product strategy (draft of 2 July 2026) names "provable trust" as one of five required capabilities. It commits Monica to making "your data, only yours" a visible feature, not only an engineering fact. Today that promise fails for a single contact. The vCard download drops notes, relationships, reminders, important dates and most other data a user enters. A full contact export makes data ownership something the user can check with one click.

## Users affected

`personas.md` does not exist yet, so this section describes users by role. Use these terms throughout:

- **Vault**: a shared space in Monica that holds contacts.
- **Vault member**: any user with access to a vault, at one of three permission levels: view, edit or manage.

Affected users:

- **Vault members on the hosted plan and on self-hosted instances.** Both groups get the same feature.
  - Some want a personal backup of what they know about one person.
  - Some want to reuse the data in other tools or in their own scripts.
- **View-only vault members.** They can export too. They can already see all of this data on the contact page.
- **Other contacts linked to the exported contact** (edge case). They are not users. They appear in the file by name and connection only. Their own details stay out of the file.

## Background

Each contact page has a "Download as vCard" link. vCard is a standard file format for address books (file extension `.vcf`). It holds names, phone numbers, email addresses and postal addresses. It does not hold notes, reminders, relationships, calls, goals, life events or the other data Monica users record. Today a user who wants a full copy of one contact must copy it by hand, page by page. Monica has no other export on the contact page.

---

## Scope

### In scope

- A new link on the contact page, next to "Download as vCard", that downloads one file for that one contact.
- The file is JSON. JSON (JavaScript Object Notation) is a common text format that people and programs can both read.
- The file contains everything recorded about the contact. That means these data types, where the contact has any:
  - Address-book details: names, nickname, gender, pronoun, contact information (email, phone, social accounts), addresses, job information, religion.
  - Labels.
  - Groups the contact belongs to, with the group name and the contact's role in it.
  - Quick facts.
  - Notes.
  - Important dates.
  - Reminders.
  - Relationships.
  - Calls.
  - Tasks, both open and completed.
  - Goals and their streaks.
  - Loans.
  - Pets.
  - Life events.
  - Mood tracking entries.
  - Photos, documents and the avatar, as a list only (see below).
- Photos, documents and the avatar appear as a list. Each entry shows the file name and the date it was added. The file content itself is not in the export.
- Other contacts (in relationships, groups, loans or life events) appear by name and by how they connect to this contact. For example: "sister: Jane Doe". None of their own details appear.
- The file includes a format version number, so scripts can detect a change in the file's structure later.
- All dates in the file use the ISO 8601 format (for example `2026-09-29`), so they cannot be misread.
- Every vault member who can view the contact can use the link. This matches who can use "Download as vCard" today.

### Out of scope

- Exporting more than one contact at once, including whole-vault or whole-account export.
- Importing the file back into Monica or into another Monica instance.
- An API or endpoint for exports. The download link is the only way in.
- Scheduled or automatic exports (assumption: the PM had no strong view, and one-click download meets the stated need).
- A human-readable version of the export, such as PDF or HTML. This may come later.
- The actual content of photos, documents and the avatar.
- Journal posts that mention the contact. Journal posts belong to journals, not to the contact.
- The contact's activity feed (the history of changes shown on the contact page).
- Success tracking or analytics for this feature.
- Any change to the existing vCard download.

### Phasing

- Phase 1: this spec — one contact, JSON, download from the contact page.
- Possible later work, not committed: a human-readable version, and file content for photos and documents.

### Vertical slice check

If this shipped alone, a vault member could download everything they recorded about one person in a single file, which they cannot do today.

---

## Figma

No designs needed. The new link matches the existing "Download as vCard" link in style and sits directly next to it in the same list of contact actions.

---

## Acceptance criteria

**AC-1**
GIVEN a vault member with view, edit or manage permission opens a contact's page
WHEN the page loads
THEN they see the new full export link next to "Download as vCard", in the same style
AND the "Download as vCard" link is still present and downloads the same `.vcf` file as before this change.

**AC-2**
GIVEN a contact has at least one entry in every data type listed under "In scope"
WHEN a vault member clicks the full export link
THEN the browser downloads one file named after the contact with the extension `.json`
AND the file is valid JSON
AND the file contains every entry of every listed data type, a format version number, and dates in ISO 8601 format.

**AC-3**
GIVEN a contact has a relationship with a second contact, and the second contact has a phone number and a note
WHEN a vault member downloads the full export of the first contact
THEN the file shows the second contact's name and the relationship type
AND the file does not contain the second contact's phone number, note or any other detail of theirs.

**AC-4**
GIVEN a contact has 2 photos and 1 document
WHEN a vault member downloads the full export
THEN the file lists 3 entries, each with its file name and the date it was added
AND the file contains no photo or document content.

**AC-5 (unhappy path — empty contact)**
GIVEN a contact has only a name and no other recorded data
WHEN a vault member downloads the full export
THEN the download succeeds
AND the file contains the name, the format version number, and every data type section present but empty.

**AC-6 (unhappy path — failure)**
GIVEN Monica cannot create the export file for a contact
WHEN a vault member clicks the full export link
THEN the contact page shows an error message that says the export failed
AND the browser downloads no file, not even a partial one
AND the vault member stays on the contact page.

---

## Assumptions

- The data types listed under "In scope" cover everything users consider "recorded about this person". **This is the riskiest assumption.**
- A contact template decides which sections appear on a contact's page. The export includes all recorded data, even data in a section the template currently hides.
- An empty contact still gets a file with empty sections, not a warning. A predictable file shape is more useful for scripts than a warning.
- Nobody needs scheduled or automatic exports in Phase 1.
- No work currently in progress changes the contact page or the vCard download. `docs/iterations/current.md` does not exist, so no one could check this.
- People who reuse the data in scripts can work from the file's field names and format version number, without separate published documentation in Phase 1.

---

## Technical notes

- **Privacy:** The user's own Monica instance must create the file. Monica must not send contact data to any third-party service. Monica must not keep a copy of the export after the download. This keeps the feature inside the strategy's privacy review gate.
- **Both hosting types:** The feature must work the same on the hosted plan and on a self-hosted instance, with no extra setup for self-hosters.
- **Permissions:** Any vault member who can view the contact can export it. This must not give view-only members any data they cannot already see in that vault.
- **No change to vCard:** The existing vCard download, and any vCard sync that other apps use, must keep working exactly as before.

---

## Open questions

- 🟡 Should the export include data in sections that the contact's template hides? Working assumption: yes, include it. — PM
- 🟡 Do script users need published documentation of the file's structure? Working assumption: not in Phase 1; the format version number is enough for now. — PM
- 🟡 Should journal posts that mention the contact be part of a later phase? Working assumption: no, they stay out of scope. — PM
- 🟢 Scheduled or automatic exports are out of scope. The PM had no strong view; this spec decides it. — PM
- 🟢 No known in-progress work conflicts with this spec. Check again when an iteration plan exists. — engineering lead

---

## Before you build

**Decisions made:**

- The export covers one contact only, as one JSON file, downloaded from the contact's page.
- The export includes everything recorded about the contact, including the vCard's address-book details.
- Photos, documents and the avatar appear as a list (file name and date), without file content.
- Other contacts appear by name and connection only.
- Any vault member who can view the contact can export it.
- The "Download as vCard" link stays exactly as it is.
- No tracking, no API, no import and no scheduled exports in this phase.

**Riskiest assumption:**
The in-scope list of data types covers everything users think of as "recorded about this person", so the export truly is a full copy.

**Next step:**
Ready for ticket generation. There are no blockers. Engineering confirms the in-scope data type list against the product at plan review.
