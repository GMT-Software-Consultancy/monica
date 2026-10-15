# Gift tracking

Last updated: 15 October 2026
Status: Ticketed
Tickets: Tickets 1–6 in `output/tickets.md`. There is no tracker in this run, so the tickets have no tracker IDs.

---

## Terms used in this spec

- **Vault:** a separate workspace in Monica that holds contacts. Several users can share one vault.
- **Contact:** a person stored in a vault. Each contact has their own page.
- **Vault member roles:** each user in a vault has one of three roles:
  - **Manager:** can do everything, including change vault settings.
  - **Editor:** can add and change contacts.
  - **Viewer:** can see contacts but not change them.
- **Gift:** something the user plans to give, or has given, to one or more contacts.
- **Recipient:** a contact that a gift is for. A gift has at least one recipient.
- **Stage:** where a gift is in its life, for example "Idea" or "Bought". Each account already manages its list of stages in Settings. The defaults, in order, are Idea, Searched, Found, Bought, Offered. Users can rename, reorder, add and delete stages.
- **Final stage:** the stage that is last in the Settings order. With the default stages, the final stage is "Offered".
- **Given gift:** a gift in the final stage.
- **Open gift:** any gift that is not a given gift.
- **Occasion:** the reason for a gift, for example "Birthday" or "Wedding". Each account already manages its list of occasions in Settings. The defaults are Birthday, Anniversary, Christmas, Just because, Wedding.

---

## Problem statement

Monica users lose gift ideas that come to them at random, and cannot recall what they gave someone before.

## Why now

Users name gift tracking as one of the main features Monica is missing. Monica's own beta sign-up page lists gift management as one of the most important missing features. That page is a public promise we have not kept yet.

Gifts also give users a reason to return to Monica before every birthday and holiday. The product strategy makes "win the first 30 days" the main goal. A gift idea is a small, quick entry, and users get value from it at the next occasion.

We have no usage or cancellation number behind this request yet. See Assumptions.

## Users affected

`docs/product/personas.md` does not exist, so this spec uses the user segment from `docs/product/strategy.md` instead.

- **The deliberate rememberer (primary):** a person who uses Monica on purpose to remember what matters about the people they care about. They are often on the hosted plan. They have gift ideas at random moments and want to write them down quickly on that person's page.
- **Vault editors and managers:** they can add, change and delete gifts, the same as other contact details.
- **Vault viewers:** they can see gifts and the vault Gifts page, but cannot add, change or delete gifts.
- **Recipients who share the vault (edge case):** a recipient might be a vault member, for example a partner who shares a household vault. They will see gift ideas meant for them. This version accepts that, because every item in a vault follows the same visibility rules. Gifts that only one user can see are later work.
- **Self-hosting users:** they get the same feature as hosted users.

## Background

Monica has no way to record a gift today. Users write gift ideas in a contact's notes, in a separate notes app, or nowhere. Notes do not record whether the user has bought or given the gift. Notes cannot be listed across all contacts before an occasion. A gift for a couple has to be written twice, on two pages. Each account already has stages and occasions in Settings, but nothing uses them yet.

---

## Scope

### In scope

- A **Gifts section on each contact's page**. It shows open gifts and given gifts in two separate lists.
- **Add, edit and delete a gift** from a contact's page. A gift has these fields:
  - **Name** (what the gift is). Required.
  - **Recipients:** one or more contacts from the same vault. Required. The contact whose page the user is on starts as a recipient.
  - **Occasion:** chosen from the account's occasions in Settings. Optional.
  - **Stage:** chosen from the account's stages in Settings. Optional. A new gift starts in the first stage in the Settings order.
  - **Estimated price:** an amount and a currency. The user chooses from the currencies Monica already offers to the account. Optional.
  - **Link:** a web address that the user pastes in. Optional.
  - **Note:** free text. Optional.
  - **Date given:** a date. Optional.
