# Download a contact as JSON — Tickets
Generated: 29 September 2026
Spec: output/spec.md
Tracker: none (tickets are written to this file only)
Initiative: Provable trust

> **Initiative note:** The spec has no `Initiative:` field. This run was non-interactive, so we could not ask the PM.
> We assigned "Provable trust" because the spec's "Why now" section links the feature to that strategy capability.
> The PM should confirm this before anyone posts these tickets to a tracker.

---

## 1. Download a contact as a JSON file from the contact page

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** None

### Summary
A user who can view a contact can download that contact's vCard details as a readable JSON file from the contact page.

### Job to be done
When a developer user wants to use a contact's details in a script, they need to download the contact in a format their code reads directly, so they can reuse their own data without a vCard parser.

### Navigation context
**Entry point(s):** A vault → the contact list → a contact's page. A vault is a shared workspace that holds contacts.
**Exit point(s):** The user stays on the contact's page. The browser saves the JSON file to the user's downloads.
**Screen/component:** The contact page, next to the existing vCard download option.

### Context
Today a user can download a contact only as a vCard. vCard is a standard contact file format that address book apps read. Scripts need a special parser to read it. JSON (JavaScript Object Notation) is a common text format that almost every programming language reads without extra libraries. The strategy names "provable trust" as a core capability, and a readable download lets users check and reuse their own data.

Rules the spec decided:
- The JSON file holds the same details as the vCard download for that contact, except the photo. It holds nothing else.
- Every field is always present. An empty single value is `null`. An empty list is `[]`.
- The birthday is the only date in the file. If Monica does not know the birth year, the year is empty.
- The file uses a plain shape with clear field names, not a formal standard such as jCard (the JSON version of vCard).
- The feature works the same for hosted and self-hosted users, and no paid plan is needed.
- The feature adds no tracking or usage measurement.

Engineering confirms the exact list of details in today's vCard download at plan review.

### Designs
No designs needed. The new option matches the vCard download option in style and wording.

### Acceptance criteria

**Scenario: A user sees the JSON download option on the contact page**
Given a vault member is on the contact list of their vault
When they open a contact's page
Then they see a "Download as JSON" option next to the vCard download option
And the two options look the same in style

**Scenario: A user downloads a contact as a JSON file**
Given a vault member is on a contact's page
When they select "Download as JSON"
Then their browser downloads one file
And the file name matches that contact's vCard file name, except that it ends in `.json`
And the file shows line breaks and indentation when they open it in a text editor

**Scenario: A script reads the downloaded file without errors**
Given a developer user has downloaded a contact's JSON file from the contact page
When they open the file with a standard JSON parser
Then the parser reads the file without errors

**Scenario: The JSON file holds the same details as the vCard**
Given a vault member is on the page of a contact that has a value in every vCard detail
When they download both the vCard and the JSON file from that page
Then every vCard detail except the photo appears in the JSON file with the same value
And the JSON file contains no photo or avatar
And the JSON file contains no notes, relationships, reminders or other Monica data that the vCard does not contain

**Scenario: A birthday with no known year keeps the year empty**
Given a vault member is on the page of a contact whose birthday has a day and a month but no year
When they select "Download as JSON"
Then the birthday in the file shows the day and the month
And the year of the birthday is empty
And the file shows no year for that birthday anywhere

**Scenario: A contact with only a name still downloads with every field**
Given a vault member is on the page of a contact that has a name and no other vCard details
When they select "Download as JSON"
Then their browser downloads the file
And the file contains every field that a contact's JSON file can contain
And each empty single value shows as `null`
And each empty list shows as `[]`

**Scenario: A user outside the vault cannot download the contact**
Given a signed-in user is not a member of the vault that holds a contact
When they open the address of that contact's JSON download in their browser
Then they do not receive a file
And they see the same response they see when they try to download that contact's vCard

**Scenario: A view-only vault member can download the contact**
Given a vault member with view-only access is on a contact's page
When they select "Download as JSON"
Then their browser downloads the contact's JSON file

**Scenario: A user without a paid plan can download the contact**
Given a hosted user without a paid plan is on a contact's page
When they select "Download as JSON"
Then their browser downloads the contact's JSON file
And they see no prompt to upgrade their plan

**Scenario: The vCard download works the same as before**
Given a vault member is on a contact's page
When they select the vCard download option
Then their browser downloads the same vCard file that it downloaded before this change

### Out of scope
- The contact's photo or avatar.
- Other Monica data about the contact, such as notes, relationships, reminders, tasks, calls, life events, pets, loans, goals, mood logs and quick facts.
- Any date other than the birthday.
- Downloading more than one contact at a time, or a whole vault.
- Getting contact JSON through the API or any other programmatic endpoint.
- Importing a contact from a JSON file.
- A formal JSON standard such as jCard.
- Public documentation of the JSON shape.
- Tracking or measuring how often people use the download.
- Any change to the existing vCard download.
