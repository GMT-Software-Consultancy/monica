# Full contact export — Tickets

Generated: 29 September 2026
Spec: output/spec.md
Tracker: none (no tracker is configured, so this file is the only copy of the tickets)
Initiative: Provable trust

## Breakdown

| #   | Title                                                                    | Type    | Depends on |
| --- | ------------------------------------------------------------------------ | ------- | ---------- |
| 1   | Let vault members download a contact's profile as a JSON file            | Feature | None       |
| 2   | Add contact details, important dates, labels and groups to the export    | Feature | 1          |
| 3   | Add notes, with their authors, to the export                             | Feature | 1          |
| 4   | Add reminders, tasks, loans and goals to the export                      | Feature | 1          |
| 5   | Add calls, life events, mood tracking, pets and quick facts to the export | Feature | 1          |
| 6   | Add relationships to the export                                          | Feature | 1          |
| 7   | Add photo and document details to the export                             | Feature | 1          |

Tickets 2 to 7 do not depend on each other. The team can build them in any order once
ticket 1 ships.

**Release note for the PM:** the action is called "Export all data". Users should not
see it until tickets 1 to 7 have all shipped, or the label promises more than the file
holds.

---

## 1. Let vault members download a contact's profile as a JSON file

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** None

### Summary

Vault members can select "Export all data" on a contact page and download one JSON file
that holds the contact's profile.

### Job to be done

When a user has recorded details about someone in Monica, a user who wants a personal
backup needs to download that contact's data as one file, so they can keep a copy they
control outside Monica.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page. The "Export all data"
action sits next to the existing vCard download action.
**Exit point(s):** The user stays on the contact page. The browser saves the file to the
user's device.
**Screen/component:** The contact page, in the same place and style as the existing vCard
download action.

### Context

The product strategy names "provable trust" as a required capability. Hosted users have
no way today to get their notes, reminders or relationships out of Monica. The existing
vCard download holds only address-book details. A vCard is a standard address-book file
(`.vcf`).

This ticket delivers the action, the file name, the top-level file format and the
`contact` section. JSON is a standard text format for structured data that most tools can
read. Tickets 2 to 7 add the remaining sections to the same file.

The export must pass the strategy's privacy review gate. Monica keeps no copy of the file
after the download. The export sends nothing to any third-party service and sends no
tracking events.

### Designs

No designs needed. The PM confirmed that no mock-ups exist. The new action uses the same
style as the existing vCard download action.

### Acceptance criteria

**Scenario: A vault member at any permission level downloads the export**
Given the user is a member of a vault as a manager, editor or viewer
And the user is on the contact page of "Anna Smith" in that vault
When the user selects "Export all data"
Then the browser downloads one file named `monica-contact-anna-smith-YYYY-MM-DD.json`, where `YYYY-MM-DD` is today's date in UTC
And the file has the content type `application/json; charset=utf-8`
And the user is still on the contact page

**Scenario: The file shows the format header and the contact's profile**
Given the user is on the contact page of a contact with a first name, last name, nickname, gender, pronoun, religion, job position and company
When the user selects "Export all data"
Then the downloaded file is valid JSON
And the top-level key `format` holds `"monica-contact-export"`
And the top-level key `format_version` holds `1`
And the top-level key `exported_at` holds the export time in ISO 8601 format in UTC, such as `2026-09-29T14:05:00Z`
And the `contact` section holds the first name, middle name, last name, nickname, maiden name, prefix, suffix, gender, pronoun, religion, job position and company

**Scenario: Lookup values appear as readable names**
Given the user is on the contact page of a contact whose gender is "Woman"
When the user selects "Export all data"
Then the `contact` section shows `"gender": "Woman"`, not a numeric ID
And the pronoun and religion also appear as the names the user sees on the contact page

**Scenario: A user outside the vault cannot export the contact**
Given the user is signed in but is not a member of the vault that holds a contact
And the user has the export URL for that contact
When the user requests the export URL
Then the user gets the same 403 or 404 response that the vCard download gives in this case
And the response contains no contact data

**Scenario: A URL that pairs a contact with the wrong vault fails**
Given the user is a member of vault A and vault B
And the user has an export URL that names vault A and a contact from vault B
When the user requests the export URL
Then the user gets the same 403 or 404 response that the vCard download gives in this case
And the response contains no contact data

**Scenario: A deleted contact cannot be exported**
Given a vault member has deleted a contact
And the user has the export URL for that contact
When the user requests the export URL
Then the user gets the same 403 or 404 response that the vCard download gives in this case
And the response contains no contact data

