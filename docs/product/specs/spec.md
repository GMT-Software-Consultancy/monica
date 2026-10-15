# Gift tracking

Last updated: 15 October 2026
Status: Ticketed
Tickets: `output/tickets.md`, tickets 1–10 (no tracker IDs, because this project has no ticket tool)

---

## Problem statement

Monica users forget gift ideas and what they gave before, because Monica has no place to record gifts.

## Why now

Users name gifts as one of the main features missing from Monica. The product admits the gap: the beta page (`resources/js/Pages/Auth/Beta.vue`) says Monica "misses … gift management". Gifts also give users a reason to come back before every birthday and holiday. That supports the strategy's top goal, which is better retention (how many users keep using Monica over time).

## Users affected

`docs/product/personas.md` does not exist, so this spec uses the target segment from `docs/product/strategy.md`.

- **Deliberate rememberer (primary).** A person who uses Monica to remember what matters about the people they care about. Some struggle to recall personal details. Gifts are a moment where that memory pays off. This user records ideas as they come and checks them before an occasion.
- **Other users of a shared vault.** A vault (a separate space with its own contacts, shared by one or more users) can have several users. Each of them sees every gift in the vault, including ideas meant for them. The PM accepts this for this version.
- **Users with viewer permission in a vault.** They can see gifts but cannot add, edit, or delete them. This matches every other item in a vault.
- **Existing users, on the hosted plan and on self-hosted installs.** They must get the feature without any setup.

## Background

Today, users keep gift ideas outside Monica, for example in a phone note, or they lose them. Some users write ideas into a contact's notes. But notes do not show what is still open, and they do not collect ideas across contacts. Nobody can see "what did I give last year" without searching old notes.

The code already has part of this feature:

- Account settings already let users manage **gift occasions** (default: Birthday, Anniversary, Christmas, Just because, Wedding).
- Account settings already let users manage **gift states** (default: Idea, Searched, Found, Bought, Offered).
- A `gifts` table and a `contact_gift` table exist, but no code reads or writes them. The design of these tables cannot support this spec (see Technical notes).

---

## Scope

Terms used in this spec:

- **Gift**: one thing the user plans to give, or has given, to one or more contacts.
- **Recipient**: a contact the gift is for. A gift has one or more recipients.
- **Gift occasion**: the reason for the gift, chosen from the account's list in Settings → Personalize → Gift occasions.
- **Gift state**: how far the gift has progressed, chosen from the account's list in Settings → Personalize → Gift states.
- **Given date**: the date the user gave the gift.
- **Open gift**: a gift with no given date.
- **Given gift**: a gift with a given date.
- **Module**: a block on the contact page, such as Loans or Goals. The account's contact templates control which modules appear and on which page.

### In scope

- A **Gifts module** on the contact page. It lists the contact's gifts in two sections:
  - "Ideas": open gifts, newest created first.
  - "Given": given gifts, most recent given date first.
- Users with editor permission can add, edit, and delete gifts from the Gifts module. A gift has these fields:
  - name (required)
  - recipients (required, at least one contact from the same vault)
  - gift occasion (optional)
  - gift state (optional)
  - estimated price (optional), with a currency from the account's active currencies
  - link (optional, a web address)
  - note (optional, free text)
  - given date (optional)
- When the user adds a gift from a contact's page, the form pre-selects that contact as a recipient. The user can add more recipients.
- If the user picks the **last** gift state and the given date is empty, the form fills in today's date. The user can change or clear it. "Last" means the highest position in Settings, whatever its name.
- A gift with several recipients is **one record**. It appears on each recipient's contact page. An edit to the gift shows on every recipient's page.
- Each gift row in the Gifts module shows the other recipients by name. Example: "Also for: Ben".
- A vault-level **Gifts page**, reached from a new "Gifts" tab in the vault's main navigation. It:
  - shows open gifts only
  - groups them by gift occasion, in the order set in Settings
  - puts gifts with no gift occasion in a last group called "No occasion"
  - hides groups with no open gifts
  - sorts gifts in each group newest created first
  - shows, for each gift: name, recipients (each name links to the contact page), gift state, and estimated price
  - is read-only; users edit gifts on a contact page
- A **Gifts switch** in vault settings → tab visibility, like the switches for Tasks or Journals. It is on by default.
- **Existing accounts and vaults** get the Gifts module and the Gifts tab without any setup (see AC-5).

### Out of scope

- Gifts the user **receives** from other people.
- Gifts that only the author can see ("private" gifts). Gifts follow the same visibility rules as everything else in a vault.
- Reminders or notifications about gifts.
- Totals, budgets, or spending reports.
- Photos on a gift.
- Gifts in the API.
- Gifts in DAV export (DAV is the protocol Monica uses to sync contacts and calendars with other apps).
- Gift suggestions from AI.
- Sorting or filtering the vault Gifts page by upcoming dates, such as the next birthday.
- Filtering the vault Gifts page by gift occasion. Grouping by gift occasion covers this need for now.
- Editing gifts from the vault Gifts page.
- Search results for gifts.
- Contact feed entries (the activity list on a contact page) for gift changes.

