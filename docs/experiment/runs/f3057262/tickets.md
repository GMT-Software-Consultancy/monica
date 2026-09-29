# Full contact export — Tickets
Generated: 29 September 2026
Spec: output/spec.md
Tracker: none (`ticket_tool: none` in `.claude/skill-config.md`; tickets are not posted, and this file is the ticket set)
Initiative: Provable trust (assumed; the spec has no `Initiative:` field — see "Assumptions made during decomposition")

## Terms used in these tickets

- **Vault:** a shared workspace that holds contacts. Each vault member has one of three permission levels: manager, editor or viewer.
- **Contact page:** the page in Monica that shows everything about one contact.
- **vCard:** the standard address-book file format. The contact page already has a vCard download link.
- **CardDAV:** the protocol that address-book apps use to sync contacts with Monica.
- **JSON (JavaScript Object Notation):** a text format that most programming languages can read.
- **Export file:** the JSON file that the "Export" link on the contact page downloads.
- **Account type name:** the name of a value that the account defines in its settings, such as a gender, pronoun, relationship type, address type or call reason. The export shows the name the user sees on the contact page, in the user's language.
- **ISO 8601:** the international date and time format, for example `2026-09-29T14:05:00Z`.
- **Other contact:** any contact who is not the exported contact but is linked to it, for example a sister in a relationship.

## Ticket order

| # | Title | Type | Depends on |
| - | ----- | ---- | ---------- |
| 1 | Download a contact export from the contact page | Feature | None |
| 2 | Include contact information, addresses and important dates in the export | Feature | 1 |
| 3 | Include notes, reminders, calls and tasks in the export | Feature | 1 |
| 4 | Include relationships, loans, gifts and shared events in the export | Feature | 1 |
| 5 | Include pets, goals, moods, quick facts, labels and groups in the export | Feature | 1 |
| 6 | List photos, documents and the avatar in the export | Feature | 1 |
| 7 | Warn developers when a new type of contact data is missing from the export | Chore | 2, 3, 4, 5, 6 |

Tickets 2 to 6 do not depend on each other. The team can build them in any order or at the same time.

---

## 1. Download a contact export from the contact page

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** None

### Summary
Vault members can download one JSON export file of a contact's own details from the contact page.

### Job to be done
When a vault member wants their own copy of what Monica holds about a person, they need to download it as one file from that person's contact page, so they can keep it or use it in other tools.

### Navigation context
**Entry point(s):** Vault → Contacts list → a contact's contact page.
**Exit point(s):** The user stays on the contact page. The browser saves the export file to the user's device.
**Screen/component:** The contact page, in the place where the vCard download link sits today. The new "Export" link sits next to it.

### Context
Monica promises "your data is yours". The product strategy names "provable trust" as a required capability. Today the vCard download holds only address-book fields, so users cannot take a full copy of a contact out of Monica. This ticket delivers the "Export" link, the file, its top-level shape and the contact's own details. Tickets 2 to 6 each add one group of data to the same file. Any vault member who can view a contact can export it, including viewers.

### Designs
No designs needed. The "Export" link uses the same style as the vCard download link and sits next to it.

### Acceptance criteria

**Scenario: Every vault member sees the Export link next to the vCard link**
Given a vault member with manager, editor or viewer permission is on the vault's contacts list
When they open a contact's contact page
Then they see an "Export" link next to the vCard download link
And the "Export" link has the same style as the vCard download link

**Scenario: A vault viewer downloads the export file**
Given a vault member with viewer permission is on the contact page of "Jane Doe"
When they click "Export"
Then the browser downloads one file named with the contact's name and today's date, for example `jane-doe-2026-09-29.json`
And the file opens as valid JSON
And the file contains `format_version` with the value `1`
And the file contains `exported_at` with the date and time of the export in ISO 8601
And the file contains a `contact` part
And the user is still on the contact page

**Scenario: The export shows the contact's own details**
Given a vault member is on the contact page of a contact with a prefix, first, middle and last name, nickname, maiden name, suffix, gender, pronoun, religion, job position and company
When they click "Export"
Then the `contact` part of the downloaded file shows the contact's ID
And it shows all seven name fields, the job position and the company name
And it shows the gender, pronoun and religion as the account type names the user sees on the contact page, in the user's language