**Scenario: A contact with only a name still exports**
Given the user is on the contact page of a contact that has only a first name
When the user selects "Export all data"
Then the download succeeds
And every field in the `contact` section that has no value holds `null`, not an empty string

**Scenario: Non-ASCII names stay readable**
Given the user is on the contact page of a contact named "Zoë 李"
When the user selects "Export all data"
Then the `contact` section shows "Zoë" and "李" exactly as written, not as escape codes such as `ë`
And the name part of the file name contains only lowercase ASCII letters, digits and hyphens

**Scenario: A name with no usable characters falls back to the contact ID**
Given the user is on the contact page of a contact whose first and last name become empty when reduced to ASCII letters and digits
When the user selects "Export all data"
Then the file name is `monica-contact-{contact ID}-YYYY-MM-DD.json`, with the contact's ID in place of the name

**Scenario: The export works on every plan and every instance**
Given the user has a hosted account without a paid plan, or uses a self-hosted instance
And the user is on a contact page
When the user selects "Export all data"
Then the download starts
And the user sees no upgrade prompt

**Scenario: Exporting leaves no trace on the contact**
Given the user is on a contact page
When the user selects "Export all data"
Then the contact's activity feed shows no new entry
And other vault members see no sign that an export happened

**Scenario: The export sends nothing outside the instance and keeps no copy**
Given the instance operator records the instance's outbound network traffic
And a vault member is on a contact page
When the vault member selects "Export all data"
Then the recording shows no request to any third-party service, including analytics services
And the instance's file storage holds no copy of the export file afterwards

### Out of scope

- The sections that tickets 2 to 7 add
- Exporting more than one contact at a time, including a whole vault or a bulk export from the contacts list
- Importing an exported file back into Monica
- PDF, HTML, Markdown or any other human-readable format
- Any change to the existing vCard download
- Usage tracking or success metrics for the export

---

## 2. Add contact details, important dates, labels and groups to the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The export file includes the contact's phone numbers, emails, social profiles, addresses,
important dates, labels and groups.

### Job to be done

When a user wants to move a contact's data into another tool, a user who wants to reuse
their data needs every way to reach the contact and every key date in the file, so they
can load them without typing them again.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact page. The browser saves the file.
**Screen/component:** The downloaded export file. The contact page does not change.

### Context

The vCard download already holds some of these details, but users need them in the same
file as everything else. Important dates can lack a year or a month. The file must show
only the parts the user entered and never invent a placeholder year.

### Designs

No designs needed. This ticket changes only the file contents.

### Acceptance criteria

**Scenario: Contact details, dates, labels and groups appear in the file**
Given the user is on the contact page of a contact with 2 phone numbers, 1 email, 1 social profile, 1 address, 1 birthday, 2 labels and 1 group
When the user selects "Export all data"
Then the `contact_information` section holds 4 entries, each with its type label, such as "Mobile"
And the `addresses` section holds 1 entry with its address type as a readable name
And the `important_dates` section holds 1 entry with its date type as a readable name
And the `labels` section holds the 2 label names
And the `groups` section holds the group name

**Scenario: Deleted details do not appear**
Given a vault member has deleted one of the contact's two addresses from the contact page
And the user is on that contact page
When the user selects "Export all data"
Then the `addresses` section holds only the 1 remaining address

**Scenario: An important date without a year keeps only the stored parts**
Given the user is on the contact page of a contact with a birthday that has a day and a month but no year
When the user selects "Export all data"
Then the `important_dates` entry shows the day and the month as separate fields
And the year field holds `null`
And the file shows no placeholder year

**Scenario: A contact with none of these records still exports**
Given the user is on the contact page of a contact that has only a name
When the user selects "Export all data"
Then the `contact_information`, `addresses`, `important_dates`, `labels` and `groups` keys are all present
And each of them holds an empty array `[]`

### Out of scope

- Data about other contacts in the same groups
- Any change to the existing vCard download

---

## 3. Add notes, with their authors, to the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The export file includes every note on the contact, with its title, body, author name and
times.

### Job to be done

When a user has logged months of notes about someone, a user who wants a personal backup
needs those notes in the export, so they keep what they wrote even if they leave Monica.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact page. The browser saves the file.
**Screen/component:** The downloaded export file. The contact page does not change.

### Context

Notes are the main data that users cannot get out of Monica today. In a shared vault,
other members can write notes on a contact. The export includes those notes, with the
author's name, because every vault member can already read them on the contact page.

### Designs

No designs needed. This ticket changes only the file contents.

### Acceptance criteria

**Scenario: Notes appear with their full details**
Given the user is on the contact page of a contact with 3 notes
When the user selects "Export all data"
Then the `notes` section holds 3 entries
And each entry shows the title, body, author name, created time and updated time
And each time is in ISO 8601 format in UTC, such as `2026-09-29T14:05:00Z`

