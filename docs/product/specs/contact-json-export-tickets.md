# Contact JSON Export — Tickets

Generated: 2 July 2026
Spec: docs/product/specs/contact-json-export.md
Tracker: none (draft only — not yet posted)
Initiative: Contact JSON Export

---

## 1. Export a single contact as a JSON file

**Type:** Feature
**Initiative:** Contact JSON Export
**Depends on:** None

### Summary
A user can download one contact's core details as a JSON file from the contact's page.

### Job to be done
When a Monica user wants to reuse a contact's data in a script or another tool, they need that contact's details in a plain, machine-readable form, so they can read the data without parsing a vCard first.

### Navigation context
**Entry point(s):** The contact's own page, at the same download control that offers the vCard download today.
**Exit point(s):** The user stays on the contact's page. The browser downloads a `.json` file named after the contact.
**Screen/component:** The single-contact view, next to the existing "download vCard" action.

### Context
Monica already lets a user download a contact as a vCard. vCard is awkward for scripts and integrations, which expect JSON. This ticket adds a second download action that returns the same core contact fields as JSON. It reuses the existing download pattern, so the change is small and self-contained.

### Designs
No designs needed. The action reuses the existing download control next to the vCard action and behaves the same way.

### Acceptance criteria

**Scenario: User downloads a contact as JSON**
Given the user is on the page of a contact they can access
When they click the "Export as JSON" action
Then the browser downloads a file named after the contact with a `.json` extension
And the file contains valid JSON.

**Scenario: The JSON contains the contact's core fields**
Given the user is on the page of a contact that has a name, a gender, an important date, an address, a contact method, work information, and a label
When they export that contact as JSON
Then the downloaded file contains each of those core fields with the contact's values.

**Scenario: The JSON matches the vCard core fields and excludes other data**
Given the user is on the page of a contact that also has notes, activities, relationships, reminders, and gifts
When they export that contact as JSON
Then the file contains the same core fields the vCard download includes
And the file does not contain notes, activities, relationships, reminders, or gifts.

**Scenario: Exporting a contact that has only a name**
Given the user is on the page of a contact that has a name and no other core fields filled in
When they export that contact as JSON
Then the download still succeeds
And the file contains valid JSON with the empty fields shown consistently as empty or null values, not an error.

**Scenario: A user without access is refused**
Given a user who does not have access to a contact's vault
When they attempt to export that contact as JSON directly
Then the export is refused
And no contact data is returned.

### Out of scope
- Bulk export of more than one contact at a time.
- Importing contacts from JSON.
- A public JSON endpoint for other systems to call.
- Non-core data: notes, activities, relationships, reminders, gifts, tasks, and journal entries.
- Showing the JSON on screen or copying it to the clipboard.