**Scenario: The vCard download still gives the same file**
Given a vault member is on a contact page
When they click the vCard download link
Then the browser downloads the same vCard file that it downloaded before this change

**Scenario: CardDAV sync still works**
Given a user syncs their Monica contacts to an address-book app over CardDAV
When the app syncs after this change ships
Then the app receives the same contact data that it received before this change

**Scenario: A user who is not a vault member cannot export**
Given a signed-in user is not a member of the vault that holds "Jane Doe"
When they open the export address for "Jane Doe" directly
Then they receive no export file
And they see the same response that the vCard download gives them for "Jane Doe"

**Scenario: A contact from another vault cannot be exported through this vault**
Given a vault member is signed in and a contact belongs to a different vault
When they open the export address for that contact through their own vault
Then they receive no export file
And they see the same response that the vCard download gives them for the same contact and vault

**Scenario: A deleted contact cannot be exported**
Given a vault member has deleted the contact "Jane Doe" and is on the vault's contacts list
When they open the export address for "Jane Doe" directly
Then they receive no export file
And they see the same response that the vCard download gives them for "Jane Doe"

**Scenario: A contact with only a first name**
Given a vault member is on the contact page of a contact who has only a first name
When they click "Export"
Then the browser downloads a valid JSON file
And the `contact` part still lists every contact field, with no value where the contact has none

**Scenario: The file name is safe when the contact's name has unusual characters**
Given a vault member is on the contact page of a contact named `Zoë / O'Brien?`
When they click "Export"
Then the downloaded file name contains the contact's name without characters that are unsafe in file names
And the file name ends with the export date and `.json`

**Scenario: Display preferences do not change the file**
Given a vault member has set their date format preference to day/month/year and is on a contact page
When they click "Export"
Then `exported_at` in the downloaded file still uses ISO 8601

**Scenario: The file does not contain raw vCard text**
Given a vault member is on the contact page of a contact that an address-book app synced into Monica over CardDAV
When they click "Export"
Then the downloaded file contains no raw vCard text
And the contact's names appear only as the separate name fields in the `contact` part

### Out of scope
- The lists of related data. Tickets 2 to 6 add them.
- Any change to the vCard download or to CardDAV sync.
- Exporting more than one contact, a whole vault or a whole account.
- Importing the file back into Monica.
- An API endpoint for the export.
- Scheduled or automatic exports.
- Human-readable formats such as PDF or HTML.
- Published documentation of the file's shape.
- Tracking or success metrics.

---

## 2. Include contact information, addresses and important dates in the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary
The export file lists every piece of contact information, every address and every important date that belongs to the contact.

### Job to be done
When a vault member exports a contact, they need the contact's phone numbers, email addresses, postal addresses and dates in the file, so they can reach and remember the person without Monica.

### Navigation context
**Entry point(s):** Vault → Contacts list → a contact's contact page → "Export" link.
**Exit point(s):** The user stays on the contact page. The browser saves the export file.
**Screen/component:** The contact page and the downloaded export file.

### Context
Contact information means phone numbers, email addresses, social profiles and similar entries. These are the details people most often need outside Monica. The vCard holds some of them, but not their Monica types or the important dates. Each record keeps its ID, so scripts can match records across two exports. Monica lets a user save an important date without a year, and the file must keep that difference.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: The export lists contact information, addresses and important dates with their types**
Given a vault member is on the contact page of a contact with a phone number, an email address, a social profile, two addresses and a birthday
When they click "Export"
Then the downloaded file has a contact information list with the phone number, email address and social profile
And each contact information entry shows its record ID, its value and its type as the account type name
And the file has an address list with both addresses, each with its record ID, all its address fields and its address type name
And the file has an important dates list with the birthday, its record ID and its date type name
And the birthday uses ISO 8601

**Scenario: Another contact's details do not appear**
Given a vault member is on the contact page of "Jane Doe", and another contact in the same vault has a phone number and an address
When they click "Export"
Then the downloaded file does not contain the other contact's phone number or address

**Scenario: An address shared with another contact appears**
Given a vault member is on the contact page of "Jane Doe", who shares a home address with another contact in the vault
When they click "Export"
Then the downloaded file lists the shared address in Jane Doe's address list
And the file contains no other details about the other contact who shares the address

