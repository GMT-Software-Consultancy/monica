# Download a contact as JSON

Last updated: 29 September 2026
Status: Ready
Tickets: None yet. No ticket tool is configured for this project, so tickets go to `output/tickets.md`.

---

## Problem statement

Monica users can download a contact only as a vCard, which is hard to reuse in scripts and other tools.

## Why now

The strategy names "provable trust" as a capability Monica needs: people's data is theirs, and they can take it out. Today people can get a single contact out only as a vCard (the standard file format for address-book contacts). vCard suits address books but is awkward for scripts, so the data is portable in theory only. This change is small because it reuses the details the vCard download already includes, so it fits the strategy's two-maintainer capacity limit.

## Users affected

`docs/product/personas.md` does not exist yet, so this spec uses two working persona names. Replace them with official names when the personas file exists.

- **Relationship keeper**: anyone who keeps their relationship data in Monica, on the hosted plan or on a self-hosted install. They gain a second download option next to the vCard download.
- **Script builder**: a Relationship keeper who is also a developer. They want to reuse contact details in scripts or other tools. This is the person who benefits most.
- **Vault members with view-only access**: a vault is a shared space in Monica that holds contacts. Anyone who can view a contact can download it as JSON, the same rule as for the vCard download.

## Background

Today the contact page has a "Download as vCard" link in its side menu. A Script builder who wants the data in a script must download the vCard. Then they parse it with a vCard library or copy the fields by hand. vCard stores values in a line-based format with special encoding rules, so this work is slow and easy to get wrong. JSON (a plain-text data format that most programming languages can read directly) removes that step.

---

## Scope

### In scope

- A "Download as JSON" link on the contact page, next to "Download as vCard", with the same look.
- The link downloads one JSON file for the contact that is open.
- The file contains the same contact details as the vCard download, and nothing more:
  - the contact's unique identifier and a link to the contact's page in Monica
  - names: the full name as Monica displays it, first name, middle name, last name, and nickname
  - gender
  - labels (the tags a user attaches to a contact, for example "Family")
  - contact information: email addresses, phone numbers, instant messaging accounts, social profiles, and other links, each with its type, kind (for example "home" or "work"), and preferred flag where Monica stores them
  - current addresses, each with its type, line 1, line 2, city, province, postal code, and country
  - company name and job position
  - birthday
  - the time the contact was last updated
- A plain, readable file shape (the rules are in "Technical notes").

### Out of scope

- Notes, relationships, reminders, activities, life events, goals, and all other Monica data that the vCard download does not include.
- Important dates other than the birthday.
- Past addresses. The vCard download does not include them either.
- Avatars, photos, and documents, including their names or links.
- Downloading more than one contact at once, or a whole vault.
- Downloading groups as JSON.
- Importing a contact from a JSON file.
- A JSON endpoint in the public API.
- Scheduled or automatic exports.
- Any JSON standard for contacts, such as jCard.
- Published documentation of the file shape.
- Usage tracking or success metrics for this feature.

### Phasing

- Phase 1: this spec.
- Phase 2 (not committed): add more Monica data to the file, such as notes, relationships, and reminders, and publish documentation of the file shape. The PM decides later whether to do this.

### Vertical slice check

If this shipped alone, a Script builder could download a contact and read it in any programming language without parsing vCard. They cannot do that today.

---

## Figma

No designs needed. The new link copies the style and placement of the existing "Download as vCard" link.

---

## Acceptance criteria

**AC-1**
GIVEN a user who can view a contact, including a vault member with view-only access
WHEN they open the contact page
THEN they see a "Download as JSON" link directly next to the "Download as vCard" link, in the same style.

**AC-2**
GIVEN a contact with a value for every detail listed under "In scope"
WHEN the user selects "Download as JSON"
THEN the browser downloads one file with the same name as the vCard download, but ending in `.json` instead of `.vcf`
AND the file is valid JSON in UTF-8 (the text encoding that supports every alphabet)
AND the file contains every detail listed under "In scope", with the same values as the vCard download for that contact
AND the contact's "last updated" time in Monica stays the same.

**AC-3**
GIVEN a contact that has only a first name, with every other detail empty
WHEN the user selects "Download as JSON"
THEN the download succeeds
AND the file contains the same set of fields as in AC-2
AND each empty single value is `null`
AND each empty list is `[]` (an empty list).

