# Download a contact as JSON — Tickets

Generated: 29 September 2026
Spec: output/spec.md
Tracker: none (no `ticket_tool` is configured, so this file is the only output)
Initiative: Provable trust

> **Initiative note:** The spec has no `Initiative:` field. The spec's "Why now" ties this work to the
> strategy's "provable trust" capability (`docs/product/strategy.md`, capability 4). So these tickets
> use the initiative name "Provable trust". The PM should confirm this name before anyone posts the
> tickets to a tracker.

> **Persona note:** `docs/product/personas.md` does not exist yet. These tickets use the spec's two
> working persona names: **Relationship keeper** and **Script builder**.

## Ticket breakdown

| # | Title | Type | Depends on |
| - | ----- | ---- | ---------- |
| 1 | Let users download a contact's names and profile details as a JSON file | Feature | None |
| 2 | Include contact information and current addresses in the JSON download | Feature | 1 |
| 3 | Include the birthday in the JSON download | Feature | 1 |

Tickets 2 and 3 do not depend on each other. The team can build them in either order, or at the same time.

## Terms used in these tickets

- **JSON:** a plain-text data format that most programming languages can read directly.
- **vCard:** the standard file format for address-book contacts. Monica already offers a "Download as vCard" link.
- **UTF-8:** the text encoding that supports every alphabet.
- **Vault:** a shared space in Monica that holds contacts.
- **Label:** a tag a user attaches to a contact, for example "Family".
- **`null`:** the JSON value that means "no value".
- **`[]`:** an empty list in JSON.
- **ISO 8601:** the international date and time standard, for example `2026-09-29T14:05:00Z`.

---

## 1. Let users download a contact's names and profile details as a JSON file

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** None

### Summary
Users can download the contact they are viewing as one JSON file that holds the contact's identity, names, and profile details.

### Job to be done
When a Script builder wants to reuse a contact's details in a script, they need to download the contact in a format their code reads directly, so they can use the data without parsing a vCard.

### Navigation context
**Entry point(s):** Any contact page, reached from the contact list in a vault. The user selects the new "Download as JSON" link in the contact page's side menu.
**Exit point(s):** The user stays on the contact page. The browser saves the JSON file in the same way it saves the vCard download.
**Screen/component:** The contact page side menu, directly next to the "Download as vCard" link.

### Context
Today a user can get one contact out of Monica only as a vCard. vCard suits address books, but scripts must parse its special encoding, so the work is slow and easy to get wrong. The strategy names "provable trust" as a needed capability: people own their data and can take it out. This ticket gives Script builders a file they can read in any language.

**What the file contains in this ticket:**
- the contact's unique identifier and a link to the contact's page in Monica
- names: the full name as Monica displays it, first name, middle name, last name, and nickname
- gender
- labels
- company name and job position
- the time the contact was last updated, in ISO 8601 format

**File shape rules (these apply to all three tickets):**
- The file holds one JSON object for one contact.
- Field names are readable English words, not vCard codes such as `FN` or `ADR`. Engineering chooses the exact names at plan review.
- Related values sit together. For example, the name parts form one group.
- Details that can have more than one value are lists. In this ticket, that means labels.
- Every field is always present. An empty single value is `null`, and an empty list is `[]`.

**Rules that come from the spec:**
- Anyone who can view the contact can download the JSON file. Nobody else can. This matches the vCard download.
- Downloading the file never changes the contact. It also never changes what the vCard download or address-book sync returns.
- Monica builds the file itself. It sends no contact data to any outside service.
- The feature works on the hosted plan and on self-hosted installs with no extra setup.
- The "Download as JSON" label is translatable, like every other label in Monica.
- The file makes no promise that its shape stays the same across releases. It carries no format version number. If the shape changes, the maintainers note it in the release notes.

**Open item for plan review:** Under the current vCard naming rule, some names may produce an empty file name. An example is a name made only of emoji. The JSON download uses the same naming rule as the vCard download. Engineering decides at plan review whether both downloads need a fallback name.

