# Gift tracking

Last updated: 15 October 2026
Status: Ready
Tickets: (fill in once created — no ticket tool in this run, so tickets go to `output/tickets.md`)

---

## Terms used in this spec

- **Vault**: a shared space in Monica that holds contacts. One or more users can have access to a vault.
- **Contact**: a person stored in a vault. Each contact has a **contact page**.
- **Section**: one block of content on a contact page, such as Loans or Goals. Users choose which sections appear on contact pages in Settings, through templates.
- **Gift**: something the user plans to give, or has given, to one or more contacts.
- **Recipient**: a contact a gift is for. A gift has one or more recipients.
- **Gift idea** (or **open gift**): a gift that the user has not given yet.
- **Past gift**: a gift that has a date given.
- **Gift state**: the progress of a gift, such as Idea or Bought. Users already manage the list of gift states in Settings → Personalize. The default list is Idea, Searched, Found, Bought, Offered.
- **Gift occasion**: the reason for a gift, such as Birthday. Users already manage the list of gift occasions in Settings → Personalize. The default list is Birthday, Anniversary, Christmas, Just because, Wedding.
- **Vault access levels**: Monica gives each user one of 3 levels in a vault. "View" can only look. "Edit" can change contacts. "Manage" can also change vault settings.

---

## Problem statement

People forget gift ideas for the people they care about, and forget past gifts, because Monica has no place to record gifts.

## Why now

Monica's own beta page names gift management as one of the 2 most important missing features. Accounts already contain gift states and gift occasions in Settings, but no feature uses them. This spec puts that existing setup to use instead of adding new configuration. Gifts also give users a reason to return before every birthday and holiday. That supports the strategy's top metric: keeping users through their first 30 days and beyond. The end-of-year holiday season starts in about 10 weeks, and it is the biggest gift-giving moment of the year.

## Users affected

`personas.md` does not exist, so this spec uses the target segment from `docs/product/strategy.md`.

- **Deliberate rememberer** (primary): a person who uses Monica on purpose to remember what matters about the people they care about. This includes people who find it hard to recall personal details. They get a gift idea at a random moment and need to save it before they forget. Before a birthday or holiday, they need to see the ideas they already have.
- **Vault members with View access**: they can see gifts but cannot add, change, or delete them.
- **Recipients who share the vault** (edge case): if a recipient also has access to the vault, they can see gifts planned for them. This version does not hide gifts from anyone with vault access. Users who want surprises must keep gift ideas in a vault the recipient cannot open.

## Background

Today Monica has no gift feature. Users keep gift ideas in notes on a contact page, in tasks, or in a separate app. Notes and tasks do not show which ideas are still open across all contacts. They also do not show what the user gave last year. The Settings pages for gift states and gift occasions already exist, but they have no visible effect. This confuses users who find them.

---

## Scope

### In scope

- A new **Gifts** section on contact pages, which lists:
  - open gifts first, most recently added first
  - then past gifts, newest date given first
- Adding a gift from a contact page. That contact is the first recipient.
- Required fields:
  - name (what the gift is)
  - at least one recipient
- Optional fields:
  - gift occasion, chosen from the account's gift occasions
  - gift state, chosen from the account's gift states (new gifts default to the first state in the list)
  - rough price: an amount and a currency from the currencies Monica already offers
  - link (a web address the user pastes in)
  - note (free text)
  - date given
- Recording a gift that was given in the past. If the user enters a date given when adding the gift, it goes straight to past gifts.
- Marking a gift as given (see the rules under "Given" below).
- Editing and deleting a gift.
- Gifts with more than one recipient. The gift shows on each recipient's contact page.
- A new vault-wide **Gifts** page, reached from the vault's main navigation. It:
  - shows open gifts only
  - groups them by gift occasion, in the order set in Settings
  - shows gifts with no occasion in a "No occasion" group at the end
  - shows each gift's name, recipients (linked to their contact pages), gift state, and price
- A vault setting to switch the Gifts page on or off. It works like the existing vault settings for other vault pages. It is on by default for all vaults, including existing ones.
- Existing accounts see the Gifts section on contact pages without any setup. They can remove it in Settings like any other section.
- Permissions follow the existing vault access levels: View can see gifts; Edit and Manage can add, change, and delete them.
- Pages work on phone-sized screens, like the rest of the app.

