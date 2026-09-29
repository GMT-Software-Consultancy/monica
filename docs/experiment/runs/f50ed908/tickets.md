# Full contact export — Tickets

Generated: 29 September 2026
Spec: output/spec.md
Tracker: none (no tracker in this run, so no tickets were posted and no tracker IDs exist)
Initiative: Provable trust (assumed — see note below)

> **Initiative note:** The spec has no `Initiative:` field. This run is non-interactive, so we could not ask the PM.
> We linked every ticket to **Provable trust**, capability 4 in `docs/product/strategy.md`.
> The spec's "Why now" section ties the feature to that capability. The PM should confirm this before the tickets go into a tracker.

## Breakdown

| #   | Title                                                                  | Type    | Depends on |
| --- | ---------------------------------------------------------------------- | ------- | ---------- |
| 1   | Download a contact's profile details as a JSON file                    | Feature | None       |
| 2   | Include the contact's relationships in the export                      | Feature | 1          |
| 3   | Include notes, reminders and the contact's other records in the export | Feature | 1          |
| 4   | Include journal posts linked to the contact in the export              | Feature | 1          |
| 5   | List the contact's documents and photos in the export                  | Feature | 1          |

Tickets 2–5 do not depend on each other. The team can build them in parallel once ticket 1 ships.

---

## 1. Download a contact's profile details as a JSON file

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** None

### Summary

A Monica user can download one JSON file with a contact's profile details from the contact page.

### Job to be done

When a Monica user wants a backup of a contact, they need a full copy in one step, so they can keep or reuse it.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page. A vault is a separate space in Monica that holds contacts. Several users can share one vault.
**Exit point(s):** The user stays on the contact page. The browser saves the file to the user's device.
**Screen/component:** The contact page, in the same place as the existing vCard download. A vCard is a standard address-book file.

### Context

Today the only download on the contact page is the vCard. It holds names, phone numbers, email addresses, postal addresses and birthday, and nothing else. The strategy makes "provable trust" a core capability, so users must be able to check that their data is theirs. This ticket adds the "Export all data" action and the first part of the file. Tickets 2–5 add the remaining data types.

The file is JSON, a common, open text format. Other tools can read it, and a person can open it in any text editor. The file goes straight from the user's own Monica, hosted or self-hosted, to the user's browser. No third party receives contact data. This keeps the feature inside the strategy's privacy review gate.

We recommend that users do not see the "Export all data" action until tickets 2–5 ship. Before then, the file does not yet hold all data, so the label would be wrong.

### Designs

No designs needed. The action uses the existing button style and sits next to the vCard download.

### Acceptance criteria

**Scenario: User downloads a contact's export from the contact page**
Given the user is on the page of a contact in a vault they belong to
When they select "Export all data"
Then the browser downloads one JSON file
And the file name contains the contact's name and the export date

**Scenario: The file shows when and where the export came from**
Given the user has downloaded a contact's export from the contact page
When they open the file in a text editor
Then they see the date and time of the export
And they see the name of the vault that holds the contact

**Scenario: The file holds every profile detail Monica shows for the contact**
Given the user is on the page of a contact with names, a nickname, gender, pronouns, company, job title and religion
And the contact has contact information, addresses, labels, groups and important dates
When they select "Export all data"
Then the file contains every one of these records
And each record has the same text and dates that Monica shows for it

**Scenario: Dates in the file use one standard format**
Given the user has downloaded the export of a contact with a birthday and other important dates
When they open the file
Then every date is written as year-month-day
And every date that has a time also shows its time zone

**Scenario: User without access to the vault cannot export the contact**
Given the user is signed in to Monica
And they are not a member of the vault that holds a contact
When they try to download that contact's export directly, without going through the contact page
Then they see the same "no access" response that Monica shows for that contact's page
And no file downloads

**Scenario: Contact with only a name exports without an error**
Given the user is on the page of a contact that has only a name and no other records
When they select "Export all data"
Then the file downloads without an error
And each profile data type appears in the file as an empty list

**Scenario: Export works on a self-hosted Monica without file uploads**
Given the user is on a contact page in a self-hosted Monica that has no file-upload service set up
When they select "Export all data"
Then the file downloads without an error

**Scenario: The vCard download still works as before**
Given the user is on a contact's page
When they select the existing vCard download
Then the browser downloads the same vCard file, with the same contents, as it did before this ticket

### Out of scope