### Designs
No designs needed. The new link copies the style and placement of the "Download as vCard" link.

### Acceptance criteria

**Scenario: View-only vault member sees the JSON download link**
Given a vault member with view-only access is on a contact page in that vault
When the page finishes loading
Then they see a "Download as JSON" link directly next to the "Download as vCard" link
And the two links have the same style

**Scenario: User downloads a contact that has every profile detail filled in**
Given the user is on the contact page of a contact with a value for every detail listed in this ticket's Context
When they select "Download as JSON"
Then the browser downloads one file
And the file name matches the vCard download's file name, but ends in `.json` instead of `.vcf`
And the file opens as valid JSON in UTF-8
And the file shows every detail listed in this ticket's Context
And each value matches the same detail in the vCard download for that contact

**Scenario: The file uses readable, grouped field names**
Given the user has downloaded a contact as JSON from the contact page
When they open the file in a text editor
Then every field name is a readable English word, not a vCard code such as `FN`
And the first, middle, and last name and the nickname appear together in one group
And the labels appear as a list

**Scenario: Downloading the file does not change the contact**
Given the user is on a contact page and has already downloaded that contact as JSON once
When they select "Download as JSON" a second time
Then the "last updated" time in the second file is the same as in the first file
And a vCard download of the contact has the same content as a vCard download taken before either JSON download

**Scenario: Contact with only a first name**
Given the user is on the contact page of a contact that has only a first name and no other details
When they select "Download as JSON"
Then the download succeeds
And the file shows the same set of fields as a file for a fully filled-in contact
And each empty single value is `null`
And each empty list is `[]`

**Scenario: The display name follows the downloading user's name-order setting**
Given the user has set their name order to "last name, first name" and is on a contact page
When they select "Download as JSON"
Then the full name in the file appears in "last name, first name" order
And it matches the full name in the vCard download that the same user takes

**Scenario: Gender appears as the name Monica shows**
Given the user is on the contact page of a contact whose gender Monica shows as "Female"
When they select "Download as JSON"
Then the gender in the file reads "Female"
And the gender is not a vCard code such as "F"

**Scenario: The file excludes data the vCard download does not include**
Given the user is on the contact page of a contact with notes, relationships, reminders, an avatar, and documents
When they select "Download as JSON"
Then the file shows none of these items
And the file shows no names or links for the avatar or the documents

**Scenario: The file excludes extra details kept from an imported vCard**
Given the user is on the contact page of a contact they imported from another app's vCard, and that vCard held fields Monica does not show
When they select "Download as JSON"
Then the file shows only the details listed in this ticket's Context
And it shows none of the extra fields from the imported vCard

**Scenario: The contact is deleted or access is lost after the page opens**
Given the user has a contact page open
And another user then deletes the contact, or removes this user from the contact's vault
When the user selects "Download as JSON"
Then no file downloads
And Monica shows the same error it shows for any other contact the user can no longer view

**Scenario: A user without access tries to open the download link directly**
Given a user is signed in to Monica but cannot view a contact in another vault
When they open that contact's JSON download link directly in the browser
Then no file downloads
And Monica shows the same error it shows for any other contact the user cannot view

**Scenario: The link label appears in the user's language**
Given the user has set Monica to a language that has a translation for "Download as JSON"
When they open a contact page
Then the link label appears in that language

### Out of scope
- Contact information (email addresses, phone numbers, instant messaging accounts, social profiles, other links). Ticket 2 adds these.
- Addresses. Ticket 2 adds current addresses.
- The birthday. Ticket 3 adds it.
- Notes, relationships, reminders, activities, life events, goals, and any other Monica data that the vCard download does not include.
- Avatars, photos, and documents, including their names or links.
- Downloading more than one contact at once, a whole vault, or a group.
- Importing a contact from a JSON file.
- A JSON endpoint in the public API.
- Scheduled or automatic exports.
- jCard or any other JSON standard for contacts.
- Published documentation of the file shape.
- A format version number or a promise that the shape stays the same.
- A warning about sensitive data.
- Usage tracking or success metrics.

