# Full contact export — Tickets

Generated: 29 September 2026
Spec: output/spec.md
Tracker: none (no `ticket_tool` in this run; tickets are not posted anywhere)
Initiative: Provable trust (assumed; the spec has no `Initiative:` field. See "Assumptions" at the end.)

## Breakdown

| # | Title | Type | Depends on | Scope |
| - | ----- | ---- | ---------- | ----- |
| 1 | Confirm every kind of data a user can record about a contact | Spike | None | Check the spec's "In scope" list against every kind of data Monica holds for a contact, and get a PM decision on each gap. |
| 2 | Download a full copy of a contact as a JSON file | Feature | 1 | Any vault member can download one JSON file that holds everything recorded about a contact. |
| 3 | Keep the full copy complete when contacts gain new kinds of data | Chore | 2 | Make sure the full copy cannot silently fall behind when someone adds a new kind of contact data. |

---

## 1. Confirm every kind of data a user can record about a contact

**Type:** Spike
**Initiative:** Provable trust
**Depends on:** None

### Summary
This spike answers one question: does the spec's "In scope" list cover every kind of data a user can record about a contact?

### Question to answer
The spec's riskiest assumption is that its "In scope" list is complete. If Monica holds a kind of contact data that the list leaves out, the file is not a full copy. The spec asks engineering to check the list before building starts. This spike does that check and answers three questions:

1. Which kinds of data can a user record about a contact that the list does not name?
2. Where an item names another contact (for example the other person in a loan or a gift), what should the file show about that person?
3. Some sections may be hidden from a contact's page by the vault's contact page template. (A template sets which sections a contact's page shows.) Should the file include data in those hidden sections?

### Output artefact
An updated "In scope" section in the spec. It marks every kind of contact data as either "in the file" or "left out", with a one-line reason for each one left out. The PM records a decision for every gap found. Ticket 2's data type list is then updated to match the spec.

### Timebox
One working day. If the check is not finished by then, the PM decides on the gaps found so far, and ticket 2 goes ahead with that list.

### What we already know
- The architecture docs list kinds of contact data that the spec does not name:
  - the contact's membership of groups, and their role in each group
  - the contact's avatar (profile picture)
  - values the vault tracks for the contact as life metrics (numbers tracked over time, for example weight)
- The spec says "names". Contacts also have a middle name and a maiden name. The check should confirm that "names" covers both.
- A life event sits inside a timeline event. One timeline event can be shared by several contacts.
- Each user can mark a contact as a favourite. This belongs to the user, not to the contact. It is probably not something "recorded about the contact".
- The spec already leaves out journal posts and the contact's activity history. The spike does not reopen those decisions.
- The spec allows only one exception to "no data about other people": related people's names and relationship types. Question 2 tests whether that exception needs to grow.

### Definition of done
- [ ] The output artefact exists and is shared
- [ ] A follow-on ticket or explicit decision is recorded
- [ ] The PM has decided on every gap, and ticket 2's data type list matches the spec

---

## 2. Download a full copy of a contact as a JSON file

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** 1

### Summary
Any member of a vault can download one JSON file that holds everything recorded about a contact.

### Job to be done
When keeping a copy outside Monica, a user needs one file with a contact's whole record, so they can back it up or reuse it.

### Navigation context
**Entry point(s):** Vault → contact list → a contact's page. The new "Download full copy (JSON)" option sits next to the existing vCard download link. (A vCard is a standard address-book file.)
**Exit point(s):** The user stays on the contact's page. The browser saves the file.
**Screen/component:** The contact's page, next to the vCard download link.

### Context
Monica's strategy names "provable trust" as a capability Monica needs. Its promise to users is "your data, only yours". Today a user can only download a vCard, which holds address-book details. Their notes, relationships, reminders and important dates cannot leave Monica in any form. This ticket makes the promise real for each contact.

Monica builds the file itself and sends it straight to the user's browser. Monica must not send the contact's data to any third-party service to build the file. This keeps the feature inside the strategy's privacy review gate. The feature works the same on the hosted service (monicahq.com) and on self-hosted installations.

A vault is a shared space that holds contacts. Each vault member has one access level: manager, editor or viewer. A viewer can see contacts but cannot change them. JSON is a common text format that programs and scripts can read.

### Designs
No designs needed. The new option matches the style and placement of the existing vCard download link.

### Acceptance criteria

The scenarios below refer to **the data type list**. It holds every kind of data that ticket 1 confirms. Before ticket 1, the list is:

- address-book details: names, nickname, prefix and suffix, job position, company, gender, pronouns, contact information (such as phone numbers and email addresses) and addresses
- notes, each with the name of the user who wrote it
- relationships: the related person's name and the relationship type
- reminders
- important dates (for example birthdays)
- calls, tasks, gifts, loans, goals, pets, life events, mood entries, quick facts, labels and religion
- a list of the contact's photos and documents, with each file's name and upload date

**Scenario: Download a contact who has every kind of data**
Given a vault editor is on the page of a contact who has at least one item of every type in the data type list
When they select "Download full copy (JSON)"
Then the browser downloads one file
And a standard JSON reader opens the file without errors
And the file contains every item of every type in the data type list
And each note in the file shows the name of the user who wrote it

**Scenario: File uses readable labels, clear dates and a layout version**
Given a vault member is on the page of a contact who has a sister and a birthday recorded
When they select "Download full copy (JSON)"
Then the file shows the relationship type as "sister", not as an internal code
And every date in the file reads as year-month-day, for example 2026-06-11
And the file states which version of its layout it uses, so script writers can tell when the layout changes

**Scenario: File name shows the contact and the download date**
Given a vault member is on the page of a contact named Anna Kowalski on 29 September 2026
When they select "Download full copy (JSON)"
Then the saved file's name contains the contact's name
And the saved file's name contains the date 29 September 2026

**Scenario: Viewer sees the download option**
Given a user with viewer access is on the vault's contact list
When they open a contact's page
Then they see "Download full copy (JSON)" next to the vCard download link

**Scenario: Viewer gets the same file as a manager**
Given a vault manager has just downloaded the full copy of a contact
And a user with viewer access to the same vault is on that contact's page
When the viewer selects "Download full copy (JSON)"
Then the viewer's file has exactly the same content as the manager's file

**Scenario: Related people and attachments stay limited**
Given a vault member is on the page of a contact who has a relationship with another contact, one photo and one document
When they select "Download full copy (JSON)"
Then the file shows the related person's name and the relationship type
And the file shows no other details about the related person, such as their notes, dates or contact information
And the file lists the photo and the document by file name and upload date
And the file contains no photo or document contents
And the file contains no data from any other contact or any other vault

**Scenario: User who is not in the vault cannot download**
Given a signed-in user who is not a member of a contact's vault has the download address for that contact's full copy
When they open that address
Then no file downloads
And they see the same "not found or not allowed" response they see today when they try to open that contact's page

**Scenario: Contact with only a first name**
Given a vault member is on the page of a contact who has only a first name and no other data
When they select "Download full copy (JSON)"
Then the browser downloads the file
And the file shows every type in the data type list, each one empty rather than missing

**Scenario: vCard download still works as before**
Given a vault member is on a contact's page after this change ships
When they select the vCard download link
Then they get the same vCard file they got for that contact before this change

**Scenario: Self-hosted install needs no setup**
Given a self-hosted Monica installation that runs with its default settings
And a vault member is on a contact's page
When they select "Download full copy (JSON)"
Then the browser downloads the file
And nobody had to change any setting or install anything first

### Out of scope
- Downloading many contacts, or a whole vault, at once
- Importing the file back into Monica or into any other tool
- A human-readable or printable version, such as PDF or HTML
- The contents of attached photos and documents
- Details of related people beyond their name and relationship type
- Journal posts, even if they mention the contact
- The contact's activity history (the automatic log of changes on the contact's page)
- Formats built for a specific tool, such as CSV for spreadsheets
- Any change to the existing vCard download
- Tracking or usage metrics for this feature
- Keeping the file complete when new kinds of contact data appear later (ticket 3)