- Relationships (ticket 2).
- Notes, reminders, tasks, calls, loans, goals, pets, life events and quick facts (ticket 3).
- Journal posts (ticket 4).
- Documents and photos (ticket 5).
- Exporting more than one contact, a whole vault or a whole account.
- Importing the file into Monica or into another tool.
- The contact's avatar image and activity feed (the history of changes to the contact).
- PDF or other human-friendly formats.
- Any change to the vCard download.
- Usage tracking or success metrics.
- A help page that describes the file's structure.

---

## 2. Include the contact's relationships in the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The contact export lists each of the contact's relationships, showing the other person's name and the relationship type only.

### Job to be done

When a Monica user exports a contact, they need that person's relationships in the file, so their backup shows who the person is connected to.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact page. The browser saves the file to the user's device.
**Screen/component:** The downloaded JSON file. The contact page does not change.

### Context

Relationships are a large part of what a user records about a person. A linked contact is another contact who appears in this contact's relationships. Linked contacts did not agree to be in someone else's export file. So the file names them and states the relationship, and holds nothing else about them. This keeps the export useful without copying other people's data.

### Designs

No designs needed. This ticket changes only the file contents.

### Acceptance criteria

**Scenario: Export lists each relationship with name and type**
Given the user is on the page of a contact whose sister is Anna Smith, another contact in the vault
When they select "Export all data"
Then the file shows "Anna Smith" with the relationship type "sister"

**Scenario: Export holds no other data about a linked contact**
Given the user is on the page of a contact with a relationship to Anna Smith
And Anna Smith's own contact page has phone numbers, addresses and notes
When they select "Export all data"
Then the file shows Anna Smith's name and the relationship type only
And the file shows none of Anna Smith's phone numbers, addresses or notes

**Scenario: Contact with several relationships**
Given the user is on the page of a contact with five relationships of different types
When they select "Export all data"
Then the file shows all five relationships
And each one shows the same name and relationship type that Monica shows on the contact page

**Scenario: Contact with no relationships**
Given the user is on the page of a contact that has no relationships
When they select "Export all data"
Then the file downloads without an error
And relationships appear in the file as an empty list

### Out of scope

- Any detail about a linked contact beyond name and relationship type.
- Changes to how relationships appear on the contact page.
- Journal posts that mention linked contacts (ticket 4).

---

## 3. Include notes, reminders and the contact's other records in the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The contact export includes the contact's notes, reminders, tasks, calls, loans, goals, pets, life events and quick facts.

### Job to be done

When a Monica user has years of notes about a contact, they need all of it in the export, so they avoid copying it by hand.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact page. The browser saves the file to the user's device.
**Screen/component:** The downloaded JSON file. The contact page does not change.

### Context

These records hold most of what a user writes about a person, so they are the main reason to export. Today users can only copy them by hand, which is slow and leads to mistakes. A vault's page layout can hide some sections of the contact page. The page layout is only a display choice, so the export still includes records in hidden sections.

### Designs

No designs needed. This ticket changes only the file contents.

### Acceptance criteria

**Scenario: Export includes every record of each type**
Given the user is on the page of a contact with at least one note, reminder, task, call, loan, goal, pet, life event and quick fact
When they select "Export all data"
Then the file contains every record of each of these types
And each record has the same text and dates that Monica shows for it

**Scenario: Text with special characters comes through unchanged**
Given the user is on the page of a contact with a note that contains line breaks, quotation marks, emoji and non-Latin script
When they select "Export all data"
Then the file opens in a text editor and in a JSON reader without an error
And the note text in the file matches the text Monica shows, character for character

**Scenario: Records in sections hidden by the page layout are included**
Given the user is on the page of a contact in a vault whose page layout hides the loans section
And the contact has two loans
When they select "Export all data"
Then the file contains both loans

**Scenario: Contact with none of these records**
Given the user is on the page of a contact that has none of these records
When they select "Export all data"
Then the file downloads without an error
And each of these data types appears in the file as an empty list

### Out of scope

- Relationships (ticket 2), journal posts (ticket 4), and documents and photos (ticket 5).
- The contact's activity feed.
- Changes to page layouts or to how these sections appear on the contact page.

---

## 4. Include journal posts linked to the contact in the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The contact export includes every journal post linked to the contact that the exporting user can see.

### Job to be done

When a Monica user has journal posts about a contact, they need those posts in the export, so their copy is complete.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact page. The browser saves the file to the user's device.
**Screen/component:** The downloaded JSON file. The contact page does not change.

### Context

