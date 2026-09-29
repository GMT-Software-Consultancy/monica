# Download a contact as JSON — Tickets

Generated: 29 September 2026
Spec: output/spec.md
Tracker: none (no `ticket_tool` configured; tickets are not posted)
Initiative: Provable trust (assumed — the spec has no `Initiative:` field; see Decomposition notes)

---

## 1. Let users download a contact as a JSON file from the contact page

**Type:** Feature
**Initiative:** Provable trust
**Depends on:** None

### Summary
Users can download the contact they are viewing as one readable JSON file with the same details as the vCard download.

### Job to be done
When a data-owning power user wants a contact's details in a script or another tool, they need to download that contact as JSON, so they can read the data in one line of code instead of parsing a vCard.

### Navigation context
**Entry point(s):** Any vault → Contacts list → a contact → the contact page. The user reaches the link from the same place as the **Download as vCard** link.
**Exit point(s):** The user stays on the contact page. The browser saves a `.json` file for that contact.
**Screen/component:** The contact page, left column, in the list that holds the **Download as vCard** link. The new **Download as JSON** link sits directly below it.

### Context
Today users can download a contact only as a vCard. vCard is a standard text format for contact cards that address books read well. Scripts and general tools handle vCard poorly, because users must parse its lines, escaping rules and parameters. JSON (JavaScript Object Notation) is a plain data format that almost every language can read. The product strategy names "provable trust" as a required capability: users can see and take their own data. This small, low-cost feature is a visible step toward that.

Key terms:
- **Vault:** a shared space in Monica that holds contacts. A user must be a member of a vault to see its contacts.
- **Viewer:** the read-only permission level in a vault.
- **CardDAV:** a protocol that syncs contacts between Monica and external address books.
- **Slug:** the URL-safe version of a name, for example `ada-lovelace` for "Ada Lovelace".

Access rule: anyone who can download a contact's vCard can download its JSON file. Anyone who cannot download the vCard cannot download the JSON file.

### File contents
The file is one JSON object. It holds only the details the vCard download includes today. This shape is a contract that users' scripts will depend on.

| JSON key | Type | Meaning | Matching vCard property |
|---|---|---|---|
| `schema_version` | number | Always `1` | none (file metadata) |
| `id` | string | The contact's unique ID (UUID) | `UID` |
| `full_name` | string | The contact's display name, as the contact page shows it | `FN` |
| `first_name` | string or null | The contact's first name | `N` |
| `middle_name` | string or null | The contact's middle name | `N` |
| `last_name` | string or null | The contact's last name | `N` |
| `nickname` | string or null | The contact's nickname | `NICKNAME` |
| `gender` | string or null | The name of the contact's gender, for example "Woman" | `GENDER` |
| `birthday` | object or null | `{ "year", "month", "day" }`. Each part is a number or null. | `BDAY` |
| `company` | string or null | The name of the contact's company | `ORG` |
| `job_position` | string or null | The contact's job position | `TITLE` |
| `labels` | array of strings | The names of the contact's labels | `CATEGORIES` |
| `contact_information` | array of objects | `{ "type", "name", "value", "kind", "preferred" }` for each entry the vCard includes | `EMAIL`, `TEL`, `IMPP`, `X-SOCIAL-PROFILE`, `URL` |
| `addresses` | array of objects | Current addresses only: `{ "type", "line_1", "line_2", "city", "province", "postal_code", "country" }` | `ADR` |
| `updated_at` | string | When the contact last changed, in ISO 8601 format, UTC, for example `2026-09-29T10:15:00Z` | `REV` |

Content rules:
- Every key is always present. A missing single value is `null`. An empty list is `[]`.
- A birthday without a year has `year` set to `null` and keeps the known month and day. The file never invents a year.
- If a contact has more than one birthday, the file shows the same one the vCard download shows.
- `contact_information` lists exactly the entries the vCard download lists. It skips the entries the vCard download skips.
- Values are readable names, such as the gender name or the label name, not internal lookup IDs. The contact's UUID is the only ID in the file.
- The file is UTF-8. It shows non-Latin characters as written, not as `\u` escape sequences. It is indented so a person can read it.
- The file name follows the vCard file name rule, with `.json` at the end. If the contact's name gives an empty slug, the file is named `contact.json`.

Example file:

```json
{
    "schema_version": 1,
    "id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "full_name": "Ada Lovelace",
    "first_name": "Ada",
    "middle_name": null,
    "last_name": "Lovelace",
    "nickname": null,
    "gender": "Woman",
    "birthday": { "year": null, "month": 12, "day": 10 },
    "company": "Analytical Engines Ltd",
    "job_position": "Mathematician",
    "labels": ["Friends"],
    "contact_information": [
        { "type": "email", "name": "Email address", "value": "ada@example.com", "kind": "work", "preferred": true }
    ],
    "addresses": [
        { "type": "home", "line_1": "12 St James's Square", "line_2": null, "city": "London", "province": null, "postal_code": "SW1Y 4JH", "country": "United Kingdom" }
    ],
    "updated_at": "2026-09-29T10:15:00Z"
}
```

### Designs
No designs needed. The **Download as JSON** link copies the **Download as vCard** link exactly: the same list, the same style and the same left-column position, placed directly below it. The link text follows the user's language setting, like the other links on the page.

### Acceptance criteria