---

## 2. Include contact information and current addresses in the JSON download

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary
The JSON download shows the contact's contact information and current addresses, with the same values as the vCard download.

### Job to be done
When a Script builder wants to email, call, or map their contacts from a script, they need each contact's contact information and addresses in the JSON file, so they can use them without parsing a vCard.

### Navigation context
**Entry point(s):** Any contact page, through the "Download as JSON" link that ticket 1 adds to the side menu.
**Exit point(s):** The user stays on the contact page. The browser saves the JSON file.
**Screen/component:** The JSON file that the contact page's "Download as JSON" link produces. The page itself does not change.

### Context
Contact information and addresses are the details Script builders most often reuse. The vCard download already includes them, so the JSON file must too. The file shape rules from ticket 1 apply here. In particular, each of these details is a list, and each address is one group of values.

**What this ticket adds to the file:**
- **Contact information:** email addresses, phone numbers, instant messaging accounts, social profiles, and other links. Each entry shows its type, its kind (for example "home" or "work"), and its preferred flag, where Monica stores them. The preferred flag marks the entry the user chose as the main one.
- **Current addresses:** each address shows its type, line 1, line 2, city, province, postal code, and country.

Past addresses stay out of the file, because the vCard download does not include them either.

### Designs
No designs needed. This ticket changes only the content of the downloaded file.

### Acceptance criteria

**Scenario: User downloads a contact with contact information and addresses**
Given the user is on the contact page of a contact with at least one email address, phone number, instant messaging account, social profile, other link, and current address
When they select "Download as JSON"
Then the file shows every one of these entries
And each contact information entry shows its type, kind, and preferred flag where Monica stores them
And each address appears as one group with its type, line 1, line 2, city, province, postal code, and country
And every value matches the same detail in the vCard download for that contact

**Scenario: Details with several values appear as lists**
Given the user is on the contact page of a contact with two email addresses, two phone numbers, and two current addresses
When they select "Download as JSON"
Then the file shows the email addresses as a list of two entries
And it shows the phone numbers as a list of two entries
And it shows the addresses as a list of two entries

**Scenario: Contact information and addresses stay hidden after access is lost**
Given the user has the contact page of a contact with contact information and addresses open
And another user then removes this user from the contact's vault
When the user selects "Download as JSON"
Then no file downloads
And the user sees none of the contact's contact information or addresses
And Monica shows the same error it shows for any other contact the user can no longer view

**Scenario: Contact with no contact information and no addresses**
Given the user is on the contact page of a contact with no contact information and no addresses
When they select "Download as JSON"
Then the download succeeds
And each contact information list and the address list is `[]`

**Scenario: An address with some parts empty**
Given the user is on the contact page of a contact whose only address has a city and country but no other parts
When they select "Download as JSON"
Then the address shows the city and country
And every other part of the address is `null`

**Scenario: Past addresses are left out**
Given the user is on the contact page of a contact with one current address and one past address
When they select "Download as JSON"
Then the file shows the current address
And the file does not show the past address

**Scenario: Non-Latin characters keep their exact form**
Given the user is on the contact page of a contact whose address city is written in Japanese characters
When they select "Download as JSON"
Then the city in the file shows the same Japanese characters as the contact page

### Out of scope
- Past addresses.
- Any contact information type that the vCard download does not include.
- Changes to the contact page or the "Download as JSON" link.
- The birthday. Ticket 3 adds it.
- Everything listed as out of scope in ticket 1.

---

## 3. Include the birthday in the JSON download

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary
The JSON download shows the contact's birthday as separate year, month, and day values, and never fills in a part that Monica does not know.

### Job to be done
When a Relationship keeper wants to build their own birthday reminders in another tool, they need each contact's birthday in the JSON file, so they can use it without guessing unknown parts.

### Navigation context
**Entry point(s):** Any contact page, through the "Download as JSON" link that ticket 1 adds to the side menu.
**Exit point(s):** The user stays on the contact page. The browser saves the JSON file.
**Screen/component:** The JSON file that the contact page's "Download as JSON" link produces. The page itself does not change.

