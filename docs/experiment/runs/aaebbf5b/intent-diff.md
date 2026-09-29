## Run aaebbf5b: score against the intent reference

| # | Score | Evidence |
|---|---|---|
| 1 | matched | "contact page side menu, directly next to the 'Download as vCard' link" |
| 2 | matched | "Download as JSON" |
| 3 | matched | "Anyone who can view… including a vault member with view-only access" |
| 4 | matched | "Nobody else can"; ticket 1 has a direct-link scenario for users with no access. Signed-out users are never named. |
| 5 | matched (implicit) | No new exclusions stated; AC-1 applies to any contact the user can view |
| 6 | **changed silently** (minor) | Every reference field is present. But the spec says "same contact details as the vCard… nothing more" while also adding "a link to the contact's page in Monica" (not in the vCard facts). It also limits addresses to "current addresses", stating "vCard download does not include them either" as fact (unverified) |
| 7 | matched | No pronoun, maiden name, prefix or suffix |
| 8 | matched | Unique identifier plus last-updated time in ISO 8601 |
| 9 | matched | "each with its type, kind… and preferred flag" |
| 10 | matched | "each address is one object" with type and its parts |
| 11 | matched | Gender shown as "Female", not a code (listed under Assumptions); labels shown by name, e.g. "Family" |
| 12 | matched | "year is `null`… no important dates other than the birthday"; "never invents a missing part" |
| 13 | matched | Notes, relationships, reminders, activities, life events, goals, avatar, photos and documents are excluded. Tasks, gifts and journal entries are covered by "all other Monica data" |
| 14 | matched | "Every field is always present… `null`… `[]`"; AC-3 is a first-name-only contact |
| 15 | matched | "one JSON object", "UTF-8"; ticket 2 has a Japanese-characters scenario |
| 16 | matched | "browser downloads one file"; "user stays on the contact page" (tickets) |
| 17 | matched | "same name as the vCard download, but ending in `.json`" |
| 18 | matched (flagged open) | 🟡 open question: keep the vCard rule for now; engineering decides on a fallback |
| 19 | matched | "no bulk export, no groups" |
| 20 | matched | "A JSON endpoint in the public API" is out of scope |
| 21 | matched, with a caveat | "must not change what the vCard download… returns". But ticket 1 leaves open "whether **both** downloads need a fallback name", which could change the vCard filename |
| 22 | matched | "must not send contact data to any outside service" |

## Possible scope creep
- A contact-page URL inside the file (minor; not in the vCard field set, and not covered by #8).
- A process commitment: "maintainers note it in the release notes" when the shape changes.
- A possible fallback filename for the vCard download too, raised in ticket 1 (would touch #21).
- A narrowing rather than creep: past addresses are excluded.
- A Phase 2 list (more data, published docs) appears, but it is marked "not committed".

## Build-detail leakage
- **Spec: 2 items**, both project-doc paths rather than code: `docs/product/personas.md` and `output/tickets.md`. There are no classes, routes, tables, frameworks or API shapes. Borderline: the address parts ("line 1, line 2, city, province, postal code") and "kind" / "preferred flag" read like column names but are written in plain English. Field naming is explicitly left to "Engineering… at plan review".
- **Tickets: 5 items**, all docs or config, no code: `docs/product/strategy.md`, `docs/product/personas.md`, `.claude/skill-config.md`, `output/tickets.md`, and the config key `ticket_tool`. No routes are named; "JSON download link" stays abstract.

## Answer-sheet fallbacks turned into decisions
| Q | Decision | Flagged? |
|---|---|---|
| 2 Why now | The "provable trust" capability | Yes (Assumptions) |
| 3 Persona names | Working personas "Relationship keeper" and "Script builder" | Yes (Users affected, Assumptions) |
| 6 Versioned format | No promise, no version number, release-notes note | Yes (Assumptions plus a 🟡 open question) |
| 7 Scheduled exports | Out of scope | Yes (Assumptions) |
| 8 Sensitive-data warning | No warning | Yes (Assumptions) |

All five fallbacks became flagged decisions. None was settled silently.

## Summary
The run matches the reference on 21 of 22 decisions, and every answer-sheet fallback it turned into a decision is flagged. **1 product-side item is changed silently: #6.** The spec calls the field set "same as vCard, nothing more", but adds a contact-page link and drops past addresses without flagging either. Separately, ticket 1 leaves open a fallback filename that could change the vCard download (#21).
