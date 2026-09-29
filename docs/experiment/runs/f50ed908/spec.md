# Full contact export

Last updated: 29 September 2026
Status: Ticketed
Tickets: Tickets 1–5 in `output/tickets.md` (no tracker IDs — ticket_tool is "none" for this run)

---

## Problem statement

Monica users cannot take a full copy of everything they have recorded about one contact out of Monica, because the vCard download contains only address-book details.

## Why now

Monica's strategy makes "provable trust" a core capability: the user's data belongs to the user alone. Today a user can put a lot of personal knowledge into a contact, but can only take out a small part of it. A full export per contact makes the "your data is yours" promise something a user can check for themselves. No specific metric or support trend triggered this request. That is recorded as an assumption below.

## Users affected

`docs/product/personas.md` does not exist, so this spec uses plain descriptions instead of persona names.

- **Monica user (hosted plan).** Keeps relationship data in Monica at monicahq.com. This user cannot reach the stored data in any other way, so they gain the most.
- **Self-hoster.** Runs their own copy of Monica. They could read the raw stored data, but that needs technical skill and does not give a per-contact file. They get the same feature as hosted users.
- **Other members of a shared vault.** A vault is a separate space in Monica that holds contacts and can be shared between several users. Any member who can open a contact can export it. The export shows only what that member can already see.
- **Linked contacts (non-users).** Other people who appear in this contact's relationships or journal posts. Their own details do not go into the file. Only their name and how they relate to this contact go in.

## Background

Today a user can download a vCard from the contact page. A vCard is a standard address-book file. It holds names, phone numbers, email addresses, postal addresses and birthday, but none of the notes, reminders, relationships or other records the user has added. A user who wants a backup, or wants to reuse this data in another tool, must copy it by hand, one section at a time. For a contact with years of notes, this is slow, and users will make mistakes.

---

## Scope

### In scope

- A new "Export all data" action on the contact page, next to the existing vCard download.
- The action downloads one file for one contact.
- The file is a JSON file. JSON is a common, open text format. Other tools can read it, and a person can open it in any text editor.
- The file contains every kind of record Monica can hold for a contact:
  - names, nickname, gender and pronouns, company and job title, religion
  - contact information (for example phone numbers and email addresses) and addresses
  - labels and groups
  - important dates
  - relationships (see the rule for linked contacts below)
  - notes
  - reminders
  - tasks
  - calls
  - loans
  - goals
  - pets
  - life events
  - quick facts
  - journal posts that are linked to this contact
  - documents and photos: file name and upload date only
- Linked contacts appear with their name and the relationship type only (for example "Anna Smith — sister").
- The file includes the date and time of the export and the name of the vault.
- The feature works the same way for hosted users and self-hosters, on every plan.

### Out of scope

- Exporting more than one contact at a time, a whole vault, or a whole account.
- Importing the file back into Monica or into another tool. This is export only.
- The contents of uploaded documents and photos. The file lists them but does not include them.
- The contact's avatar image.
- The contact's activity feed (the history of changes to the contact).
- Human-friendly formats such as PDF or a printable page.
- Changes to the existing vCard download.
- Usage tracking or success metrics.

### Phasing

- Phase 1: this spec — one contact, one JSON file, text data only.
- Phase 2 (not planned yet): a possible bundle that also includes uploaded documents and photos, and a human-friendly format such as PDF. Both depend on how Phase 1 is used.

### Vertical slice check

If this shipped alone, a user could download, in one click, a full copy of what they have recorded about a contact. Today they cannot do this at all. This is a user-facing feature, not an enabler story.

---

## Figma

No designs needed. The feature adds one action next to the existing vCard download on the contact page and uses the existing button style. There are no designs or mock-ups.

---

## Acceptance criteria

**AC-1**
GIVEN a user can open a contact's page
WHEN the user selects "Export all data"
THEN the browser downloads one JSON file
AND the file name contains the contact's name and the export date.

