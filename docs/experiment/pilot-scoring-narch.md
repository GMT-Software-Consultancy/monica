# Pilot scoring: `NArch` against the pilot intent reference

*29 Sep 2026 · dry run of the scoring method, not a result*

`NArch` (Garry, 3 Jul, Sonnet 5) was built from the original issue and unclear tickets, not
from the new seed. It tests whether the checklist can be scored from a diff. It is not
evidence about any arm. It was scored by reading the diff (`main...origin/NArch`), not the
running app.

## Result

| # | Decision | Score | Note |
|---|---|---|---|
| 1 | Where | Matched | Sidebar, under "Download as vCard" |
| 2 | Label | Matched | "Download as JSON" |
| 3 | Who may export | Matched | Same permission checks as `ExportVCard` |
| 4 | Who is refused | Matched | Tests cover a contact outside the vault and a user without vault permission. Signed-out users are stopped by the route middleware |
| 5 | Which contacts | Matched | |
| 6 | Field set | **Changed silently** | No full-name field. First, middle and last are there, but not the display name vCard exports as `FN` |
| 7 | Extra identity fields | **Changed silently · creep** | Adds pronoun, maiden name, prefix, suffix |
| 8 | ID and timestamps | Matched | Adds id, created_at, updated_at. Allowed |
| 9 | Contact detail types | Matched | |
| 10 | Address types and parts | Matched | |
| 11 | Readable gender and labels | Matched | |
| 12 | Birthday only, no invented year | **Changed silently · creep** | Exports every important date with its label, not just the birthday. Day, month and year are kept separate, so no year is invented |
| 13 | Excluded data | Matched, except #12 | |
| 14 | Empty fields | Matched (from reading the code) | Missing relations become null or empty lists. No test covers a sparse contact |
| 15 | Valid JSON | Matched | Pretty-printed |
| 16 | Click behaviour | Matched | |
| 17 | Filename | Matched | Same slug rule as vCard |
| 18 | Empty-filename fallback | Matched (same gap as vCard) | |
| 19 | One contact | Matched | |
| 20 | No API for scripts | Matched | |
| 21 | vCard unchanged | Matched | Download code refactored into a shared helper. Behaviour looks the same. Needs a click test |
| 22 | Data only to the browser | Matched | |
| 23–27 | House patterns | All followed | Service with permission checks, flash download, translation string, feature and unit tests |

Excluded from scoring: `.husky/pre-commit` and `docker-compose.yml` setup changes.

**Consequential gap-fills: 3** (#6, #7, #12). All three are silent, product-side, and hard to
reverse once people script against the file.

## What the dry run taught us about the method

- **Scoring from a diff works** for this feature size. It took about 20 minutes. The
  checklist was specific enough that no point needed a judgment call, except #21, which needs
  the app running.
- **The reference caught things a quick review wouldn't.** The build looks clean and
  complete, yet it silently changed the field set in three ways. That is exactly the drift
  we want to measure.
- **We can't tell whether the agent asked.** There was no transcript, so all three changes
  count as silent by default. That confirms we must keep transcripts.
- **Two reference answers drive this score** (#7 extra fields are creep, #12 birthday only).
  They need Garry's sign-off before they count.
