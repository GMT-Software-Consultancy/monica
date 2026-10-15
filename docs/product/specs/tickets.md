# Gift tracking — Tickets
Generated: 15 October 2026
Spec: output/spec.md
Tracker: none (no tracker in this run, so no tracker IDs)
Initiative: Gift tracking (phase 1)

## Breakdown

| # | Title | Type | Depends on |
|---|-------|------|------------|
| 1 | Add a gift idea from a contact's page | Feature | None |
| 2 | Edit and delete a gift from a contact's page | Feature | 1 |
| 3 | Show given gifts in a separate list on the contact's page | Feature | 1, 2 |
| 4 | Give one gift to more than one contact | Feature | 1, 2, 3 |
| 5 | See every open gift in the vault on the vault Gifts page | Feature | 1, 3 |
| 6 | Let vault managers turn the vault Gifts page on or off | Feature | 5 |

## Terms used in these tickets

- **Vault:** a separate workspace in Monica that holds contacts. Several users can share one vault.
- **Vault member roles:** a **manager** can do everything, including change vault settings. An **editor** can add and change contacts. A **viewer** can see contacts but not change them.
- **Gift:** something the user plans to give, or has given, to one or more contacts.
- **Recipient:** a contact that a gift is for. A gift has at least one recipient.
- **Stage:** where a gift is in its life, for example "Idea" or "Bought". Each account manages its stages in Settings. The defaults, in order, are Idea, Searched, Found, Bought, Offered.
- **Final stage:** the stage that is last in the Settings order. With the default stages, it is "Offered".
- **Given gift:** a gift in the final stage.
- **Open gift:** any gift that is not a given gift.
- **Occasion:** the reason for a gift, for example "Birthday". Each account manages its occasions in Settings. The defaults are Birthday, Anniversary, Christmas, Just because, Wedding.
- **Contact page layout:** the account setting that decides which sections appear on a contact's page. Some users have customised it.

---

## 1. Add a gift idea from a contact's page

**Type:** Feature
**Initiative:** Gift tracking (phase 1)
**Depends on:** None

### Summary
Editors and managers can add a gift to a contact from a new Gifts section on that contact's page.

### Job to be done
When a gift idea comes to them at a random moment, the deliberate rememberer needs to write it down on that person's page in a few seconds, so they can find it again before the next occasion.

### Navigation context
**Entry point(s):** Vault → Contacts → a contact's page → Gifts section → "Add a gift".
**Exit point(s):** The user stays on the contact's page. The new gift appears in the open list of the Gifts section.
**Screen/component:** The Gifts section of the contact page, and the add-gift form that opens from it.

### Context
Users write gift ideas in a contact's notes, in another app, or nowhere. Monica's beta sign-up page promises gift management, and we have not delivered it yet. Each account already has stages and occasions in Settings, but nothing uses them. This ticket delivers the first gift behaviour. Later tickets add editing, the given list, more recipients and the vault Gifts page.

### Designs
No designs needed. Follow the layout of existing contact page sections, such as Tasks and Notes.

### Acceptance criteria

**Scenario: Editor adds a gift with only a name**
Given a user with the editor or manager role is on contact Alex's page
When they add a gift with only the name "Pottery class" and save it
Then "Pottery class" appears in the open list of Alex's Gifts section
And its stage is the first stage in the account's Settings order
And Alex is its only recipient.

**Scenario: Editor adds a gift with every optional detail**
Given an editor is on contact Alex's page with the add-gift form open
When they save a gift with name "Pottery class", occasion "Birthday", stage "Found", estimated price 80 EUR, a link, a note and a date given of 3 October 2026
Then "Pottery class" appears in the open list of Alex's Gifts section
And it shows each of the values they entered.

**Scenario: Saving a gift with an empty name**
Given an editor is on contact Alex's page with the add-gift form open
And they have chosen the occasion "Birthday" and typed a note
When they save the form with an empty name
Then they see a message next to the Name field
And no new gift appears in Alex's Gifts section
And the form still shows the occasion and note they entered.

