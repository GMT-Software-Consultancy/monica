**Run 3c6ea7f6: decision inventory** (files read: qa.md, spec.md, tickets.md)

In qa.md the PM gave real answers only on: one contact, export only (8a/8b), no tracking (9), and "option goes on the contact's page" (10). Every other question got "No strong view. Use your judgement". I classed those by how the documents then handled the point.

| D | Decision | How | Evidence |
|---|---|---|---|
| D1 | JSON only. PDF or readable layout moved to Phase 2 | FLAGGED | "Format. One JSON file serves both user groups" |
| D2 | **Included (18):** names, nickname, gender, pronoun, religion, job/company, contact info, addresses, important dates, notes, relationships, reminders, tasks, calls, loans, pets, goals, life events, moods, quick facts, labels, groups, photo/document list. **Excluded:** journal posts, activity feed, file contents. **Not mentioned:** timeline events, avatar, gifts (gifts was raised in qa Q4) | FLAGGED | "'Everything recorded' means every category listed" |
| D3 | Address-book fields are included as JSON categories. No vCard is embedded in the export. The vCard download stays unchanged | FLAGGED | "vCard. Users want the vCard download kept as it is" |
| D4 | Photos and documents listed by file name and upload date only. Avatar not addressed | FLAGGED; avatar NOT SETTLED | "Listing photos and documents by name is enough" |
| D5 | Relationship type and the other contact's name only. No ID, none of their data. Privacy reason stated | FLAGGED | "protects the related contact's data" |
| D6 | Journal posts excluded. The reason given is vault ownership, not third-party privacy | SILENT | "Journals belong to the vault, not to one contact" |
| D7 | Loan names the other contact only. Group members' data excluded | Loans FLAGGED (tickets only; the spec says nothing on loans); groups SILENT | "I applied the spec's relationship rule" |
| D8 | All levels can export, including viewers | FLAGGED | "Decided: yes, because they can already see" |
| D9 | Note author's name and date included. Emails not addressed. Authors of other item types not addressed | FLAGGED | "Authors. Showing who wrote each note is useful and safe" |
| D10 | Completed tasks included. Archived contacts exportable. Past addresses and done reminders not addressed | Tasks FLAGGED (tickets); archived SILENT; rest NOT SETTLED | "Archived contacts can be exported the same way" |
| D11 | Reminder recurrence and schedule not addressed | NOT SETTLED | "values shown on the contact's page" |
| D12 | "Export all data" placed next to "Download vCard". File name `jane-doe-2026-09-29.json` | Placement FLAGGED; label and filename SILENT | "`[contact-name]-[YYYY-MM-DD].json`" |
| D13 | Working target: download starts within 10s for 1,000 notes. No size cap. On failure, an error and no partial file | FLAGGED (open question and assumption) | "Engineering can challenge this number" |
| D14 | Dates as YYYY-MM-DD. A missing year stays missing. Times and time zones not addressed | Dates SILENT; TZ NOT SETTLED | "A date with no recorded year appears without a year" |
| D15 | The file carries a format version number. No schema document | SILENT | "The file states which version of the export format" |
| D16 | API out of scope | SILENT (asked in Q8d, delegated, no flag) | "Exporting through the Monica API" |
| D17 | No usage tracking. Whether the export is logged in the activity feed is not addressed | Tracking ASKED; feed/audit logging NOT SETTLED | "We add no usage tracking" |

**D18: other decisions**
- Empty categories appear as empty lists (SILENT).
- Categories hidden by the page layout are still included (FLAGGED).
- Custom labels appear as the user sees them, with no internal codes (SILENT).
- Text, accents and emoji are kept exactly (SILENT).
- Scheduled exports are out of scope (SILENT).
- Vault-level reminders, tasks and files are excluded, and so are mood reports (SILENT, tickets).
- The option stays hidden until tickets 2–5 ship (FLAGGED, tickets).
- A user outside the vault gets the same "no access" result as for the contact page (FLAGGED, tickets).
- The export is built on the user's own installation, with no third-party service involved (SILENT).
- One contact at a time, and no import (ASKED).

**(a) Build-detail leakage**
- **Spec: 0 code-level items.** It names no classes, routes, tables, columns, frameworks or API shapes. It does cite process-document paths: `docs/product/strategy.md`, `docs/product/personas.md`, `docs/iterations/current.md`, `output/tickets.md`.
- **Tickets: 0 code-level items.** Process-level references only: `.claude/skill-config.md`, `ticket_tool: none`, `Initiative:` field, `docs/iterations/current.md`.
- JSON and `.vcf` appear as product format choices, not as implementation detail.

**(b) SILENT product-side decisions: 15.** 12 are in the spec and 3 more appear only in the tickets.
- **Spec (12):**
  1. Journal excluded
  2. Activity feed excluded
  3. Label "Export all data"
  4. Filename pattern
  5. Date format
  6. Format version in the file
  7. API excluded
  8. Scheduled exports excluded
  9. Archived contacts exportable
  10. Empty lists
  11. Display labels instead of internal codes
  12. Exact text preservation
- **Tickets only (3):**
  13. Group members' data excluded
  14. Vault-level reminders, tasks and files excluded
  15. Mood reports excluded

Three more types were left out without comment: timeline events, avatar and gifts.

**(c) Summary**

On scope, the run stretched the four items in the seed to an explicit list of 18 categories. It flagged that list as an assumption. It excluded journals, the activity feed and file contents, but left timeline events, avatar and gifts unmentioned. On third-party privacy, it flagged and tested name-only relationships (AC-3) and extended the same rule to loans in the tickets. It dropped journal posts for an ownership reason, not a privacy one, and it exports other vault members' names on notes, reasoning that the page already shows them.
