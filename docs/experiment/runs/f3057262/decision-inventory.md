# Decision inventory — run f3057262 (spec.md + tickets.md)

| # | Decision | Status | Evidence |
|---|---|---|---|
| D1 | One JSON file | ASKED | Q3 "JSON is fine" |
| D2 | All contact data; activity feed, journal, life metrics out; gifts in | ASKED (core) | Q4 "everything recorded about this person"; feed FLAGGED (Assumptions); gifts FLAGGED (open q) |
| D3 | Address-book fields in `contact`/lists; vCard unchanged | ASKED | Q4 "including the address-book details"; Q8 |
| D4 | Photos/docs by name+date, no files, no URLs; avatar also listed | ASKED | Q5 "by name and date"; avatar/no-URL extension silent |
| D5 | Other contacts as ID + display name + connection | ASKED | Q6 "not their own details" |
| D6 | Journal posts excluded | SILENT | "belong to vault-level features" |
| D7 | Loans (lend/borrow), gifts, shared life events with other party by ID/name | ASKED (by extension of Q4+Q6) | "loans where this contact lends or borrows" |
| D8 | Any member who can view (incl. viewers) | ASKED | Q7 "Anyone who can view the contact" |
| D9 | Other members' notes included with author name | SILENT (attribution) | T3 "includes them with the author's name" |
| D10 | All records incl. past/completed; status fields unspecified | SILENT | "every record of every listed data type" |
| D11 | Reminder = date + recurrence | SILENT | "with their date and how often they repeat" |
| D12 | Next to vCard, same style: ASKED. Label "Export", filename `name-date.json`: SILENT | ASKED / SILENT | Q8, Q10; "jane-doe-2026-09-29.json" |
| D13 | Built in one request, no background job | FLAGGED | "One request can build the whole file" |
| D14 | ISO 8601, ignore display prefs, localized type names; no time-zone rule | SILENT | "Ignore the user's display preferences" |
| D15 | No shape docs: ASKED. `format_version: 1`: SILENT | ASKED / SILENT | Q9d; "`format_version`: the number `1`" |
| D16 | No API | ASKED | Q9d "download is enough" |
| D17 | Export logged/audited | NOT SETTLED | nothing beyond "no tracking" (metrics) |
| D18 | Scheduled exports out; leavers as side effect; no trigger; no in-flight work; initiative "Provable trust"; release of T1 alone left to team | FLAGGED (6) | Assumptions sections |

## (a) SILENT product-side decisions

1. Journal posts excluded [NEVER RAISED] (cuts against PM's "everything recorded"; reversible)
2. Life metric values excluded [NEVER RAISED]
3. Notes by other vault members exported with author's name [NEVER RAISED] **CONSEQUENTIAL** (another user's identity leaves the vault in a portable file)
4. Past/completed items all included, and past/completed status not specified [NEVER RAISED]
5. Reminder detail limited to date + recurrence [NEVER RAISED]
6. Link label "Export" [NEVER RAISED]
7. Filename contains the contact's name + date [NEVER RAISED]
8. ISO 8601, display preferences ignored, no time-zone rule [NEVER RAISED]
9. Type names exported in the exporter's language [NEVER RAISED]
10. `format_version: 1` versioning [NEVER RAISED]
11. Record IDs and other contacts' IDs included [NEVER RAISED]
12. Avatar listed by name/date, no URLs [NEVER RAISED]
13. Deleted/foreign contacts return the same response as vCard [NEVER RAISED]
14. Shared address included in the exported contact's list [NEVER RAISED]
15. Empty lists always present (spec) [NEVER RAISED]
16. Labels/groups by name only, no co-members [NEVER RAISED]
17. Money as raw amount + currency code [NEVER RAISED]

## (b) False authority

- Spec Open questions: "🟢 Should a later phase include journal posts and life metric values that mention the contact? Not in Phase 1. — PM". Green marks it resolved, and the resolution is attributed to the PM. qa.md never mentions journal posts or life metrics. ("— PM" may denote owner, but with 🟢 it reads as a PM ruling.)

## (c) Leakage

**Spec: about 18 items**, mostly in Technical notes. Examples: `vault-viewer`/`contact-owner` gates and `vault_id`; route `POST /vaults/{vault}/contacts/{contact}/vcard`; "bypass the Inertia middleware", Laravel, Vue; `ExportVCardResource`, `VCardResource` in `app/Domains/Contact/Dav/`; the "raw `vcard` column on the `contacts` table" and `app/Models/Contact.php`.

**Tickets: about 6 items.** Examples: JSON keys `format_version`, `exported_at`, the "`contact` part"; "relation on the Contact model"; "The gifts table links a gift to a giver contact and a receiver contact"; `ticket_tool: none`.

## (d) Counts

ASKED 10, FLAGGED 7, SILENT 17 (DEFERRED 0, NEVER RAISED 17, CONSEQUENTIAL 1), NOT SETTLED 1, FALSE AUTHORITY 1