**Scenario: Viewer sees gifts but cannot add one**
Given a user with the viewer role opens contact Alex's page, and Alex has gifts
When the page loads
Then they see every gift in Alex's Gifts section
And they see no control to add a gift.

**Scenario: Viewer tries to add a gift by another route**
Given a user with the viewer role is signed in to the vault that contains Alex
When they try to add a gift to Alex by any route other than the contact page, for example by replaying a request
Then Monica refuses the change and shows a permission error
And no new gift appears in Alex's Gifts section.

**Scenario: Currency choice offers only the account's currencies**
Given an editor is on contact Alex's page with the add-gift form open
When they open the currency choice for the estimated price
Then they see only the currencies that Monica offers to their account.

**Scenario: Link shows as plain text**
Given contact Alex's Gifts section shows a gift with a link
When the page loads
Then the link shows exactly as the user pasted it
And Monica shows no preview, title or image for the link.

**Scenario: Account has no stages**
Given the user's account has no stages in Settings, and an editor is on contact Alex's page
When they save a gift with only the name "Pottery class"
Then "Pottery class" appears in the open list of Alex's Gifts section with the stage "No stage".

**Scenario: Customised contact page layout**
Given a user customised their contact page layout before this release
When they open contact Alex's page
Then they see the Gifts section without changing any setting.

**Scenario: Labels show in the user's language**
Given a user has chosen French as their language and is on contact Alex's page
When they open the add-gift form
Then every label and message in the Gifts section and the form shows in French
And the default stage and occasion names show in French, as they do in Settings.

### Out of scope
- Editing and deleting gifts (ticket 2).
- A separate list for given gifts (ticket 3).
- More than one recipient, and choosing recipients (ticket 4).
- The vault Gifts page (ticket 5).
- Gifts the user receives, reminders, notifications, photos, totals, budgets, imports and an API.
- Previews, price tracking or any visit to the gift link.
- Gifts in the contact data that Monica syncs to phones and other apps.

---

## 2. Edit and delete a gift from a contact's page

**Type:** Feature
**Initiative:** Gift tracking (phase 1)
**Depends on:** 1

### Summary
Editors and managers can change any field of a gift, or delete the gift, from the contact's page.

### Job to be done
When a gift idea moves forward or changes, the deliberate rememberer needs to update it where they wrote it, so the gift record stays true until the occasion.

### Navigation context
**Entry point(s):** Vault → Contacts → a contact's page → Gifts section → a gift's edit or delete control.
**Exit point(s):** The user stays on the contact's page. The Gifts section shows the changed gift, or no longer shows the deleted gift.
**Screen/component:** The Gifts section of the contact page, and the edit-gift form.

### Context
A gift idea changes as the user searches for it, buys it and gives it. Without editing, users would have to delete and re-add a gift to record progress. Edits must keep working after a user renames, reorders, adds or deletes stages and occasions in Settings.

### Designs
No designs needed. Follow the edit and delete patterns of existing contact page sections, such as Tasks and Notes.

### Acceptance criteria

**Scenario: Editor changes a gift's stage**
Given an editor is editing the gift "Pottery class" on contact Alex's page, and its stage is "Idea"
When they save the form with the stage set to "Bought"
Then "Pottery class" shows the stage "Bought" in Alex's Gifts section.

**Scenario: Editor deletes a gift**
Given an editor is on contact Alex's page, and Alex has the gift "Pottery class"
When they delete "Pottery class"
Then "Pottery class" no longer appears in Alex's Gifts section.

**Scenario: Saving an edit with an empty name**
Given an editor is editing the gift "Pottery class" on contact Alex's page
And they have changed the note
When they save the form with an empty name
Then they see a message next to the Name field
And Alex's Gifts section still shows the gift as it was before the edit
And the form still shows the new note they typed.

