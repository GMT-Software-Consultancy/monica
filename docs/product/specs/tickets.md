# Gift tracking — Tickets
Generated: 15 October 2026
Spec: output/spec.md
Tracker: none (no tracker configured in `.claude/skill-config.md` — tickets are not posted)
Initiative: Gift tracking

---

## Run notes

- **Initiative:** the spec has no `Initiative:` field. This run is non-interactive, so all tickets use the spec title, "Gift tracking", as the initiative. The PM should confirm or replace it before posting.
- **Personas:** `docs/product/personas.md` does not exist. The job-to-be-done statements use the user groups from the spec and `docs/product/strategy.md`.
- **Open questions:** the spec has three 🟡 questions. Each one has a working assumption, so none blocks a ticket. The tickets follow those assumptions:
  - The final gift state means "given".
  - The hosted plan can count vaults with gifts without reading gift content.
  - Moving a gift to the final gift state does not fill in the date given.
- **In-flight work:** `docs/iterations/current.md` does not exist, so no conflicts were checked.

## Terms used in these tickets

These match the spec's "Terms used in this spec" section.

- **Vault:** a shared workspace in Monica. It holds contacts and can have several members.
- **Vault member roles:** a *viewer* can only read. An *editor* can add and change things. A *manager* can also change vault settings.
- **Gift:** something the user plans to give, or has given, to one or more contacts.
- **Recipient:** a contact that a gift is for.
- **Gift state:** the step a gift has reached, for example "Idea" or "Bought". Each account manages its list in Settings → Personalize → Gift states.
- **Final gift state:** the gift state in the last position of the account's list. With the defaults, this is "Offered".
- **Given gift:** a gift in the final gift state.
- **Open gift:** any gift that is not a given gift. This includes gifts with no gift state.
- **Gift occasion:** the reason for a gift, for example "Birthday". Each account manages its list in Settings → Personalize → Gift occasions.
- **Contact page section:** a block on a contact's page, for example "Notes".
- **Vault tab:** a page in a vault's top navigation, for example "Tasks".

## Breakdown

| # | Title | Type | Depends on |
| - | ----- | ---- | ---------- |
| 1 | Record a gift idea from a contact's page | Feature | None |
| 2 | Edit and delete a gift | Feature | 1 |
| 3 | Show given gifts as a contact's gift history | Feature | 2 |
| 4 | Add a rough price and a link to a gift | Feature | 1 |
| 5 | Give one gift to several recipients | Feature | 2 |
| 6 | List all open gifts in a Gifts vault tab | Feature | 1 |
| 7 | Let vault managers show or hide the Gifts vault tab | Feature | 6 |
| 8 | Keep gifts when someone deletes a gift state or gift occasion | Feature | 1 |
| 9 | Count how many active hosted vaults have gifts | Chore | 1 |

---

## 1. Record a gift idea from a contact's page

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** None

### Summary
Editors can save a gift idea from a contact's page and see it in that contact's new Gifts section.

### Job to be done
When a gift idea comes to mind for someone, a deliberate rememberer needs to save it on that person's page at once, so they can find it again before the next occasion.

### Navigation context
**Entry point(s):** Vault → Contacts → a contact's page → Gifts section → "Add a gift".
**Exit point(s):** The user stays on the contact's page. The new gift appears under open gifts in the Gifts section.
**Screen/component:** The Gifts contact page section and the add gift form.

### Context
Today users can only write gift ideas in a free-text note, which has no state. Gift management is one of the two biggest gaps compared with the previous Monica. The account already has gift states and gift occasions in Settings, but nothing uses them yet. This ticket gives users the first thing they can do with gifts.

The add gift form has these fields in this ticket:
- **Name** — required.
- **Gift occasion** — optional. The choices are the account's gift occasions.
- **Gift state** — optional. The choices are the account's gift states. The form selects the first gift state in the list by default.
- **Note** — optional free text.

