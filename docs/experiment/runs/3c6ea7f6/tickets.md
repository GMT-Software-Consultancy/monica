# Full contact export — Tickets

Generated: 29 September 2026
Spec: output/spec.md
Tracker: none (`ticket_tool: none` in `.claude/skill-config.md`). These tickets are not posted to any tracker.
Initiative: Provable trust (assumed — the spec has no `Initiative:` field; see "Assumptions made during decomposition")

## Ticket order

| #   | Title                                                                  | Type    | Depends on |
| --- | ---------------------------------------------------------------------- | ------- | ---------- |
| 1   | Let users download a contact's profile data as one export file         | Feature | None       |
| 2   | Include notes and relationships in the contact export                  | Feature | 1          |
| 3   | Include reminders, tasks, calls and loans in the contact export        | Feature | 1          |
| 4   | Include pets, goals, life events, moods and quick facts in the export  | Feature | 1          |
| 5   | Include labels, groups, and the photo and document list in the export  | Feature | 1          |

Tickets 2–5 do not depend on each other. The team can build them in any order, or at the same time.

---

## 1. Let users download a contact's profile data as one export file

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** None

### Summary

A user can choose "Export all data" on a contact's page and download one JSON file with that contact's profile, contact information, addresses and important dates.

### Job to be done

When a backup keeper wants a personal copy of what they know about someone, they need to download that contact's data as one file, so they can keep it outside Monica.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page. This includes archived contacts.
**Exit point(s):** The user stays on the contact's page. The browser downloads the file. If the export fails, the user stays on the contact's page and sees an error message.
**Screen/component:** The contact's page. The new "Export all data" option sits next to the existing "Download vCard" option.

### Context

Today the only download on a contact's page is the vCard. A vCard is a standard address-book file (`.vcf`) that holds names, phone numbers, email addresses and postal addresses only. Monica's strategy promises that users' data is theirs alone, and this ticket starts to let users check that promise. It also sets the rules for the whole file, which tickets 2–5 follow: file name, format version, empty lists, labels, dates, access and failure.

Terms used in this ticket:

- **JSON** is a common text format. People can read it in any text editor, and other software can read it too.
- **Vault** is a separate space of contacts that several users can share. Each member has one of three access levels: viewer (can only read), editor or manager.
- **Export format version** is a value in the file that names the version of the file's layout. Other tools use it to detect future changes.

Users should not see "Export all data" until tickets 2–5 also ship. The option promises all data. A file that is missing categories could look like a full backup when it is not.

### Designs

No designs needed. The option follows the same style as the existing "Download vCard" option. It adds no new screens.

### Acceptance criteria

**Scenario: The contact's page shows the export option next to the vCard option**
Given a user can view a contact in a vault
When the user opens the contact's page
Then the page shows an "Export all data" option next to the "Download vCard" option

**Scenario: The user downloads the export file with the contact's profile data**
Given the date is 29 September 2026
And the contact "Jane Doe" has a nickname, gender, pronoun, religion, job, company, phone number, email address, postal address and birthday
And the user is on Jane Doe's contact page
When the user chooses "Export all data"
Then the browser downloads one file named `jane-doe-2026-09-29.json`
And the file contains each of those items with the values shown on the contact's page
And the file states its export format version

**Scenario: The vCard download still works as before**
Given the user is on a contact's page
When the user chooses "Download vCard"
Then the browser downloads the same vCard file as before this change

**Scenario: The export fails and the user gets no file**
Given the user is on a contact's page
And Monica cannot produce the export file for that contact, for example because of a server error
When the user chooses "Export all data"
Then the contact's page shows an error message that says the export failed and asks the user to try again
And the browser downloads no file, and no partial file
And the contact's data on the page is the same as before

**Scenario: A user outside the vault cannot export the contact**
Given a user is not a member of the vault that holds the contact "Jane Doe"
When the user requests the export for Jane Doe directly, without opening the contact's page
Then the user sees the same "no access" result they get when they try to open Jane Doe's contact page
And the browser downloads no file

**Scenario: A vault viewer can export a contact**
Given a user has viewer access to a shared vault
And the user is on the page of a contact in that vault
When the user chooses "Export all data"
Then the browser downloads the export file for that contact

**Scenario: An archived contact exports the same way as an active contact**
Given the user is on the page of an archived contact
When the user chooses "Export all data"
Then the browser downloads the export file for that contact
And the file contains the same categories as the file for an active contact

**Scenario: A contact with only a name exports every category as an empty list**
Given the contact has a name and no other recorded data
And the user is on that contact's page
When the user chooses "Export all data"
Then the browser downloads a valid JSON file
And each category in this ticket appears in the file as an empty list

