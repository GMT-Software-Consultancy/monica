# Full contact export — Tickets
Generated: 29 September 2026
Spec: output/spec.md
Tracker: none (no tracker configured in `.claude/skill-config.md`; tickets not posted)
Initiative: Provable trust

## Terms used in these tickets

- **Vault**: a shared space in Monica that holds contacts.
- **Vault member**: any user with access to a vault, at one of three permission levels: view, edit or manage.
- **Full export link**: the new link on the contact page that downloads the full export file. It sits next to "Download as vCard".
- **Full export file**: the one JSON file the full export link downloads. JSON (JavaScript Object Notation) is a common text format that people and programs can both read.
- **Format version number**: a number in the file that tells scripts which structure the file uses. Scripts use it to detect a later change in the file's structure.
- **Address-book details**: names, nickname, gender, pronoun, contact information (email, phone, social accounts), addresses, job information and religion.
- **Contact template**: the setting that decides which sections appear on a contact's page.
- **Linked contact**: another contact that appears in this contact's relationships, groups, loans or life events.

## Breakdown

| # | Title | Type | Depends on |
| - | ----- | ---- | ---------- |
| 1 | Download a contact's full export file from the contact page | Feature | None |
| 2 | Include the contact's own recorded entries in the full export | Feature | 1 |
| 3 | Show linked contacts in the full export by name and connection only | Feature | 1 |
| 4 | List the contact's photos, documents and avatar in the full export | Feature | 1 |

Tickets 2, 3 and 4 do not depend on each other. The team can build them in any order, or at the same time.

---

## 1. Download a contact's full export file from the contact page

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** None

### Summary
Vault members download one JSON file with a contact's address-book details and labels from the contact page.

### Job to be done
When a vault member wants a copy of a contact's data, they need one download on the contact page, so they can keep a backup.

### Navigation context
**Entry point(s):** Vault → Contacts list → a contact's page. The full export link sits in the list of contact actions, directly next to "Download as vCard".
**Exit point(s):** The vault member stays on the same contact page. The browser saves the full export file. If the export fails, the contact page shows an error message.
**Screen/component:** The contact page, in the list of contact actions.

### Context
The product strategy names "provable trust" as a required capability. Monica promises "your data, only yours", but today the only download is a vCard. A vCard holds address-book details alone. This ticket delivers the full export link and the file with its first sections. Tickets 2, 3 and 4 add the other data types to the same file.

Privacy rules apply to this ticket and to tickets 2–4:
- The user's own Monica instance creates the file.
- Monica sends no contact data to any third-party service.
- Monica keeps no copy of the export after the download.

The feature works the same on the hosted plan and on a self-hosted instance. Self-hosters need no extra setup.

### Designs
No designs needed. The full export link matches the "Download as vCard" link in style and sits directly next to it.

### Acceptance criteria

**Scenario: Vault member sees the full export link next to the vCard link**
Given a vault member with view, edit or manage permission is on a contact's page
When the page loads
Then they see the full export link next to "Download as vCard", in the same style
And the "Download as vCard" link still downloads the same `.vcf` file as before this change

**Scenario: Vault member downloads a contact's address-book details and labels**
Given a vault member is on the page of a contact who has every address-book detail and at least one label
When they click the full export link
Then the browser downloads one file named after the contact, with the extension `.json`
And the file opens without errors in a JSON viewer
And the file contains every address-book detail and every label of the contact
And the file contains a format version number
And every date in the file uses the ISO 8601 format, for example `2026-09-29`

**Scenario: Export fails and no partial file downloads**
Given a vault member is on a contact's page
And Monica cannot create the export file for this contact
When they click the full export link
Then the contact page shows an error message that says the export failed
And the browser downloads no file, not even a partial one
And the vault member stays on the contact page

**Scenario: Contact with only a name still exports**
Given a vault member is on the page of a contact who has only a name and no other recorded data
When they click the full export link
Then the browser downloads the full export file
And the file contains the name and the format version number
And the file contains a section for each address-book detail and for labels, each present but empty

**Scenario: View-only vault member exports a contact**
Given a vault member with view permission only is on a contact's page
When they click the full export link
Then the browser downloads the full export file
And the file contains no data that this vault member cannot already see in that vault

**Scenario: Export includes data in a section the contact template hides**
Given a vault member is on the page of a contact whose contact template hides the job information section
And the contact has job information recorded
When they click the full export link
Then the full export file contains the contact's job information

**Scenario: Self-hosted instance exports with no extra setup**
Given a vault member is on a contact's page on a self-hosted instance with no extra setup for this feature
When they click the full export link
Then the browser downloads the full export file with the same sections as on the hosted plan