### Context
Many users know a friend's birthday month and day but not the year. The vCard download already includes the birthday, so the JSON file must too. A script that reads a made-up year would show wrong ages. So the file keeps year, month, and day separate, and any unknown part is `null`. The birthday is the only important date in the file. Important dates are the dates a user records for a contact, such as an anniversary.

**Decision for this ticket:** If a contact has no birthday, the birthday group is still present, and its year, month, and day are all `null`. This follows the spec rule that every field is always present.

### Designs
No designs needed. This ticket changes only the content of the downloaded file.

### Acceptance criteria

**Scenario: Contact with a full birthday**
Given the user is on the contact page of a contact whose birthday is 14 March 1990
When they select "Download as JSON"
Then the file shows the birthday with year 1990, month 3, and day 14 as separate values
And the birthday matches the birthday in the vCard download for that contact

**Scenario: Birthday without a year**
Given the user is on the contact page of a contact whose birthday has a month and day but no year
When they select "Download as JSON"
Then the file shows the known month and day
And the birthday year is `null`
And the year is not a placeholder value such as 1604 or 0000

**Scenario: Birthday stays hidden after access is lost**
Given the user has the contact page of a contact with a birthday open
And another user then deletes the contact
When the user selects "Download as JSON"
Then no file downloads
And the user does not see the contact's birthday
And Monica shows the same error it shows for any other contact the user can no longer view

**Scenario: Other important dates are left out**
Given the user is on the contact page of a contact with a birthday and an anniversary
When they select "Download as JSON"
Then the file shows the birthday
And the file shows no important date other than the birthday

**Scenario: Contact with no birthday**
Given the user is on the contact page of a contact with no birthday
When they select "Download as JSON"
Then the download succeeds
And the file shows the birthday group with year, month, and day all `null`

### Out of scope
- Important dates other than the birthday, such as anniversaries.
- The contact's age, or any value Monica calculates from the birthday.
- Changes to how Monica stores or shows birthdays.
- Everything listed as out of scope in ticket 1.

---

## Report

**Tracker:** none. No `ticket_tool` is configured in `.claude/skill-config.md`, so nobody posted the tickets. This file is the final ticket set.

**Initiative:** Provable trust. There is no tracker grouping ID.

**Tickets created:**
1. Let users download a contact's names and profile details as a JSON file
2. Include contact information and current addresses in the JSON download
3. Include the birthday in the JSON download

**File saved:** `output/tickets.md`

**Assumptions made during decomposition:**
- The spec has no `Initiative:` field. The tickets use "Provable trust", the strategy capability that the spec's "Why now" names. The PM should confirm this before posting to any tracker.
- The spec's two 🟡 open questions already have a stated way forward. Ticket 1 records both: no format version number, and the vCard file-naming rule. Neither blocks a ticket, so no Spike is needed.
- The work splits by groups of details. Each ticket is safe to ship alone and gives the user a visible change. The maintainers may still choose to release all three together.
- Ticket 1 carries all shared rules: the link, access, errors, file name, no side effects, translation, and the file shape rules. Tickets 2 and 3 only add details to the file.
- There is no Chore ticket. The spec describes no data change, and engineering decides how to build the file.
- A contact with no birthday shows a birthday group with year, month, and day all `null`. The spec did not say this directly. It follows from the rule that every field is always present.
- The spec's AC-5 splits across tickets. Ticket 1 checks that notes, relationships, reminders, avatars, and documents are left out. Ticket 2 checks that past addresses are left out.
- Ticket 1 adds a scenario for opening the download link directly without access. This tests the spec's access rule: "Nobody else can."
- The spec's `Status:` and `Tickets:` fields are unchanged, because no tracker IDs exist.

**Recommended next step:** All three tickets are ready for sprint planning once the PM confirms the initiative name. Ticket 1 goes first. At plan review, engineering confirms the field names and the empty file name case.