**Scenario: User sees the JSON link and downloads a correctly named file**
Given a vault member is on the contact page for "Ada Lovelace"
When they click **Download as JSON**
Then the browser downloads a file named `ada-lovelace.json`
And the **Download as JSON** link appears directly below the **Download as vCard** link
And they stay on the contact page.

**Scenario: File holds exactly the contact's vCard details**
Given a vault member is on the contact page for a contact with a first name, last name, nickname, gender, company, job position, one label, one email address, one phone number, one current address, one past address and a full birthday
When they click **Download as JSON**
Then the downloaded file is valid JSON with exactly the keys listed in "File contents"
And each value matches the details the contact page shows for that contact
And `addresses` lists only the current address
And the file contains no notes, photo, avatar or other data outside the "File contents" table.

**Scenario: Birthday without a year keeps the month and day**
Given a vault member is on the contact page for a contact whose birthday has a month and a day but no year
When they click **Download as JSON**
Then the file's `birthday` value is `{ "year": null, "month": <month>, "day": <day> }` with the contact's month and day.

**Scenario: Contact with only a first name still gives every key**
Given a vault member is on the contact page for a contact that has only a first name
When they click **Download as JSON**
Then the browser downloads a valid JSON file
And every key from "File contents" is present
And each missing single value is `null` and each missing list is `[]`.

**Scenario: User outside the vault gets no file**
Given a signed-in user is not a member of the vault that holds "Ada Lovelace"
When they request the JSON download for "Ada Lovelace"
Then they receive no file
And they see the same denial they see when they request the vCard download for that contact.

**Scenario: Contact from a different vault gets no file**
Given a vault member is in a vault that does not hold the contact "Ada Lovelace"
When they request the JSON download for "Ada Lovelace" through their own vault
Then they receive no file
And they see the same denial they see when they request the vCard download in that case.

**Scenario: Vault viewer downloads without changing the contact**
Given a user with viewer permission is on the contact page for "Ada Lovelace"
When they click **Download as JSON**
Then the browser downloads `ada-lovelace.json`
And the contact's activity feed shows no new entry
And a second JSON download shows the same `updated_at` value as the first
And an address book synced over CardDAV receives no change for this contact.

**Scenario: Contact synced from an external address book shows only Monica's details**
Given a vault member is on the contact page for a contact that came from an external address book over CardDAV, with a photo in that address book
When they click **Download as JSON**
Then the file contains only the keys listed in "File contents"
And the file contains no photo, image URL or other property from the external address book.

**Scenario: Contact with more than one birthday**
Given a vault member is on the contact page for a contact with two birthdays
When they click **Download as JSON**
Then the file's `birthday` value matches the birthday in the vCard download of the same contact.

**Scenario: Archived contact can be downloaded**
Given a vault member is on the contact page for an archived contact
When they click **Download as JSON**
Then the browser downloads a valid JSON file for that contact.

**Scenario: Name that gives an empty slug falls back to a default file name**
Given a vault member is on the contact page for a contact whose name gives an empty slug
When they click **Download as JSON**
Then the browser downloads a file named `contact.json`.

**Scenario: Non-Latin characters stay readable**
Given a vault member is on the contact page for a contact named "Иван Петров"
When they click **Download as JSON**
Then the file shows `"full_name": "Иван Петров"` as written, with no `\u` escape sequences
And the file is indented, one key per line.

**Scenario: vCard download still behaves as before**
Given a vault member is on the contact page for "Ada Lovelace"
When they click **Download as vCard**
Then the browser downloads `ada-lovelace.vcf` with the same content as before this change.

### Out of scope
- Downloading several contacts at once: a whole vault, a group or a selection.
- Importing or updating contacts from a JSON file.
- An API endpoint for the JSON download. The web download is the only way in.
- Monica data the vCard does not include: notes, relationships, reminders, tasks, calls, pets, loans, goals, life events, mood tracking, documents and files. Name prefix, suffix, maiden name and pronouns are also excluded.
- Photos and avatars, as image data or as URLs.
- CSV or any other format.
- Any change to the vCard link, file or behaviour, including its empty-slug file name.
- An activity feed entry for the download.
- Usage tracking or metrics.
- Published documentation of the JSON shape.

---

## Decomposition notes

- **One ticket, not several.** The spec is one thin vertical slice. We considered splitting the file by field groups. We rejected that, because scripts would see the version 1 shape change between releases. Access checks and the "no change on download" rule belong to the same slice. Shipping the download without them would be unsafe.
- **Initiative assumed.** The spec has no `Initiative:` field. We used "Provable trust", the strategy capability the spec's "Why now" cites. The PM should confirm this before posting to any tracker.
- **Open questions closed with the spec's working assumptions.** An empty slug gives `contact.json` for JSON only, and the vCard stays as it is. Documenting the JSON shape stays out of scope.
- **Persona.** There is no `docs/product/personas.md`. The ticket uses the spec's own persona, the "data-owning power user".
- **In-flight work.** There is no `docs/iterations/current.md`, so we found no conflicting work. Before starting, the developer should check for open changes to the contact page and the vCard download.
- **Implementation guidance.** The spec's technical notes stay in the spec, not in the ticket. They cover the reference vCard behaviour, reuse limits and the file name rule. Developers should read them in `output/spec.md`.
- **Spec status not changed.** No tracker IDs exist, so the spec keeps `Status: Ready`.
