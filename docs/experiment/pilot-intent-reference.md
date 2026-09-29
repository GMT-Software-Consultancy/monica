# Pilot feature intent reference · JSON export

*Draft · 29 Sep 2026 · Mike drafts, Garry signs off · Status: not frozen*

This is the "right answer" every pilot build is scored against. It is written before any
new run, and no run may see it.

**Scoring each point, per build:** matched / changed and flagged / changed silently / missing.
**Side:** P = product-side (changes what the user sees; the spec should own it).
M = mechanism-side (the engineer's call; recorded, but not drift).
**Reversible?** Easy = local code. Hard = stored data, schema, or a published contract.

Facts checked against the code: the vCard export writes full name; first, middle and last
name; nickname; gender; birthday (the only date); addresses; email, phone, messaging and
social profiles; other contact details as web links; company; job title; labels; and a
last-changed timestamp. It has no photo, pronoun, maiden name, prefix or suffix. Anyone in the
contact's vault can export, view-only members included (`ExportVCard` uses
`author_must_be_in_vault`).

---

## Where and who

| # | Decision | Right answer | Side | Reversible? |
|---|---|---|---|---|
| 1 | Where the option appears | Contact page sidebar, next to "Download as vCard" | P | Easy |
| 2 | Label | Says JSON clearly, for example "Download as JSON". Exact wording is free | P | Easy |
| 3 | Who may export | Anyone who can view the contact: any member of its vault, view-only included. Same as vCard | P | Easy |
| 4 | Who is refused | Anyone not in the contact's vault, and anyone signed out. Nothing is returned | P | Easy |
| 5 | Which contacts | Any contact the vCard download works for. No new exclusions | P | Easy |

## What's in the file

| # | Decision | Right answer | Side | Reversible? |
|---|---|---|---|---|
| 6 | Field set | The vCard fields: full name, first, middle and last name, nickname, gender, birthday, addresses, contact details, company, job title, labels | P | Hard, once people script against it |
| 7 | Extra identity fields (pronoun, maiden name, prefix, suffix) | Not asked for. If added, counts as **scope creep, minor** | P | Hard |
| 8 | Internal ID and timestamps | Allowed. Harmless and useful to scripts. Not counted as creep | M | Hard |
| 9 | Contact details | Each value carries its type (email, phone, etc.) | P | Hard |
| 10 | Addresses | Current addresses only, as in vCard (past addresses are skipped). Each carries its type (home, work…) and its parts, not one joined string | P | Hard |
| 11 | Gender and labels | Readable names, not internal IDs | P | Hard |
| 12 | Birthday without a year | Kept as day and month. **No year invented.** Other important dates are left out | P | Hard |
| 13 | Excluded data | No notes, relationships, reminders, tasks, gifts, activities, journal entries, other dates, photo or avatar | P | Easy |
| 14 | Empty fields | A contact with only a name still exports. Empty fields are null or empty lists, consistently | P | Hard |
| 15 | Valid JSON | One well-formed JSON object per file, UTF-8, non-Latin characters intact | P | Easy |

## The file and the download

| # | Decision | Right answer | Side | Reversible? |
|---|---|---|---|---|
| 16 | What happens on click | Browser downloads one `.json` file. User stays on the contact page | P | Easy |
| 17 | Filename | Contact's name, same rule as the vCard file, `.json` extension | P | Easy |
| 18 | Name that makes an empty filename (emoji only, for example) | Not required: vCard has the same gap. A fallback is a bonus, not creep | P | Easy |
| 19 | One contact only | No bulk or "export all" | P | Easy |
| 20 | Scripts calling it directly | Not required. The seed's "their own scripts" is met by the downloaded file. A new API endpoint counts as **scope creep** | P | Hard |
| 21 | vCard export | Unchanged | P | Easy |
| 22 | Where data goes | Only to the user's browser. Nothing sent anywhere else | P | Easy |

## Mechanism-side: recorded, not scored as drift

| # | Decision | House pattern (for code-quality scoring) |
|---|---|---|
| 23 | Route, controller and service names | Free |
| 24 | Authorisation | Uses the existing service permission checks, like `ExportVCard` |
| 25 | Download mechanism | Reuses or mirrors the vCard download, not a new pattern |
| 26 | New label text | Goes through the translation files |
| 27 | Tests | Adds feature and unit tests in the existing style |

---

## Open for sign-off

- **#7 and #20 set the scope-creep line.** Agree them, or scoring is contested later.
- **#18.** Is matching vCard's gap acceptable, or is a fallback required?
- **#12.** The 2 July `NArch` build exports `day`, `month` and `year` separately, so it passes.
  Worth checking in each new build.
