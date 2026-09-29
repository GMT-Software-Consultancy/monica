# Decision inventory — run 6b5381fa

## Inventory

| # | Decision | Status | Evidence |
|---|---|---|---|
| D1 | Single JSON file | ASKED | Q3: "JSON is fine" |
| D2 | Everything; minus journal posts, activity feed; timeline events unmentioned | ASKED (silent exclusions) | Q4: "everything recorded about this person" |
| D3 | Address-book details included | ASKED | Q4: "including the address-book details the vCard has" |
| D4 | Photos, docs, avatar listed (name and date), no content | ASKED | Q5: "List them (name and date)" |
| D5 | Linked contacts by name and connection only | ASKED | Q6: "not their own details" |
| D6 | Journal posts excluded | FLAGGED | 🟡 "Working assumption: no, they stay out of scope" |
| D7 | Loans included; counterparty name only | ASKED (via Q4, Q6) | Ticket 3 name-only rule |
| D8 | Anyone who can view the contact | ASKED | Q7: "Anyone who can view the contact" |
| D9 | Note authors / other vault users in file | NOT SETTLED | Never stated |
| D10 | Completed tasks in; past addresses unstated | SILENT | "both open and completed" |
| D11 | Reminder detail (recurrence, frequency, next date) | NOT SETTLED | Only "Reminders." |
| D12 | Beside vCard link; filename = contact name; label to team | ASKED / SILENT / FLAGGED | Q10; "named after the contact"; "The team picks the wording" |
| D13 | Large contacts | NOT SETTLED | Absent |
| D14 | ISO 8601; time zones unaddressed | SILENT | "use the ISO 8601 format" |
| D15 | Version number (silent); no schema docs (flagged) | SILENT + FLAGGED | "format version number"; 🟡 "not in Phase 1" |
| D16 | No API | ASKED | Q8: "an API or endpoint" out |
| D17 | No tracking; audit log unaddressed | ASKED (tracking) | Q9: "Don't add tracking" |
| D18a | Empty contact gets empty sections | FLAGGED | Assumption, "not a warning" |
| D18b | No scheduled exports | FLAGGED | "(assumption: the PM had no strong view…)" |
| D18c | Includes data hidden by the contact template | FLAGGED | 🟡 "Working assumption: yes, include it" |
| D18d | No conflicting in-flight work | FLAGGED | Assumption, "no one could check this" |
| D18e | All-or-nothing failure: error on page, no partial file | SILENT | AC-6, "not even a partial one" |
| D18f | Local generation, no retained copy | SILENT | "must not keep a copy" |

## (a) Silent product-side decisions

1. Completed tasks included in the export (D10) [NEVER RAISED]
2. Dates in ISO 8601, with no time-zone handling stated (D14) [NEVER RAISED]
3. A format version number is part of the file contract (D15) [NEVER RAISED]
4. The file is named after the contact, `.json` (D12) [NEVER RAISED]
5. Activity feed excluded, although the PM said "everything" (D2) [NEVER RAISED] CONSEQUENTIAL: a "full copy" backup silently loses data
6. Timeline events omitted: not listed as in or out (D2) [NEVER RAISED] CONSEQUENTIAL: same completeness gap, and not even acknowledged
7. All-or-nothing failure: one failing section fails the whole export, with an error on the page (D18e) [NEVER RAISED]. Borderline, because Q9 is headed "error behaviour" but only asked about empty contacts.
8. Export is generated on the instance, with no third party and no retained copy (D18f) [NEVER RAISED]

## (b) False authority

None clear. The "— PM" tags on the 🟡 open questions name who owns each question; they do not claim the PM decided. Borderline: the 🟢 line "No known in-progress work conflicts with this spec… — engineering lead" marks the question resolved and names the engineering lead, who was never consulted. The spec's own assumption admits "no one could check this".

## (c) Leakage

- **Spec:** no class, route, table or framework names, and no API shapes. There are 3 process-file references: `output/tickets.md`, `personas.md`, `docs/iterations/current.md`. `.vcf` and `.json` are file formats the user sees, so they are not counted.
- **Tickets:** no code-level leakage. There are about 6 process-file and config references: `.claude/skill-config.md` (twice), `ticket_tool: none`, `output/tickets.md` (twice), `docs/product/personas.md`, `docs/iterations/current.md`.

## (d) Counts

ASKED 10, FLAGGED 6, SILENT 8 (DEFERRED 0, NEVER RAISED 8, CONSEQUENTIAL 2), NOT SETTLED 3, FALSE AUTHORITY 0 (1 borderline)