- **Gifts for more than one person.** One gift shows on the page of every recipient. A change made on one page shows on every recipient's page.
- **Given gifts.** When a gift reaches the final stage, it moves from the open list to the given list on every recipient's page. The given list shows the date given if the user entered one.
- **A vault Gifts page.** It lists every open gift in the vault, grouped by occasion. Users reach it from the vault's main navigation.
- **A vault setting to turn the Gifts page on or off.** It works the same way as the existing on/off settings for other vault pages, such as Tasks and Journal. The page is on by default.

### Out of scope

- We are not building gifts the user **receives**. This version covers gifts the user gives.
- We are not building **reminders, notifications or dashboard widgets** for gifts.
- We are not building **price tracking, shopping links or affiliate deals**. A link is plain text the user pastes in. Monica does not visit the link or show a preview.
- We are not building **totals, budgets or spending reports**.
- We are not building **import** from Monica version 4 or any other tool.
- We are not building an **API** for gifts.
- We are not building **shared wishlists**, where other people see or claim gifts. The strategy says Monica will not become social.
- We are not building **gifts that only one vault member can see**.
- We are not building **photos** on gifts.
- We are not building **choosing a whole group** of contacts as recipients. Users choose individual contacts.
- We are not building **sorting by next birthday**, or any filter or sort on the vault Gifts page other than grouping by occasion.
- We are not adding gifts to the **contact data Monica syncs to phones and other apps**.

### Phasing

- Phase 1: this spec.
- Later, not planned yet:
  - sorting the vault Gifts page by whose birthday is next
  - a dashboard reminder or widget before occasions
  - gifts that only one vault member can see
  - gifts the user receives

### Vertical slice check

If this shipped alone, a user could write down a gift idea on a person's page, track it until it is given, and see all open ideas in one place before an occasion. They cannot do any of this today. This is not an enabler story.

---

## Figma

No designs needed. The Gifts section follows the layout of existing sections on the contact page, such as Tasks and Notes. The vault Gifts page follows the layout of the existing vault Tasks page. A designer can review the screens at the end of the build if wanted.

---

## Acceptance criteria

**AC-1: add a gift**
GIVEN a user with the editor or manager role is on contact Alex's page
WHEN they add a gift with only the name "Pottery class" and save it
THEN "Pottery class" appears in the open list of Alex's Gifts section
AND its stage is the first stage in the account's Settings order
AND Alex is its only recipient.

**AC-2: a gift becomes a given gift**
GIVEN an open gift has recipients Sam and Priya
WHEN an editor changes the gift's stage to the final stage and enters the date given as 3 October 2026
THEN the gift appears in the given list on both Sam's page and Priya's page, with the date 3 October 2026
AND the gift no longer appears in the open list on either page
AND the gift no longer appears on the vault Gifts page.

**AC-3: deleting one recipient**
GIVEN a gift has recipients Sam and Priya
WHEN a user deletes contact Sam
THEN the gift still appears on Priya's page, and Priya is its only recipient.
AND WHEN a user then deletes contact Priya
THEN the gift no longer appears anywhere in the vault.

**AC-4: vault Gifts page (checklist)**
GIVEN a vault contains open gifts and given gifts, with and without an occasion, then all of these are true:

- [ ] A "Gifts" entry appears in the vault's main navigation and opens the vault Gifts page.
- [ ] The page shows every open gift in the vault and no given gifts.
- [ ] The page groups gifts under occasion headings, in the account's Settings order for occasions.
- [ ] Gifts without an occasion appear last, under the heading "No occasion".
- [ ] Each gift shows its name, its recipients and its stage.
- [ ] If the vault has no open gifts, the page shows the message "No open gift ideas yet" and no headings.
- [ ] When a vault manager turns the Gifts page off in vault settings, the navigation entry disappears for every member of that vault. Gifts still appear on contact pages.

**AC-5 (unhappy path): missing required fields**
GIVEN a user with the editor or manager role is adding or editing a gift
WHEN they save with an empty name, or with no recipient
THEN Monica does not save the gift
AND Monica shows a message next to each empty required field
AND Monica keeps every other value the user typed.

