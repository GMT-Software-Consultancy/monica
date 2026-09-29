## Decision inventory for run 5c94a1b4 (plan.md + qa.md)

qa.md held 10 questions. Only three got substantive answers: Q5 ("No. It's export only."), Q8 in part ("It goes on the contact's page") and Q10 in part ("one contact only… no bulk"). Every other answer was "No strong view. Use your judgement." Where the plan then recorded a decision in its "Assumptions" table, I marked it FLAGGED.

| D | Decision | How | Evidence |
|---|---|---|---|
| D1 | One pretty-printed UTF-8 JSON file | FLAGGED | "One pretty-printed UTF-8 **JSON** file" |
| D2 | **In:** profile basics (the name parts, nickname, maiden name, prefix/suffix, gender, pronoun, archived flag, timestamps), notes, relationships, reminders, important dates. **Out:** calls, tasks, loans, goals, pets, life events/timeline, mood, quick facts, posts, groups, labels, contact info, addresses, job, religion, documents, photos, avatar. **Not mentioned:** activity feed, company. | FLAGGED | "the four items the requester named, plus enough identity" |
| D3 | vCard fields are left out and the vCard is not embedded. It stays a separate link. | FLAGGED | "We deliberately **do not embed** the vCard" |
| D4 | All excluded: no binaries and no metadata | FLAGGED | "neither the binaries nor their metadata" |
| D5 | Related contact's id and name, the relationship type from this contact's side, and the group. None of their other data. A privacy reason is given. | FLAGGED | "avoids leaking other contacts' data" |
| D6 | Posts excluded from v1. The third-party mention problem is never discussed. | FLAGGED (scope only) | "posts… **not in v1**" |
| D7 | Loans excluded from v1. Records shared with other contacts are not discussed. | FLAGGED (scope only) | same list |
| D8 | Viewer, editor and manager can all export | FLAGGED | "Anyone who can view the contact" |
| D9 | Note `author_name` is included (it can be empty). No email. The privacy of other vault users is not discussed. | FLAGGED | "Notes include `author_name` (nullable)" |
| D10 | Archived contacts can be exported. Past addresses and completed tasks don't come up because those sections are out. Done or triggered reminders: trigger history is excluded, but whether past reminders are listed is not settled. | FLAGGED (archived) / NOT SETTLED (rest) | "It is shown for archived contacts too" |
| D11 | Reminders keep label, day/month/year, type and frequency_number. Notification channels and trigger counts are excluded. | FLAGGED | "Per-user notification channels… **excluded**" |
| D12 | On the contact page: asked and answered. The "Download all data" link sits under the vCard link, which stays: flagged. File name `{contact-name-slug}.json`: flagged. Fallback name "contact": silent. | ASKED + FLAGGED | "directly under 'Download as vCard'" |
| D13 | Immediate download with no background job and no size or time limits. The Risks section mentions a streaming fallback. | FLAGGED | "Four text sections… are small" |
| D14 | Timestamps are ISO-8601 in UTC. Date parts are raw integers. Labels follow the exporting user's language (called "a known trade-off"). | SILENT (UTC and date parts) / FLAGGED (language) | "All timestamps are ISO-8601 in UTC" |
| D15 | `schema_version: 1` at the top of the file. Adding keys keeps the version at 1. No importer, since export only was an asked answer. The file's shape is not documented for users. | FLAGGED (version) / SILENT (version rule) / ASKED (no import) | "`schema_version` staying at 1 because keys are only added" |
| D16 | No API endpoint for now | FLAGGED | "**No API endpoint.**" |
| D17 | Not discussed. The plan only implies it: "must not write anything". | NOT SETTLED | — |
| D18 | Silent: `exported_at` and source URL in the file; empty sections shown as `[]`; ordering rules; note emotion included; internal IDs included; relationships with deleted contacts skipped. Asked: no bulk or whole-vault export. | mixed | "Rows where either contact is soft-deleted are skipped" |

## Counts

**(a) Build-detail leakage:** skipped because this run produced a plan, not a spec and tickets. For context, the plan is written at implementation level throughout: service, controller, route, view helper and test file names, plus line numbers.

**(b) SILENT product-side decisions: 10**
1. Relationships with deleted contacts are skipped.
2. Timestamps are ISO-8601 in UTC.
3. Date parts are kept as raw integers.
4. Empty sections are always present as `[]`.
5. Fixed ordering rules for each section.
6. `exported_at` and source URL are in the file.
7. Note emotion is included.
8. Internal IDs are included.
9. File name falls back to "contact".
10. Schema version stays at 1 when keys are added.

**(c) Summary:** The run kept scope narrow and flagged it. v1 is the four named items plus basic identity fields, and it lists exactly what is excluded, including the vCard fields and all files, with a follow-up order. On third-party privacy it only protected related contacts' details. It never considered what journal posts, loans or note authors reveal about others, because those items were either scoped out or included without discussion.