**AC-2**
GIVEN a contact has at least one record in each data type listed under "In scope"
WHEN the user exports the contact
THEN the file contains every record of those data types that exists for the contact
AND each record in the file has the same text and dates that Monica shows for it.

**AC-3**
GIVEN a contact has a relationship with another contact
WHEN the user exports the contact
THEN the file shows the other contact's name and the relationship type
AND the file contains no other data about the other contact.

**AC-4**
GIVEN a contact has uploaded documents or photos
WHEN the user exports the contact
THEN the file lists each document and photo with its file name and upload date
AND the file does not contain the document or photo contents.

**AC-5 (unhappy path — empty contact)**
GIVEN a contact has only a name and no other records
WHEN the user exports the contact
THEN the file downloads without an error
AND each data type appears in the file as an empty list.

**AC-6 (unhappy path — no access)**
GIVEN a user does not have access to the vault that holds a contact
WHEN the user tries to export that contact
THEN Monica refuses the request
AND no file downloads.

---

## Assumptions

- **No single trigger.** The PM gave no metric or specific trigger. We believe the reason to build this is to make the data-ownership promise real and checkable. We do not expect a measurable change in retention.
- **JSON serves both user needs.** We believe one JSON file is good enough for both backup and reuse in other tools. Backup users can open and search it in a text editor. Users who reuse data can load it into other tools. This is the riskiest assumption: backup users may find JSON hard to read and ask for a PDF.
- **"What you can see is what you can export."** Any vault member who can open a contact page can already download its vCard. We believe the same rule is right for the full export. The export contains nothing that the user's vault access does not already let them see.
- **Linked contacts get name and relationship only.** We believe this gives a useful picture of the person's relationships without copying other people's data into the file.
- **Document and photo contents are not needed in Phase 1.** We believe most users want the text they wrote, and that file names are enough to show what else exists.
- **Journal posts belong in the export.** Posts linked to a contact are part of what the user has recorded about that person, so we include them.
- **Contacts are small enough for an instant download.** We believe even a contact with years of records produces a file small enough to download straight away, without a "we will email you" step.

---

## Technical notes

- The feature must work on a self-hosted Monica that has no file-upload service set up. File uploads are optional for self-hosters, and the export must not depend on them.
- The export must not send contact data to any third party. The file goes straight from the user's own Monica (hosted or self-hosted) to the user's browser. This keeps the feature inside the strategy's privacy review gate.
- The export must follow the same access rule as the contact page. A user who cannot open the contact page cannot export the contact.
- The existing vCard download must keep working exactly as it does today.
- Dates in the file use one unambiguous, standard format (year-month-day, with time zone where there is a time), so other tools can read them.
- A vault can hide some sections of the contact page through its page layout. The export still includes records in hidden sections, because the user has access to them and the page layout is only a display choice.

---

## Open questions

- 🟡 Should we publish a short description of the file's structure (section names and fields), so users can map it to other tools? Working assumption: yes, as a short help page, written after the build. — PM
- 🟡 Should journal posts that are private to one vault member appear in another member's export? Working assumption: the export shows only posts that the exporting user can see. — engineering, at plan-review
- 🟢 Does this change the vCard download? No. The vCard download stays exactly as it is. — PM

---

## Before you build

**Decisions made:**

- The export covers one contact at a time, started from the contact's page. Bulk export and import are out of scope.
- The file is one JSON file. PDF and other human-friendly formats are out of scope for Phase 1.
- The file lists uploaded documents and photos but does not include their contents.
- Linked contacts appear only by name and relationship type.
- Any user who can open a contact page can export that contact.
- Every user gets the feature, on hosted and self-hosted Monica, on every plan.
- The new action sits next to the vCard download, and the vCard download does not change.
- We add no usage tracking.

**Riskiest assumption:**
One JSON file is good enough for users who want a readable personal backup, not only for users who want to reuse data in other tools.

**Next step:**
Ready for ticket generation — no blockers. Engineering confirms the two 🟡 open questions at plan-review.
