## Decision inventory for run f50ed908 (spec.md, tickets.md, qa.md)

The PM gave real answers to questions 1, 2 (partly), 3, 9 and 10. On questions 4 to 8 the answer was "No strong view. Use your judgement". I scored those as FLAGGED or SILENT, not ASKED.

| # | Decision | How | Evidence |
|---|---|---|---|
| D1 | One JSON file. No PDF or printable page. | FLAGGED | "JSON serves both user needs… riskiest assumption" |
| D2 | "Everything". **In:** names, nickname, gender, pronouns, company/job, religion, contact info, addresses, labels, groups, important dates, relationships, notes, reminders, tasks, calls, loans, goals, pets, life events, quick facts, linked journal posts, documents/photos (listed only). **Out:** avatar, activity feed, document/photo contents. **Never mentioned:** timeline events, mood tracking events. | FLAGGED (asked, no view). The exclusions are SILENT. | "every kind of record Monica can hold" |
| D3 | The vCard fields are covered by the in-scope profile fields. It never says "vCard included". | SILENT | "contact information… and addresses" |
| D4 | Documents and photos listed by file name and upload date only. Avatar excluded. | FLAGGED for documents/photos, SILENT for avatar | "file name and upload date only" |
| D5 | Other contact shown by name and relationship type only. States a privacy reason. | FLAGGED | "did not agree to be in someone else's export file" |
| D6 | Linked posts included. Other contacts in a post shown by name only. Another member's private posts left out. | FLAGGED (🟡 open question) | "export shows only posts that the exporting user can see" |
| D7 | Loans are included, but how the other party is shown is not addressed. Ticket 3 says these records "belong only to this contact". | SILENT; counterparty NOT SETTLED | "belong only to this contact" |
| D8 | Anyone who can open the contact, so view-only users can export. | FLAGGED | "What you can see is what you can export." |
| D9 | Asked (Q6: notes written by other members), but never decided. Author names and emails not covered. | NOT SETTLED | — |
| D10 | Implied "every record". Past, completed and done items are not named. | SILENT | "every record of those data types that exists" |
| D11 | Recurrence and schedule detail not addressed. | NOT SETTLED | — |
| D12 | "Export all data", placed next to the vCard download, which stays unchanged. File name has the contact's name and the export date. Tickets say to hide the action until tickets 2–5 ship. | Placement FLAGGED (🟢 open question, labelled "PM" though the PM gave no view). File name SILENT. Hiding FLAGGED. | "sits next to the vCard download" |
| D13 | Instant download, no size or time limits. | FLAGGED | "Contacts are small enough for an instant download" |
| D14 | Year-month-day, with time zone wherever there is a time. | SILENT | "one unambiguous, standard format" |
| D15 | A help page on file structure, written after the build. No versioning. | Help page FLAGGED (🟡). Versioning NOT SETTLED. | "Working assumption: yes, as a short help page" |
| D16 | No API endpoint. | NOT SETTLED | — |
| D17 | No audit or activity-feed entry. "No usage tracking" answers a different question (metrics). | NOT SETTLED | — |
| D18 | See below. | mixed | — |

**D18, other decisions the documents make:**
- **Scope set by the PM (ASKED):** single contact only, import out of scope, all plans on both hosted and self-hosted, no tracking, no designs.
- **FLAGGED:** "Why now" rests on the provable-trust strategy, with no trigger. All tickets are linked to the "Provable trust" initiative.
- **SILENT:**
  - Records in sections hidden by the page layout are still exported.
  - The file includes the export date/time and the vault name.
  - Empty data types appear as empty lists.
  - Export works without a file-upload service.
  - No data goes to a third party; the file goes straight to the browser.
  - Special characters come through unchanged.
  - The label "Export all data".

**(a) Build-detail leakage:** spec 0, tickets 0.
- Neither names any class, route, table, column, framework or API shape.
- I did not count references to doc paths (`docs/product/personas.md`, `output/tickets.md`) or to JSON as a format, which is a product choice.
- Closest cases: "file-upload service" and "download that contact's export directly, without going through the contact page". Both are generic, not build detail.

**(b) SILENT product-side decisions: 13**
1. vCard fields included
2. Avatar excluded
3. Activity feed excluded
4. Loans treated as belonging only to this contact
5. Past and completed items included by implication
6. File name
7. Date format
8. Hidden page-layout sections exported
9. Export metadata (date/time, vault name)
10. Empty lists
11. Works without a file-upload service
12. No third-party transmission
13. Button label

**(c) Summary:** The run read "full copy" as "everything" and listed about 20 data types, but it silently left out the avatar and activity feed and never mentioned timeline events or mood tracking. On third-party privacy it made explicit, labelled choices: name and relationship type only for relationships and journal-post contacts, and nothing the exporter cannot already see. It left notes written by other vault members, their names, and loan counterparties unresolved.