### Out of scope
- The data types in tickets 2, 3 and 4.
- Exporting more than one contact at once, including whole-vault or whole-account export.
- Importing the file back into Monica or into another Monica instance.
- An API or endpoint for exports. The full export link is the only way in.
- Scheduled or automatic exports.
- A human-readable version of the export, such as PDF or HTML.
- Published documentation of the file's structure.
- Success tracking or analytics for this feature.
- Any change to the existing vCard download or to vCard sync.

---

## 2. Include the contact's own recorded entries in the full export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary
The full export file includes the contact's quick facts, notes, important dates, reminders, tasks, calls, goals, pets and mood tracking entries.

### Job to be done
When a vault member backs up a contact, they need the notes and entries they wrote, so the file is a true copy.

### Navigation context
**Entry point(s):** Vault → Contacts list → a contact's page → the full export link, next to "Download as vCard".
**Exit point(s):** The vault member stays on the same contact page. The browser saves the full export file. If the export fails, the contact page shows an error message.
**Screen/component:** The contact page, in the list of contact actions. This ticket changes the content of the full export file only.

### Context
Users record these entries by hand, often over months. The vCard download drops all of them, so this is the data users most risk losing today. This ticket covers these data types:
- Quick facts.
- Notes.
- Important dates.
- Reminders.
- Tasks, both open and completed.
- Calls.
- Goals and their streaks.
- Pets.
- Mood tracking entries.

### Designs
No designs needed. The contact page does not change.

### Acceptance criteria

**Scenario: Vault member exports every recorded entry of the contact**
Given a vault member is on the page of a contact who has at least one entry of each data type in this ticket
When they click the full export link
Then the full export file contains every entry of each of these data types
And the tasks include both open and completed tasks
And each goal shows its streaks
And every date in the file uses the ISO 8601 format

**Scenario: Export fails when the contact's notes cannot be included**
Given a vault member is on the page of a contact who has at least one note
And Monica cannot include the contact's notes in the export
When they click the full export link
Then the contact page shows an error message that says the export failed
And the browser downloads no file, not even a partial one
And the vault member stays on the contact page

**Scenario: Contact with none of these entries still exports every section**
Given a vault member is on the page of a contact who has only a name
When they click the full export link
Then the browser downloads the full export file
And the file contains a section for each data type in this ticket, each present but empty

**Scenario: Export includes notes the contact template hides**
Given a vault member is on the page of a contact whose contact template hides the notes section
And the contact has 2 notes recorded
When they click the full export link
Then the full export file contains both notes

### Out of scope
- Relationships, groups, loans and life events (ticket 3).
- Photos, documents and the avatar (ticket 4).
- Journal posts that mention the contact. Journal posts belong to journals, not to the contact.
- The contact's activity feed (the history of changes shown on the contact page).

---

## 3. Show linked contacts in the full export by name and connection only

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary
The full export file includes the contact's relationships, groups, loans and life events, and shows each linked contact by name and connection only.

### Job to be done
When a vault member exports a contact, they need that person's connections to others, so they can keep the full picture.

### Navigation context
**Entry point(s):** Vault → Contacts list → a contact's page → the full export link, next to "Download as vCard".
**Exit point(s):** The vault member stays on the same contact page. The browser saves the full export file. If the export fails, the contact page shows an error message.
**Screen/component:** The contact page, in the list of contact actions. This ticket changes the content of the full export file only.

### Context
Relationships, groups, loans and life events often name other contacts. The export is about one contact, not about the people linked to them. The file names each linked contact and shows how they connect, for example "sister: Jane Doe". It never includes the linked contact's own details. This rule protects people who are not the subject of the export.

For groups, the file shows the group name and this contact's role in the group.

### Designs
No designs needed. The contact page does not change.

### Acceptance criteria

**Scenario: Vault member exports the contact's relationships, groups, loans and life events**
Given a vault member is on the page of a contact who has at least one relationship, group, loan and life event
When they click the full export link
Then the full export file contains every relationship, group, loan and life event of the contact
And each group shows the group name and this contact's role in it
And each linked contact appears by name and by how they connect to this contact

**Scenario: Linked contact's own details stay out of the file**
Given a vault member is on the page of a contact who has a relationship with a second contact
And the second contact has a phone number and a note
When they click the full export link
Then the full export file shows the second contact's name and the relationship type
And the file does not contain the second contact's phone number, note or any other detail of theirs

**Scenario: Export fails when the contact's relationships cannot be included**
Given a vault member is on the page of a contact who has at least one relationship
And Monica cannot include the contact's relationships in the export
When they click the full export link
Then the contact page shows an error message that says the export failed
And the browser downloads no file, not even a partial one
And the vault member stays on the contact page

