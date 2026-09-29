# Decision inventory: run 9e1a61e7 (plan.md + qa.md)

## Inventory

| # | Decision | Status | Evidence |
|---|---|---|---|
| D1 Format | One JSON file | ASKED | PM: "JSON is fine" |
| D2 Scope | Everything recorded, including vCard details. Contact feed excluded (flagged). Journal posts as references (flagged) | ASKED | PM: "full copy of everything recorded" |
| D3 Address-book details | Structured fields plus the raw vCard string embedded | ASKED | PM: "including the address-book details the vCard has" |
| D4 Photos/docs/avatar | Metadata only, no files, no URLs | ASKED | PM: "by name and date" |
| D5 Relationships | Reference only (id, name, url); soft-deleted contacts skipped (flagged, A4) | ASKED | PM: "not their own details" |
| D6 Journal posts | Title, date and journal name only | FLAGGED | A5: "listed as references only" |
| D7 Loans/shared records | Counterparties as references | ASKED | Q4 covered "loans"; PM: "how they're connected" |
| D8 Who may export | Any viewer | ASKED | PM: "Anyone who can view the contact" |
| D9 Other users' info | Note, task and call author names included | ASKED | Q8; PM: "Viewers can already see all of it" |
| D10 Past/completed | Past addresses, completed tasks, settled loans and inactive goals all included | SILENT | schema: `is_past_address`, `completed`, `settled` |
| D11 Reminder detail | Label, type, frequency, partial date, trigger history | SILENT | "last_triggered_at… number_times_triggered" |
| D12 Placement/label/filename | Placement ASKED; label and filename unlabelled | ASKED (+SILENT) | "Download all data (JSON)"; "fall back to `contact-{id}.json`" |
| D13 Large contacts | No limit, built in memory | SILENT | "small enough to build in memory" |
| D14 Dates/time zones | ISO UTC, partial-date objects, locale labels next to fixed keys | FLAGGED | A3; Q9 answered "No strong view" |
| D15 Versioning | `format_version: 1`; no user-facing documentation of the shape | FLAGGED | A7 |
| D16 API | Not addressed | NOT SETTLED | — |
| D17 Logging | No audit logging | FLAGGED | A1; Q7 answered "No strong view" |
| D18 Other | Hidden-template modules exported (A2); activity feed excluded (A6) | FLAGGED | "still exported"; "left out" |

## (a) SILENT product-side decisions

1. Past, completed, settled and inactive items are all included (past addresses, completed tasks, settled loans, inactive goals). [NEVER RAISED]
2. Reminder export includes trigger history (`last_triggered_at`, `number_times_triggered`). [NEVER RAISED]
3. Link label is "Download all data (JSON)". [NEVER RAISED]
4. Filename is a slug of the contact's name, falling back to `contact-{id}.json`. [NEVER RAISED]
5. No size limit or async handling; "no streaming or queued job is needed". [NEVER RAISED]
6. The file carries the vault id and name, the instance URL and the app version (the `source`/`vault` blocks). [NEVER RAISED] Mild privacy point.
7. File metadata adds MIME type and size, beyond the PM's "name and date", while the table says "name and date only". [NEVER RAISED, goes beyond the Q3 answer]
8. Lists are sorted oldest first so exports can be diffed. [NEVER RAISED]

None judged CONSEQUENTIAL. All are reversible.

## (b) FALSE AUTHORITY

- Header "Assumptions I made (requester had no strong view)" covers A4–A7. The PM said "No strong view" only on logging, on template-disabled modules and on Q9. Soft-deleted contacts, the activity feed and versioning were never put to the PM. For journal posts (A5), Q8 asked about "journal entries written by other vault members". The PM answered "a full copy of everything recorded", which points against the references-only choice. Counted: **1**.
- Borderline, not counted: "Moving the vCard download off the session-flash mechanism… was explicitly left as it is". The PM's "Keep the vCard download as it is" was about placement in the UI, not the mechanism.

## (c) Leakage

Skipped (the output is a plan).

## (d) Counts

ASKED 9, FLAGGED 5, SILENT 8 (DEFERRED 0, NEVER RAISED 8, CONSEQUENTIAL 0), NOT SETTLED 1, FALSE AUTHORITY 1
(D-row statuses: 9 ASKED, 5 FLAGGED, 3 SILENT, 1 NOT SETTLED.)
