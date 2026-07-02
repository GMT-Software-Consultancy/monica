# Export a contact as JSON — Tickets

Generated: 2 July 2026
Spec: docs/product/specs/export-contact-as-json.md
Tracker: Local markdown (no tracker connected — written to this file at PM request)
Initiative: Contact JSON export

---

## 1. Download a contact as JSON

**Ticket ID:** T1
**Type:** Feature
**Initiative:** Contact JSON export
**Depends on:** None

### Summary
A signed-in contact owner can download one contact's data as a JSON file that carries the same fields the vCard export includes.

### Job to be done
When a portability-motivated user wants a machine-readable copy of a contact, they need to download that contact as JSON, so they can back it up, migrate it, or build an integration on it.

### Navigation context
**Entry point(s):** The contact's JSON export address, requested by a signed-in user who owns the contact. A script can request it directly, or the link added in ticket 2 can trigger it.
**Exit point(s):** The browser downloads a `.json` file for that contact.
**Screen/component:** No page. This is a download action, not a screen.

### Context
Monica exports a single contact only as a vCard file today. Tools and scripts that work with JSON cannot read that easily. This ticket adds the JSON export itself, so a contact's data can leave Monica in a machine-readable form. It does not add any visible link — ticket 2 does that.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: Owner downloads a full contact as JSON**
Given a signed-in user who owns a contact with a name, emails, phone numbers, addresses, company, job title, gender, birthday, labels, and website links
When they request that contact's JSON export
Then a JSON file downloads, named after the contact (for example `john-doe.json`)
And the file is valid JSON that contains each of those fields with the contact's values.

**Scenario: A user who does not own the contact is refused**
Given a user who is not signed in, or is signed in but does not own the contact
When they request that contact's JSON export
Then no file downloads
And Monica refuses access, the same way it does for the vCard export.

**Scenario: A contact with only a name still exports**
Given a signed-in owner of a contact that has only a name and no other details
When they request that contact's JSON export
Then a valid JSON file downloads
And the missing details are empty or absent rather than causing an error.

**Scenario: A contact whose name has special characters gets a safe filename**
Given a signed-in owner of a contact whose name contains special characters, emoji, or only non-Latin script
When they request that contact's JSON export
Then a valid JSON file downloads with a safe, non-empty filename
And the filename falls back to the contact's identifier if the name reduces to an empty filename.

### Out of scope
- The contact's photo or avatar. Excluded from Phase 1.
- Notes, relationships, reminders, tasks, and important dates other than birthday.
- Exporting more than one contact at a time.
- The visible link on the contact page. That is ticket 2.

---

## 2. Add a "Download as JSON" option to the contact page

**Ticket ID:** T2
**Type:** Feature
**Initiative:** Contact JSON export
**Depends on:** T1

### Summary
A signed-in contact owner sees a "Download as JSON" link next to the existing "Download as vCard" link and downloads the file in one click.

### Job to be done
When an everyday privacy-conscious user is looking at a contact, they need an obvious way to download that contact as JSON, so they can keep their own copy of the data without using an outside tool.

### Navigation context
**Entry point(s):** The contact profile page, in the sidebar where the "Download as vCard" link sits.
**Exit point(s):** The browser downloads a `.json` file and the user stays on the contact profile page.
**Screen/component:** The contact profile page sidebar.

### Context
Ticket 1 makes the JSON export available but not visible. This ticket adds the link, so any owner can reach it without building a request by hand. The link sits next to the vCard link, so users find the two exports together.

### Designs
No designs needed. The link reuses the existing sidebar text-link pattern.

### Acceptance criteria

**Scenario: Owner downloads a contact from the sidebar link**
Given a signed-in user who owns a contact and is on that contact's profile page
When they click "Download as JSON" in the sidebar
Then a `.json` file for that contact downloads
And they stay on the contact profile page
And the link sits next to the "Download as vCard" link.

**Scenario: The link is not offered to a user who cannot export the contact**
Given a user viewing a contact they can see but do not own
When they look at the contact profile page
Then no "Download as JSON" link is available to them
And this matches how the "Download as vCard" link behaves for that user.

**Scenario: The link works for a contact with only a name**
Given a signed-in owner on the profile page of a contact that has only a name
When they click "Download as JSON"
Then a valid `.json` file downloads without an error.

### Out of scope
- The export behaviour and file contents. That is ticket 1.
- Any "export all contacts" action anywhere in the interface.
- Any change to the existing "Download as vCard" link.