The contact whose page the user is on is the gift's recipient. Ticket 5 adds more recipients. Ticket 4 adds price and link. Ticket 3 adds date given.

The Gifts section appears on the default contact page layout for every existing account, with no setup. Users can also add it to other layouts in Settings, like any other section.

### Designs
Add gift form: designs to be created. The Gifts contact page section follows the layout of existing sections, so it needs no new design.

### Acceptance criteria

**Scenario: Editor saves a gift with only a name**
Given an editor is on the page of contact "Sam"
When they add a gift with only the name "Cookbook" and save it
Then "Cookbook" appears under open gifts in Sam's Gifts section
And Sam is its only recipient
And its gift state is the first gift state in the account's list.

**Scenario: Editor saves a gift with an occasion, a state and a note**
Given an editor is on the add gift form on Sam's page
When they enter the name "Scarf", choose the occasion "Christmas", choose the state "Found", write a note, and save
Then Sam's Gifts section shows "Scarf" under open gifts with the occasion "Christmas" and the state "Found"
And the note is visible on the gift.

**Scenario: Editor tries to save a gift with no name**
Given an editor is on the add gift form on Sam's page
When they leave the name empty and select "Save"
Then Monica does not save the gift
And Monica shows a message next to the name field that asks for a name.

**Scenario: Viewer sees gifts but cannot add one**
Given a vault member with the viewer role is on Sam's page
When they look at Sam's Gifts section
Then they see every gift for Sam
And they see no control to add a gift.

**Scenario: Account has deleted every gift state**
Given the account has no gift states in Settings → Personalize → Gift states
When an editor adds a gift named "Book" on Sam's page and saves it
Then "Book" appears under open gifts in Sam's Gifts section
And the gift shows no gift state.

**Scenario: Existing account sees the Gifts section with no setup**
Given an account created before this release has a contact that uses the default contact page layout
When an editor opens that contact's page
Then the Gifts section appears
And it shows a message that explains how to add the first gift.

**Scenario: Gifts section is available for other layouts**
Given a user is editing a contact page layout in Settings
When they look at the list of sections they can add
Then "Gifts" is in the list.

### Out of scope
- Editing and deleting gifts (ticket 2).
- Given gifts and the date given (ticket 3).
- Rough price and link (ticket 4).
- More than one recipient (ticket 5).
- The Gifts vault tab (ticket 6).
- Gifts the user receives, private gifts, photos, reminders and notifications.

---

## 2. Edit and delete a gift

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 1

### Summary
Editors can change any field of a saved gift, and can delete a gift after they confirm.

### Job to be done
When a gift idea moves forward or stops making sense, a deliberate rememberer needs to update or remove it, so their gift list stays accurate.

### Navigation context
**Entry point(s):** A contact's page → Gifts section → a gift → "Edit" or "Delete".
**Exit point(s):** After saving, the user stays on the contact's page and sees the updated gift. After deleting, the user stays on the contact's page and the gift is gone.
**Screen/component:** The Gifts contact page section, the edit gift form, and the delete confirmation.

### Context
Users move a gift through its gift states over time, for example from "Idea" to "Bought". They also drop ideas they no longer want. The spec decides that users drop an idea by deleting it, not by an "abandoned" state. Deleting a gift cannot be undone, so Monica asks the user to confirm first.

### Designs
Edit gift form: designs to be created (shared with the add gift form). The delete confirmation follows the existing pattern for deleting items on a contact's page.

### Acceptance criteria

**Scenario: Editor changes a gift's state**
Given an editor is on Sam's page and the gift "Cookbook" has the state "Idea"
When they edit the gift, choose the state "Bought", and save
Then Sam's Gifts section shows "Cookbook" with the state "Bought".

**Scenario: Editor deletes a gift after confirming**
Given an editor is on Sam's page and Sam has the gift "Cookbook"
When they choose "Delete" on the gift and confirm
Then "Cookbook" no longer appears in Sam's Gifts section.