**Given.** A gift is given when it has a date given. Apply these 2 rules:

1. If the user sets the gift state to the **last** state in the account's list, fill the date given with today. The user can change that date.
2. If the user enters a date given, set the gift state to the last state in the account's list.

Removing the date given makes the gift open again.

**Gifts with several recipients.** A gift with several recipients is one gift, not a copy per recipient:

- An edit from any recipient's page shows on every recipient's page.
- Deleting the gift removes it from every recipient's page. Monica asks the user to confirm first.
- Removing a recipient (through Edit) removes the gift from that contact's page only.
- If someone deletes a recipient contact, the gift stays for the other recipients. If it was the only recipient, Monica deletes the gift too.
- A past gift appears once in each recipient's past gifts.

### Out of scope

- Gifts the user **received** from others. This is planned for Phase 2.
- Private gifts that only their creator can see.
- New reminders, notifications, or a dashboard widget for gifts.
- Sorting the Gifts page by whose birthday is next.
- Price totals, budgets, or spending reports.
- Shopping integrations, affiliate deals, and link previews. A link is only text the user pastes in.
- Sharing a wishlist with the recipient or anyone outside the vault.
- AI gift suggestions.
- A mobile-specific quick-add flow. The normal form must still work on a phone.
- Adding a gift from the vault-wide Gifts page. Users add gifts from a contact page.
- Photos of gifts and a "date bought" field.

### Phasing

- Phase 1: this spec. The user can record, track, and look back on gifts they give.
- Phase 2 (not specified yet): gifts the user received; private gifts; reminders or a dashboard widget before occasions.

### Vertical slice check

If this feature shipped alone, a user could save a gift idea on a contact page, mark it as given, and see every open idea in the vault in one place. They cannot do any of this today. This is not an enabler story (work that delivers no user value on its own).

---

## Figma

No designs needed. The Gifts section follows the existing Loans and Goals sections on contact pages. The vault-wide Gifts page follows the existing vault-wide Tasks page. A designer may review the built screens before release. That review is optional and does not block the build.

---

## Acceptance criteria

**AC-1**
GIVEN an account created before this release, where nobody has changed any settings
AND a user with Edit access opens a contact page
WHEN the user adds a gift with only a name, such as "Pottery class"
THEN Monica shows the Gifts section on the contact page without any setup
AND Monica saves the gift with that contact as the recipient and the first gift state in the account's list
AND the gift appears at the top of the open gifts on the contact page
AND the gift appears in the "No occasion" group on the vault-wide Gifts page.

**AC-2**
GIVEN a contact with 2 past gifts, given on 3 March 2025 and 10 December 2025, and 1 open gift
WHEN a user with Edit access sets the open gift's state to the last state in the account's list and saves
THEN Monica fills the date given with today's date
AND the gift moves from the open gifts to the top of the past gifts on the contact page
AND the 10 December 2025 gift appears above the 3 March 2025 gift
AND the gift no longer appears on the vault-wide Gifts page.

**AC-3**
GIVEN an open gift "Wedding present" with 2 recipients, Contact A and Contact B, in the same vault
WHEN a user with Edit access changes the gift's name to "Wedding vase" on Contact A's page
THEN Contact B's page shows the gift as "Wedding vase"
AND the vault-wide Gifts page shows the gift once, with both recipients
AND WHEN a user then deletes Contact A
THEN the gift still appears on Contact B's page with Contact B as its only recipient.

**AC-4**
GIVEN a vault with open gifts for the occasions "Birthday" and "Christmas", and one gift with no occasion
WHEN a user opens the Gifts page from the vault's main navigation
THEN Monica groups the open gifts under "Birthday", then "Christmas", then "No occasion"
AND Monica shows no past gifts
AND WHEN a user with Manage access switches the Gifts page off in the vault settings
THEN the Gifts item disappears from the vault's main navigation
AND the Gifts sections on contact pages still show.
A vault with no open gifts shows an empty-state message on the Gifts page. The message tells the user to add gift ideas from a contact page.

