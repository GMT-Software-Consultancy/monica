# Download a contact as JSON

Last updated: 29 September 2026
Status: Ready
Tickets: none yet. The ticket tool is set to none, so tickets will be written to `output/tickets.md`.

---

## Problem statement

People who reuse contact data in scripts can only download a contact as a vCard, which is hard to read in code.

## Why now

The product strategy names "provable trust" as a core capability: users must be able to see and control their own data. A plain JSON download lets users check and reuse their data without special tools. This makes "your data is yours" something users can see for themselves. The build is small because the file holds the same details as the existing vCard download. So the feature supports the strategy without adding much to maintain. This reasoning is an assumption. The PM had no strong view on it.

## Users affected

The personas file (`docs/product/personas.md`) does not exist yet. This spec describes users by role instead of persona name.

- **Monica user.** Anyone who keeps their relationship data in Monica, on the hosted service or on a self-hosted server. They get a second download option on the contact page. The feature is free for everyone. No paid plan is needed.
- **Developer user.** A Monica user who writes scripts or uses other tools with their contact data. This is the main user for this feature. Today they must parse a vCard, which is slow and error-prone.
- **Vault member with view-only access.** A vault is a shared workspace that holds contacts. A member with view-only access can already download a contact's vCard. They can also download the JSON file.
- **Non-users.** People who do not have access to the vault cannot download anything. This does not change.

## Background

Today a user can download one contact as a vCard from the contact page. vCard is a standard file format for contact details that address book apps can read. It works well for address books. But a script needs a special vCard parser to read it, and the format has many unusual rules. JSON (JavaScript Object Notation) is a common text format for structured data. Almost every programming language can read JSON without extra libraries. Users have asked for a way to download a contact as JSON from the same place as the vCard.

---

## Scope

### In scope

- A "download as JSON" option on the contact page, next to the existing vCard download.
- A JSON file for one contact. It contains the same details as the vCard download for that contact, except the photo.
- A plain, readable JSON shape with clear field names. The file is pretty-printed, which means it has line breaks and indentation so a person can read it.
- The birthday, which is the only date in the file. If Monica does not know the birth year, the file leaves the year empty. It does not make up a year.
- Consistent handling of empty fields (see "Decisions made" below).
- A file name based on the contact's name, the same way the vCard file is named, but ending in `.json`.

### Out of scope

- The contact's photo or avatar.
- Other Monica data about the contact, such as notes, relationships, reminders, tasks, calls, life events, pets, loans, goals, mood logs and quick facts.
- Other dates besides the birthday.
- Downloading more than one contact at a time, or a whole vault.
- Getting contact JSON through the API or any other programmatic endpoint. Downloading the file from the contact page is enough.
- Importing a contact from a JSON file. This is export only.
- A formal JSON standard such as jCard (the JSON version of vCard).
- Public documentation of the JSON shape. This is nice to have, but not needed now.
- Tracking or measuring how often people use the download.
- Any change to the existing vCard download.

### Phasing

- Phase 1: this spec. One contact, vCard details only, downloaded from the contact page.
- Phase 2 (possible, not committed): add other Monica data about the contact to the JSON file, such as notes and relationships. The PM said "maybe later". A full account export is also possible later. This spec does not commit to it.

### Vertical slice check

If this shipped alone, a user could download a contact as a JSON file and use it in a script right away. Today they cannot do this without a vCard parser. This is a full vertical slice, not an enabler story.

---

## Figma

No designs needed. The new option sits next to the vCard download on the contact page. It matches the vCard link in style and wording.

---

## Acceptance criteria

**AC-1**
GIVEN a user who can view a contact
WHEN the user opens the contact page
THEN the user sees a "download as JSON" option next to the vCard download option
AND the option matches the vCard option in style.

**AC-2**
GIVEN a user who can view a contact
WHEN the user selects the "download as JSON" option
THEN the browser downloads one file
AND the file name is the same as the vCard file name for that contact, except that it ends in `.json`
AND a standard JSON parser reads the file without errors.

**AC-3**
GIVEN a contact with a value in every detail that the vCard download includes
WHEN a user downloads the contact as JSON and as a vCard
THEN every detail in the vCard, except the photo, also appears in the JSON file with the same value
AND the JSON file contains no photo, avatar, notes, relationships, reminders or other Monica data.

**AC-4**
GIVEN a contact whose birthday has a day and month but no year
WHEN a user downloads the contact as JSON
THEN the birthday in the file shows the day and the month
AND the year is empty
AND the file contains no invented year.

**AC-5 (unhappy path)**
GIVEN a contact with only a name, and every other vCard detail empty
WHEN a user downloads the contact as JSON
THEN the download succeeds
AND every field that the file can contain is still present
AND each empty single value is `null` and each empty list is `[]`.

**AC-6 (unhappy path)**
GIVEN a user who cannot view a contact, because they are not a member of the contact's vault
WHEN the user tries to download that contact as JSON
THEN Monica does not return the file
AND the user gets the same response as when they try to download that contact's vCard.

---

## Assumptions

- The vCard download is the source of truth for which details go in the JSON file. If a detail is in the vCard, it goes in the JSON file (except the photo). If it is not in the vCard, it does not go in the JSON file.
- Users asked for this through community channels, such as GitHub issues. We do not know how many asked. The PM had no strong view.
- The feature passes the strategy's privacy review gate without a separate review. The JSON file holds the same details as the vCard. The same people can already download the vCard. So the feature exposes no new data to anyone. **This is the riskiest assumption** (see below).
- A pretty-printed file is the better choice. It is easier for people to read, and scripts read it just as easily as a compact file.
- A plain JSON shape with clear field names helps developer users more than a formal standard such as jCard.
- No work currently in progress conflicts with this feature. The file that lists current work (`docs/iterations/current.md`) does not exist, so nobody could check.

---

## Technical notes

- The feature must work the same way for hosted users and self-hosted users. It must work for every user, with no paid plan needed.
- The existing vCard download must keep working exactly as it does today.
- The JSON download must follow the same view permission as the vCard download. Anyone who can view the contact can download it. Nobody else can.
- The JSON file must never include a year for a birthday when Monica does not know the year.
- The feature must not add tracking or usage measurement.

---

## Open questions

- 🟡 What is the exact list of details that the vCard download includes today? We proceed on the rule "same as the vCard, minus the photo". Engineering confirms the list at plan review. — owner: engineering
- 🟡 Do we need a privacy review before build? We assume no, because the data and the audience match the vCard download. — owner: PM
- 🟢 Will we document the JSON shape publicly? Not now. The PM called it nice to have. — owner: PM

---

## Before you build

**Decisions made:**

- The JSON file contains the same details as the vCard download, except the photo, and nothing more.
- Birthday is the only date in the file. If the year is unknown, the year is empty.
- Every field is always present. An empty single value is `null`. An empty list is `[]`.
- The file is pretty-printed and uses a plain shape, not jCard.
- The option sits next to the vCard download on the contact page. It matches the vCard option in style.
- The file name matches the vCard file name, with a `.json` ending.
- Anyone who can view the contact can download the file.
- One contact at a time. No API, no import, no tracking.

**Riskiest assumption:**
The JSON file needs no separate privacy review, because it exposes nothing that the vCard download does not already expose.

**Next step:**
Ready for ticket generation. Engineering confirms the exact list of vCard details at plan review.