### Phasing

- Phase 1: this spec.
- Phase 2 (not planned yet): sort the vault Gifts page by the next important date, such as a birthday. Private gifts may follow later as a bigger change.

### Vertical slice check

Alone, this feature lets a user record gift ideas per contact, see all open ideas in one place, and look back at past gifts. They cannot do any of this today.

---

## Figma

No new designs needed. Reuse the layout of the Loans module for the Gifts module, and the layout of the vault Tasks page for the vault Gifts page. The design pattern for both already exists in the product.

---

## Acceptance criteria

**AC-1**
GIVEN a user with editor permission in a vault that has the contacts Anna and Ben
WHEN the user opens Anna's contact page, adds a gift named "Cookbook" with recipients Anna and Ben, and saves
THEN "Cookbook" appears in the "Ideas" section of the Gifts module on Anna's page and on Ben's page
AND "Cookbook" appears exactly once on the vault Gifts page.

**AC-2**
GIVEN Anna has an open gift "Cookbook" and a given gift "Scarf" with the given date 10 December 2025
WHEN the user edits "Cookbook", sets the given date to 15 October 2026, and saves
THEN Anna's Gifts module shows "Cookbook" in the "Given" section, above "Scarf"
AND "Cookbook" no longer appears in the "Ideas" section or on the vault Gifts page.

**AC-3**
GIVEN the account's gift occasions are, in order, Birthday, Anniversary, Christmas, Just because, Wedding
AND the vault has open gifts "Book" (Birthday), "Lamp" (Christmas), and "Mug" (no gift occasion)
AND the vault has a given gift "Pen" (Birthday)
WHEN the user opens the vault Gifts page
THEN the page shows three groups in this order: Birthday with "Book", Christmas with "Lamp", No occasion with "Mug"
AND the page does not show "Pen"
AND the page shows no Anniversary, Just because, or Wedding group.

**AC-4 (unhappy path — empty state)**
GIVEN a vault with no open gifts
WHEN the user opens the vault Gifts page
THEN the page shows an empty-state message that explains how to add a gift from a contact page
AND the page shows no groups.

**AC-5 (existing accounts)**
After the release's database migrations run on an install with existing accounts:

- [ ] Every contact template in every existing account contains the Gifts module.
- [ ] Every existing vault shows the Gifts tab in its main navigation.
- [ ] The vault settings tab visibility section has a Gifts switch, and it is on.
- [ ] When a vault manager turns the Gifts switch off, the Gifts tab no longer appears in that vault's navigation.

**AC-6 (unhappy path — recipient deleted)**
GIVEN a gift "Wedding present" with recipients Anna and Ben
AND a gift "Book" with Anna as its only recipient
WHEN a user deletes the contact Anna
THEN "Wedding present" still appears on Ben's page and on the vault Gifts page, with Ben as its only recipient
AND "Book" no longer exists and does not appear on the vault Gifts page.

---

## Assumptions

- **The given date decides whether a gift is open or given.** The gift state does not. Users can rename, reorder, or delete gift states, so no single state name can mean "given". The form fills the given date when the user picks the last gift state, so most users never notice this rule. *This is the riskiest assumption.*
- The default gift states (Idea → Offered) match how users think about the steps of a gift.
- All recipients of a gift belong to the same vault as the gift. Gifts cannot cross vaults.
- No user data exists in the current `gifts` and `contact_gift` tables, because no code has ever written to them. Engineering can replace both tables.
- Users of a shared vault accept that other users see gift ideas, including ideas meant for them.
- Success measure: 90 days after release, at least 10% of hosted vaults that were active in that period have at least one gift. The PM had no strong view; this target is a starting guess. A count of gifts per vault in the database is enough, so no new tracking of users is needed.
- Users ask for this feature often enough to justify it, even though the strategy prefers fewer features. The PM did not provide an issue count or churn data.

---

## Technical notes

**Data model.** Replace the unused tables from `database/migrations/2022_06_09_173049_create_gifts_table.php`. Do not edit the old migration. Add a new migration that drops `contact_gift` and `gifts`, then creates them again:

- `gifts`:
  - `id`
  - `vault_id`: foreign key to vaults, deleted with the vault.
  - `gift_occasion_id`: nullable foreign key. If a user deletes the gift occasion in Settings, the value becomes null.
  - `gift_state_id`: nullable foreign key. If a user deletes the gift state in Settings, the value becomes null.
  - `name`: string, required.
  - `description`: text, nullable. This is the "note" field.
  - `url`: string, nullable, maximum 2048 characters.
  - `estimated_price`: integer, nullable.
  - `currency_id`: nullable foreign key. If the currency is deleted, the value becomes null.
  - `given_at`: date, nullable.
  - timestamps.