**Scenario: Viewer cannot edit or delete gifts**
Given a user with the viewer role opens contact Alex's page, and Alex has gifts
When the page loads
Then they see no control to edit or delete a gift.

**Scenario: Viewer tries to change a gift by another route**
Given a user with the viewer role is signed in to the vault that contains Alex
When they try to edit or delete Alex's gift "Pottery class" by any route other than the contact page
Then Monica refuses the change and shows a permission error
And "Pottery class" stays the same in Alex's Gifts section.

**Scenario: Stage renamed in Settings**
Given a manager renamed the stage "Found" to "Located" in Settings, and Alex's gift "Pottery class" was in "Found"
When an editor opens contact Alex's page
Then "Pottery class" shows the stage "Located".

**Scenario: Stage deleted in Settings**
Given a manager deleted the stage "Found" in Settings, and Alex's gift "Pottery class" was in "Found"
When an editor opens contact Alex's page
Then "Pottery class" shows the stage "No stage" and stays in the open list
And the edit form lets them choose one of the remaining stages.

**Scenario: Occasion deleted in Settings**
Given a manager deleted the occasion "Wedding" in Settings, and Alex's gift "Pottery class" had the occasion "Wedding"
When an editor opens contact Alex's page
Then "Pottery class" shows no occasion
And the edit form lets them choose one of the remaining occasions.

### Out of scope
- Moving a gift to the given list when it reaches the final stage (ticket 3).
- Changing recipients (ticket 4).
- Editing or deleting gifts from the vault Gifts page.
- Undoing a delete, or a history of changes to a gift.

---

## 3. Show given gifts in a separate list on the contact's page

**Type:** Feature
**Initiative:** Gift tracking (phase 1)
**Depends on:** 1, 2

### Summary
The Gifts section shows given gifts in a given list, separate from open gifts.

### Job to be done
When an occasion comes round again, the deliberate rememberer needs to see what they already gave that person, so they do not give the same thing twice.

### Navigation context
**Entry point(s):** Vault → Contacts → a contact's page → Gifts section.
**Exit point(s):** The user stays on the contact's page. A gift that reaches the final stage moves from the open list to the given list.
**Screen/component:** The open list and the given list in the Gifts section of the contact page.

### Context
Notes cannot show whether the user bought or gave a gift. Monica treats a gift as given when it is in the final stage, which is the last stage in the account's Settings order. With the default stages, the final stage is "Offered", so this works without setup. The PM rates this the riskiest assumption in the spec, because users can reorder and rename stages.

### Designs
No designs needed. The given list follows the layout of the open list.

### Acceptance criteria

**Scenario: Gift reaches the final stage with a date given**
Given an editor is editing the gift "Pottery class" on contact Alex's page, and the gift is in the open list
When they save the form with the final stage and the date given 3 October 2026
Then "Pottery class" appears in the given list with the date 3 October 2026
And "Pottery class" no longer appears in the open list.

**Scenario: Gift reaches the final stage without a date given**
Given an editor is editing the gift "Pottery class" on contact Alex's page, and the gift is in the open list
When they save the form with the final stage and an empty date given
Then "Pottery class" appears in the given list with no date.

**Scenario: Viewer sees both lists but cannot move a gift**
Given a user with the viewer role opens contact Alex's page, and Alex has open gifts and given gifts
When the page loads
Then they see the open list and the given list
And they see no control to change a gift's stage.

**Scenario: Gift moves back from the final stage**
Given an editor is editing the gift "Pottery class" on contact Alex's page, and the gift is in the given list
When they save the form with the stage set to "Bought"
Then "Pottery class" appears in the open list
And it no longer appears in the given list.

**Scenario: A manager reorders stages in Settings**
Given a manager moved the stage "Bought" to the end of the stage order in Settings
And Alex has one gift in "Bought" and one gift in "Offered"
When an editor opens contact Alex's page
Then the gift in "Bought" appears in the given list
And the gift in "Offered" appears in the open list.

