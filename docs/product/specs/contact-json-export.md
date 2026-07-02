# Contact JSON Export

Last updated: 2 July 2026
Status: Ready
Initiative: Contact JSON Export
Tickets: (fill in once created)

---

## Problem statement

A user can export one contact as a vCard file, but cannot export that contact as a JSON file that other tools and scripts can read directly.

## Why now

Monica already treats data portability as a feature: every contact can be downloaded as a vCard. vCard suits address-book apps, but it is awkward for scripts and integrations, which expect JSON. JSON is the common format for programmatic data exchange. Adding a JSON export lets people build on their own Monica data without writing a vCard parser first. This is a small, self-contained addition next to an export path that already exists.

## Users affected

No `personas.md` exists in this project, so these personas are described from the product itself. Confirm or rename them when the personas doc lands.

- **The self-hosting Monica user** — runs their own Monica instance and wants their relationship data in a portable, machine-readable form. Today they can only get vCard.
- **The developer or integrator** — writes a script or a small tool that reads contact data out of Monica. A vCard forces them to parse an address-book format; JSON is far easier to consume.

## Background

Today a user opens a contact's page and clicks a download action. The system builds a vCard for that one contact and the browser downloads a `.vcf` file. There is no equivalent for JSON. Anyone who wants JSON must either call a broader interface or convert the vCard themselves. Both are more work than the task deserves.

---

## Scope

### In scope

- A second download action on the contact page, next to the existing vCard download.
- Exporting a single contact as a JSON file.
- Including the same **core contact fields** the vCard export already covers: full name and its parts, nicknames, gender, important dates (such as birthdate), postal addresses, contact methods (such as phone numbers and email addresses), work and job information, and labels.
- Downloading the result as a `.json` file named after the contact, following the same naming pattern as the `.vcf` file today.
- The export is available to any user who can already view the contact.

### Out of scope

- Bulk export — exporting more than one contact at a time.
- A JSON import feature. This spec only exports.
- A public or documented JSON API endpoint. This is a file download, not an interface for other systems to call.
- Non-core contact data: notes, activities, relationships, reminders, gifts, tasks, and journal entries.
- Choosing or adopting an external JSON standard. The export uses Monica's own field shape.

### Phasing (if applicable)

- Phase 1: this spec — single-contact JSON download of core fields.
- Phase 2 (not committed): bulk export, extended data, or a documented schema for integrators, if demand appears.

### Vertical slice check

If this shipped alone, a user could open any contact, click a button, and download that contact's core details as a JSON file that a script can read. That is observable value on its own.

---

## Figma

No designs needed. The new action reuses the existing download control next to the vCard action. It adds one button in the same place, with the same behaviour.

---

## Acceptance criteria

**AC-1**
GIVEN a user is viewing a contact they can access
WHEN they click the "Export as JSON" action
THEN the browser downloads a `.json` file named after the contact.

**AC-2**
GIVEN a contact has core fields filled in (name, gender, an important date, an address, a contact method, work information, and a label)
WHEN the user exports that contact as JSON
THEN the downloaded file is valid JSON and contains each of those core fields with the contact's values.

**AC-3**
GIVEN a contact and the same contact exported as a vCard
WHEN the user exports that contact as JSON
THEN the JSON contains the same core fields the vCard export includes, and no non-core data (no notes, activities, relationships, reminders, or gifts).

**AC-4 (unhappy path)**
GIVEN a contact with only a name and no other core fields filled in
WHEN the user exports that contact as JSON
THEN the download still succeeds and returns valid JSON, with the empty fields represented consistently (for example, empty or null values) rather than an error.

**AC-5 (unhappy path)**
GIVEN a user who does not have access to a contact's vault
WHEN they attempt to export that contact as JSON (for example, by calling the export directly)
THEN the export is refused and no contact data is returned.

---

## Assumptions

- The core contact fields the vCard export already includes are the right set for the JSON export. If that set is wrong, the scope of AC-2 and AC-3 changes.
- A user who can view a contact is allowed to export it, the same rule the vCard export uses.
- A downloaded file meets the need. Users do not need the JSON shown on screen or copied to the clipboard in this phase.
- Consumers of the file will adapt to Monica's own JSON field shape, because no external JSON standard is being adopted.

## Technical notes

- The export must produce valid, well-formed JSON that a standard JSON parser can read.
- The JSON export must be available wherever the vCard export is available for a single contact.
- The export must work for a contact with sparse data (few fields filled in) without failing.
- The JSON export must not change or break the existing vCard export.

---

## Open questions

- 🟡 What are the exact field names and nesting in the JSON? Proceeding on the decision to mirror the core vCard fields as a plain JSON object. A developer can finalise names at build time. — owner: PM / eng
- 🟡 Should the field shape be written down anywhere for integrators, or is the file self-explanatory for Phase 1? Proceeding without published documentation in Phase 1. — owner: PM
- 🟢 File is named after the contact with a `.json` extension, matching the `.vcf` pattern. Documented, not open. — owner: PM

---

## Before you build

**Decisions made:**

- The JSON export mirrors the same core contact fields the vCard export covers. It uses Monica's own field shape, not an external standard.
- Single contact only. No bulk export in Phase 1.
- Any user who can view a contact can export it.
- The result is a downloaded `.json` file, following the vCard naming pattern.

**Riskiest assumption:**
The core vCard field set is the right content for the JSON export, and consumers will accept Monica's own JSON shape rather than a recognised standard.

**Next step:**
Ready for ticket generation — no blockers. A developer decides the exact JSON field names and nesting at build time.