**AC-4**
GIVEN a contact whose birthday has a month and day but no year, and who also has other important dates such as an anniversary
WHEN the user selects "Download as JSON"
THEN the birthday in the file has the known month and day, and its year is `null`
AND the file contains no important dates other than the birthday.

**AC-5**
GIVEN a contact with notes, relationships, reminders, an avatar, documents, and a past address
WHEN the user selects "Download as JSON"
THEN the file contains none of these items.

**AC-6 (unhappy path)**
GIVEN a user has the contact page open
AND the contact is then deleted, or the user loses access to its vault
WHEN the user selects "Download as JSON"
THEN no file downloads
AND Monica shows the same error it shows for any other contact the user can no longer view.

---

## Assumptions

- **Riskiest:** The details in the vCard download are enough to make the JSON file useful for Script builders, without notes or relationships.
- The "why now" is the strategy's "provable trust" capability. The PM gave no specific trigger, such as a count of requests.
- The two working persona names above describe the real users well enough until `personas.md` exists.
- We make no promise that the file shape will stay the same across releases. We will not add a format version number in Phase 1. If the shape changes, the maintainers note it in the release notes.
- The file needs no warning about sensitive data. It holds the same details as the vCard download, which has no warning.
- Scheduled or automatic exports stay out of scope. Nobody has asked for them, and they would add ongoing work for two maintainers.
- "Same details as the vCard download" means the Monica details listed in "In scope". It does not include extra vCard data that Monica kept when someone imported the contact from another app.
- The file uses the display name the downloading user sees, which follows that user's name-order setting. This matches the vCard download.
- Gender appears as the gender name Monica shows on the contact page, for example "Female", not as a vCard code.

---

## Technical notes

- **Access rule:** anyone who can view the contact can download the JSON file. Nobody else can. This is the same rule as for the vCard download.
- **No side effects:** downloading the JSON file must not change the contact. It must not change what the vCard download or address-book sync returns.
- **Works everywhere Monica runs:** the feature must work on the hosted plan and on self-hosted installs, with no extra setup.
- **Privacy:** Monica builds the file itself. It must not send contact data to any outside service.
- **Translation:** the "Download as JSON" label must be translatable, like every other label in Monica.
- **File shape rules:**
  - The file holds one JSON object for one contact.
  - Field names are readable English words, not vCard codes such as `FN` or `ADR`. Engineering chooses the exact names at plan review.
  - Related values are grouped. For example, name parts sit together, and each address is one object.
  - Details that can have more than one value are lists: email addresses, phone numbers, other contact information, addresses, and labels.
  - Every field is always present. An empty single value is `null`, and an empty list is `[]`.
  - The birthday has separate year, month, and day values. Any unknown part is `null`. Monica never invents a missing part.
  - The "last updated" time uses ISO 8601 format (the international date and time standard), for example `2026-09-29T14:05:00Z`.

---

## Open questions

- 🟡 Should the file shape carry a stability promise or a format version number? We proceed with no promise and no version number (see "Assumptions"). Owner: PM.
- 🟡 Some names may produce an empty file name under the current vCard naming rule, for example a name with only emoji. We proceed with the same naming rule as the vCard download. Owner: engineering, at plan review.
- 🟢 More Monica data, such as notes, relationships, and reminders, may come in a later phase. This spec excludes it. Owner: PM.

---

## Before you build

**Decisions made:**

- The JSON file contains exactly the details that the vCard download contains, and nothing more.
- The only important date is the birthday, and a missing year stays `null`.
- Every field is always present. Empty values are `null` or `[]`.
- The file uses a plain, readable shape, not jCard or another contact standard.
- Photos, documents, and avatars are excluded.
- Only one contact at a time: no bulk export, no groups, no import, no API, no scheduled exports.
- Anyone who can view the contact can download it, the same as the vCard download.
- There is no design work, no usage tracking, and no warning about sensitive data.

**Riskiest assumption:**
The vCard set of details is enough for Script builders to find the JSON file useful.

**Next step:**
Ready for ticket generation, with no blockers. Engineering confirms the field names and the file-naming edge case at plan review.