**Scenario: Custom labels appear as the user sees them**
Given the account has a custom contact information type named "Signal"
And the contact has a "Signal" entry
And the user is on that contact's page
When the user chooses "Export all data"
Then the file shows "Signal" as the type of that entry
And the file shows no internal code in place of the label

**Scenario: Dates use YYYY-MM-DD and keep a missing year missing**
Given the contact has a birthday of 14 March 1990 and an anniversary on 2 June with no year
And the user is on that contact's page
When the user chooses "Export all data"
Then the file shows the birthday as 1990-03-14
And the file shows the anniversary with its month and day and no year

**Scenario: The file keeps accents, non-Latin scripts and emoji exactly as written**
Given the contact has the nickname "Zoë 🌻" and an address in the city "Київ"
And the user is on that contact's page
When the user chooses "Export all data"
Then the file shows the nickname as "Zoë 🌻"
And the file shows the city as "Київ"

**Scenario: The file includes data that the page layout hides**
Given the contact has an address
And the contact's page layout does not show addresses
And the user is on that contact's page
When the user chooses "Export all data"
Then the file contains the contact's address

### Out of scope

- Notes and relationships (ticket 2).
- Reminders, tasks, calls and loans (ticket 3).
- Pets, goals, life events, mood tracking entries and quick facts (ticket 4).
- Labels, groups, and the list of photos and documents (ticket 5).
- Exporting more than one contact, a whole vault or a whole account.
- Importing the file into Monica or any other tool.
- Scheduled or automatic exports.
- Exporting through the Monica API.
- A human-readable layout of the file, such as PDF.
- Journal posts and the contact's activity history.
- Any change to the vCard download or to address-book sync with phones and other apps.
- Usage tracking.

---

## 2. Include notes and relationships in the contact export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The contact export file includes every note, with its author and date, and every relationship, with only the relationship type and the related contact's name.

### Job to be done

When a data reuser moves what they know about someone into a notes app, they need the contact's notes and relationships in the export, so they keep the history they wrote in Monica.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact's page. The browser downloads the file.
**Screen/component:** The export file that the "Export all data" option downloads. The page itself does not change.

### Context

Notes are the main thing users record in Monica, and the vCard cannot carry them. A relationship links this contact to another contact, for example "Sister: Jane Doe". The file names the related contact but carries no other data about them. This protects the related contact's data. Notes from other vault members appear with their author, because members already see note authors on the page.

### Designs

No designs needed. This ticket changes only the contents of the downloaded file.

### Acceptance criteria

**Scenario: The file contains every note with its author and date**
Given the contact has three notes
And the user is on that contact's page
When the user chooses "Export all data"
Then the file contains all three notes with the text shown on the contact's page
And each note shows the name of the user who wrote it and the date they wrote it

**Scenario: A relationship shows only the type and the related contact's name**
Given contact A has the relationship "Sister" with contact B
And contact B has notes, important dates and contact information
And the user is on contact A's page
When the user chooses "Export all data"
Then the file shows the relationship type "Sister" and contact B's name
And the file contains no notes, important dates, contact information or other data that belongs to contact B

**Scenario: A vault viewer exports notes that another member wrote**
Given a user has viewer access to a shared vault
And another vault member wrote a note on a contact in that vault
And the viewer is on that contact's page
When the viewer chooses "Export all data"
Then the browser downloads the export file
And the file shows that note with its author's name and creation date

**Scenario: The export fails if Monica cannot include the notes**
Given the user is on a contact's page
And Monica cannot read that contact's notes while it builds the file
When the user chooses "Export all data"
Then the contact's page shows an error message that says the export failed and asks the user to try again
And the browser downloads no file, and no partial file

**Scenario: A contact with many notes downloads within 10 seconds**
Given the contact has 1,000 notes
And the user is on that contact's page
When the user chooses "Export all data"
Then the download starts within 10 seconds
And the file contains all 1,000 notes

**Scenario: A custom relationship type appears as the user sees it**
Given the account has a custom relationship type named "Godmother"
And the contact has a "Godmother" relationship with another contact
And the user is on that contact's page
When the user chooses "Export all data"
Then the file shows "Godmother" as the relationship type

**Scenario: A contact with no notes or relationships shows empty lists**
Given the contact has no notes and no relationships
And the user is on that contact's page
When the user chooses "Export all data"
Then the file shows notes as an empty list
And the file shows relationships as an empty list

### Out of scope

- Any data about the related contact other than their name.
- Journal posts, even when they mention the contact.
- The contact's activity history.
- Changing who can see note authors on the contact's page.
- The engineering team may challenge the 10-second target at plan review. Any change to it needs PM agreement.