**Scenario: Gift whose stage was deleted**
Given Alex's gift "Pottery class" was in the final stage, and a manager then deleted that stage in Settings
When an editor opens contact Alex's page
Then "Pottery class" shows "No stage" and appears in the open list.

**Scenario: Account has no stages**
Given the user's account has no stages in Settings
When an editor opens contact Alex's page
Then every gift on Alex's page appears in the open list
And the given list is empty.

### Out of scope
- A per-stage "counts as given" choice in Settings.
- Requiring a date given when a gift reaches the final stage.
- Showing a given gift on the pages of other recipients (ticket 4).
- Reminders before an occasion.

---

## 4. Give one gift to more than one contact

**Type:** Feature
**Initiative:** Gift tracking (phase 1)
**Depends on:** 1, 2, 3

### Summary
Editors and managers can choose several contacts from the same vault as recipients of one gift, and the gift shows on every recipient's page.

### Job to be done
When the deliberate rememberer plans one gift for a couple or a family, they need to record it once, so every recipient's page shows the same, up-to-date gift.

### Navigation context
**Entry point(s):** Vault → Contacts → a contact's page → Gifts section → "Add a gift", or the edit control on an existing gift.
**Exit point(s):** The user stays on the contact's page. The gift appears in the Gifts section of every recipient.
**Screen/component:** The Recipients field of the add-gift and edit-gift forms, and the Gifts section of each recipient's contact page.

### Context
Today a gift for a couple has to be written twice, on two pages. The spec treats a shared gift as one gift with several recipients, not as copies. Deleting one recipient keeps the gift for the others. Users choose individual contacts; choosing a whole group is out of scope.

### Designs
No designs needed. Follow the existing patterns in Monica for choosing contacts.

### Acceptance criteria

**Scenario: Editor adds a gift for two contacts**
Given an editor is on contact Sam's page with the add-gift form open, and Sam is already a recipient
When they save the gift "Board game" with Sam and Priya as recipients
Then "Board game" appears in the open list on Sam's page
And "Board game" appears in the open list on Priya's page
And both pages show Sam and Priya as its recipients.

**Scenario: A change on one page shows on every recipient's page**
Given an editor is editing the gift "Board game" on contact Priya's page, and its recipients are Sam and Priya
When they save the form with the name "Card game"
Then Priya's page shows "Card game"
And Sam's page shows "Card game".

**Scenario: A shared gift becomes a given gift**
Given an editor is editing an open gift on Sam's page, and its recipients are Sam and Priya
When they save the form with the final stage and the date given 3 October 2026
Then the gift appears in the given list on both Sam's page and Priya's page, with the date 3 October 2026
And the gift no longer appears in the open list on either page.

**Scenario: Saving a gift with no recipient**
Given an editor is editing the gift "Board game" on Sam's page
And they have changed the note
When they save the form with no recipients
Then they see a message next to the Recipients field
And the gift stays the same on Sam's page and Priya's page
And the form still shows the new note they typed.

**Scenario: Recipients come only from the same vault**
Given an editor is on Sam's page in the vault "Family" with the add-gift form open
When they search for a recipient
Then the results show only contacts from the vault "Family".

**Scenario: Editor removes the current contact as a recipient**
Given an editor is editing the gift "Board game" on Sam's page, and its recipients are Sam and Priya
When they save the form with Priya as the only recipient
Then "Board game" no longer appears on Sam's page
And "Board game" still appears on Priya's page with Priya as its only recipient.

**Scenario: Deleting a shared gift**
Given an editor is on Priya's page, and the gift "Board game" has recipients Sam and Priya
When they delete "Board game"
Then "Board game" no longer appears on Priya's page or on Sam's page.

**Scenario: Deleting one recipient keeps the gift for the others**
Given an editor is on contact Sam's page, and the gift "Board game" has recipients Sam and Priya
When they delete contact Sam
Then "Board game" still appears on Priya's page
And Priya is its only recipient.

