# Gift tracking

Last updated: 15 October 2026
Status: Ready
Tickets: (fill in once created — this project writes tickets to `output/tickets.md`)

---

## Problem statement

People who use Monica forget gift ideas and past gifts because Monica has no place to record gifts for the people in their vault.

## Why now

The app's own beta page names gift management as one of the two most important features that the old Monica had and this version lacks. Users who move from the old Monica lose a feature they used. The year-end holidays start in about 10 weeks, and gifts give users a reason to open Monica before every birthday and holiday. This supports the strategy goal of bringing users back at the moments when their stored memory is useful.

## Users affected

`docs/product/personas.md` does not exist. This spec uses one working persona, taken from the target segment in `docs/product/strategy.md`:

- **Deliberate rememberer** (primary): a person who uses Monica to remember what matters about the people they care about. They record gift ideas when the ideas come to them. They check those ideas before a birthday or holiday. They look back at what they gave before so they do not repeat a gift.
- **Vault viewer** (secondary): a user who has view-only access to a shared vault. They can see every gift in the vault but cannot add, change or delete gifts.
- **Account administrator** (edge case): the only user who can rename, reorder, add or delete gift stages and gift occasions in Settings. Their changes affect every vault in the account.

Shared vaults have one known side effect. If two partners share a vault, one partner can see the gift ideas the other partner records for them. This is accepted for this phase.

## Background

Today, users keep gift ideas in notes, tasks or a separate app. Notes and tasks have no stage and no occasion. They are also not collected in one list per vault. Users cannot answer "what did I give Anna last year?" without searching through notes.

Monica already has part of the setup:

- **Gift stages** (in the code: `gift_states`). Each account has an ordered, editable list of stages. The defaults are Idea, Searched, Found, Bought and Offered.
- **Gift occasions** (in the code: `gift_occasions`). Each account has an ordered, editable list of occasions. The defaults are Birthday, Anniversary, Christmas, Just because and Wedding.

Users can already edit both lists in Settings, but no other part of the app uses them yet.

---

## Scope

### In scope

- A **Gifts module** on the contact page. A module is a section of the contact page that an account's template places on a template page. The Gifts module works like the Loans and Goals modules.
  - Shows "Open ideas" first, then "Past gifts". Past gifts are sorted by given date, newest first.
  - Lets a vault editor add, edit and delete a gift.
- **Gift fields:**
  - Name (what the gift is). Required.
  - Recipients: one or more contacts in the same vault. Required.
  - Occasion, chosen from the account's gift occasions. Optional.
  - Stage, chosen from the account's gift stages. New gifts default to the first stage.
  - Rough price, with a currency from the account's active currencies. Optional.
  - Link: a URL the user pastes in. Optional.
  - Note: free text. Optional.
  - Given date. This field exists only on given gifts (see "The given rule" below).
- **Several recipients.** A gift can have more than one recipient, for example a wedding present for a couple. The gift appears in the Gifts module of every recipient.
- **A vault Gifts page.** It appears in the vault's main navigation and lists every open gift in the vault, grouped by occasion.
  - Groups follow the occasion order from Settings. Gifts with no occasion go in a last group called "No occasion".
  - Each gift shows its name, recipients, stage and price, and links to each recipient's contact page.
  - If the vault has no open gifts, the page shows an empty-state message and explains how to add a gift from a contact page.
- **A vault setting to show or hide the Gifts page.** It sits with the existing tab visibility settings (Tasks, Files, Journal and others). It is on by default.
- **Contact history entries.** The contact history (in the code: the contact feed, `ContactFeedItem`) gets an entry on each recipient's page when someone adds a gift. It gets another entry when someone marks a gift as given.
- **Existing accounts.** A data migration adds the Gifts module to existing accounts, so users see it without changing any settings (see Technical notes). Users can remove it from a template in Settings, the same way they manage other modules.
- **Phone screens.** The Gifts module and the Gifts page work at phone screen widths, like the rest of the app.

#### The given rule

- A gift is **given** when it has a given date. A gift is **open** when it has no given date.
- The **final stage** is the gift stage with the highest position, which is the last stage in the Settings list. With the default stages, this is "Offered".
- When a user moves a gift into the final stage, Monica sets the given date to today. The user can change that date to an earlier day, for example to log a gift from last year. The date cannot be in the future.
- When a user moves a gift out of the final stage, Monica clears the given date. The gift becomes open again.
- If an administrator later reorders, renames or deletes stages, existing gifts keep their given date. Their given or open status does not change.
- If an administrator deletes the stage a gift is in, the gift keeps its given date and shows "No stage".

### Out of scope