---

## 3. Include reminders, tasks, calls and loans in the contact export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The contact export file includes every reminder, task, call and loan recorded for the contact.

### Job to be done

When a backup keeper keeps a personal copy of a contact, they need the reminders, tasks, calls and loans in it, so they do not lose track of what they owe or promised that person.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact's page. The browser downloads the file.
**Screen/component:** The export file that the "Export all data" option downloads. The page itself does not change.

### Context

These categories record what the user plans to do with a person and what happened between them. A backup without them is not complete. A loan can involve another contact. The file follows the same rule as relationships: it names the other contact and carries no other data about them.

### Designs

No designs needed. This ticket changes only the contents of the downloaded file.

### Acceptance criteria

**Scenario: The file contains every reminder, task, call and loan**
Given the contact has one reminder, one task, one logged call and one loan
And the user is on that contact's page
When the user chooses "Export all data"
Then the file contains each of those items with the values shown on the contact's page

**Scenario: The export fails if Monica cannot include the loans**
Given the user is on a contact's page
And Monica cannot read that contact's loans while it builds the file
When the user chooses "Export all data"
Then the contact's page shows an error message that says the export failed and asks the user to try again
And the browser downloads no file, and no partial file

**Scenario: Completed tasks appear alongside open tasks**
Given the contact has one open task and one completed task
And the user is on that contact's page
When the user chooses "Export all data"
Then the file contains both tasks
And the file shows which task is completed

**Scenario: A loan with another contact names that contact only**
Given contact A lent money to contact B
And contact B has notes and contact information
And the user is on contact A's page
When the user chooses "Export all data"
Then the file shows the loan with contact B's name
And the file contains no notes, contact information or other data that belongs to contact B

**Scenario: A custom call reason appears as the user sees it**
Given the account has a custom call reason named "Catch-up"
And the contact has a logged call with the reason "Catch-up"
And the user is on that contact's page
When the user chooses "Export all data"
Then the file shows "Catch-up" as the reason for that call

**Scenario: A contact with none of these items shows empty lists**
Given the contact has no reminders, tasks, calls or loans
And the user is on that contact's page
When the user chooses "Export all data"
Then the file shows reminders, tasks, calls and loans as four empty lists

### Out of scope

- Reminders or tasks that belong to the vault and not to this contact.
- Any data about another contact in a loan other than their name.
- Changing how reminders send notifications.

---

## 4. Include pets, goals, life events, moods and quick facts in the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The contact export file includes every pet, goal, life event, mood tracking entry and quick fact recorded for the contact.

### Job to be done

When a data reuser loads what they know about someone into a spreadsheet or a script, they need the contact's pets, goals, life events, moods and quick facts in the export, so the new tool holds the full picture.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact's page. The browser downloads the file.
**Screen/component:** The export file that the "Export all data" option downloads. The page itself does not change.

### Context

These categories hold the personal details that make Monica more than an address book. Terms used in this ticket:

- **Life event** is a dated event in the contact's life, such as a graduation or a marriage.
- **Mood tracking entry** is a dated record of the contact's mood, using a mood label the vault defines.
- **Quick fact** is a short fact shown at the top of a contact's page.

Page layouts often hide some of these categories, so the file must include them even when the page does not show them.

### Designs

No designs needed. This ticket changes only the contents of the downloaded file.

### Acceptance criteria

**Scenario: The file contains every pet, goal, life event, mood and quick fact**
Given the contact has one pet, one goal, one life event, one mood tracking entry and one quick fact
And the user is on that contact's page
When the user chooses "Export all data"
Then the file contains each of those items with the values shown on the contact's page

**Scenario: The export fails if Monica cannot include the life events**
Given the user is on a contact's page
And Monica cannot read that contact's life events while it builds the file
When the user chooses "Export all data"
Then the contact's page shows an error message that says the export failed and asks the user to try again
And the browser downloads no file, and no partial file

**Scenario: Custom mood labels and quick fact names appear as the user sees them**
Given the vault has a custom mood label named "Energised"
And the vault has a custom quick fact named "Favourite tea"
And the contact has an "Energised" mood entry and a "Favourite tea" quick fact
And the user is on that contact's page
When the user chooses "Export all data"
Then the file shows "Energised" as the mood of that entry
And the file shows "Favourite tea" as the name of that quick fact

**Scenario: The file includes pets that the page layout hides**
Given the contact has a pet
And the contact's page layout does not show pets
And the user is on that contact's page
When the user chooses "Export all data"
Then the file contains the contact's pet