**Scenario: An important date without a year**
Given a vault member is on the contact page of a contact whose birthday has a day and a month but no year
When they click "Export"
Then the downloaded file shows the birthday's day and month
And the file shows no year for the birthday and does not invent one

**Scenario: A contact with none of these records**
Given a vault member is on the contact page of a contact with no contact information, no addresses and no important dates
When they click "Export"
Then the downloaded file contains a contact information list, an address list and an important dates list
And all three lists are empty

### Out of scope
- Any other type of related data. Tickets 3 to 6 cover them.
- Changing how the contact page shows these records.
- Map or geocoding data from external services. The export uses only what Monica already stores.

---

## 3. Include notes, reminders, calls and tasks in the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary
The export file lists every note, reminder, call and task that belongs to the contact.

### Job to be done
When a vault member exports a contact, they need the notes and activity history they recorded about the person, so they keep what they know about that person even outside Monica.

### Navigation context
**Entry point(s):** Vault → Contacts list → a contact's contact page → "Export" link.
**Exit point(s):** The user stays on the contact page. The browser saves the export file.
**Screen/component:** The contact page and the downloaded export file.

### Context
Notes are the main reason people keep relationship data in Monica, and the vCard does not include them. In a shared vault, other members may write notes about the same contact. Any vault member who can view the contact already sees those notes, so the export includes them with the author's name. Each record keeps its ID, so scripts can match records across two exports.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: The export lists notes, reminders, calls and tasks**
Given a vault member is on the contact page of a contact with one note, one yearly reminder, one call and one task
When they click "Export"
Then the downloaded file has a notes list with the note's record ID, title, body, author name, and the dates it was created and last changed
And the file has a reminders list with the reminder's record ID, its date and how often it repeats
And the file has a calls list with the call's record ID, its details and its call reason as the account type name
And the file has a tasks list with the task's record ID and its details
And every date and time uses ISO 8601

**Scenario: Another contact's notes and reminders do not appear**
Given a vault member is on the contact page of "Jane Doe", and another contact in the same vault has notes and reminders
When they click "Export"
Then the downloaded file does not contain the other contact's notes or reminders

**Scenario: A viewer exports notes that other members wrote**
Given a vault member with viewer permission is on the contact page of a contact with a note that a vault editor wrote
When they click "Export"
Then the downloaded file lists that note
And the note shows the editor's name as its author

**Scenario: A contact with none of these records**
Given a vault member is on the contact page of a contact with no notes, reminders, calls or tasks
When they click "Export"
Then the downloaded file contains a notes list, a reminders list, a calls list and a tasks list
And all four lists are empty

### Out of scope
- Journal posts that mention the contact. They belong to the vault's journal, not to the contact.
- The contact's activity feed, which is Monica's log of changes such as "contact created".
- Any other type of related data. Tickets 2 and 4 to 6 cover them.

---

## 4. Include relationships, loans, gifts and shared events in the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary
The export file lists the contact's relationships, loans, gifts, timeline events and life events, and shows other contacts only by ID, display name and connection.

### Job to be done
When a vault member exports a contact, they need to see who the person is connected to and what they shared, so they keep the person's social context without exposing other people's private details.

### Navigation context
**Entry point(s):** Vault → Contacts list → a contact's contact page → "Export" link.
**Exit point(s):** The user stays on the contact page. The browser saves the export file.
**Screen/component:** The contact page and the downloaded export file.

### Context
These data types link the exported contact to other contacts. The export must name those other contacts without leaking their own data. Each other contact appears only as their ID, their display name and how they connect to the exported contact. The display name is the name Monica shows for that contact. A timeline event is a group of life events, such as "Trip to Spain" with a flight and a hotel stay inside it. Monica has no screen to record gifts today, so the gifts list will usually be empty.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: The export lists relationships, loans and shared events**
Given a vault member is on the contact page of "Jane Doe", who has a sister "Sarah Doe", lent 50 EUR to "Tom Smith", and shares a life event with "Sarah Doe"
When they click "Export"
Then the downloaded file has a relationships list with Sarah Doe's ID, her display name and the relationship type name "sister"
And the file has a loans list with the loan's record ID, the amount `50`, the currency code `EUR`, and that Jane Doe is the lender
And the loan shows Tom Smith by ID and display name as the borrower
And the file has a timeline events list with the timeline event, the life event inside it, and Sarah Doe by ID and display name as a participant
And every date and time uses ISO 8601

