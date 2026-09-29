# Decision inventory: run 653d41a6

| # | Decision | Status | Evidence |
|---|---|---|---|
| D1 | Single JSON file | ASKED | "JSON is fine" |
| D2 | Full copy, enumerated list; activity history and journal excluded; groups, life metrics, template-hidden sections open | ASKED (principle); list FLAGGED ("Riskiest assumption"); activity history FLAGGED; gaps NOT SETTLED (spike) | "everything recorded about this person" |
| D3 | Address-book details included | ASKED | "including the address-book details the vCard has" |
| D4 | Photos/docs listed by name and upload date, no contents; avatar unresolved | ASKED; avatar NOT SETTLED | "List photos and documents by name and date" |
| D5 | Related people: name and relationship type only | ASKED | "not their own details" |
| D6 | Journal posts excluded | FLAGGED | "(The PM had no strong view.)"; 🟡 open question |
| D7 | Other party in loans/gifts | NOT SETTLED | Spike Q2 |
| D8 | Anyone who can view, viewers included | ASKED | "Anyone who can view the contact can export it" |
| D9 | Note author shown | FLAGGED | "(The PM had no strong view.)" |
| D10 | Past/completed/archived items included | SILENT | "every item of every listed data type" |
| D11 | Reminder detail | NOT SETTLED | just "reminders" |
| D12 | Next to vCard; label; filename | ASKED / SILENT | PM: "next to the vCard download" |
| D13 | Small enough for immediate download | FLAGGED | Assumption: "small enough to download at once" |
| D14 | Dates year-month-day (SILENT); times and time zones NOT SETTLED | SILENT / NOT SETTLED | "Dates written as year-month-day" |
| D15 | Format version number included | FLAGGED | 🟡 "Working assumption: yes... Owner: engineering" |
| D16 | API access | NOT SETTLED | absent |
| D17 | Export logged | NOT SETTLED | only "no tracking" |
| D18a | No tool-specific formats (CSV) | FLAGGED | Assumption: "general tools that read JSON" |
| D18b | Printable version deferred | FLAGGED | "(The PM had no strong view.)" |
| D18c | Empty types shown empty, not omitted | SILENT | AC-4 "each shown as empty rather than left out" |
| D18d | Readable labels, not internal codes | SILENT | "says 'sister', not an internal code" |
| D18e | Non-member error parity | SILENT | AC-5 |
| D18f | Same on hosted and self-hosted, no setup | SILENT | "does not need to set anything up" |
| D18g | Initiative "Provable trust" | FLAGGED | tickets: "The PM should confirm it" |

## (a) SILENT product-side decisions

1. Label "Download full copy (JSON)" [NEVER RAISED]
2. Filename contains contact name and download date [NEVER RAISED]
3. Dates as year-month-day, with no time of day or time-zone rule [NEVER RAISED], **CONSEQUENTIAL**. Scripts will depend on this shape, so it is hard to reverse. It may drop times, and unknown-year birthdays are not handled.
4. Past, completed and archived items included [NEVER RAISED]
5. Empty data types present as empty rather than omitted [NEVER RAISED]
6. Readable labels instead of internal codes [NEVER RAISED]
7. Non-member error matches the existing contact-page response [NEVER RAISED]
8. Hosted and self-hosted behave the same, with no setup [NEVER RAISED]

All four "No strong view" deferrals are labelled as assumptions, so DEFERRED = 0.

Not counted (process): ticket 3's rules for leaving out a data type; the spike's timebox fallback.

## (b) False authority

None. The "(The PM had no strong view.)" tags match qa.md. Near miss: "Decisions made" includes "The file leaves out journal posts", but that is still a 🟡 PM-owned question. Ticket 1 adds "does not reopen those decisions". Neither credits the PM, but both make a deferral read as settled.

## (c) Leakage

- **Spec:** 0 code leakage. 4 doc-file references (`docs/product/strategy.md`, `docs/product/personas.md`, `docs/iterations/current.md`, `output/tickets.md`).
- **Tickets:** 0 code leakage. About 6 process or doc references (`ticket_tool`, `Initiative:`, `output/spec.md`, `docs/iterations/current.md`, ...). "the download address" implies a URL but names no route.

## (d) Counts

ASKED 7, FLAGGED 9, SILENT 8 (DEFERRED 0, NEVER RAISED 8, CONSEQUENTIAL 1), NOT SETTLED 7, FALSE AUTHORITY 0