**Scenario: A contact with none of these items shows empty lists**
Given the contact has no pets, goals, life events, mood tracking entries or quick facts
And the user is on that contact's page
When the user chooses "Export all data"
Then the file shows pets, goals, life events, mood tracking entries and quick facts as five empty lists

### Out of scope

- Mood reports and other reports built across many contacts.
- Journal posts, even when they mention a life event.
- Any change to how the page layout shows or hides these categories.

---

## 5. Include labels, groups, and the photo and document list in the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The contact export file includes the labels and groups the contact belongs to, and lists each photo and document by file name and upload date.

### Job to be done

When a backup keeper keeps a personal copy of a contact, they need to see which labels, groups, photos and documents belong to that contact, so they know what else to save.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact's page. The browser downloads the file.
**Screen/component:** The export file that the "Export all data" option downloads. The page itself does not change.

### Context

Labels and groups show how the user organises their contacts. Phase 1 lists photos and documents by name only. Including the files themselves would turn the download into a bundle and add new ways for it to fail. A later phase may add the files if users ask for them. Only after this ticket ships does the file hold every category the spec lists.

### Designs

No designs needed. This ticket changes only the contents of the downloaded file.

### Acceptance criteria

**Scenario: The file contains labels, groups, photos and documents**
Given the contact has two labels, belongs to one group, and has one photo and one document
And the user is on that contact's page
When the user chooses "Export all data"
Then the file shows the names of both labels and the name of the group
And the file lists the photo and the document, each with its file name and upload date

**Scenario: The export fails if Monica cannot include the photo and document list**
Given the user is on a contact's page
And Monica cannot read that contact's photos and documents while it builds the file
When the user chooses "Export all data"
Then the contact's page shows an error message that says the export failed and asks the user to try again
And the browser downloads no file, and no partial file

**Scenario: Photos and documents appear by name only**
Given the contact has a photo named "beach-2025.jpg" uploaded on 3 August 2025
And the user is on that contact's page
When the user chooses "Export all data"
Then the file shows "beach-2025.jpg" with the upload date 2025-08-03
And the file contains no image content or other details of that photo

**Scenario: A contact with no labels, groups, photos or documents shows empty lists**
Given the contact has no labels, no groups, no photos and no documents
And the user is on that contact's page
When the user chooses "Export all data"
Then the file shows labels, groups, photos and documents as four empty lists

### Out of scope

- The photo and document files themselves (a candidate for Phase 2).
- Data about the other members of a group.
- Files that belong to the vault but not to this contact.

---

## Assumptions made during decomposition

- **Initiative.** The spec has no `Initiative:` field. The skill says to ask the PM, but this run was non-interactive. I used "Provable trust", because the spec says the feature supports that strategy capability. The PM should confirm or replace it.
- **No tracker.** `ticket_tool` is `none`, so I posted nothing. I did not change the spec's `Status:` or `Tickets:` fields, because there are no tracker IDs to add.
- **Split by category group.** The spec lists 18 categories. One ticket for all of them would be too large, so tickets 2–5 each add a group of categories to the file that ticket 1 creates. Each ticket changes what the user sees in the file.
- **Release timing.** The option promises "all data". Ticket 1 states that users should not see the option until tickets 2–5 ship. The team decides how to achieve that.
- **One format version.** Tickets 1–5 together define the first export format version. The version does not change between these tickets, because users do not see the option until all five ship.
- **No partial file per category.** The spec forbids partial files. Each of tickets 2–5 carries its own failure scenario: if Monica cannot include that category, the whole export fails with no file.
- **Loans with another contact.** The spec does not say how loans show the other contact. I applied the spec's relationship rule: the file names the other contact and carries no other data about them (ticket 3).
- **Completed tasks.** "Every item recorded" includes completed tasks, not only open ones (ticket 3).
- **Access check.** Ticket 1 includes a scenario for a user outside the vault. The spec's technical notes require the export to respect vault access rules.
- **Speed target.** The 10-second target for 1,000 notes sits in ticket 2, because notes drive the size of the file. It stays a working target until engineering confirms it at plan review.
- **No Spike and no Chore.** The spec leaves no approach unknown, and no ticket needs work that users cannot observe. The team may still find a Chore at plan review.
- **Personas.** `docs/product/personas.md` does not exist. The job statements use the spec's plain user descriptions: backup keeper and data reuser.
- **In-flight work.** `docs/iterations/current.md` does not exist, so I could not check for conflicting work.

## Recommended next step

Confirm the initiative. Then take ticket 1 to plan review. At that review, engineering should confirm the 10-second target and check for conflicting work in progress. After ticket 1, the team can build tickets 2–5 in parallel.