**Scenario: Notes by other vault members show their author**
Given Alex and Sam are both members of a vault
And Sam wrote a note on a contact in that vault
And Alex is on that contact page
When Alex selects "Export all data"
Then the `notes` section includes Sam's note
And that entry shows Sam as the author

**Scenario: Deleted notes do not appear**
Given a vault member has deleted one of the contact's 3 notes
And the user is on that contact page
When the user selects "Export all data"
Then the `notes` section holds only the 2 remaining notes

**Scenario: A note without a title exports with a null title**
Given the user is on the contact page of a contact with a note that has a body but no title
When the user selects "Export all data"
Then that note's entry shows the title as `null`, not an empty string

**Scenario: A contact with many notes exports quickly**
Given Monica runs on the Docker Compose setup that the project ships
And the user is on the contact page of a contact with 1,000 notes
When the user selects "Export all data"
Then the download completes within 5 seconds
And the `notes` section holds 1,000 entries

**Scenario: A contact with no notes still exports**
Given the user is on the contact page of a contact that has no notes
When the user selects "Export all data"
Then the `notes` key is present and holds an empty array `[]`

### Out of scope

- Journal posts that mention the contact, because journals belong to the vault
- The contact's activity feed, which is Monica's automatic history of changes

---

## 4. Add reminders, tasks, loans and goals to the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The export file includes the contact's reminders, tasks, loans and goals.

### Job to be done

When a user tracks what they plan to do or owe for someone, a user who wants to reuse
their data needs those records in the export, so they can carry them into another tool.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact page. The browser saves the file.
**Screen/component:** The downloaded export file. The contact page does not change.

### Context

These records hold commitments the user made about the contact. A user who leaves Monica
without them loses track of what they planned or owe. A loan can name another contact.
The export shows only that contact's name and Monica ID, so a one-contact export stays a
one-contact export.

### Designs

No designs needed. This ticket changes only the file contents.

### Acceptance criteria

**Scenario: Reminders, tasks, loans and goals appear in the file**
Given the user is on the contact page of a contact with 1 reminder, 2 tasks, 1 loan and 1 goal
When the user selects "Export all data"
Then the `reminders` section holds 1 entry, `tasks` holds 2, `loans` holds 1 and `goals` holds 1
And each entry shows every field the contact page shows for that record
And times appear in ISO 8601 format in UTC, and date-only values appear as `YYYY-MM-DD`

**Scenario: Deleted records do not appear**
Given a vault member has deleted one of the contact's 2 tasks
And the user is on that contact page
When the user selects "Export all data"
Then the `tasks` section holds only the 1 remaining task

**Scenario: A completed task keeps its state**
Given the user is on the contact page of a contact with one open task and one completed task
When the user selects "Export all data"
Then the `tasks` section holds both tasks
And each entry shows whether the task is completed

**Scenario: A loan that names another contact shows only that contact's name and ID**
Given the user is on the contact page of a contact with a loan that names a second contact
When the user selects "Export all data"
Then the loan entry shows the second contact's full name and Monica ID
And the file contains no other data about the second contact

**Scenario: A contact with none of these records still exports**
Given the user is on the contact page of a contact that has only a name
When the user selects "Export all data"
Then the `reminders`, `tasks`, `loans` and `goals` keys are all present
And each of them holds an empty array `[]`

### Out of scope

- Any other data about contacts that a loan names
- Gifts. The spec leaves them out of phase 1 unless the team finds per-contact gift data that users expect

---

## 5. Add calls, life events, mood tracking, pets and quick facts to the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The export file includes the contact's calls, life events, mood tracking events, pets and
quick facts.

### Job to be done

When a user has logged the history of a relationship, a user who wants a personal backup
needs that history in the export, so the record of what happened stays with them.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact page. The browser saves the file.
**Screen/component:** The downloaded export file. The contact page does not change.

### Context

These records are the history and small facts a user builds up over time. Each life
event belongs to a timeline event. A timeline event is an entry on the contact's timeline
that groups one or more life events. A life event can involve other contacts. The export
shows only their name and Monica ID.

### Designs

No designs needed. This ticket changes only the file contents.

### Acceptance criteria

**Scenario: Calls, life events, mood, pets and quick facts appear in the file**
Given the user is on the contact page of a contact with 2 calls, 1 life event, 1 mood tracking event, 1 pet and 1 quick fact
When the user selects "Export all data"
Then the `calls` section holds 2 entries, and `life_events`, `mood_tracking_events`, `pets` and `quick_facts` each hold 1
And each entry shows every field the contact page shows for that record
And lookup values, such as the call reason, the mood and the pet category, appear as readable names

