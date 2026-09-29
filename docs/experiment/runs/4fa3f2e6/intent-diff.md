## Run 4fa3f2e6: blind score (spec.md + tickets.md)

| # | Score | Evidence |
|---|---|---|
| 1 | matched | "directly below the **Download as vCard** link", "left column" |
| 2 | matched | "Download as JSON" |
| 3 | matched | Viewers included: "vault viewers can download" (AC-6) |
| 4 | matched | Non-member and other-vault contact "returns no file". Signed-out users are covered only by "Anyone who cannot download the vCard cannot download the JSON" (tickets) |
| 5 | matched | Archived contacts downloadable "because they can be downloaded as a vCard today" (Assumption) |
| 6 | matched | All 12 fields are in the table. Addresses are "Current addresses only", which the spec says follows the vCard rule. I could not check that against the code |
| 7 | matched | "name prefix, suffix, maiden name and pronouns" listed as out of scope |
| 8 | matched | `id` (UUID) and `updated_at` included |
| 9 | matched | `{ "type", "name", "value", "kind", "preferred" }` |
| 10 | matched | `type`, `line_1`… `country` as separate parts |
| 11 | matched | "Readable values, not internal IDs" |
| 12 | matched | "`year` to `null`… Never invent a year". Birthday is the only date |
| 13 | matched | Notes, relationships, reminders, tasks etc. and photo/avatar out. Gifts, activities and journal are covered only by the blanket "Any Monica data the vCard does not include" |
| 14 | matched | "Every key is always present… `null`… `[]`". AC-4 covers a first-name-only contact |
| 15 | matched | "one JSON object", UTF-8, non-Latin characters written as-is (not escaped) |
| 16 | matched | One `.json` file. Tickets: "they stay on the contact page" |
| 17 | matched | "same file-name rule as the vCard file" |
| 18 | matched (bonus) | `contact.json` fallback. The spec flags it as an open question with a working assumption; the tickets commit to it |
| 19 | matched | "Several contacts at once" out of scope |
| 20 | matched | "We are not adding a route under `/api`" |
| 21 | matched | "stay exactly as they are". The tickets add a test that vCard is unchanged |
| 22 | matched | Only implied, never stated as "browser only": no tracking, no writes, no CardDAV change (tickets). Nothing sends data elsewhere |

**Totals:** 22 matched, 0 changed and flagged, 0 changed silently, 0 missing.

## Possible scope creep
- **`schema_version: 1` field.** Not asked for, since the PM said versioning was optional. It is flagged as an assumption. Minor, but hard to reverse once scripts depend on it.
- **Tickets call the shape "a contract that users' scripts will depend on".** That is a stronger commitment than the PM's "plain, readable shape" plus "documenting is nice to have". The spec does not say this. Minor and unflagged.
- **Pretty-printed / indented output.** Not asked for. Trivial.
- **`contact.json` fallback.** The reference counts this as a bonus, not creep.
- No API, bulk export, extra identity fields or extra data.

## Build-detail leakage
**Spec: about 40 distinct items.** About 30 sit in "Technical notes" and about 10 in product sections: the "Source in Monica" column, AC-6 and Assumptions. Examples:
- `contacts.first_name`, `Contact::name` (field table)
- `POST /vaults/{vault}/contacts/{contact}/vcard`, `routes/web.php`
- `ContactVCardController::download`, `resources/js/Pages/Vault/Contact/Show.vue`
- `BaseService`, `ExportVCard`, `author_must_be_in_vault`
- `ContactImportantDate::TYPE_BIRTHDATE`, `Str::of($contact->name)->slug(...)`

I did not count the JSON key names as leakage, because they are the product's output format.

**Tickets: about 2, both low-grade.** "CardDAV" (a protocol name) and "slug". The tickets deliberately leave implementation notes in the spec ("technical notes stay in the spec, not in the ticket").

## "Use your judgement" answers turned into decisions
| QA item | Decision | Flagged? |
|---|---|---|
| Q1 persona | Defined its own persona, "Data-owning power user" | Yes (Assumptions) |
| Q2 why now | "provable trust" | Yes ("our judgement, not a measured trigger") |
| Q5 versioning | `schema_version: 1` | Yes (Assumptions: "Format stability") |
| Q5 internal IDs | UUID is the only ID | Yes (in "Decisions made", not Assumptions) |
| Q5 labels vs IDs | Readable names | Yes (in "Decisions made") |
| Q7 other formats / vCard changes | Both out of scope | Yes (Out of scope and "Decisions made") |
| Q8 activity feed | No entry, with reason: "vCard download does not add one either" | Yes (Out of scope and "Decisions made") |
| Q9 unlisted/partial contacts | Archived contacts downloadable; partial contacts export (AC-4) | Yes (Assumptions) |

Every fallback was written down as a decision. The ones in Q5, Q7 and Q8 are stated as decisions, not labelled as assumptions.

## Summary
The run matches all 22 scored decisions. It adds one flagged product extra (`schema_version`), and the tickets call the JSON shape a "contract", which goes slightly beyond what the PM asked for. The spec leaks a lot of implementation detail (about 40 items, mostly in Technical notes); the tickets are clean. **Product-side "changed silently": 0.**