- `contact_gift`: `gift_id` and `contact_id`, both foreign keys with cascade delete, plus timestamps.
- Drop these old columns: `contact_id`, `type`, `received_at`, `bought_at`. Received gifts are out of scope. The gift state replaces `bought_at`.

Why replace the tables: the current `gifts.contact_id` allows only one recipient. The current `contact_gift` table is a wrong copy of the loans pivot: it references `loans` and has `loaner_id` and `loanee_id` columns.

**Follow the Loans module.** `app/Models/Loan.php` and `app/Domains/Contact/ManageLoans/` already link one vault record to many contacts. Build gifts the same way:

- `app/Models/Gift.php`, with `belongsTo` Vault, GiftOccasion, GiftState, Currency, and `belongsToMany` Contact through `contact_gift`.
- `app/Domains/Contact/ManageGifts/` with `Services/` (CreateGift, UpdateGift, DestroyGift), `Web/Controllers/`, and `Web/ViewHelpers/`.
- Store and show the estimated price the same way Loans stores `amount_lent`: use `MonetaryNumberHelper` (`parseInput`, `format`, `inputValue`).

**Service rules.** Each service extends `BaseService`:

- Permissions: `author_must_belong_to_account`, `vault_must_belong_to_account`, `author_must_be_vault_editor`.
- Every recipient ID must be a contact in the same vault. Reject the request otherwise.
- `name` is required, maximum 255 characters. `recipients` needs at least one contact. If either is missing, the form shows a validation error and the service saves nothing.
- The gift occasion and gift state must belong to the author's account.
- `url` must be a valid web address.

**Deleting a contact.** `DestroyContact` (`app/Domains/Contact/ManageContact/Services/DestroyContact.php`) soft-deletes the contact. A soft delete marks the row as deleted but keeps it, so the foreign key cascade does not run. `DestroyContact` must therefore:

1. Remove the contact from every gift in `contact_gift`.
2. Delete every gift that then has no recipients.

Monica has no feature to restore a deleted contact, so these links never need to come back.

**Moving a contact to another vault.** Gifts must not cross vaults. When a contact moves to another vault:

- If the contact is the only recipient of a gift, the gift moves too (update `vault_id`). Gift occasions and gift states belong to the account, so they stay valid.
- If the gift has other recipients, remove the moving contact from the gift. The gift stays in the old vault.

**Contact page module.**

- Add `Module::TYPE_GIFTS = 'gifts'` in `app/Models/Module.php`.
- In `SetupAccount` (`app/Domains/Settings/CreateAccount/Jobs/SetupAccount.php`), create the Gifts module (`can_be_deleted` false). Attach it to the "Information" template page, right after Loans.

**Existing accounts.** Add a data migration that, for each account:

1. Creates the Gifts module.
2. Attaches it to each of the account's templates, on the template page that already holds the Loans module.
3. If a template has no page with the Loans module, attaches it to that template's last page by position.

**Vault tab.**

- Add `show_gifts_tab` (boolean, default true) to `vaults`. The default makes it true for every existing vault.
- Update `app/Models/Vault.php` (fillable and casts), `UpdateVaultTabVisibility`, `VaultSettingsTabVisibilityController`, `VaultSettingsIndexViewHelper`, and `VaultIndexViewHelper`, following `show_tasks_tab`.
- Add the vault Gifts page under `app/Domains/Vault/ManageGifts/`. Any user who can see the vault can open it (`author_must_be_in_vault`).
- Recipient names on the Gifts page must leave out soft-deleted contacts.

**Translations.** Add every new user-facing string with `$t()` in Vue and `trans()`/`trans_key()` in PHP, as the rest of the app does.

---

## Open questions

- 🟡 Is 10% of active hosted vaults with at least one gift after 90 days the right success target? Working assumption: yes. — PM
- 🟡 Will users understand that the given date, not the gift state, decides whether a gift is "given"? Working assumption: yes, because the form fills the given date when the user picks the last gift state. Check this with 3–5 users after release. — PM
- 🟡 Do any self-hosted installs have rows in the current `gifts` table, entered by hand or by a script? Working assumption: no, because no code writes to it. — Engineering
- 🟢 Can other users of a shared vault see gift ideas meant for them? Yes. The PM decided gifts follow the same visibility rules as everything else in a vault. — PM

---

## Before you build

**Decisions made:**

- A gift is one record that can have several recipients. All recipients belong to the same vault.
- The given date decides whether a gift is open or given. The gift state only describes progress.
- Gift occasions and gift states come from the existing account settings. Users can rename them and add their own.
- The vault Gifts page shows open gifts only, grouped by gift occasion, and is read-only.
- Existing accounts get the Gifts module and the Gifts tab through migrations, with no setup by users.
- Received gifts, private gifts, reminders, budgets, photos, the API, DAV export, and AI suggestions are out of this version.

**Riskiest assumption:**
Users will see a gift as given only when it has a given date, even though they can rename or reorder gift states.

**Next step:**
Ready for ticket generation. Nothing blocks the build.
