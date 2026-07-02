# Export a contact as JSON

Last updated: 2 July 2026
Status: Ticketed
Tickets: T1, T2 — see docs/product/specs/export-contact-as-json-tickets.md (local, no tracker connected)

---

## Problem statement

A user can only export a single contact as a VCard file, so people and tools that work with JSON cannot easily read a contact's data.

## Why now

Monica's main differentiator is that a user's relationship data belongs to them alone. Export in an open, machine-readable format makes that promise concrete rather than stated. Today the only single-contact export is VCard (a contact-card file format), which many scripts and modern tools do not read as readily as JSON. Adding JSON export is a small, low-cost change that visibly proves the "your data is yours" position — with no change to the no-surveillance promise, because the export is a direct download to the user.

## Users affected

Note: this project has no `personas.md` yet, so the persona below is inferred from the strategy document and should be confirmed.

- **The portability-motivated user** (self-hoster or developer who chose Monica because the data is theirs): wants a machine-readable copy of a contact to back up, migrate, or build integrations on. Today they must parse a VCard file, which is more awkward for scripting than JSON.
- **The everyday privacy-conscious user**: benefits indirectly. A visible JSON export is proof the product does not lock their data in.

## Background

Monica already lets a user download one contact as a VCard file. The link sits in the contact page sidebar and is labelled "Download as vCard". Clicking it downloads a `.vcf` file for that one contact. There is no JSON export anywhere in Monica today — not for a single contact, and not for a whole account. A user who wants JSON must convert the VCard file themselves with an outside tool.

## Success metric

We will know Phase 1 worked if the export is used and does not fail:

- At least 50 JSON exports happen across all accounts within 30 days of launch.
- The download error rate stays below 1% over the same period.

Tune the 50-export target against the current VCard-export volume once that baseline is known — the point is a measurable floor, not this exact number.

---

## Scope

### In scope

- A new export option on the single-contact page, placed next to the existing "Download as vCard" link.
- Clicking it downloads one `.json` file for that one contact.
- The JSON contains the same contact fields the VCard export already includes: full name and structured name, nickname, emails, phone numbers, addresses, company and job title, gender, birthday, labels, and website links.
- Available to any signed-in user who owns the contact, on both the hosted service and self-hosted installations, with no paid-plan requirement.

### Out of scope

- Bulk export (exporting many or all contacts at once). This spec covers one contact per download.
- Monica-specific data that the VCard export does not carry: notes, relationships between contacts, reminders, tasks, and important dates other than birthday. These are a possible later phase.
- Re-importing a JSON file back into Monica. This spec is export only.
- The `jCard` standard (RFC 7095) or any other JSON contact format. This spec uses one plain JSON shape.
- Account-level or full-data (GDPR-style) export.

### Phasing

- Phase 1 (this spec): export one contact as JSON, matching the fields the VCard export already carries.
- Phase 2 (future, not committed): a richer JSON export that also includes Monica-specific data — notes, relationships, reminders, tasks. This is where the data-ownership advantage deepens, so it is the natural next step if Phase 1 lands well.

### Vertical slice check

If this shipped alone, a user could open any contact, click one link, and download that contact's data as a JSON file they can read in any JSON tool. That is new value they cannot get today. This is a real vertical slice, not an enabler story.

---

## Figma

No designs needed. The export option reuses the existing sidebar link pattern (a small text link next to "Download as vCard").

---

## Acceptance criteria

**AC-1**
GIVEN a signed-in user is viewing a contact they own
WHEN they open the contact page
THEN they see a JSON export option next to the existing "Download as vCard" link.

**AC-2**
GIVEN a signed-in user is viewing a contact they own
WHEN they click the JSON export option
THEN their browser downloads one `.json` file, named after the contact (for example `john-doe.json`).

**AC-3**
GIVEN a contact has values for name, emails, phone numbers, addresses, company, job title, gender, birthday, labels, and website links
WHEN the user exports that contact as JSON
THEN the downloaded file is valid JSON and contains each of those fields with the contact's values.

**AC-4 (unhappy path — sparse contact)**
GIVEN a contact has only a name and no other details
WHEN the user exports that contact as JSON
THEN the download still succeeds, the file is valid JSON, and the missing details are absent or empty rather than causing an error.

**AC-5 (unhappy path — not authorised)**
GIVEN a user is not signed in, or does not own the contact
WHEN they try to reach that contact's JSON export
THEN Monica does not return the file and blocks the request, the same way it does for the VCard export.

**AC-6 (unhappy path — special characters in name)**
GIVEN a contact whose name contains special characters, emoji, or only non-Latin script
WHEN the user exports that contact as JSON
THEN the download still succeeds with a safe, non-empty filename, falling back to the contact's identifier if the name reduces to an empty filename.

---

## Assumptions

- The set of fields the VCard export already carries is enough to deliver useful JSON in Phase 1. (Riskiest — see below.)
- Users reach the export only after signing in, and Monica already knows which contacts each user owns.
- A single plain JSON shape is acceptable for now. We do not need to match an external JSON contact standard to be useful.
- The export is a direct download to the user and sends no data to any third party, so it does not weaken the no-surveillance promise.

---

## Technical notes

- Must work on both the hosted service and self-hosted installations, with no paid-plan requirement — the same availability as the VCard export.
- Must not send contact data to any third party. The export is a direct download to the user only.
- The downloaded file must be valid JSON that standard tools can parse without special handling.
- Authorisation must match the VCard export: only a signed-in user who owns the contact can export it.

---

## Open questions

- 🟡 What exact JSON shape and field names should the file use? A clear, documented shape helps the interoperability goal. This can be settled at build time and does not block the spec. — owner: engineering at plan-review
- 🟡 Should a contact's photo or avatar be included in Phase 1? The VCard export handles it as metadata; JSON would need a defined approach. Working assumption: exclude it from Phase 1.
- 🟢 Should the JSON shape be published so integrators can rely on it? Yes over time, but not required for Phase 1. — owner: PM

---

## Before you build

**Decisions made:**

- Phase 1 exports one contact only. Bulk export is out of scope.
- Phase 1 JSON carries the same fields as the VCard export — no notes, relationships, reminders, or tasks yet.
- Same availability and permission rules as the VCard export: all signed-in owners, hosted and self-hosted, no paid plan.
- One plain JSON shape. Not `jCard`, not any other standard.

**Riskiest assumption:**
That VCard-parity fields are enough. The strategic value of JSON export is carrying the Monica-specific data VCard cannot (notes, relationships, reminders). If users expect that in v1, Phase 1 will feel thin and Phase 2 becomes urgent.

**Next step:**
Ready for ticket generation once you confirm this draft. No blockers.