**Scenario: Editor clears the name while editing**
Given an editor is editing the gift "Cookbook" on Sam's page
When they clear the name and select "Save"
Then Monica does not save the change
And Monica shows a message next to the name field that asks for a name
And the gift still shows the name "Cookbook" after the user cancels.

**Scenario: Editor cancels the delete confirmation**
Given an editor has chosen "Delete" on the gift "Cookbook" on Sam's page
When they cancel the confirmation
Then "Cookbook" still appears in Sam's Gifts section with no changes.

**Scenario: Viewer cannot edit or delete gifts**
Given a vault member with the viewer role is on Sam's page
When they look at a gift in Sam's Gifts section
Then they see no control to edit or delete the gift.

### Out of scope
- Adding or removing recipients (ticket 5).
- Undo after delete, or an archive of deleted gifts.
- An "abandoned" gift state. Users can add one in Settings if they want.

---

## 3. Show given gifts as a contact's gift history

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2

### Summary
A contact's Gifts section lists given gifts apart from open gifts, with the date each one was given.

### Job to be done
When an occasion comes round again, a deliberate rememberer needs to see what they gave this person before, so they do not repeat a gift and can build on past ones.

### Navigation context
**Entry point(s):** A contact's page → Gifts section. Users reach the given list by moving a gift to the final gift state from the add or edit gift form.
**Exit point(s):** The user stays on the contact's page. The gift moves from open gifts to given gifts.
**Screen/component:** The Gifts contact page section (open gifts and given gifts) and the "Date given" field on the add and edit gift forms.

### Context
Users cannot remember what they gave someone last year. A given gift is a gift in the final gift state, the last state in the account's list. The add and edit gift forms gain an optional "Date given" field. Moving a gift to the final gift state does not fill in the date given. The user enters it if they want it.

The final gift state can change when a user reorders the list in Settings. Gifts then move between open and given to match the new order. The spec accepts this. The riskiest assumption in the spec is that users treat the last state as "given".

### Designs
No new designs needed. The given gifts list follows the layout of the open gifts list in the same section. The "Date given" field is part of the add/edit gift form designs.

### Acceptance criteria

**Scenario: Editor marks a gift as given with a date**
Given an editor is on Sam's page and Sam has the open gift "Cookbook"
When they edit the gift, choose the final gift state, set the date given to 12 March 2026, and save
Then Sam's Gifts section shows "Cookbook" under given gifts with the date 12 March 2026
And "Cookbook" no longer appears under open gifts.

**Scenario: Editor enters a date that does not exist**
Given an editor is editing the gift "Cookbook" on Sam's page
When they enter 31 February 2026 as the date given and select "Save"
Then Monica does not save the change
And Monica shows a message next to the date given field.

**Scenario: Given gift with no date given**
Given an editor is editing the open gift "Scarf" on Sam's page
When they choose the final gift state, leave the date given empty, and save
Then Sam's Gifts section shows "Scarf" under given gifts with no date
And the date given field stays empty when they open the gift again.

**Scenario: Reordering gift states changes which gifts are given**
Given the account's gift states end with "Bought" then "Offered", and Sam has a gift in "Bought" and a gift in "Offered"
When a user moves "Bought" to the last position in Settings → Personalize → Gift states and then opens Sam's page
Then Sam's Gifts section shows the "Bought" gift under given gifts
And it shows the "Offered" gift under open gifts.

**Scenario: Editor moves a given gift back to an earlier state**
Given an editor is on Sam's page and "Cookbook" is under given gifts
When they edit the gift, choose the first gift state, and save
Then "Cookbook" appears under open gifts again.

### Out of scope
- Suggesting today as the date given (open question; working assumption is no).
- Gifts the user receives.
- Reminders based on past gifts.

---

## 4. Add a rough price and a link to a gift

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 1

### Summary
Editors can record a rough price and a web link on a gift, and Monica never visits the link on its own.

