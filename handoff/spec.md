# Full contact export

Last updated: 29 September 2026
Status: Ready
Tickets: (none yet; this run has no ticket tool, so tickets will go to `output/tickets.md`)

---

## Problem statement

People who keep their relationship data in Monica cannot download a full copy of everything they have recorded about one contact.

## Why now

Monica's strategy (`docs/product/strategy.md`) names "provable trust" as a capability Monica needs. Its central promise to users is "your data, only yours". Today a user can only download a vCard for a contact. A vCard (a standard address-book file) holds names, phone numbers, email addresses and postal addresses. The user cannot download their notes, relationships, reminders or important dates in any form. Until they can, the promise stays a claim. This feature makes it concrete for each contact.

## Users affected

`docs/product/personas.md` does not exist, so this spec describes users by what they do. It does not use persona names.

- **Monica users who want a backup.** They want a copy of a contact's full record that they keep themselves, outside Monica.
- **Monica users who reuse their data.** They want to load a contact's record into other tools or into scripts they write themselves. Many of them host Monica on their own server ("self-hosters").
- **Users with viewer access to a shared vault.** A vault is a shared space that holds contacts. Each member of a vault has one of three access levels: manager, editor or viewer. A viewer can see contacts but cannot change them. Viewers can use this feature too, because they can already see everything in the file.
- **People related to the contact.** They are not Monica users, but the file names them. The file shows who they are and how they relate to the contact. It does not include their own details.

## Background

On a contact's page, a user can download a vCard today. The vCard only covers address-book details. To keep a copy of everything else, a user must copy each note, reminder and date by hand, or take screenshots. A user who wants the data in a script has no clean way to get it. That work is slow, and the user can easily miss something. The result cannot be read by a program.

---

## Scope

### In scope

- A new download option on the contact's page, next to the existing vCard download. It is labelled "Download full copy (JSON)".
- One file per contact, in JSON format. JSON is a common text format that programs and scripts can read.
- The file contains everything recorded about the contact that the user can see on the contact's pages. This includes:
  - address-book details: names, nickname, prefix and suffix, job position, company, gender, pronouns, contact information (such as phone numbers and email addresses) and addresses
  - notes, with the name of the user who wrote each note
  - relationships: the related person's name and the type of relationship (for example "sister")
  - reminders
  - important dates (for example birthdays)
  - calls, tasks, gifts, loans, goals, pets, life events, mood entries, quick facts, labels and religion
  - a list of the contact's photos and documents, with each file's name and upload date
- Plain, readable labels in the file. For example, the file says "sister", not an internal code.
- Dates written as year-month-day (for example `2026-06-11`), so scripts can read them without guessing the format.
- A file name that includes the contact's name and the download date.
- The same feature for every vault access level (manager, editor and viewer). It also works the same on the hosted service at monicahq.com and on self-hosted installations.

### Out of scope

- Downloading many contacts, or a whole vault, at once. This spec covers one contact at a time.
- Importing the file back into Monica, or into any other tool. This feature only exports.
- A human-readable or printable version (for example PDF or HTML).
- The contents of attached photos and documents. The file lists them by name and date only.
- Details of related people beyond their name and relationship type.
- Journal posts, even if they mention the contact.
- The contact's activity history (the automatic log of changes shown on the contact page).
- Formats built for a specific tool, such as a CSV file for spreadsheets.
- Any change to the existing vCard download.
- Tracking or usage metrics for this feature.

### Phasing

- Phase 1: this spec. One machine-readable JSON file per contact.
- Later (not yet planned):
  - a human-readable version
  - including the attached photos and documents
  - downloading many contacts at once

### Vertical slice check

If this feature shipped alone, a user could download everything they have recorded about a contact in one file. Today they cannot do that in any form. This is not an enabler story.

---

## Figma

No designs needed. The new option matches the style and placement of the existing vCard download link on the contact's page, and sits next to it.

---

## Acceptance criteria

**AC-1**
GIVEN a contact that has at least one item of every data type listed under "In scope"
WHEN a user with access to the vault selects "Download full copy (JSON)" on the contact's page
THEN the browser downloads one JSON file
AND a standard JSON reader opens the file without errors
AND the file contains every item of every listed data type, and each note shows the name of the user who wrote it
AND the vCard download link is still on the page and produces the same file as before this change.