**Scenario: The export lists gifts with the other party**
Given a vault member is on the contact page of "Jane Doe", who gave a gift to "Tom Smith"
When they click "Export"
Then the downloaded file has a gifts list with the gift's record ID and details
And the gift shows that Jane Doe gave it
And the gift shows Tom Smith by ID and display name as the receiver

**Scenario: Other contacts' private details do not appear**
Given a vault member is on the contact page of "Jane Doe", and her sister "Sarah Doe" has notes, important dates and a phone number in Monica
When they click "Export"
Then the downloaded file shows Sarah Doe only as her ID, her display name and her connection to Jane Doe
And the file contains none of Sarah Doe's notes, dates, contact information or other fields

**Scenario: A contact who borrows as well as lends**
Given a vault member is on the contact page of "Jane Doe", who lent money to "Tom Smith" and borrowed a book from "Sarah Doe"
When they click "Export"
Then the downloaded file lists both loans
And each loan shows whether Jane Doe lent or borrowed

**Scenario: A contact with none of these records**
Given a vault member is on the contact page of a contact with no relationships, loans, gifts or timeline events
When they click "Export"
Then the downloaded file contains a relationships list, a loans list, a gifts list and a timeline events list
And all four lists are empty

### Out of scope
- Any data about other contacts beyond their ID, display name and connection.
- A way to record gifts in Monica.
- Any other type of related data. Tickets 2, 3, 5 and 6 cover them.

---

## 5. Include pets, goals, moods, quick facts, labels and groups in the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary
The export file lists the contact's pets, goals, mood tracking events, quick facts, labels and groups.

### Job to be done
When a vault member exports a contact, they need the smaller personal details they recorded about the person, so the file is a complete record and not just the main facts.

### Navigation context
**Entry point(s):** Vault → Contacts list → a contact's contact page → "Export" link.
**Exit point(s):** The user stays on the contact page. The browser saves the export file.
**Screen/component:** The contact page and the downloaded export file.

### Context
The spec promises every record Monica stores about the contact. These are the remaining data types on the contact page. A goal has streaks, which are the dates the user marked the goal as done. A mood tracking event records how the person felt, with a mood label the vault defines. Labels and groups are vault-wide, so the export shows them by name only. Each record keeps its ID, so scripts can match records across two exports.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: The export lists pets, goals, moods, quick facts, labels and groups**
Given a vault member is on the contact page of a contact with a pet, a goal with two streaks, a mood tracking event, a quick fact, a label and a group
When they click "Export"
Then the downloaded file has a pets list with the pet's record ID, its name and its pet category name
And the file has a goals list with the goal's record ID and both streak dates
And the file has a mood tracking events list with the event's record ID, its date and its mood label
And the file has a quick facts list with the quick fact's record ID and its text
And the file has a labels list and a groups list, each showing the label or group by name
And every date uses ISO 8601

**Scenario: Another contact's records do not appear**
Given a vault member is on the contact page of "Jane Doe", and another contact in the same vault has a pet, a goal and a mood tracking event
When they click "Export"
Then the downloaded file does not contain the other contact's pet, goal or mood tracking event

**Scenario: A label that other contacts also use**
Given a vault member is on the contact page of "Jane Doe", who has the label "Family" that ten other contacts in the vault also have
When they click "Export"
Then the downloaded file lists the label "Family"
And the file does not name the other contacts that have the label

**Scenario: A contact with none of these records**
Given a vault member is on the contact page of a contact with no pets, goals, mood tracking events, quick facts, labels or groups
When they click "Export"
Then the downloaded file contains a pets list, a goals list, a mood tracking events list, a quick facts list, a labels list and a groups list
And all six lists are empty

### Out of scope
- Life metric values that mention the contact. They belong to the vault, not to the contact.
- The members of a group other than the exported contact.
- Any other type of related data. Tickets 2, 3, 4 and 6 cover them.

---

## 6. List photos, documents and the avatar in the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary
The export file lists the contact's photos, documents and avatar by file name and upload date, without the files themselves.