### Job to be done
When a deliberate rememberer finds a possible gift online or in a shop, they need to note what it costs and where it is, so they can buy it later without searching again.

### Navigation context
**Entry point(s):** A contact's page → Gifts section → "Add a gift" or "Edit" on a gift.
**Exit point(s):** The user stays on the contact's page. The gift shows its price and link.
**Screen/component:** The "Rough price" and "Link" fields on the add and edit gift forms, and the gift as shown in the Gifts section.

### Context
Users often find gift ideas online. The price is a number plus a currency. The currency choices are the currencies the account has enabled in Settings. The link must pass the strategy's privacy review gate. Monica must not fetch, preview or check a pasted link, and no gift data goes to a third party. Opening the link is always the user's own action.

### Designs
Designs to be created, as part of the add/edit gift form.

### Acceptance criteria

**Scenario: Editor adds a price and a link**
Given an editor is on the add gift form on Sam's page
When they enter the name "Headphones", the price 80 in euros, paste the link "https://example.com/headphones", and save
Then the gift in Sam's Gifts section shows the price 80 in euros
And it shows the link "https://example.com/headphones".

**Scenario: Editor enters a price that is not a number**
Given an editor is on the add gift form on Sam's page
When they enter "about eighty" as the price and select "Save"
Then Monica does not save the gift
And Monica shows a message next to the price field that asks for a number.

**Scenario: Only enabled currencies are offered**
Given the account has enabled only euros and British pounds in Settings
When an editor opens the currency choices on the add gift form
Then they see only euros and British pounds.

**Scenario: Monica shows the link exactly as pasted**
Given an editor has saved a gift with the link "https://example.com/headphones"
When any vault member views the gift in Sam's Gifts section
Then they see the link text exactly as pasted
And they see no preview, page title or image taken from that address.

**Scenario: User opens the link**
Given a vault member is on Sam's page and a gift shows a link
When they select the link
Then the linked page opens in a new browser tab.

### Out of scope
- Link previews, price tracking, shops, buying links and affiliate deals.
- Budgets and spending totals.
- Converting prices between currencies.

---

## 5. Give one gift to several recipients

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2

### Summary
Editors can give one gift to several contacts in the same vault, and the gift shows on every recipient's page.

### Job to be done
When a gift is for more than one person, such as a wedding present for a couple, a deliberate rememberer needs to record it once for all of them, so it shows on each person's page.

### Navigation context
**Entry point(s):** A contact's page → Gifts section → "Add a gift" or "Edit" on a gift → the recipients field.
**Exit point(s):** The user stays on the contact's page. The gift appears in the Gifts section of every recipient.
**Screen/component:** The recipients field on the add and edit gift forms, and the Gifts section on each recipient's page.

### Context
Today a note on one contact does not show on another contact's page. A gift is one shared record. A change on any recipient's page changes it for every recipient. Recipients must be contacts in the same vault as the gift. A gift must keep at least one recipient. If someone deletes a recipient from the vault, the gift stays with the others. If they delete the last remaining recipient, the gift is deleted with that contact.

### Designs
Designs to be created, as part of the add/edit gift form (choosing several recipients).

### Acceptance criteria

**Scenario: Editor adds a second recipient**
Given an editor is on the add gift form on Ana's page, with Ana already filled in as a recipient
When they enter the name "Wedding present", add Ben as a recipient, and save
Then "Wedding present" appears in Ana's Gifts section
And it appears in Ben's Gifts section
And both pages list Ana and Ben as its recipients.

**Scenario: A change on one page shows on every recipient's page**
Given the gift "Wedding present" has the recipients Ana and Ben
When an editor changes its gift state to "Bought" on Ana's page
Then Ben's page shows the same gift with the state "Bought".

**Scenario: Editor removes every recipient**
Given an editor is editing the gift "Wedding present"
When they remove every recipient and select "Save"
Then Monica does not save the change
And Monica shows a message next to the recipients field that asks for at least one recipient.

