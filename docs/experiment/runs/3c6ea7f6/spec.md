# Full contact export

Last updated: 29 September 2026
Status: Ready
Tickets: None yet. The ticket tool setting is "none", so tickets go to `output/tickets.md`.

---

## Problem statement

Monica users cannot take a full copy of one contact out of Monica, because the vCard download holds only address-book details.

## Why now

Monica's strategy says users choose Monica because their data is theirs alone. Today that promise stops at the vCard. Notes, relationships, reminders and important dates cannot leave Monica. A full export for each contact lets users check the promise themselves. It supports the "provable trust" capability in `docs/product/strategy.md`. Lock-in means being unable to leave a tool with your data. We expect the export to lower that fear for people deciding whether to trust Monica with their data. The PM had no strong view on why now, so this reasoning is an assumption (see Assumptions).

## Users affected

`docs/product/personas.md` does not exist, so this spec uses plain descriptions, not persona names.

- **Backup keepers.** People who keep their relationship data in Monica and want a personal copy they control. This includes people on the hosted plan (monicahq.com) and self-hosters (people who run Monica on their own server).
- **Data reusers.** People who want to move what they recorded about a person into another tool. Examples: a notes app, a spreadsheet, or a script they write.
- **Shared-vault members.** A vault is a separate space of contacts that several users can share. Each member has one of three access levels: viewer (can only read), editor, or manager. All three levels can export (see Decisions).
- **Related people (non-users).** A relationship links this contact to another contact, for example "Sister: Jane Doe". The export names the related contact but carries no other data about them. This protects the related contact's data.

## Background

Today a user can download a vCard from a contact's page. A vCard is a standard address-book file (`.vcf`). It holds names, phone numbers, email addresses and postal addresses. It holds nothing else the user has recorded, such as notes, relationships, reminders or important dates. To keep a copy of those, the user must copy each item by hand or take screenshots. That is slow, easy to get wrong, and gives a result no other tool can read.

---

## Scope

### In scope