### Job to be done
When a vault member exports a contact, they need to know which photos and documents Monica holds for the person, so they can see what else to save by hand.

### Navigation context
**Entry point(s):** Vault → Contacts list → a contact's contact page → "Export" link.
**Exit point(s):** The user stays on the contact page. The browser saves the export file.
**Screen/component:** The contact page and the downloaded export file.

### Context
Monica stores uploaded files with an external file storage service. Putting the files in the export would need a different file format and calls to that service. So the export only lists each file by name and upload date. The export must never contact an external service, because Monica promises that relationship data stays private. The export must also work when that service is down.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: The export lists photos, documents and the avatar**
Given a vault member is on the contact page of a contact with two photos, one document and an uploaded avatar
When they click "Export"
Then the downloaded file has a photos list with both photos, each with its record ID, file name and upload date
And the file has a documents list with the document's record ID, file name and upload date
And the file shows the avatar's file name and upload date
And every upload date uses ISO 8601

**Scenario: The file contains no file content and no web addresses**
Given a vault member is on the contact page of a contact with photos, documents and an uploaded avatar
When they click "Export"
Then the downloaded file contains no content from any photo, document or avatar
And the file contains no web address for any photo, document or avatar

**Scenario: The export works when the file storage service is down**
Given the external file storage service is unreachable, and a vault member is on the contact page of a contact with photos
When they click "Export"
Then the browser downloads the export file
And the file lists every photo, document and avatar with its file name and upload date

**Scenario: A contact with no uploaded files**
Given a vault member is on the contact page of a contact with no photos, no documents and no uploaded avatar
When they click "Export"
Then the downloaded file contains a photos list and a documents list, and both are empty
And the file shows that the contact has no uploaded avatar

### Out of scope
- The photo, document and avatar files themselves.
- Links to download the files.
- Any other type of related data. Tickets 2 to 5 cover them.

---

## 7. Warn developers when a new type of contact data is missing from the export

**Type:** Chore
**Initiative:** Provable trust
**Depends on:** 2, 3, 4, 5, 6

### Summary
Make the automated test suite fail when a developer adds a new type of contact data but does not add it to the export.

### Why now
The export promises every record Monica stores about a contact. Monica keeps adding new types of contact data. Without a check, a new type can ship without reaching the export, and the export quietly stops being complete. This check protects that promise after Tickets 2 to 6 ship.

### Definition of done
- [ ] Adding a new type of contact data without adding it to the export makes the automated test suite fail.
- [ ] The failure message names the missing data type.
- [ ] The data types the spec leaves out on purpose do not make the check fail. These are the activity feed, journal posts and life metric values.
- [ ] Every data type in Tickets 2 to 6 passes the check.

---

## Assumptions made during decomposition

- **Initiative.** The spec has no `Initiative:` field, and this run could not ask the PM. All tickets use "Provable trust", the strategy capability the spec names in "Why now". The PM should confirm or replace it.
- **Open questions from the spec.** This run treated them as approved. The work assumes no in-flight work touches the contact page or the vCard download, because `docs/iterations/current.md` does not exist. The activity feed stays out.
- **Data type check.** The spec's "Next step" asked a developer to check its data type list against the Contact model. This run did that check. Every relation on the Contact model is in the export, except journal posts and life metrics, which the spec excludes on purpose.
- **Gifts.** The Contact model has no gifts relation, and Monica has no gift model or screen to record gifts. The gifts table links a gift to a giver contact and a receiver contact. Ticket 4 includes the gifts list as the spec asks, but it will be empty for real users until Monica can record gifts. The PM may choose to drop gifts from the export instead.
- **Split into six features.** The export is split by group of data so each ticket is small and testable. Ticket 1 alone gives a file with only the contact's own details. The team decides whether to release the "Export" link before Tickets 2 to 6 ship.
- **Empty contact fields.** The spec says empty lists stay in the file. Ticket 1 applies the same rule to the contact's own fields: every field appears, with no value where the contact has none.
- **Avatar.** A contact has one avatar, not a list. Ticket 6 shows it as a single entry, or shows that the contact has no uploaded avatar.
- **Personas.** `docs/product/personas.md` does not exist. The tickets use "vault member" and the permission levels from the spec in place of persona names.