**Scenario: Editor removes one recipient**
Given an editor is editing the gift "Wedding present" on Ana's page, with recipients Ana and Ben
When they remove Ben and save
Then the gift no longer appears in Ben's Gifts section
And it still appears in Ana's Gifts section with Ana as its only recipient.

**Scenario: Only contacts in the same vault can be recipients**
Given the user is a member of two vaults, and contact "Kim" is only in the other vault
When an editor searches for "Kim" in the recipients field
Then Monica does not offer Kim as a recipient.

**Scenario: Someone deletes one recipient from the vault**
Given the gift "Wedding present" has the recipients Ana and Ben
When an editor deletes the contact Ana from the vault and then opens Ben's page
Then Ben's Gifts section still shows "Wedding present"
And Ben is its only recipient.

**Scenario: Someone deletes the last recipient from the vault**
Given the gift "Cookbook" has Sam as its only recipient
When an editor deletes the contact Sam from the vault and then opens the Gifts vault tab
Then "Cookbook" does not appear.

### Out of scope
- Recipients from other vaults.
- Separate copies of a gift for each recipient.
- Private gifts that only one vault member can see.

---

## 6. List all open gifts in a Gifts vault tab

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 1

### Summary
Every vault member can open a Gifts vault tab that lists all open gifts in the vault, grouped by gift occasion.

### Job to be done
When a birthday or holiday is coming, a privacy-motivated professional with a large personal network needs to see every open gift idea in one place, so they can plan and buy in time.

### Navigation context
**Entry point(s):** A vault's top navigation → "Gifts".
**Exit point(s):** The user stays on the Gifts vault tab. This ticket adds no actions that leave the tab.
**Screen/component:** The Gifts vault tab, including its empty state.

### Context
No view collects open gift ideas before an occasion today. This tab gives users a reason to come back before every birthday and holiday. That supports the strategy's priority of keeping users active. The tab groups open gifts by gift occasion. Gifts with no occasion appear in a "No occasion" group. Given gifts do not appear. The tab is visible by default in every vault, including existing vaults. Users add gifts from a contact's page, not from this tab.

### Designs
Designs to be created: the Gifts vault tab and its empty state.

### Acceptance criteria

**Scenario: Member sees open gifts grouped by occasion**
Given a vault has open gifts with the occasions "Birthday" and "Christmas", one open gift with no occasion, and one given gift
When a vault member selects "Gifts" in the vault's top navigation
Then the tab shows three groups: "Birthday", "Christmas" and "No occasion"
And each gift shows its name, its recipients and its gift state
And the given gift does not appear.

**Scenario: Vault has no open gifts**
Given a vault has no gifts, or only given gifts
When a vault member opens the Gifts vault tab
Then the tab shows an empty-state message
And the message explains how to add a gift from a contact's page.

**Scenario: Viewer cannot change gifts from the tab**
Given a vault member with the viewer role is on the Gifts vault tab
When they look at the list of gifts
Then they see every open gift
And they see no control to add, edit or delete a gift.

**Scenario: Existing vault shows the tab with no setup**
Given a vault was created before this release
When a vault member opens the vault
Then "Gifts" appears in the vault's top navigation.

**Scenario: Gift with no gift state**
Given the account has deleted every gift state, and the vault has a gift with no gift state
When a vault member opens the Gifts vault tab
Then the gift appears in the list as an open gift
And it shows no gift state.

### Out of scope
- Filtering by contact. A contact's Gifts section already shows this.
- Bringing forward contacts whose birthday is next.
- Adding, editing or deleting gifts from the tab.
- Budgets and spending totals.
- Showing gifts in calendar or contact sync to other apps.

---

## 7. Let vault managers show or hide the Gifts vault tab

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 6

### Summary
Vault managers can show or hide the Gifts vault tab in "Tabs visibility", like the other vault tabs.