**Scenario: Deleting the last recipient removes the gift**
Given an editor is on contact Priya's page, and Priya is the only recipient left on "Board game"
When they delete contact Priya
Then "Board game" no longer appears on any contact page in the vault.

### Out of scope
- Choosing a whole group of contacts as recipients.
- Recipients from another vault.
- Gifts that only one vault member can see. A recipient who is a vault member can see gift ideas meant for them; this version accepts that.
- Shared wishlists, where other people see or claim gifts.

---

## 5. See every open gift in the vault on the vault Gifts page

**Type:** Feature
**Initiative:** Gift tracking (phase 1)
**Depends on:** 1, 3

### Summary
Vault members can open a vault Gifts page that lists every open gift in the vault, grouped by occasion.

### Job to be done
When a birthday or holiday is coming, the deliberate rememberer needs to see all their open gift ideas in one place, so they can decide what to buy without opening each contact's page.

### Navigation context
**Entry point(s):** The vault's main navigation → "Gifts".
**Exit point(s):** The user stays on the vault Gifts page. They leave through the vault's main navigation.
**Screen/component:** The vault Gifts page.

### Context
Notes cannot list gift ideas across all contacts before an occasion. This page gives users a reason to return to Monica before every birthday and holiday, which supports the strategy's goal to win the first 30 days. Grouping by occasion is the only grouping, filter or sort in phase 1.

### Designs
No designs needed. Follow the layout of the existing vault Tasks page.

### Acceptance criteria

**Scenario: Member opens the vault Gifts page from the navigation**
Given a vault member is on the vault dashboard, and the vault has open gifts
When they choose "Gifts" in the vault's main navigation
Then the vault Gifts page opens
And it shows every open gift in the vault
And each gift shows its name, its recipients and its stage.

**Scenario: Gifts are grouped by occasion**
Given a vault has open gifts for the occasions "Birthday" and "Christmas", and open gifts with no occasion
When a vault member opens the vault Gifts page
Then they see the gifts under occasion headings, in the account's Settings order for occasions
And the gifts with no occasion appear last, under the heading "No occasion"
And inside each heading, the newest gift appears first
And no heading appears for an occasion that has no open gifts.

**Scenario: Given gifts do not appear**
Given a vault has the open gift "Board game" and the given gift "Pottery class"
When a vault member opens the vault Gifts page
Then they see "Board game"
And they do not see "Pottery class".

**Scenario: Vault has no open gifts**
Given a vault has no open gifts
When a vault member opens the vault Gifts page
Then they see the message "No open gift ideas yet"
And they see no occasion headings.

**Scenario: User without access to the vault**
Given a signed-in user is not a member of the vault "Family"
When they open the address of the vault Gifts page for "Family"
Then Monica refuses access and shows an error
And they see no gifts from "Family".

**Scenario: Viewer can see the page**
Given a user with the viewer role is on the vault dashboard
When they choose "Gifts" in the vault's main navigation
Then they see every open gift in the vault.

**Scenario: Occasion deleted in Settings**
Given the open gift "Pottery class" had the occasion "Wedding", and a manager then deleted "Wedding" in Settings
When a vault member opens the vault Gifts page
Then "Pottery class" appears under "No occasion".

**Scenario: Gift whose only recipient was deleted**
Given the open gift "Pottery class" has Alex as its only recipient, and a user then deleted contact Alex
When a vault member opens the vault Gifts page
Then "Pottery class" does not appear.

**Scenario: Labels show in the user's language**
Given a user has chosen French as their language
When they open the vault Gifts page
Then the navigation entry, the headings and the empty message show in French.

### Out of scope
- Sorting by whose birthday is next, or any filter or sort other than grouping by occasion.
- Given gifts on this page.
- Adding, editing or deleting gifts from this page.
- Totals, budgets or spending reports.
- A dashboard widget or reminder before occasions.
- Turning the page on or off (ticket 6).

---

## 6. Let vault managers turn the vault Gifts page on or off

**Type:** Feature
**Initiative:** Gift tracking (phase 1)
**Depends on:** 5