**AC-5**
GIVEN a user with View access to a vault that contains gifts
WHEN the user opens a contact page and the vault-wide Gifts page
THEN the user can see every gift for contacts in that vault
AND the user sees no controls to add, edit, or delete a gift
AND Monica rejects any attempt by this user to add, change, or delete a gift. Monica does not change the gift.

**AC-6 (unhappy path)**
GIVEN a user with Edit access is adding or editing a gift
WHEN the user saves with an empty name, or after removing every recipient
THEN Monica does not save the gift
AND Monica shows an error next to the empty field: "Enter what the gift is" for the name, or "Choose at least one person" for recipients
AND the values the user already entered stay in the form.

---

## Assumptions

- The strategy's "deliberate rememberer" is the right primary user. There is no `personas.md` to confirm this.
- **Riskiest:** Users understand that the **last** gift state in their list means "given". Users who reorder or add states after "Offered" could mark gifts as given by accident.
- A gift with several recipients is one shared gift. Edits apply to all recipients, and the gift appears once in each recipient's past gifts.
- Users accept that anyone with access to a vault can see gift ideas, including a recipient who shares the vault.
- Users will add ideas through the normal form on a phone. A separate quick-add flow is not needed for Phase 1.
- Existing sections such as Loans and Goals, and the existing vault-wide Tasks page, give enough design guidance. No Figma designs are needed.
- Gift tracking gives users a reason to return before birthdays and holidays. We have no usage data for this yet. The beta page and user requests are the only evidence.
- New gifts default to the first gift state in the account's list, which is "Idea" by default.

---

## Technical notes

- **Privacy:** Monica must never open, fetch, or preview a link the user pastes in. Saving or viewing a gift must not contact the linked website. This keeps the feature inside the strategy's privacy review gate.
- **No outside services:** The feature must work the same on the hosted plan and on self-hosted installs. It must not need any new outside service.
- **Existing accounts:** Existing accounts must get the Gifts section on contact pages with no user action. The section appears on the "Information" tab, where Loans appears by default. Monica must not change any other part of a user's customised templates.
- **Settings changes:** If a user renames, reorders, or adds a gift state or gift occasion in Settings, existing gifts show the change straight away.
- **Deleting a gift state or gift occasion:** If a user deletes a state or occasion that gifts use, Monica must not delete those gifts. Gifts that lose their occasion move to "No occasion". Gifts that lose their state show no state.
- **Deleting contacts:** Deleting a contact must not delete a gift that still has other recipients.
- **In-flight work:** `docs/iterations/current.md` does not exist, so this spec cannot check for conflicts with current work. The engineering team should check at plan-review.

---

## Open questions

- 🟡 Should "given" depend on the last gift state? The alternative is a separate "Mark as given" action, with state as a label only. The working assumption is the 2 rules under "Given". — PM
- 🟡 Some users have removed or renamed the "Information" tab in their template. Where should the Gifts section go for them? Working assumption: the first tab of their default template. — Engineering at plan-review
- 🟡 Should switching off the vault Gifts page also hide the Gifts sections on contact pages? Working assumption: no, it hides only the vault-wide page. — PM
- 🟡 Does the beta page need updating once gifts ship? It currently lists gift management as missing. Working assumption: yes, remove gift management from that list in the same release. — PM
- 🟢 Gifts the user received are Phase 2, not this spec. — PM (confirmed)

---

## Before you build

**Decisions made:**

- This version covers only gifts the user gives.
- Gifts use the gift states and gift occasions that users already manage in Settings.
- Anyone with access to the vault can see gifts. Users with Edit or Manage access can change them; users with View access cannot.
- A gift with several recipients is one shared gift. It survives the deletion of one recipient.
- The vault-wide Gifts page shows open gifts only, grouped by occasion. A vault setting can switch it off.
- No reminders, budgets, link previews, or Figma designs in this version.

**Riskiest assumption:**
Users understand that the last gift state in their list means "given", even after they rename or reorder states.

**Next step:**
Ready for ticket generation. At plan-review, the engineering team decides where the Gifts section goes in customised templates.