### Job to be done
When a vault does not use gifts, a vault manager needs to hide the Gifts vault tab, so the vault's navigation shows only what its members use.

### Navigation context
**Entry point(s):** A vault → Vault settings → "Tabs visibility".
**Exit point(s):** The user stays on the vault settings page. The vault's top navigation shows or hides "Gifts" for every member.
**Screen/component:** The "Tabs visibility" part of vault settings.

### Context
Vault managers already choose which tabs their vault shows. Gifts should behave like the other tabs, so users do not meet a new rule. The tab is visible by default. Hiding the tab does not hide gifts on contact pages.

### Designs
No designs needed. "Gifts" follows the existing entries in "Tabs visibility".

### Acceptance criteria

**Scenario: Manager hides the Gifts vault tab**
Given a vault manager is on the "Tabs visibility" part of vault settings, and the Gifts tab is visible
When they turn off "Gifts"
Then "Gifts" disappears from the vault's top navigation for every vault member.

**Scenario: Manager shows the Gifts vault tab again**
Given a vault manager is on the "Tabs visibility" part of vault settings, and the Gifts tab is hidden
When they turn on "Gifts"
Then "Gifts" appears again in the vault's top navigation for every vault member.

**Scenario: Member opens a saved link to a hidden Gifts tab**
Given a vault manager has hidden the Gifts vault tab
When a vault member opens a saved link to the Gifts vault tab
Then they get the same result as for any other hidden vault tab.

**Scenario: Hiding the tab keeps gifts on contact pages**
Given a vault manager has hidden the Gifts vault tab
When a vault member opens Sam's page
Then Sam's Gifts section still shows Sam's gifts.

**Scenario: Existing vault starts with the tab visible**
Given a vault was created before this release and its manager has never changed "Tabs visibility"
When the manager opens "Tabs visibility"
Then "Gifts" is turned on.

### Out of scope
- Hiding the Gifts contact page section. Users do this through contact page layouts in Settings.
- Hiding the tab for some members only.

---

## 8. Keep gifts when someone deletes a gift state or gift occasion

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 1

### Summary
Deleting a gift state or gift occasion in Settings never deletes gifts. Gifts that used it lose only that value.

### Job to be done
When an account owner tidies their gift states or gift occasions, a deliberate rememberer needs every gift to survive, so they never lose gift ideas or history by accident.

### Navigation context
**Entry point(s):** Settings → Personalize → Gift states, or Settings → Personalize → Gift occasions → delete an item.
**Exit point(s):** The user stays on the Settings page. Affected gifts show no gift state or no occasion on contact pages and on the Gifts vault tab.
**Screen/component:** The Gift states and Gift occasions settings pages, the Gifts contact page section, and the Gifts vault tab.

### Context
Users can already rename, reorder, add and delete gift states and gift occasions. Before this release, no gifts used them, so deleting one had no effect on gifts. Now gifts depend on these lists. Many existing accounts have already changed their lists. A gift that loses its gift state counts as open. A gift that loses its occasion moves to the "No occasion" group.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: Deleting an occasion keeps its gifts**
Given Sam has the open gift "Scarf" with the occasion "Christmas"
When a user deletes "Christmas" in Settings → Personalize → Gift occasions and then opens the Gifts vault tab
Then "Scarf" appears in the "No occasion" group
And its name, recipients, gift state and note are unchanged.

**Scenario: Deleting a gift state keeps its gifts**
Given Sam has the gift "Cookbook" with the state "Found"
When a user deletes "Found" in Settings → Personalize → Gift states and then opens Sam's page
Then Sam's Gifts section shows "Cookbook" under open gifts with no gift state
And every other field of the gift is unchanged.

**Scenario: Deleting the final gift state**
Given the account's gift states end with "Bought" then "Offered", and Sam has a given gift in "Offered"
When a user deletes "Offered" in Settings → Personalize → Gift states and then opens Sam's page
Then that gift shows under open gifts with no gift state
And gifts in "Bought" now show under given gifts.