**AC-6 (unhappy path): viewers cannot change gifts**
GIVEN a user with the viewer role opens a contact page that has gifts
WHEN the page loads
THEN they see every gift on that contact
AND they see no controls to add, edit or delete a gift
AND if they try to change a gift by another route, Monica refuses the change and the gift stays the same.

---

## Assumptions

- **The final stage in the Settings order means "given".** The defaults end with "Offered", so this works without setup. A user who reorders or adds stages controls which stage is final. This is the riskiest assumption.
- **The deliberate rememberer is the primary user.** No personas file exists to confirm this.
- **Gift tracking will bring users back before occasions.** We have no usage or cancellation figure to prove demand yet. We assume the repeated user requests and the beta page promise are enough reason to build.
- **Vault-wide visibility is acceptable.** Users who share a vault with a recipient will accept that the recipient can see gift ideas. Users who need surprise gifts can keep a separate vault until private gifts exist.
- **Grouping by occasion is enough for the vault Gifts page.** Users do not need other filters or sorts in phase 1.
- **The order inside each occasion group is newest gift first.** Users have not asked for a different order.
- **Choosing individual contacts is enough for shared gifts.** Choosing a whole group is not needed in phase 1.
- **Photos and shared wishlists are not needed in phase 1.** A link or a note covers the photo need, and shared wishlists conflict with the strategy.
- **The Gifts section appears on contact pages without setup.** Existing users do not have to change their contact page layout to see it. See open questions.

---

## Technical notes

- **Use the stages and occasions each account already manages in Settings.** Gifts must keep working after a user renames, reorders, adds or deletes them.
  - If a user deletes a stage that gifts use, those gifts show "No stage" and count as open gifts.
  - If a user deletes an occasion that gifts use, those gifts move to the "No occasion" group.
  - If an account has no stages, every gift is an open gift.
- **Follow the vault roles exactly as other contact details do.** Editors and managers can change gifts. Viewers can only see them. A user can only choose recipients from the same vault.
- **Keep gift data inside the vault.** It must pass the strategy's privacy review gate:
  - Monica sends no gift data to any third party.
  - Monica does not open or preview links.
- **Ship the same feature on the hosted plan and on self-hosted installs.**
- **Translate all new labels and messages** into every language Monica already supports. Show default stage and occasion names in the user's language, as Settings already does.
- **Do not break existing flows.** Contact deletion, the vault's other pages, and the stage and occasion screens in Settings must work as they do today.

---

## Open questions

- 🟡 **Is "final stage means given" clear enough to users?** We are building on this assumption. If users get confused when they reorder stages, a later version could add a "counts as given" choice for each stage in Settings. Owner: PM.
- 🟡 **Do users who changed their contact page layout see the Gifts section automatically?** Working assumption: yes, the Gifts section appears without setup. Engineering confirms at plan review whether this is possible for customised layouts. Owner: engineering lead.
- 🟡 **How do we measure success?** Working assumption: count the share of active hosted vaults with at least one gift, 60 days after launch. Count only on the hosted plan, using totals and no gift content, to keep the privacy promise. Owner: PM.
- 🟢 **Will we support choosing a whole group as recipients?** Not in phase 1. Users choose individual contacts. Owner: PM.

---

## Before you build

**Decisions made:**

- Gifts are gifts the user gives. Gifts the user receives are out of scope.
- Gifts use the stages and occasions from Settings. The final stage in Settings order means "given".
- One gift can have several recipients. It shows on each recipient's page. Deleting one recipient keeps the gift for the others.
- Gifts follow the vault's normal visibility and roles. There are no gifts that only one user can see.
- The vault Gifts page shows open gifts only, grouped by occasion. Each vault can turn it on or off. It is on by default.
- There are no reminders, notifications, budgets, imports, API, photos or shared wishlists in phase 1.

**Riskiest assumption:**
Users will understand that the last stage in their Settings order marks a gift as given, even after they rename or reorder stages.

**Next step:**
Ready for ticket generation. Engineering confirms the contact page layout question during plan review.