### Summary
Vault managers can turn the vault Gifts page on or off in vault settings, the same way they can for other vault pages such as Tasks and Journal.

### Job to be done
When a vault does not use gifts, its manager needs to hide the Gifts page, so the vault's navigation shows only what its members use.

### Navigation context
**Entry point(s):** The vault's main navigation → Vault settings → the section that turns vault pages on or off.
**Exit point(s):** The user stays on vault settings. The Gifts entry appears or disappears in the vault's main navigation for every member.
**Screen/component:** The vault settings page, and the vault's main navigation.

### Context
Monica already lets vault managers turn other vault pages on or off, such as Tasks and Journal. The Gifts page follows the same behaviour so users do not have to learn a new pattern. The page is on by default, so existing vaults get it without any action.

### Designs
No designs needed. Follow the existing on/off settings for other vault pages.

### Acceptance criteria

**Scenario: Gifts page is on by default**
Given a vault that existed before this release, and its manager has not changed any setting
When a vault member opens the vault dashboard
Then they see "Gifts" in the vault's main navigation.

**Scenario: Manager turns the Gifts page off**
Given a vault manager is on the vault settings page, and the Gifts page is on
When they turn the Gifts page off
Then the "Gifts" entry disappears from the vault's main navigation for every member of that vault
And gifts still appear on contact pages in that vault.

**Scenario: Manager turns the Gifts page back on**
Given a vault manager is on the vault settings page, and the Gifts page is off
When they turn the Gifts page on
Then the "Gifts" entry appears in the vault's main navigation for every member of that vault.

**Scenario: Editor or viewer cannot change the setting**
Given a user with the editor or viewer role is on the vault dashboard
When they try to turn the Gifts page off by any route
Then Monica refuses the change, in the same way it refuses changes to the other vault page settings
And the "Gifts" entry stays in the vault's main navigation.

**Scenario: Setting affects only one vault**
Given a user is a member of the vaults "Family" and "Work"
When the manager of "Family" turns the Gifts page off
Then "Gifts" disappears from the navigation of "Family"
And "Gifts" still appears in the navigation of "Work".

### Out of scope
- Hiding the Gifts section on contact pages.
- Turning gifts off for a whole account.
- Changing how the existing on/off settings for other vault pages work.

---

## Decomposition notes

- **Initiative:** The spec has no `Initiative:` field. This run is non-interactive, so the tickets use "Gift tracking (phase 1)", taken from the spec title. The PM should confirm or replace it before posting to a tracker.
- **No separate data Chore:** Each ticket delivers its own storage as part of a vertical slice. The tickets do not prescribe how gifts are stored. Engineering can split out a Chore at planning if they need one, and it must come first in the chain.
- **Role rules are inside each Feature:** Tickets 1, 2, 3 and 6 each carry the viewer rules for their own action, so no ticket ships a gap that lets a viewer change gifts. AC-6 from the spec is spread across tickets 1 and 2.
- **Split scenarios:** The spec's AC-2 and AC-3 chain several actions. The tickets split them so each `When` is one action.
- **Spec acceptance criteria map:** AC-1 → ticket 1. AC-2 → tickets 3 and 4. AC-3 → ticket 4 (the vault-page part → ticket 5). AC-4 → tickets 5 and 6. AC-5 → tickets 1, 2 and 4. AC-6 → tickets 1 and 2.
- **Contact page layout (open question 🟡):** Ticket 1 holds the working assumption as a scenario: users with a customised layout see the Gifts section without setup. If engineering finds at plan review that this is not possible, the PM must revise ticket 1 before work starts.
- **Empty occasion headings:** The spec does not say whether an occasion with no open gifts shows a heading. Ticket 5 shows only headings that have gifts, and shows none when the vault has no open gifts.
- **Success measure (open question 🟡):** No ticket counts usage. The spec leaves measurement as a PM open question. If the PM wants it in phase 1, it needs its own ticket that passes the privacy review gate.