**Scenario: Renaming an occasion updates its gifts**
Given Sam has a gift with the occasion "Birthday"
When a user renames "Birthday" to "Birthday party" in Settings → Personalize → Gift occasions and then opens Sam's page
Then the gift shows the occasion "Birthday party".

### Out of scope
- Moving gifts to another gift state or occasion when one is deleted.
- Warning the user how many gifts use a gift state or occasion before they delete it.

---

## 9. Count how many active hosted vaults have gifts

**Type:** Chore
**Initiative:** Gift tracking
**Depends on:** 1

### Summary
Maintainers can see how many active hosted vaults have at least one gift, without anyone reading gift content.

### Why now
The spec's success measure is that at least 15% of active hosted vaults have one or more gifts within 60 days of release. Maintainers cannot check this measure unless the count exists from release day. The count must pass the strategy's privacy review gate, so it can only be a number per vault. Whether hosted analytics can do this is an open 🟡 question. The working assumption is yes.

### Definition of done
- [ ] Maintainers can see the number of active hosted vaults that have at least one gift.
- [ ] Maintainers can see that number as a share of all active hosted vaults.
- [ ] The count shows no gift names, notes, links, prices, recipients or other gift content.
- [ ] The count covers the hosted plan only. Self-hosted instances report nothing new.
- [ ] The count is available from release day, so the 60-day window can be measured.
- [ ] If hosted analytics cannot produce the count without reading gift content, the PM and maintainers record that decision and agree another success measure.

---

## Report

**Tracker:** none — no tracker is configured in `.claude/skill-config.md`, so no tickets were posted and no tracker IDs exist.

**Initiative:** Gift tracking — no backend grouping ID (not posted).

**Tickets written (in dependency order):**
1. Record a gift idea from a contact's page
2. Edit and delete a gift
3. Show given gifts as a contact's gift history
4. Add a rough price and a link to a gift
5. Give one gift to several recipients
6. List all open gifts in a Gifts vault tab
7. Let vault managers show or hide the Gifts vault tab
8. Keep gifts when someone deletes a gift state or gift occasion
9. Count how many active hosted vaults have gifts

**File saved:** `output/tickets.md`. The spec's `Status:` and `Tickets:` fields were not changed, because no tickets were posted.

**Assumptions made during decomposition:**
- Used "Gift tracking" as the initiative, because the spec has no `Initiative:` field.
- Used the spec's user groups for job-to-be-done statements, because `docs/product/personas.md` does not exist.
- Did not add a separate setup or migration Chore. "Works for existing accounts and vaults with no setup" is a user-visible outcome. Scenarios in tickets 1, 6 and 7 test it directly. Engineering may still choose to do setup work inside those tickets. The architecture docs list an older gifts store from 2022. Engineering decides whether to reuse it.
- Split the gift fields across tickets. Ticket 1 covers name, occasion, state and note. Ticket 3 adds date given. Ticket 4 adds price and link. Ticket 5 adds more recipients. Each ticket ships alone and adds a visible change.
- Made ticket 8 (deleting gift states and occasions) a Feature, not a Chore. Users see its result on contact pages and the Gifts vault tab.
- Added ticket 9 (success measure count) as a Chore. The spec states the success measure but lists no work to measure it.
- Added two validation scenarios that the spec implies but does not state: a price must be a number (ticket 4), and a date given must be a real date (ticket 3).
- Assumed links open in a new browser tab (ticket 4). Design review can change this.
- Assumed a hidden Gifts tab behaves like any other hidden vault tab when someone opens a saved link to it (ticket 7).

**Recommended next step:** Hold design review for the add/edit gift form and the Gifts vault tab. Tickets 1, 2, 4, 5 and 6 need those designs. Tickets 3, 7 and 8 need no new designs. The PM should also confirm the initiative name before anyone posts these tickets to a tracker.