Journal posts linked to a contact are part of what the user has recorded about that person. The export follows one rule: it contains nothing the user cannot already see. So another vault member's private posts stay out of the file. A journal post can also be linked to other contacts. The file shows those other contacts by name only.

Engineering confirms the private-post rule at plan-review. The spec lists it as an open question with this working answer.

### Designs

No designs needed. This ticket changes only the file contents.

### Acceptance criteria

**Scenario: Export includes journal posts linked to the contact**
Given the user is on the page of a contact linked to three journal posts
When they select "Export all data"
Then the file contains all three posts
And each post has the same title, text and date that Monica shows for it

**Scenario: Posts the user cannot see are not in the export**
Given the user is on the page of a contact in a shared vault
And another vault member wrote a private journal post linked to this contact
When the user selects "Export all data"
Then the file does not contain that private post

**Scenario: Other contacts linked to a post appear by name only**
Given the user is on the page of a contact linked to a journal post
And the same post is also linked to Anna Smith, another contact in the vault
When they select "Export all data"
Then the post in the file shows Anna Smith's name
And the file holds no other data about Anna Smith

**Scenario: Contact with no linked journal posts**
Given the user is on the page of a contact that no journal post is linked to
When they select "Export all data"
Then the file downloads without an error
And journal posts appear in the file as an empty list

### Out of scope

- Journal posts not linked to this contact.
- Photos or files attached to journal posts.
- Changes to journal privacy or to who can see a post.

---

## 5. List the contact's documents and photos in the export

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary

The contact export lists each document and photo uploaded to the contact, with its file name and upload date only.

### Job to be done

When a Monica user exports a contact, they need a list of that person's documents and photos, so they know what else to save.

### Navigation context

**Entry point(s):** Vault → Contacts list → a contact's page → "Export all data".
**Exit point(s):** The user stays on the contact page. The browser saves the file to the user's device.
**Screen/component:** The downloaded JSON file. The contact page does not change.

### Context

Phase 1 of this feature exports text only. We believe most users want the text they wrote. A list of file names shows what else exists without making the download large. File uploads are optional on self-hosted Monica, so the export must work where no file-upload service is set up. A later phase may add the files themselves.

### Designs

No designs needed. This ticket changes only the file contents.

### Acceptance criteria

**Scenario: Export lists each document and photo**
Given the user is on the page of a contact with two documents and three photos
When they select "Export all data"
Then the file lists all five items
And each item shows the same file name and upload date that Monica shows for it

**Scenario: Export does not contain the files themselves**
Given the user is on the page of a contact with uploaded documents and photos
When they select "Export all data"
Then the browser downloads only the single JSON file
And the file does not contain the contents of any document or photo

**Scenario: Self-hosted Monica without file uploads**
Given the user is on a contact page in a self-hosted Monica that has no file-upload service set up
When they select "Export all data"
Then the file downloads without an error
And documents and photos appear in the file as empty lists

**Scenario: Contact with no documents or photos**
Given the user is on the page of a contact with no uploaded documents or photos
When they select "Export all data"
Then the file downloads without an error
And documents and photos appear in the file as empty lists

### Out of scope

- The contents of documents and photos.
- The contact's avatar image.
- A bundle or archive that includes the files (possible Phase 2).

---

## Assumptions made during decomposition

- **Initiative.** The spec has no `Initiative:` field. We linked all tickets to "Provable trust" from the strategy. The PM should confirm this.
- **Split by data type.** The spec is one vertical slice. We split it into one core ticket and four tickets that each add data types. Each ticket changes what the user sees in the file, and tickets 2–5 can run in parallel.
- **Grouping in ticket 3.** Notes, reminders, tasks, calls, loans, goals, pets, life events and quick facts share one ticket. They all belong only to this contact and follow the same rules. Relationships, journal posts, and documents and photos each have their own ticket, because each has a separate privacy or availability rule.
- **Release timing.** Ticket 1 recommends that users do not see the action until tickets 2–5 ship. Otherwise the "Export all data" label would be wrong. This is a product call, and the PM may change it.
- **Open questions.** Both 🟡 questions in the spec have working answers. Ticket 4 uses the private-post answer, and engineering confirms it at plan-review. The help page that describes the file's structure is not a ticket. The spec says to write it after the build, so the PM should raise it as a follow-up.
- **No personas file.** `docs/product/personas.md` does not exist. The tickets use "Monica user", as the spec does.
- **No in-flight work checked.** `docs/iterations/current.md` does not exist, so we found no conflicts with current work.
