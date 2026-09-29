**Run 393d9d26: decision inventory** (files read: qa.md, spec.md, tickets.md)

Note on status: the stand-in PM gave real answers only to Q1 (one contact, export only, no import), the plan part of Q6 (every plan gets it), Q8 (no tracking) and Q10 (no designs), plus "it goes on the contact's page" in Q9. Every other question got "No strong view… write the assumption down". Where a question was asked but deferred, the status below records how the documents then handled it. Tickets 6 and 7 say "The PM decided" about relationships and photos. That is wrong: the PM deferred both.

| # | Decision | How | Evidence |
|---|---|---|---|
| D1 | One JSON file. No PDF, HTML or Markdown. | FLAGGED (asked, deferred) | "Riskiest: One JSON file serves both user groups" |
| D2 | **In:** name parts, gender, pronoun, religion, job, company, contact_information, addresses, important_dates, notes, reminders, relationships, calls, tasks, loans, goals, pets, life_events (with their timeline event), mood_tracking_events, quick_facts, labels, groups, and photo/document metadata. **Out:** activity feed, journal posts, gifts, file contents. **Not mentioned:** avatar, standalone timeline events. | Asked and deferred. The section list is SILENT (in the scope list, not in assumptions). Gifts are FLAGGED. | "every record a user has entered about the contact" |
| D3 | Yes. vCard-type fields are in the file. | SILENT (asked, deferred, no assumption written) | "`contact_information`: phone numbers, emails, social profiles" |
| D4 | Photos and documents: metadata only (name, MIME type, size, upload time). No contents, no URLs, no call to Uploadcare. Avatar: not mentioned. | FLAGGED. Avatar NOT SETTLED. | "metadata only… never contains file contents or download URLs" |
| D5 | Relationship type, the other contact's full name and their Monica ID, nothing more. Justified by keeping it a one-contact export, not by the other person's privacy. | FLAGGED | "Including the related contact's data would turn a one-contact export into a multi-contact export" |
| D6 | Journal posts excluded | SILENT | "Journals belong to the vault, not to the contact" |
| D7 | Loans and life events show the other contact's name and ID only | SILENT (tickets 4 and 5) | "shows only that contact's name and Monica ID" |
| D8 | Manager, editor and viewer can all export, on every plan | FLAGGED (levels). ASKED (plans) | "including viewers… matches the existing vCard route" |
| D9 | Note authors' names included. Emails not mentioned. | FLAGGED (names). Emails NOT SETTLED. | "Notes written by other vault members belong in the export, with the author's name" |
| D10 | Completed tasks included with their state. Soft-deleted records excluded. Past addresses and done reminders not addressed. | SILENT / NOT SETTLED | "each entry shows whether the task is completed" |
| D11 | Reminder recurrence and schedule not specified | NOT SETTLED | "every field the contact page shows for that record" |
| D12 | "Export all data" action beside the vCard link, which stays unchanged. File name `monica-contact-{name}-{YYYY-MM-DD}.json`, falling back to the contact ID. | Placement FLAGGED (PM gave the page only). File name SILENT. | "sits beside the vCard download and does not replace it" |
| D13 | File built during the request, no background job. 1,000 notes in under 5s. No size limit. | FLAGGED (built in request). SILENT (5s target). | "One contact's data is small enough to build during the request" |
| D14 | ISO 8601 in UTC. Date-only values as YYYY-MM-DD. Partial dates as separate parts with null for missing ones. Viewer's time zone not considered. | SILENT | "Write timestamps as ISO 8601 in UTC" |
| D15 | `format: "monica-contact-export"`, `format_version: 1`. No schema documentation for users. | SILENT. Documenting the shape NOT SETTLED. | "`format_version` (value `1`)" |
| D16 | No API endpoint for scripts. Only a web route is given. | NOT SETTLED | none |
| D17 | Not recorded in the activity feed, no tracking. Server or audit logs not mentioned. | FLAGGED (feed). ASKED (tracking). | "Users do not expect exports to appear in the contact's activity feed" |
| D18 | (a) Lookup values as readable labels, not IDs. (b) null rather than empty string, [] for empty sections. (c) UTF-8 with non-ASCII kept unescaped. (d) No copy kept on the server, no third-party calls. (e) Same 403/404 as the vCard route. (f) Action hidden until tickets 1–7 all ship. (g) No import, one contact only. (h) Why-now taken from the strategy. | (a)–(f) SILENT. (g) ASKED. (h) FLAGGED. | "Users should not see it until tickets 1 to 7 have all shipped" |

**(a) Build-detail leakage.** JSON section keys are not counted: they describe the file's shape, which is a product output.
- **Spec: about 19 items**, almost all in "Technical notes". Examples:
  - `POST /vaults/{vault}/contacts/{contact}/export`
  - `contact-owner` / `vault-viewer` gates
  - `BaseService` and its permission names
  - `ContactFeedItem`
  - `ManageNotes` / `ManageContactImportantDates`, Laravel route model binding, Inertia/Vue, eager loading
- **Tickets: about 3 items.** `application/json; charset=utf-8`, HTTP 403/404, Docker Compose. Uploadcare is borderline.

**(b) SILENT product decisions: 14.** D3, D6, D7, D10, D12 file name, D13 5s target, D14, D15, and D18 (a)–(f). That rises to 15 if D2's section list is counted.

**(c) Summary.** The run took an "everything" scope: 21 sections, with the activity feed and journal posts excluded as fact and gifts left as an open question. It never mentions the avatar, past addresses or reminder recurrence. It limits other contacts to name and ID, which contains third-party exposure, but justifies this as keeping to one contact rather than as privacy. It also chose, as a flagged assumption, to let viewers export notes written by other vault members, with those authors' names.
