# Gift tracking

Last updated: 15 October 2026
Status: Ready
Tickets: (fill in once created — this run writes tickets to `output/tickets.md`)

---

## Terms used in this spec

- **Vault:** a shared workspace in Monica. It holds contacts and can have several members.
- **Contact:** a person the user keeps a record of inside a vault.
- **Vault member roles:** each vault member has one of three roles. A *viewer* can only read. An *editor* can add and change things. A *manager* can also change vault settings.
- **Gift:** something the user plans to give, or has given, to one or more contacts.
- **Recipient:** a contact that a gift is for. A gift has one or more recipients.
- **Gift state:** the step a gift has reached, for example "Idea" or "Bought". Each account manages its list of gift states in Settings → Personalize → Gift states. The default list is Idea, Searched, Found, Bought, Offered. Users can rename, reorder, add and delete gift states.
- **Final gift state:** the gift state in the last position of the account's list. With the defaults, this is "Offered".
- **Given gift:** a gift in the final gift state.
- **Open gift:** any gift that is not a given gift. This includes gifts with no gift state.
- **Gift occasion:** the reason for a gift, for example "Birthday". Each account manages its list of gift occasions in Settings → Personalize → Gift occasions. The default list is Birthday, Anniversary, Christmas, Just because, Wedding.
- **Contact page section:** a block on a contact's page, for example "Notes" or "Reminders". Users choose which sections each contact page layout shows in Settings.
- **Vault tab:** a page in a vault's top navigation, for example "Tasks" or "Journals". Vault managers can show or hide some tabs in the vault settings ("Tabs visibility").

---

## Problem statement

People who use Monica to remember what matters about others cannot record gift ideas, track them until they give them, or look back at past gifts.

## Why now

Gift management is one of the two most important features that the current Monica lacks compared with the previous version. The app's own beta page says this to every new user. Gifts also give users a reason to come back before every birthday and holiday. That supports the strategy's top priority: keep users active in their first 30 days and after. Account settings already let users manage gift states and gift occasions, but nothing in the product uses them yet.

## Users affected

`docs/product/personas.md` does not exist, so this spec uses the user groups in `docs/product/strategy.md`.

- **Primary — deliberate rememberers:** people who use Monica on purpose to remember personal details about people they care about. The strategy lists introverts, neurodivergent users, and people who struggle to recall personal details. Gifts are a moment where this memory pays off.
- **Secondary — privacy-motivated professionals with large personal networks:** they buy gifts for many people and need one place to see open ideas.
- **Secondary — other vault members:** for example, a partner who shares a vault. Editors can add and change gifts. Viewers can see gifts but cannot change them.
- **Edge case — the recipient is also a vault member:** in a shared vault, the recipient can see gift ideas meant for them. This spec accepts that. Gifts follow the same visibility rules as everything else in a vault.

## Background

Users get gift ideas at random moments, then forget them. They also cannot remember what they gave someone last year. Today a user can only write a gift idea in a free-text note on the contact. A note has no state, and no vault-wide view collects open ideas before a birthday or holiday. Some gifts are for more than one person, such as a wedding present for a couple. A note on one contact does not show on the other contact's page.

---

## Scope

### In scope

- Add a gift from a contact's page. That contact is filled in as the first recipient.
- Give a gift these fields:
  - **Name** — what the gift is. Required.
  - **Recipients** — one or more contacts in the same vault. Required.
  - **Gift occasion** — optional, chosen from the account's gift occasions.
  - **Gift state** — optional, chosen from the account's gift states. New gifts start in the first gift state in the list.
  - **Rough price** — optional. A number plus a currency, chosen from the currencies the account has enabled.
  - **Link** — optional web address that the user pastes in.
  - **Note** — optional free text.
  - **Date given** — optional date.
- Edit a gift, including adding or removing recipients. A gift must keep at least one recipient.
- Delete a gift, after the user confirms.
- Show a "Gifts" contact page section. It lists the contact's open gifts and, separately, their given gifts (the gift history).
- Add a "Gifts" vault tab. It lists every open gift in the vault, grouped by gift occasion. Gifts without an occasion appear in a "No occasion" group.
- Let vault managers show or hide the Gifts vault tab in "Tabs visibility", like the other vault tabs.
- Turn the feature on for existing accounts and vaults without any setup. The Gifts section appears on the default contact page layout. The Gifts vault tab is visible by default.

### Out of scope

- Gifts the user *receives*. This version covers only gifts the user gives.
- Private gifts that only one vault member can see.
- New reminders or notifications about gifts.
- Bringing forward contacts whose birthday is next on the Gifts vault tab.
- Filtering the Gifts vault tab by contact. The contact's own Gifts section already shows this.
- Budgets and spending totals.
- Buying links, shops, affiliate deals, link previews, and price tracking.
- Photos of gifts.
- Wishlists that the recipient can see or share.
- Importing gifts from the previous version of Monica.
- Showing gifts in calendar or contact sync to other apps.

### Phasing

- **Phase 1 (this spec):** record, track, and look back at gifts the user gives; the vault-wide list of open gifts.
- **Phase 2 (possible future work, not committed):** gifts received; private gifts; highlighting contacts with an upcoming birthday; filtering by contact; photos.