**Scenario: Other group members' details stay out of the file**
Given a vault member is on the page of a contact who belongs to a group with 3 other members
And each other member has an email address recorded
When they click the full export link
Then the full export file shows the group name and this contact's role in it
And the file does not contain the email address or any other detail of the other members

**Scenario: Contact with no linked contacts still exports every section**
Given a vault member is on the page of a contact who has only a name
When they click the full export link
Then the browser downloads the full export file
And the file contains a section for relationships, groups, loans and life events, each present but empty

### Out of scope
- Any detail of a linked contact beyond their name and their connection to this contact.
- Exporting the linked contacts themselves.
- The contact's own recorded entries (ticket 2) and files (ticket 4).

---

## 4. List the contact's photos, documents and avatar in the full export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary
The full export file lists the contact's photos, documents and avatar by file name and date added, without the file content.

### Job to be done
When a vault member exports a contact, they need to know which files they stored for that person, so they can find the originals later.

### Navigation context
**Entry point(s):** Vault → Contacts list → a contact's page → the full export link, next to "Download as vCard".
**Exit point(s):** The vault member stays on the same contact page. The browser saves the full export file. If the export fails, the contact page shows an error message.
**Screen/component:** The contact page, in the list of contact actions. This ticket changes the content of the full export file only.

### Context
Without this list, a user cannot tell from the export which files they stored for a contact. Phase 1 lists the files but leaves out their content. That keeps the export to one text file. File content may come in a later phase, but no one has committed to it.

### Designs
No designs needed. The contact page does not change.

### Acceptance criteria

**Scenario: Vault member exports a list of the contact's photos and documents**
Given a vault member is on the page of a contact who has 2 photos and 1 document
When they click the full export link
Then the full export file lists 3 entries, each with its file name and the date it was added
And the file contains no photo or document content

**Scenario: Vault member exports the contact's avatar entry**
Given a vault member is on the page of a contact who has an uploaded avatar
When they click the full export link
Then the full export file lists the avatar with its file name and the date it was added
And the file contains no avatar image content

**Scenario: Export fails when the contact's file list cannot be included**
Given a vault member is on the page of a contact who has at least one photo
And Monica cannot include the contact's photo list in the export
When they click the full export link
Then the contact page shows an error message that says the export failed
And the browser downloads no file, not even a partial one
And the vault member stays on the contact page

**Scenario: Contact with no files still exports every section**
Given a vault member is on the page of a contact who has no photos, no documents and no uploaded avatar
When they click the full export link
Then the browser downloads the full export file
And the file contains a section for photos, documents and the avatar, each present but empty

### Out of scope
- The content of photos, documents and the avatar.
- Download links to the original files.

---

## Summary

**Tracker:** none. `.claude/skill-config.md` sets `ticket_tool: none`, so no tickets were posted and no tracker IDs exist. The spec's `Status` and `Tickets` fields were left unchanged.

**Initiative:** Provable trust (no tracker grouping ID)

**Tickets:**
1. Download a contact's full export file from the contact page
2. Include the contact's own recorded entries in the full export
3. Show linked contacts in the full export by name and connection only
4. List the contact's photos, documents and avatar in the full export

**File saved:** `output/tickets.md`

**Assumptions made during decomposition:**
- The spec has no `Initiative:` field. We used "Provable trust" because the spec's "Why now" ties the feature to that strategy capability. The PM should confirm this.
- The three 🟡 open questions in the spec were treated as resolved with their working assumptions:
  - The export includes data in sections the contact template hides.
  - Scripts get no published documentation of the file's structure in Phase 1.
  - Journal posts stay out of scope.
- `docs/product/personas.md` does not exist, so the job statements use the role "vault member".
- `docs/iterations/current.md` does not exist, so we could not check for conflicting in-progress work.
- We split the file's content by concern, not by data type. Ticket 1 carries the link, the file shape, the failure behaviour and the permission rules. Ticket 3 holds every data type that names other contacts, because they share the same privacy rule. Groups sit in ticket 3 for this reason.
- Each later ticket repeats the "no partial file" failure rule for its own data. A missing section must fail the export, not silently drop data.
- The spec does not give the text of the full export link. The team picks the wording.
- No Chore or Spike was needed. The spec needs no data migration, and the approach is not unknown.

**Recommended next step:** Before building, engineering confirms the in-scope data type list against the product at plan review. This is the spec's riskiest assumption. Ticket 1 alone produces a file with address-book details and labels only. The PM should decide whether to show the full export link to users before tickets 2, 3 and 4 ship. After ticket 1, tickets 2, 3 and 4 are unblocked and can run in parallel.
