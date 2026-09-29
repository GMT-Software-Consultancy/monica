## Run b6ac0744: scoring report (plan.md + qa.md)

I read only the intent reference, `plan.md` and `qa.md`.

| # | Score | Evidence |
|---|---|---|
| 1 | matched | "Add a 'Download as JSON' link next to 'Download as vCard' on the contact page" |
| 2 | matched | Label is "Download as JSON" |
| 3 | matched | Same route gates as vCard: "any vault viewer can download" |
| 4 | matched | Tests: a user without vault access gets 403, and a contact from another vault gets 403. Signed-out users are covered only implicitly, through the shared route group. |
| 5 | matched | Same `vault-viewer` and `contact-owner` gates as vCard. No new exclusions. |
| 6 | matched | The shape has name, first/middle/last name, nickname, gender, birthday, company, job_position, labels, contact details and addresses. Only current addresses are included, which is what vCard does. |
| 7 | matched | No pronoun, maiden name, prefix or suffix |
| 8 | matched | `id` (UUID) and `updated_at` are included, listed as assumptions 2–3 |
| 9 | matched | Type is shown by which list a value sits in (emails, phones, instant_messaging, social_profiles, urls), not by a field on each value. Emails and phones also carry `kind`. Literal reading is borderline. |
| 10 | matched | `type` plus line_1, line_2, city, province, postal_code, country |
| 11 | matched | Gender is `{ "name": "Female", "code": "F" }`. Labels are name strings. |
| 12 | matched | `{ "year": null, "month": 3, "day": 14 }`, "Nothing is invented". Non-birthday dates are ignored. |
| 13 | matched | Out of scope: "Photo/avatar, notes, relationships, reminders, other important dates". Tasks, gifts and journal are left out because the JSON is built field by field. |
| 14 | matched | "Missing single values are `null` and missing lists are `[]`". A bare-contact test is planned. |
| 15 | matched | One object, UTF-8, `JSON_UNESCAPED_UNICODE` |
| 16 | matched | Same Blob download plus `Redirect::back()` flash as vCard |
| 17 | matched | Contact name slug, same as vCard, `"$name.json"` |
| 18 | matched | Flagged under Known limitations: "the file is named `.json`, just as the vCard is named `.vcf`" |
| 19 | matched | "multi-contact or vault export" is out of scope |
| 20 | matched | "no API endpoint" |
| 21 | matched | vCard output is unchanged. `download()` in Show.vue is changed to `download(url)` so both links share it, which is a code change only and invisible to users. |
| 22 | matched | Data goes only to the browser through the flash, as with vCard. The plan also declines to run `monica:localize` because that sends strings to Google Translate. |

## Possible scope creep
- A top-level `"version": 1` field. Minor, and flagged as an assumption.
- Gender `code` (M/F/O), and `kind`/`preferred` on emails and phones. These carry vCard attributes over, so they are arguably not creep.
- `urls[].type` holds a protocol prefix ("https://"). This is odd but not new scope.
- New feature tests assert that `contacts.vcard` is unchanged. The plan also notes this is the first test of the download flow. This is test scope, not product scope.

No bulk export, API, import, extra identity fields or docs.

## Build-detail leakage
Not counted, because this is a plan document and naming code is expected. For information: it names routes, controllers, view helpers, Vue files, test files and PHP flags throughout.

## Fallbacks turned into decisions
- **Q4 (version field / stable contract):** decided to include `"version": 1` but "**not** yet a documented, stable public contract", with no docs. Flagged (Assumption 1).
- **Q7 (IDs, timestamps, ISO 8601):** decided to include the UUID `id`, and `updated_at` as an ISO 8601 UTC string. It leaves out `created_at` and the vault or child-row IDs, and stores the birthday as an object rather than an ISO string, with the reasoning given. Flagged (Assumptions 2–4).

Both fallbacks were written down as explicit assumptions.

## Summary
The plan matches all 22 decisions. Two are borderline: #9 shows type by list rather than on each value, and #4 covers signed-out users only through the route group. The only additions are a flagged `version` field and vCard-derived attributes. Product-side items changed silently: **0**.