- Gifts the user receives. This phase covers only gifts the user gives.
- Gifts that are private to one vault user. Every user who can see the vault can see every gift.
- Recipients who are not contacts in the vault.
- Totals, budgets or spending reports.
- Links to shops, product lookups or affiliate deals. A link is plain text that the user pastes in.
- Photos on gifts.
- Links between a gift and a specific important date (for example, Anna's birthday on 3 March).
- Sorting the vault Gifts page by whose birthday comes next.
- A dashboard widget, new reminders or notifications about gifts.
- The public API, importing gifts from the old Monica, and CardDAV/CalDAV sync. vCard has no gift field, so sync has nothing to carry.
- Search results for gifts.
- History entries for editing or deleting a gift. Only "gift added" and "gift given" create entries.

### Phasing

- Phase 1: this spec.
- Phase 2 (not yet specified): sort the Gifts page by the next important date, add a gift reminder or dashboard widget, and possibly support private gifts or received gifts.

### Vertical slice check

If this feature shipped alone, a user could record a gift idea on a contact page, follow it until it is given, and see all open ideas for the vault on one page. They cannot do any of that today.

---

## Figma

No designs needed. The Gifts module reuses the layout and add/edit form patterns of the existing Loans module (`ManageLoans`). The vault Gifts page reuses the layout of the existing vault Tasks page (`Vault/ManageTasks`). A developer who needs a new pattern should ask the PM before building it.

---

## Acceptance criteria

**AC-1 — Add a gift for several recipients**
GIVEN a vault editor is on Anna's contact page, and Anna and Ben are contacts in the same vault
WHEN the editor adds a gift named "Cookbook" with recipients Anna and Ben, occasion "Wedding" and no stage chosen
THEN "Cookbook" appears under "Open ideas" in the Gifts module on both Anna's and Ben's contact pages, with the first gift stage ("Idea" by default)
AND the contact history of both Anna and Ben shows one "gift added" entry for "Cookbook".

**AC-2 — Mark a gift as given**
GIVEN "Cookbook" is an open gift for Anna and Ben
WHEN a vault editor changes its stage to the final stage ("Offered" by default) and saves without changing the date
THEN the given date of "Cookbook" is today
AND "Cookbook" appears under "Past gifts" on both contact pages, above any gift with an earlier given date
AND "Cookbook" no longer appears on the vault Gifts page
AND the contact history of both Anna and Ben shows one "gift given" entry for "Cookbook".

**AC-3 — Vault Gifts page**
GIVEN a vault with the Gifts page switched on, where the account's occasions are Birthday then Christmas in Settings order, and the vault has one open Christmas gift, one open Birthday gift, one open gift with no occasion and one given gift
WHEN a vault user opens the Gifts page
THEN the page shows three groups in this order: "Birthday", "Christmas", "No occasion", each holding its one open gift
AND the given gift does not appear
AND when a vault manager switches the Gifts page off in vault settings, the Gifts link disappears from that vault's main navigation.

**AC-4 — Existing accounts see Gifts without setup**
GIVEN an account and vault created before this release, with the default template unchanged
WHEN the release migrations have run and a vault user opens any contact page in that vault
THEN the Gifts module appears on the same template page as the Loans module
AND the Gifts link appears in the vault's main navigation.

**AC-5 (unhappy path) — View-only users cannot change gifts**
GIVEN a user with vault viewer permission opens a contact page with gifts, and the vault Gifts page
WHEN the page loads
THEN the user sees every gift, but no add, edit or delete controls
AND if that user sends a request to create, update or delete a gift directly, Monica refuses it with HTTP 403 (forbidden) and the gift data does not change.

**AC-6 (unhappy path) — A recipient is deleted**
GIVEN "Cookbook" is a gift for Anna and Ben
WHEN a vault editor deletes Anna's contact
THEN "Cookbook" stays on Ben's contact page with Ben as its only recipient
AND WHEN the editor then deletes Ben's contact, "Cookbook" is deleted and no longer appears on the vault Gifts page.

---

## Assumptions

- **The existing `gifts` and `contact_gift` tables are empty everywhere,** including on the hosted plan and on self-hosted instances. No code writes to them, so we can drop and recreate them. *(Riskiest assumption.)*
- The deliberate rememberer is the right primary persona. We will replace this with a persona from `personas.md` when that file exists.
- The final stage in the Settings list is a reliable signal that a gift was given. Users who rename the default stages will keep "given" as the last stage.
- Every account has at least one gift stage, because new accounts get five by default. If an administrator deletes all stages, users cannot mark gifts as given until they add a stage again.
- Users accept that everyone in a shared vault sees all gift ideas, including ideas meant for them.
- Success measure (working assumption, not confirmed by the PM): 60 days after release, at least 15% of hosted vaults with any activity in that period have at least one gift. We will measure this with an aggregate count only. We will not read gift content.

---

## Technical notes

- **Rebuild the tables.** Add a new migration that drops `contact_gift` and `gifts` and creates them again. Follow the shape of `loans` and `contact_loan` (`database/migrations/2022_03_23_005751_create_loans_table.php`):
  - `gifts`: `vault_id` (cascade on delete), `gift_occasion_id` (nullable, null on delete), `gift_state_id` (nullable, null on delete), `name`, `description` (the note), `url` (nullable), `estimated_price` (nullable integer), `currency_id` (nullable, null on delete), `given_at` (nullable), timestamps.
  - `contact_gift`: `gift_id` (cascade on delete), `contact_id` (cascade on delete), timestamps.
  - Drop the unused `type`, `received_at` and `bought_at` columns.
- **Price.** Store and show the price with `MonetaryNumberHelper`, exactly as the Loans module does for `amount_lent`.
- **Code location.** Put services, controllers and view helpers in a new `app/Domains/Contact/ManageGifts/` folder. Put the vault page in a new `app/Domains/Vault/ManageGifts/` folder. Every service extends `BaseService`.
- **Permissions.**
  - Create, update and delete services declare `author_must_be_vault_editor` and `contact_must_belong_to_vault`.
  - Every recipient must belong to the same vault. The service rejects the request otherwise.
  - Read routes use the `vault-viewer` gate. Write routes use the `vault-editor` gate.
- **Validation.** The create and update services require a name and at least one recipient. A save that has neither returns a validation error, and Monica saves nothing.
- **Deleting a contact.** Contacts use soft delete (`Contact` uses `SoftDeletes`), so the `contact_gift` cascade does not run. `DestroyContact` (`app/Domains/Contact/ManageContact/Services/DestroyContact.php`) must remove the contact from each of its gifts. It must also delete any gift that has no recipients left.
- **Contact page module.** Add `Module::TYPE_GIFTS` in `app/Models/Module.php`. Render it in `ContactShowViewHelper`, next to the Loans and Goals cases.
  - `SetupAccount` creates the module for new accounts and places it after Loans on the "Information" template page.
  - A data migration does the same for existing accounts. For each template, it adds Gifts to the template page that holds the Loans module. If a template has no Loans module, the migration skips that template.
- **Vault page setting.** Add a `show_gifts_tab` boolean column to `vaults`, default `true`. Add it to `UpdateVaultTabVisibility`, `TabVisibility.vue` and the vault navigation in `resources/js/Layouts/Layout.vue`.
- **History entries.** Add `ACTION_GIFT_CREATED` and `ACTION_GIFT_GIVEN` to `ContactFeedItem`. Write one feed item per recipient.
- **Shared settings.** Gift stages and gift occasions belong to the account, not the vault. Only account administrators can edit them, and the changes apply to every vault in the account.
- **Privacy review.** This feature stores data only in Monica's own database. It sends no data to any third party, so it passes the strategy's privacy review gate.
- **Work in progress.** `docs/iterations/current.md` does not exist, so this spec has not been checked against current work.

---

## Open questions

- 🟡 What is the right success measure, and do we already have an aggregate count for hosted vaults? Working assumption: the 15% measure above. — PM
- 🟡 Does any self-hosted instance have rows in the `gifts` table, for example from manual inserts? Working assumption: no. If the migration finds rows, should it stop instead of dropping the table? — PM with maintainers
- 🟡 Do users understand that the last stage in Settings means "given"? Working assumption: yes, because the default list ends with "Offered". We can add a hint in the Gift stages settings page later. — PM
- 🟢 Sorting the Gifts page by whose birthday comes next is Phase 2, not this spec. — PM

---

## Before you build

**Decisions made:**

- Gifts belong to a vault and link to one or more recipient contacts. Every vault user can see them. Only vault editors can change them.
- A gift is given when it has a given date. Moving a gift into the final stage sets that date, and moving it out of the final stage clears it.
- When a gift loses its last recipient, Monica deletes the gift.
- The vault Gifts page shows only open gifts, grouped by occasion. A vault setting switches it on or off, and it is on by default.
- Existing accounts get the Gifts module through a data migration. It goes on the same template page as Loans.
- Photos, received gifts, budgets, reminders, the API and CardDAV/CalDAV sync are not part of this phase.

**Riskiest assumption:**
The unused `gifts` and `contact_gift` tables are empty on every instance, so the migration can drop them without losing user data.

**Next step:**
Ready for ticket generation. Write tickets to `output/tickets.md`. No open question blocks the build.