### Vertical slice check

If this shipped alone, a user could save a gift idea on a contact's page the moment they think of it. Then they could see all open ideas before an occasion and look back at what they gave. They cannot do any of this today.

---

## Figma

Designs to be created before build, for two screens only:

- the Gifts vault tab, including the empty state
- the add/edit gift form, including choosing several recipients

The Gifts contact page section follows the same layout as existing contact page sections, so it does not need a new design.

---

## Acceptance criteria

**AC-1 — Add a gift from a contact's page**
GIVEN an editor is on the page of contact "Sam"
WHEN they add a gift with only the name "Cookbook" and save it
THEN the gift appears under open gifts in Sam's Gifts section
AND Sam is its only recipient
AND its gift state is the first gift state in the account's list.

**AC-2 — One gift for several recipients**
GIVEN a gift "Wedding present" has recipients "Ana" and "Ben"
WHEN an editor changes its price on Ana's page
THEN Ben's page shows the same gift with the new price
AND after someone deletes Ana from the vault, the gift still appears on Ben's page with Ben as its only recipient.

**AC-3 — Given gifts become history**
GIVEN an open gift for Sam
WHEN an editor moves it to the final gift state and sets a date given
THEN Sam's Gifts section shows it under given gifts, with the date given
AND the Gifts vault tab no longer lists it.

**AC-4 — Gifts vault tab lists open gifts by occasion**
GIVEN a vault has open gifts with the occasions "Birthday" and "Christmas", one open gift with no occasion, and one given gift
WHEN a member opens the Gifts vault tab
THEN the tab shows three groups: "Birthday", "Christmas" and "No occasion"
AND each gift shows its name, recipients and gift state
AND the given gift does not appear
AND if the vault has no open gifts, the tab shows an empty-state message that explains how to add a gift from a contact's page.

**AC-5 — Viewers can read but not change gifts**
GIVEN a vault member has the viewer role
WHEN they open a contact's Gifts section or the Gifts vault tab
THEN they can see every gift
AND they see no controls to add, edit or delete a gift.

**AC-6 (unhappy path) — Required fields missing**
GIVEN an editor is adding or editing a gift
WHEN they try to save it with an empty name, or with every recipient removed
THEN Monica does not save the gift
AND Monica shows a message next to the field that needs a value.

---

## Assumptions

- Users treat the gift state in the last position of their list as "given". This holds for the default list, which ends with "Offered". **This is the riskiest assumption.**
- A gift is one shared record. An edit on any recipient's page changes it for every recipient.
- Users drop an idea by deleting it. They do not need an "abandoned" state, and they can add one in Settings if they want.
- Users accept that every vault member, including a recipient who is a vault member, can see gift ideas.
- Moving a gift to the final gift state does not fill in the date given automatically. The user enters it if they want it.
- If someone deletes a gift's last remaining recipient from the vault, the gift is deleted with that contact.
- Success measure: within 60 days of release, at least 15% of active hosted vaults have one or more gifts. Monica counts this only as a number per vault, without reading gift content.

---

## Technical notes

- **Existing accounts:** the feature must work for every existing account and vault without setup. This includes accounts that have renamed, reordered or deleted gift states or gift occasions.
- **An account with no gift states:** if an account has deleted every gift state, users can still save gifts. Those gifts have no gift state and count as open.
- **Deleting a gift state or occasion in Settings:** this must never delete gifts. Gifts that used it keep everything else and lose only that gift state or occasion.
- **Changing the order of gift states:** the final gift state can change when a user reorders the list. Gifts then move between "open" and "given" to match the new order. This is expected behaviour.
- **Privacy review gate:** Monica must not fetch, preview or check a pasted link. Opening the link is the user's own action. No gift data goes to a third party.
- **Vault permissions:** gifts follow the existing vault member roles. No new roles or per-gift permissions are added.
- **Recipients:** recipients must be contacts in the same vault as the gift.

---

## Open questions

- 🟡 Do users who customise gift states expect the last state to mean "given"? Working assumption: yes. Revisit if users report gifts in the wrong list. — PM
- 🟡 Can the hosted-plan analytics count vaults with gifts, without reading gift content? Working assumption: yes, as an aggregate count. — PM with maintainers
- 🟡 Should moving a gift to the final gift state suggest today as the date given? Working assumption: no, the user enters it. — design review
- 🟢 Gifts received are out of scope for Phase 1. The PM decided this. — PM

---

## Before you build

**Decisions made:**

- Phase 1 covers only gifts the user gives.
- Gifts use the account's existing gift states and gift occasions from Settings. The final gift state means "given".
- A gift can have several recipients. It is one shared record, shown on each recipient's page.
- Every vault member can see gifts. Viewers cannot change them.
- The Gifts vault tab groups open gifts by occasion. It has no filter by contact and does not highlight upcoming birthdays.
- No reminders, notifications, photos, budgets, buying links, price tracking, wishlists, import or sync in Phase 1.
- Existing accounts get the feature with no setup.

**Riskiest assumption:**
Users who customise their gift states will treat the last state in their list as "given", so open and given gifts appear in the right place.

**Next step:**
Design review of the Gifts vault tab and the add/edit gift form. Then the spec is ready for ticket generation.