**Scenario: Each life event shows its timeline event**
Given the user is on the contact page of a contact with two life events in one timeline event
When the user selects "Export all data"
Then both entries in `life_events` show the same timeline event

**Scenario: Deleted records do not appear**
Given a vault member has deleted one of the contact's 2 calls
And the user is on that contact page
When the user selects "Export all data"
Then the `calls` section holds only the 1 remaining call

**Scenario: A life event with other contacts shows only their names and IDs**
Given the user is on the contact page of a contact with a life event that involves a second contact
When the user selects "Export all data"
Then the life event entry shows the second contact's full name and Monica ID
And the file contains no other data about the second contact

**Scenario: A contact with none of these records still exports**
Given the user is on the contact page of a contact that has only a name
When the user selects "Export all data"
Then the `calls`, `life_events`, `mood_tracking_events`, `pets` and `quick_facts` keys are all present
And each of them holds an empty array `[]`

### Out of scope

- Any other data about contacts that a life event involves
- The contact's activity feed

---

## 6. Add relationships to the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The export file lists each of the contact's relationships with the relationship type and
the related contact's name and Monica ID.

### Job to be done

When a user has mapped how people in their life connect, a user who wants to reuse their
data needs those links in the export, so they can rebuild them in another tool.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact page. The browser saves the file.
**Screen/component:** The downloaded export file. The contact page does not change.

### Context

Relationships link two contacts. The PM decided that each entry holds only the
relationship type, the related contact's full name and their Monica ID. More data about
the related contact would turn a one-contact export into a multi-contact export.

### Designs

No designs needed. This ticket changes only the file contents.

### Acceptance criteria

**Scenario: Relationships appear with type, name and ID**
Given contact A has a "Partner" relationship with contact B
And the user is on contact A's page
When the user selects "Export all data"
Then the `relationships` section holds 1 entry
And that entry holds only the relationship type "Partner", contact B's full name and contact B's Monica ID

**Scenario: No other data about the related contact appears**
Given contact A has a relationship with contact B
And contact B has notes, important dates and phone numbers
And the user is on contact A's page
When the user selects "Export all data"
Then the file contains none of contact B's notes, important dates or phone numbers

**Scenario: Removed relationships do not appear**
Given a vault member has removed one of contact A's 2 relationships
And the user is on contact A's page
When the user selects "Export all data"
Then the `relationships` section holds only the 1 remaining relationship

**Scenario: A contact with no relationships still exports**
Given the user is on the contact page of a contact with no relationships
When the user selects "Export all data"
Then the `relationships` key is present and holds an empty array `[]`

### Out of scope

- Any data about the related contact beyond their full name and Monica ID

---

## 7. Add photo and document details to the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The export file lists the contact's photos and documents by name, type, size and upload
time, without the files themselves.

### Job to be done

When a user has attached files to a contact, a user who wants a personal backup needs to
know which files exist, so they can check what they must save by hand.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact page. The browser saves the file.
**Screen/component:** The downloaded export file. The contact page does not change.

### Context

The PM decided that phase 1 lists photos and documents but does not include the files.
Including them would need a ZIP archive and larger downloads. Some instances store files
in Uploadcare, an external file-hosting service. The export must not contact Uploadcare
and must not read the files at all.

### Designs

No designs needed. This ticket changes only the file contents.

### Acceptance criteria

**Scenario: Photos and documents appear as details only**
Given the user is on the contact page of a contact with 2 photos and 1 document
When the user selects "Export all data"
Then the `photos` section holds 2 entries and the `documents` section holds 1
And each entry holds only the file name, MIME type (the file-format label, such as `image/jpeg`), size in bytes and upload time
And the file contains no file contents and no download URLs

**Scenario: The export succeeds when the stored files are unreachable**
Given the stored files for a contact's photos and documents are missing or unreachable
And the user is on that contact page
When the user selects "Export all data"
Then the download succeeds
And the `photos` and `documents` sections still list every photo and document

**Scenario: The export does not contact Uploadcare**
Given an instance stores files in Uploadcare
And the instance operator records the instance's outbound network traffic
And a vault member is on the page of a contact with photos
When the vault member selects "Export all data"
Then the recording shows no request to Uploadcare

**Scenario: A contact with no photos or documents still exports**
Given the user is on the contact page of a contact with no photos and no documents
When the user selects "Export all data"
Then the `photos` and `documents` keys are both present
And each of them holds an empty array `[]`

### Out of scope

- The photo and document files themselves
- A ZIP archive of the export