---

## 3. Keep the full copy complete when contacts gain new kinds of data

**Type:** Chore
**Initiative:** Provable trust
**Depends on:** 2

### Summary
This chore makes sure the full copy cannot silently fall behind when someone adds a new kind of contact data to Monica.

### Why now
Monica keeps growing, and contributors add new kinds of contact data over time. If the full copy misses one, the file stops being a full copy, and no user or developer notices. That silently breaks the "your data, only yours" promise that ticket 2 exists to keep. The spec's technical notes require the export to stay complete.

### Definition of done
- [ ] If a change adds a new kind of contact data and the full copy does not include it, the team finds out before that change is released.
- [ ] A developer can leave a new kind of data out of the full copy only by recording the reason where the team can see it.
- [ ] Every kind of data in the confirmed data type list from ticket 1 is covered today, and the team can show that covering holds.
- [ ] The contributor guide tells contributors that new kinds of contact data must appear in the full copy.

---

## Report

**Tracker:** none. This run has no `ticket_tool`, so no tickets were posted and no tracker IDs exist. The numbers 1–3 above are local IDs only.

**Initiative:** Provable trust (no backend grouping; assumed, see below)

**Tickets:**
1. Confirm every kind of data a user can record about a contact (Spike)
2. Download a full copy of a contact as a JSON file (Feature)
3. Keep the full copy complete when contacts gain new kinds of data (Chore)

**File saved:** output/tickets.md. The spec (output/spec.md) was left unchanged. Its `Status:` stays `Ready`, because no tracker IDs exist to put in `Tickets:`.

**Assumptions made during decomposition:**
- **Initiative.** The spec has no `Initiative:` field. The skill would normally stop and ask. Because this run is non-interactive, I used "Provable trust". That is the strategy capability the spec's "Why now" section cites. The PM should confirm it or replace it.
- **Open questions treated as settled.** The spec's two 🟡 open questions each had a working assumption, and I used both. Journal posts stay out of the file. The file carries a layout version number (ticket 2, "File uses readable labels, clear dates and a layout version"). Engineering can still revisit the version number at plan review.
- **The data type check became a Spike (ticket 1).** The spec says engineering must check the "In scope" list before ticketing. That check has not happened. A quick read of the architecture docs already shows three kinds of contact data missing from the list: group membership, the avatar and life metric values. The build approach is known, but the scope is not. So I made the check a time-boxed investigation with a concrete output, separate from the build. Ticket 2 carries the current list and updates to match the spike's result.
- **One Feature ticket, not several.** The label promises a "full copy". Shipping the file with only some data types would break that promise, so all data types ship together. Access rules (viewers allowed, non-members refused) also ship with the download, because the download must never exist without them. The skill says to merge tickets that always ship together, so these stay in ticket 2.
- **"Keep the export complete" is a separate Chore (ticket 3).** The spec's technical notes require it, but users see no change from it. It can ship after ticket 2 without breaking anything.
- **Privacy constraint in Context, not in a scenario.** "Monica sends no contact data to a third party" cannot be observed on screen. Ticket 2 states it as a constraint in its Context section.
- **No personas.** `docs/product/personas.md` does not exist, and neither does `docs/iterations/current.md`. The job-to-be-done uses a plain description of the user. No conflicts with in-flight work could be checked.

**Recommended next step:** The PM confirms the initiative. Then engineering runs the Spike (ticket 1), and the PM decides on each gap it finds, starting with group membership, the avatar and life metric values. Ticket 2 can begin once ticket 1's list is recorded in the spec.