**AC-2**
GIVEN a user who has viewer access to the vault
WHEN the user opens a contact's page
THEN the user sees the "Download full copy (JSON)" option
AND the file they download is identical in content to the file a manager of the same vault downloads for the same contact at the same time.

**AC-3**
GIVEN a contact who has a relationship with another contact, and who has one photo and one document attached
WHEN a user downloads the full copy
THEN the file shows the related person's name and the relationship type
AND the file contains no other details about the related person (for example no notes, dates or contact information)
AND the file lists the photo and the document by file name and upload date
AND the file contains no photo or document contents.

**AC-4 (unhappy path: empty contact)**
GIVEN a contact that has only a first name and no other recorded data
WHEN a user downloads the full copy
THEN the download succeeds
AND the file contains every data type listed under "In scope", each shown as empty rather than left out.

**AC-5 (unhappy path: no access)**
GIVEN a user who is not a member of the contact's vault
WHEN the user tries to download the full copy of that contact
THEN Monica does not return a file
AND Monica shows the same "not found or not allowed" response it shows today when this user tries to open that contact's page.

---

## Assumptions

- **The list of data types under "In scope" covers everything a user can record about a contact.** If Monica stores a type this spec does not list, the file is not a full copy. That breaks the promise this feature exists to keep.
- Users who want the file for other tools will write their own scripts or use general tools that read JSON. They do not need a format built for a specific product.
- A printable version matters less than backup and reuse. It can wait for a later phase. (The PM had no strong view.)
- Showing who wrote each note is correct, because members of a shared vault can already see this on the contact's page. (The PM had no strong view.)
- Journal posts belong to a journal, not to a contact. They are left out of this first version. (The PM had no strong view.)
- The activity history is a log of changes, not something the user recorded about the person. So it is left out.
- A single contact's data is small enough to download at once. The user does not need to wait for an emailed link or a background job.
- The file carries the same privacy risk as the data the user can already see on screen. So viewers may download it too.

---

## Technical notes

- **Access:** every access level (manager, editor and viewer) can use the feature. It must never return data to a user who cannot open the contact's page.
- **Privacy:** Monica builds the file itself and sends it directly to the user's browser. Monica must not send the contact's data to any third-party service to build the file. This keeps the feature within the strategy's privacy review gate.
- **Hosted and self-hosted:** the feature works the same on both. A self-hoster does not need to set anything up or change any settings to use it.
- **No side effects on other data:** the file must not contain data from other contacts or other vaults. The only exception is the related people's names and relationship types.
- **The vCard download must not change.** Users rely on it for their address-book apps.
- **Keeping the export complete:** when someone later adds a new data type to contacts, the export must include it. Otherwise the file silently stops being a full copy.
- **Work in flight:** unknown. `docs/iterations/current.md` does not exist, so this spec cannot flag conflicts with current work.

---

## Open questions

- 🟡 Should journal posts that mention the contact be in the file? Working assumption: no, not in this first version. Owner: PM.
- 🟡 Should the file include a format version number, so script writers can tell when the layout changes? Working assumption: yes, include one. Owner: engineering, at plan review.
- 🟢 Will a human-readable version follow? Yes, as later work. It is not part of this spec. Owner: PM.

---

## Before you build

**Decisions made:**

- The file is a single JSON file for one contact. It has no human-readable version yet.
- The option is labelled "Download full copy (JSON)". It sits next to the vCard download, which stays unchanged.
- Anyone who can view the contact can download the file, including viewers.
- Photos and documents are listed by name and upload date only. Their contents are not included.
- Related people appear by name and relationship type only.
- Each note shows the name of the user who wrote it.
- The file leaves out journal posts and the activity history.
- The feature has no tracking or usage metrics.

**Riskiest assumption:**
The "In scope" list covers every data type a user can record about a contact, so the file really is a full copy.

**Next step:**
Before creating tickets, engineering checks the "In scope" list against every data type Monica stores for a contact. After that check, the spec is ready for ticket generation.