- A new "Export all data" option on the contact's page, next to the existing "Download vCard" option.
- When the user chooses it, the browser downloads one file for that contact.
- The file uses JSON, a common text format that people can read in any text editor and other software can read.
- The file name contains the contact's name and the export date, for example `jane-doe-2026-09-29.json`.
- The file contains every item recorded about the contact in these categories:
  - Names, nickname, gender, pronoun and religion
  - Job and company
  - Contact information (phone numbers, email addresses, social profiles and similar)
  - Addresses
  - Important dates (birthdays, anniversaries and similar)
  - Notes
  - Relationships
  - Reminders
  - Tasks
  - Calls
  - Loans
  - Pets
  - Goals
  - Life events
  - Mood tracking entries
  - Quick facts (short facts shown at the top of a contact's page)
  - Labels and groups the contact belongs to
  - A list of the contact's photos and documents: file name and upload date only
- Categories that the contact's page layout currently hides still appear in the file. The file holds all recorded data, not only what the page shows.
- Each category with no items still appears in the file, as an empty list.
- Each relationship shows only the relationship type and the related contact's name.
- Each note shows who wrote it and when.
- Labels that the account can customise (for example relationship types or contact information types) appear as the user sees them, such as "Sister". Internal codes do not appear.
- Dates use the format YYYY-MM-DD, for example 2026-09-29. A date with no recorded year appears without a year.
- The file states which version of the export format it uses. Other tools can then detect future changes.
- Archived contacts can be exported the same way as active contacts.

### Out of scope

- Exporting more than one contact at a time, or a whole vault or account. (Confirmed by the PM.)
- Importing the file back into Monica or into any other tool. (Confirmed by the PM.)
- Scheduled or automatic exports.
- Exporting through the Monica API (the interface that other programs use to talk to Monica).
- Including the photo and document files themselves. Phase 1 lists them by name only.
- A human-readable layout of the file, such as PDF or a printable page.
- Journal posts, even when they mention the contact. Journals belong to the vault, not to one contact.
- The contact's activity history (the feed of changes made to the contact).
- Any change to the existing "Download vCard" option or to address-book sync with phones and other apps.
- Usage tracking or success metrics for this feature. (Confirmed by the PM.)

### Phasing

- Phase 1: this spec. One JSON file per contact, with photos and documents listed by name.
- Phase 2 (not specced): candidates are including photo and document files in a bundle, and a human-readable file (such as PDF). Build these only if Phase 1 users ask for them.

### Vertical slice check

If this shipped alone, a user could download everything they have recorded about one person as one file they control. They cannot do that today. This is not an enabler story.

---

## Figma

No designs needed. The feature adds one option next to the existing "Download vCard" option on the contact's page. It follows the same style as that option. It adds no new screens.

---

## Acceptance criteria

**AC-1**
GIVEN a user can view a contact in a vault
WHEN the user opens the contact's page
THEN the page shows an "Export all data" option next to the "Download vCard" option
AND the "Download vCard" option downloads the same vCard file as before this change.

**AC-2**
GIVEN a contact has at least one item in every category listed under "In scope"
WHEN the user chooses "Export all data"
THEN the browser downloads one JSON file named `[contact-name]-[YYYY-MM-DD].json`
AND the file contains every one of those items with the values shown on the contact's page
AND each photo and document appears with its file name and upload date only.

**AC-3**
GIVEN contact A has a relationship "Sister" with contact B
AND contact B has notes, important dates and contact information
WHEN the user exports contact A
THEN the file shows the relationship type "Sister" and contact B's name
AND the file contains no notes, important dates, contact information or other data belonging to contact B.

**AC-4**
GIVEN a user has viewer access to a shared vault
AND another vault member wrote a note on a contact in that vault
WHEN the viewer chooses "Export all data" on that contact
THEN the download succeeds
AND the file shows that note with its author's name and creation date.

**AC-5 (empty state)**
GIVEN a contact has only a name and no other recorded data
WHEN the user chooses "Export all data"
THEN the browser downloads a valid JSON file
AND every category listed under "In scope" appears in the file as an empty list.

**AC-6 (unhappy path)**
GIVEN Monica cannot produce the export file for a contact (for example, a server error)
WHEN the user chooses "Export all data"
THEN the contact's page shows an error message saying the export failed and asking the user to try again
AND the browser downloads no file, and no partial file
AND the contact's data does not change.

---

## Assumptions

- **Why now.** A full export supports the "provable trust" capability in the strategy. We believe it reduces fear of lock-in for new and hosted users. The PM had no strong view on this.
- **Users.** Backup keepers and data reusers both accept a JSON file. Backup keepers can open it in a text editor. Data reusers can load it into other tools.
- **Format.** One JSON file serves both user groups well enough for Phase 1. A readable layout (such as PDF) can wait for Phase 2.
- **Contents.** "Everything recorded" means every category listed under "In scope", including categories the page layout hides.
- **Attachments.** Listing photos and documents by name is enough for Phase 1. Including the files would turn the download into a bundle and add new ways to fail.
- **Relationships.** Users expect the related contact's name and relationship type only. They do not expect the related contact's data.
- **Access.** Anyone who can view the contact can export it, including vault viewers. Viewers can already read all of this data on screen, and they can already download the vCard. Export gives them no data they cannot already see.
- **Authors.** Showing who wrote each note is useful and safe, because vault members already see note authors on the page.
- **vCard.** Users want the vCard download kept as it is. The new option sits next to it and does not replace it.
- **Entry point.** One extra option next to "Download vCard" is easy enough to find. No design work is needed.
- **Failure.** An error message with no file is better than a partial file. A partial file could look like a complete backup when it is not.

---

## Technical notes

- **Privacy.** The user's own Monica installation builds the file. No contact data goes to any third-party service to produce it. The feature must pass the strategy's privacy review ("does this weaken the no-surveillance promise?").
- **Self-hosting.** The export must work on a self-hosted installation with no extra setup, settings or third-party accounts.
- **Access.** The export must respect the existing vault access rules. A user who cannot view a contact cannot export it.
- **Existing flows.** The "Download vCard" option and address-book sync with phones and other apps must behave exactly as before.
- **Accurate text.** The file must keep the user's text exactly as written, including accents, non-Latin scripts and emoji.
- **Speed (working target).** The download should start within 10 seconds for a contact with 1,000 notes. Engineering can challenge this number at plan review.
- **In-flight work.** `docs/iterations/current.md` does not exist, so we could not check for conflicting work in progress. Engineering should check this at plan review.

---

## Open questions

- 🟡 Is JSON alone enough for backup keepers, or do they need a readable file (such as PDF) in Phase 1? Working assumption: JSON alone is enough. — PM
- 🟡 Should Phase 2 include the photo and document files in a bundle? Working assumption: not in Phase 1. — PM
- 🟡 Is 10 seconds for a contact with 1,000 notes the right speed target? Working assumption: yes, until engineering says otherwise at plan review. — Engineering
- 🟢 Can vault viewers export? Decided: yes, because they can already see all of this data. — PM (delegated)

---

## Before you build

**Decisions made:**

- Export works on one contact at a time, from the contact's page only.
- Phase 1 produces one JSON file. It does not produce PDF, a bundle, or an import feature.
- Photos and documents appear by name and upload date only.
- Relationships show only the relationship type and the related contact's name.
- Anyone who can view the contact can export it, including vault viewers.
- The "Download vCard" option stays unchanged. The new option sits next to it.
- On failure, the user sees an error and gets no file.
- We add no usage tracking.

**Riskiest assumption:**
A single JSON file, without a readable layout or attachment files, is useful enough for people who want a personal backup.

**Next step:**
Ready for ticket generation. Engineering confirms the speed target and checks for conflicting in-flight work at plan review.
