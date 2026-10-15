# Gift tracking — Tickets
Generated: 15 October 2026
Spec: output/spec.md
Tracker: none (no `ticket_tool` in this run; tickets are not posted)
Initiative: Gift tracking (provisional — the spec has no `Initiative:` field; see "Run notes")

---

## Run notes

This run was non-interactive. The skill's approval checkpoints were treated as approved as-is.

### Initiative

The spec has no `Initiative:` field. The skill normally stops and asks the PM. Instead, every ticket uses the provisional initiative **Gift tracking**, taken from the spec title. The PM should confirm or replace it before anyone posts these tickets.

### Open questions — resolved by working assumption

The spec lists 4 open questions (🟡). Each one has a working assumption. These tickets treat each working assumption as the answer:

1. **"Given" depends on the last gift state.** Tickets 3 and 7 use the 2 rules in the spec. There is no separate "Mark as given" action.
2. **Gifts section placement in customised templates.** If a user's template has no "Information" tab, the Gifts section goes on the first tab of their default template (ticket 1). Engineering can still revisit this at plan-review.
3. **Switching off the vault Gifts page** hides only the vault-wide page. Gifts sections on contact pages still show (ticket 5).
4. **Beta page.** Gift management comes off the beta page's list of missing features in the same release (ticket 8).

### Other context

- `docs/product/personas.md` does not exist. Job statements use the **deliberate rememberer** from `docs/product/strategy.md`, as the spec does.
- `docs/iterations/current.md` does not exist. Nobody has checked these tickets against in-flight work. The engineering team should check at plan-review.

### Breakdown

| # | Title | Type | Depends on |
| - | ----- | ---- | ---------- |
| 1 | Record gifts on a contact page | Feature | None |
| 2 | Edit and delete a gift | Feature | 1 |
| 3 | Mark a gift as given through its gift state | Feature | 2 |
| 4 | See all open gift ideas on a vault-wide Gifts page | Feature | 1 |
| 5 | Switch the vault-wide Gifts page on or off | Feature | 4 |
| 6 | Share one gift between several recipients | Feature | 2, 4 |
| 7 | Keep gifts in step with changes to gift states and occasions | Feature | 3, 4 |
| 8 | Remove gift management from the beta page's missing-features list | Chore | 1, 2, 3, 4, 5, 6, 7 |

---

## 1. Record gifts on a contact page

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** None

### Summary
A user can add a gift on a contact page and see that contact's open gifts and past gifts there.

### Job to be done
When a gift idea comes to mind at a random moment, a deliberate rememberer needs to save it on that person's contact page, so they can find it before the next birthday or holiday.

### Navigation context
**Entry point(s):** A vault → a contact page → the Gifts section on the "Information" tab. Loans also appears on this tab by default.
**Exit point(s):** The user stays on the contact page. The new gift appears in the Gifts section.
**Screen/component:** The Gifts section on the contact page, and the add-gift form that opens from it.

### Context
Monica has no place to record gifts, so users keep ideas in notes, tasks, or other apps. Monica's beta page names gift management as one of the 2 most important missing features. Accounts already have gift states and gift occasions in Settings, but no feature uses them yet. The end-of-year holidays start in about 10 weeks.

Terms used in this ticket:
- **Gift**: something the user plans to give, or has given, to a contact.
- **Recipient**: a contact a gift is for.
- **Open gift** (gift idea): a gift with no date given.
- **Past gift**: a gift with a date given.
- **Gift state** and **gift occasion**: the lists users already manage in Settings → Personalize. The default states are Idea, Searched, Found, Bought, Offered. The default occasions are Birthday, Anniversary, Christmas, Just because, Wedding.
- **Vault access levels**: View can only look. Edit can change contacts. Manage can also change vault settings.

Privacy rule: Monica must never open, fetch, or preview a link the user pastes in. Saving or viewing a gift must not contact the linked website. The feature must work the same on the hosted plan and on self-hosted installs, with no new outside service.

### Designs
No designs needed. Follow the existing Loans and Goals sections on contact pages. A designer may review the built screens before release. That review is optional and does not block the build.

### Acceptance criteria

**Scenario: Existing account sees the Gifts section without any setup**
Given an account created before this release, where nobody has changed any settings
And a user with Edit access is on a contact page in that account
When the user opens the "Information" tab
Then they see a Gifts section on that tab
And every other section on the contact page stays where it was before

**Scenario: Add a gift with only a name**
Given a user with Edit access is on a contact page, in the Gifts section
When the user adds a gift named "Pottery class" and saves
Then the gift appears at the top of the open gifts in the Gifts section
And the gift shows the first gift state in the account's list, which is "Idea" by default
And the gift shows no occasion

**Scenario: Add a gift with every optional detail**
Given a user with Edit access is on a contact page, in the add-gift form
When the user saves a gift with a name, the occasion "Birthday", the state "Bought", a price of 40 EUR, a link, and a note
Then the gift appears in the open gifts with each of these values
And the link shows exactly as the user typed it, with no preview, page title, or image

**Scenario: Choose from the account's own states, occasions, and currencies**
Given a user with Edit access is on a contact page, in the add-gift form
When the user opens the occasion, state, or currency choices
Then they see only the account's gift occasions, the account's gift states, and the currencies Monica already offers

**Scenario: Record a gift given in the past**
Given a user with Edit access is on a contact page, in the add-gift form
When the user saves a gift with the date given 10 December 2025
Then the gift appears in the past gifts, not in the open gifts

**Scenario: Open gifts and past gifts appear in the right order**
Given a contact has 2 open gifts, added on different days
And the contact has 2 past gifts, given on 3 March 2025 and 10 December 2025
When a user opens that contact's page
Then the open gifts appear first, with the most recently added gift at the top
And the past gifts appear below them, with the 10 December 2025 gift above the 3 March 2025 gift

**Scenario: Saving without a name shows an error**
Given a user with Edit access is on a contact page, in the add-gift form
And the user has entered a price and a note but no name
When the user saves
Then Monica does not add the gift
And Monica shows "Enter what the gift is" next to the name
And the price and note the user entered stay in the form

**Scenario: View-only user cannot add a gift**
Given a user with View access is on a contact page that has gifts
When the user looks at the Gifts section
Then they see every gift for that contact
And they see no control to add a gift
And if they try to add a gift by any other route, Monica refuses and no new gift appears

**Scenario: Customised template with no "Information" tab**
Given a user has removed the "Information" tab from their default template
When the user opens a contact page
Then they see the Gifts section on the first tab of their default template

**Scenario: User removes the Gifts section in Settings**
Given a user is on the Settings page where they manage templates
When the user removes the Gifts section from a template, the same way as any other section
Then contact pages that use that template no longer show the Gifts section

**Scenario: Add a gift on a phone-sized screen**
Given a user with Edit access is on a contact page on a phone-sized screen
When the user opens the add-gift form
Then they can see, fill in, and save every field without zooming or scrolling sideways

### Out of scope
- Editing and deleting a gift (ticket 2)
- The link between the last gift state and the date given (ticket 3)
- The vault-wide Gifts page (ticket 4)
- Gifts with more than one recipient, and what happens when a recipient contact is deleted (ticket 6)
- Gifts the user received from others (Phase 2)
- Private gifts, or hiding gifts from a recipient who shares the vault (Phase 2)
- Reminders, notifications, or a dashboard widget for gifts
- Price totals, budgets, or spending reports
- Link previews, shopping integrations, or affiliate deals
- Photos of gifts and a "date bought" field
- A separate mobile quick-add flow

---

## 2. Edit and delete a gift

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 1

### Summary
A user with Edit or Manage access can change any detail of a gift, or delete it, from a contact page.

### Job to be done
When a gift idea changes or is no longer wanted, a deliberate rememberer needs to update or remove it, so they can trust that their list of gifts is current.

### Navigation context
**Entry point(s):** A vault → a contact page → the Gifts section → the Edit or Delete action on a gift.
**Exit point(s):** The user returns to the contact page. The Gifts section shows the changed gift, or no longer shows the deleted gift.
**Screen/component:** The Gifts section on the contact page, the edit-gift form, and the delete confirmation.

### Context
Gift ideas change as the user learns more: the price moves, the state moves from Idea to Bought, or the idea is dropped. Without edit and delete, the list fills with wrong or stale ideas. Then users stop trusting it.

### Designs
No designs needed. Follow the edit and delete actions on the existing Loans and Goals sections.

### Acceptance criteria

**Scenario: Change a gift's details**
Given a user with Edit access is on a contact page with the open gift "Pottery class"
When the user edits the gift, changes the name to "Pottery class for two" and the price to 80 EUR, and saves
Then the Gifts section shows "Pottery class for two" with a price of 80 EUR

**Scenario: Editing an open gift keeps its place in the list**
Given a contact has 3 open gifts, and "Pottery class" is the second most recently added
When a user with Edit access changes the note on "Pottery class" and saves
Then "Pottery class" is still second in the open gifts

**Scenario: Delete a gift after confirming**
Given a user with Edit access is on a contact page with the open gift "Pottery class"
When the user deletes the gift and confirms
Then "Pottery class" no longer appears in the Gifts section

**Scenario: Cancel a delete**
Given a user with Edit access is on a contact page, looking at the delete confirmation for "Pottery class"
When the user cancels
Then "Pottery class" still appears in the Gifts section, unchanged

**Scenario: Clearing the name shows an error**
Given a user with Edit access is in the edit-gift form for "Pottery class"
When the user clears the name, changes the note, and saves
Then Monica shows "Enter what the gift is" next to the name
And the changed note stays in the form
And after the user cancels, the Gifts section still shows "Pottery class" with its old note

**Scenario: View-only user cannot edit or delete**
Given a user with View access is on a contact page that has gifts
When the user looks at a gift in the Gifts section
Then they see no control to edit or delete the gift
And if they try to change or delete it by any other route, Monica refuses and the gift stays unchanged

### Out of scope
- Setting a gift as given through its state or date given (ticket 3)
- Adding or removing recipients, and deleting a gift that has several recipients (ticket 6)
- Undoing a delete
- Editing or deleting gifts from the vault-wide Gifts page

---

## 3. Mark a gift as given through its gift state

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2

### Summary
Setting a gift to the last gift state, or entering a date given, marks the gift as given and moves it to the past gifts.

### Job to be done
When a deliberate rememberer hands over a gift, they need to mark it as given in one step, so they can see next year what they already gave.

### Navigation context
**Entry point(s):** A vault → a contact page → the Gifts section → the add-gift form or the edit-gift form.
**Exit point(s):** The user returns to the contact page. The gift appears in the open gifts or the past gifts, depending on its date given.
**Screen/component:** The add-gift and edit-gift forms, and the Gifts section on the contact page.

### Context
A gift is given when it has a date given. Users already track progress with gift states, and the last default state is "Offered". So Monica links the two with 2 rules:
1. If the user sets the state to the **last** state in the account's list, Monica fills the date given with today. The user can change that date.
2. If the user enters a date given, Monica sets the state to the last state in the account's list.

Removing the date given makes the gift open again. This is the spec's riskiest assumption. Users who add states after "Offered" could mark gifts as given by accident.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: Setting the last state marks the gift as given**
Given a contact has 2 past gifts, given on 3 March 2025 and 10 December 2025, and 1 open gift
And a user with Edit access is in the edit-gift form for the open gift
When the user sets the state to the last state in the account's list and saves
Then the gift shows today's date as its date given
And the gift moves from the open gifts to the top of the past gifts
And the 10 December 2025 gift appears above the 3 March 2025 gift

**Scenario: Today's date appears in the form before saving**
Given a user with Edit access is in the edit-gift form for an open gift
When the user sets the state to the last state in the account's list
Then the date given in the form shows today's date
And the user can change that date before saving

**Scenario: Change the date Monica filled in**
Given a user with Edit access is in the edit-gift form, and has just set the state to the last state
When the user changes the date given to 1 October 2026 and saves
Then the gift appears in the past gifts with the date given 1 October 2026

**Scenario: Entering a date given sets the last state**
Given a user with Edit access is in the edit-gift form for an open gift in the state "Bought"
When the user enters the date given 12 October 2026 and saves
Then the gift appears in the past gifts
And the gift shows the last state in the account's list, which is "Offered" by default

**Scenario: Adding a new gift in the last state**
Given a user with Edit access is in the add-gift form on a contact page
When the user saves a new gift in the last state in the account's list
Then the gift appears in the past gifts with today's date as its date given

**Scenario: Removing the date given reopens the gift**
Given a user with Edit access is in the edit-gift form for a past gift
When the user clears the date given and saves
Then the gift moves back to the open gifts

**Scenario: A state that is not last does not mark the gift as given**
Given an account whose gift states end with "Bought", then "Offered"
And a user with Edit access is in the edit-gift form for an open gift
When the user sets the state to "Bought" and saves
Then the gift stays in the open gifts with no date given

**Scenario: View-only user cannot mark a gift as given**
Given a user with View access is on a contact page with an open gift
When the user looks at the gift in the Gifts section
Then they see no control to change its state or date given
And if they try to change either by any other route, Monica refuses and the gift stays open

### Out of scope
- A separate "Mark as given" action
- Gifts the user received from others (Phase 2)
- Reminders before an occasion
- What happens when the user renames, reorders, adds, or deletes gift states (ticket 7)

---

## 4. See all open gift ideas on a vault-wide Gifts page

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 1

### Summary
A user can open a Gifts page from the vault's main navigation and see every open gift in the vault, grouped by gift occasion.

### Job to be done
When a birthday or holiday is coming, a deliberate rememberer needs to see every gift idea they already have in one place, so they can choose and buy in time.

### Navigation context
**Entry point(s):** A vault → the Gifts item in the vault's main navigation.
**Exit point(s):** The user stays on the Gifts page, or selects a recipient's name to open that contact's page.
**Screen/component:** The vault-wide Gifts page.

### Context
Notes and tasks cannot show which gift ideas are still open across all contacts. This page answers "what ideas do I already have?" before an occasion. It shows open gifts only. It follows the layout of the existing vault-wide Tasks page.

### Designs
No designs needed. Follow the existing vault-wide Tasks page.

### Acceptance criteria

**Scenario: Open gifts appear grouped by occasion**
Given a vault has open gifts for the occasions "Birthday" and "Christmas", and 1 open gift with no occasion
And the account's occasion list has "Birthday" before "Christmas"
When a user selects Gifts in the vault's main navigation
Then the Gifts page shows the gifts under "Birthday", then "Christmas", then "No occasion"

**Scenario: Each gift shows its key details**
Given a user is on the vault-wide Gifts page, and the vault has the open gift "Pottery class" for a contact
When the user looks at that gift
Then they see its name, its recipient, its gift state, and its price

**Scenario: Open a recipient's contact page from the Gifts page**
Given a user is on the vault-wide Gifts page
When the user selects a recipient's name on a gift
Then Monica opens that recipient's contact page

**Scenario: Past gifts do not appear**
Given a vault has 2 open gifts and 3 past gifts
When a user opens the vault-wide Gifts page
Then the page shows the 2 open gifts
And the page shows none of the 3 past gifts

**Scenario: Vault with no open gifts shows an empty state**
Given a vault has no open gifts
When a user opens the vault-wide Gifts page
Then they see a message that tells them to add gift ideas from a contact page

**Scenario: User without access to the vault cannot open its Gifts page**
Given a user has no access to a vault
When the user tries to open that vault's Gifts page
Then Monica refuses access, the same way it does for the vault's other pages
And the user sees no gifts from that vault

**Scenario: Gifts from other vaults do not appear**
Given a user has access to 2 vaults, and both vaults have open gifts
When the user opens the Gifts page in the first vault
Then they see only the open gifts for contacts in the first vault

**Scenario: View-only user sees the page without change controls**
Given a user with View access to a vault that has open gifts
When the user opens the vault-wide Gifts page
Then they see every open gift in the vault
And they see no control to edit or delete a gift

**Scenario: Gifts page on a phone-sized screen**
Given a user is on the vault-wide Gifts page on a phone-sized screen
When the page loads
Then they can read every group and gift without zooming or scrolling sideways

### Out of scope
- Adding a gift from this page. Users add gifts from a contact page.
- Showing past gifts on this page
- Sorting by whose birthday is next
- Price totals or budgets
- Switching the page on or off (ticket 5)
- Showing one gift with several recipients (ticket 6)

---

## 5. Switch the vault-wide Gifts page on or off

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 4

### Summary
A user with Manage access can switch the vault-wide Gifts page on or off in the vault settings.

### Job to be done
When a vault is not used for gifts, a deliberate rememberer who manages the vault needs to hide the Gifts page, so they can keep the vault's navigation short.

### Navigation context
**Entry point(s):** A vault → vault settings, where the existing switches for other vault pages are.
**Exit point(s):** The user stays on the vault settings page. The vault's main navigation shows or hides the Gifts item.
**Screen/component:** The vault settings page and the vault's main navigation.

### Context
Monica already lets users switch other vault pages on or off. The Gifts page must work the same way. It is on by default for every vault, including vaults that exist before this release. Switching it off hides only the vault-wide page. Gifts sections on contact pages still show.

### Designs
No designs needed. Follow the existing switches for other vault pages.

### Acceptance criteria

**Scenario: The Gifts page is on by default in existing vaults**
Given a vault created before this release
When a user with Manage access opens the vault settings
Then the Gifts page switch is on
And the Gifts item shows in the vault's main navigation

**Scenario: Switch the Gifts page off**
Given a user with Manage access is on the vault settings page, and the Gifts page is on
When the user switches the Gifts page off
Then the Gifts item disappears from the vault's main navigation
And the Gifts sections on contact pages in that vault still show

**Scenario: Switch the Gifts page back on**
Given the Gifts page is off in a vault that has open gifts
And a user with Manage access is on the vault settings page
When the user switches the Gifts page on
Then the Gifts item shows again in the vault's main navigation
And the Gifts page shows the same open gifts as before

**Scenario: User with Edit access cannot change the switch**
Given a user with Edit access, but not Manage access, is in the vault
When the user looks for the Gifts page switch in the vault settings
Then they cannot change it, the same way they cannot change the vault's other settings

**Scenario: Opening a saved link to a switched-off Gifts page**
Given the Gifts page is off in a vault
When a user opens a saved link to that vault's Gifts page
Then Monica responds the same way it does for other vault pages that are switched off

### Out of scope
- Hiding Gifts sections on contact pages when the vault page is off
- Removing the Gifts section from contact pages. Users do this in Settings, through templates (ticket 1).

---

## 6. Share one gift between several recipients

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2, 4

### Summary
A user can give one gift to several contacts in the same vault, and the gift shows on every recipient's contact page.

### Job to be done
When a deliberate rememberer plans one gift for several people, such as a wedding present for a couple, they need to record it once, so they can see it on each person's page without keeping copies in step.

### Navigation context
**Entry point(s):** A vault → a contact page → the Gifts section → the add-gift or edit-gift form, where the user chooses recipients. Also: a contact page → the existing delete-contact action.
**Exit point(s):** The user returns to the contact page they started from. The gift shows on every recipient's contact page.
**Screen/component:** The recipients choice in the add-gift and edit-gift forms, the Gifts section on each recipient's contact page, the delete confirmation, and the vault-wide Gifts page.

### Context
A gift with several recipients is one gift, not a copy per recipient. A change from any recipient's page shows on every recipient's page. The gift survives when someone deletes one of its recipients. Monica deletes it only when its last recipient is deleted.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: Add a gift for 2 recipients**
Given a user with Edit access is on Contact A's page, in the add-gift form
And Contact A is already the first recipient
When the user adds Contact B, from the same vault, as a recipient and saves
Then the gift appears in the Gifts section on Contact A's page
And the gift appears in the Gifts section on Contact B's page

**Scenario: An edit on one recipient's page shows on every recipient's page**
Given an open gift "Wedding present" has 2 recipients, Contact A and Contact B
And a user with Edit access is on Contact A's page
When the user changes the gift's name to "Wedding vase" and saves
Then Contact B's page shows the gift as "Wedding vase"

**Scenario: The vault-wide Gifts page shows a shared gift once**
Given an open gift "Wedding vase" has 2 recipients, Contact A and Contact B
When a user opens the vault-wide Gifts page
Then the page shows "Wedding vase" once
And the gift lists both Contact A and Contact B, each linked to their contact page

**Scenario: Delete a shared gift after confirming**
Given an open gift has 2 recipients, Contact A and Contact B
And a user with Edit access is on Contact A's page
When the user deletes the gift
Then Monica asks the user to confirm, and says the gift will go from every recipient's page
And after the user confirms, the gift no longer appears on Contact A's page or Contact B's page

**Scenario: Remove one recipient**
Given an open gift has 2 recipients, Contact A and Contact B
And a user with Edit access is in the edit-gift form on Contact A's page
When the user removes Contact B as a recipient and saves
Then the gift no longer appears on Contact B's page
And the gift still appears on Contact A's page

**Scenario: Removing every recipient shows an error**
Given a user with Edit access is in the add-gift or edit-gift form
When the user removes every recipient and saves
Then Monica does not save the gift
And Monica shows "Choose at least one person" next to the recipients
And the other values the user entered stay in the form

**Scenario: Deleting one recipient contact keeps the gift for the others**
Given an open gift "Wedding vase" has 2 recipients, Contact A and Contact B
When a user deletes Contact A
Then the gift still appears on Contact B's page, with Contact B as its only recipient
And the vault-wide Gifts page shows the gift with Contact B only

**Scenario: Deleting the only recipient contact deletes the gift**
Given an open gift "Pottery class" has 1 recipient, Contact A
When a user deletes Contact A
Then "Pottery class" no longer appears on the vault-wide Gifts page

**Scenario: A shared past gift appears once on each recipient's page**
Given a past gift has 2 recipients, Contact A and Contact B
When a user opens Contact A's page, then Contact B's page
Then each page shows the gift once in its past gifts

### Out of scope
- Recipients from a different vault
- A separate copy of the gift for each recipient
- Hiding a gift from a recipient who shares the vault (Phase 2)

---

## 7. Keep gifts in step with changes to gift states and occasions

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 3, 4

### Summary
When a user renames, reorders, adds, or deletes gift states or gift occasions in Settings, existing gifts show the change straight away.

### Job to be done
When a deliberate rememberer changes their gift states or occasions to match how they think, they need existing gifts to follow, so they can keep using their list without fixing each gift.

### Navigation context
**Entry point(s):** Settings → Personalize → Gift states, or Settings → Personalize → Gift occasions.
**Exit point(s):** The user returns to a contact page or the vault-wide Gifts page and sees the change on existing gifts.
**Screen/component:** The existing gift state and gift occasion settings pages, the Gifts section on contact pages, and the vault-wide Gifts page.

### Context
Users already manage these lists in Settings, but until now the lists had no effect. Gifts now depend on them. The Gifts page groups by occasion order, and the last state marks a gift as given (ticket 3). A change to a list must never delete gifts.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: Renaming a state shows on existing gifts**
Given an account has open gifts in the state "Idea"
And a user is on Settings → Personalize → Gift states
When the user renames "Idea" to "Maybe"
Then those gifts show the state "Maybe" on contact pages and on the vault-wide Gifts page

**Scenario: Reordering occasions reorders the Gifts page**
Given a vault has open gifts for "Birthday" and "Christmas", with "Birthday" first in the account's list
And a user is on Settings → Personalize → Gift occasions
When the user moves "Christmas" above "Birthday"
Then the vault-wide Gifts page shows the "Christmas" group before the "Birthday" group

**Scenario: A state added at the end becomes the "given" state**
Given an account's gift states end with "Offered"
And a user has added the state "Thanked" after "Offered" in Settings → Personalize → Gift states
When a user with Edit access sets an open gift to "Offered" and saves
Then the gift stays in the open gifts
And when the user sets the gift to "Thanked" and saves, the gift moves to the past gifts with today's date given

**Scenario: Deleting an occasion that gifts use keeps those gifts**
Given a vault has open gifts for the occasion "Wedding"
And a user is on Settings → Personalize → Gift occasions
When the user deletes "Wedding"
Then those gifts still appear on contact pages
And the vault-wide Gifts page shows them in the "No occasion" group

**Scenario: Deleting a state that gifts use keeps those gifts**
Given an account has gifts in the state "Searched"
And a user is on Settings → Personalize → Gift states
When the user deletes "Searched"
Then those gifts still appear on contact pages and on the vault-wide Gifts page
And those gifts show no state

### Out of scope
- New screens for managing gift states or gift occasions
- Warnings when a user reorders or adds states after "Offered"
- Moving gifts to a different state or occasion in bulk

---

## 8. Remove gift management from the beta page's missing-features list

**Type:** Chore
**Initiative:** Gift tracking
**Depends on:** 1, 2, 3, 4, 5, 6, 7

### Summary
Update Monica's beta page so it no longer lists gift management as a missing feature.

### Why now
The beta page names gift management as one of the 2 most important missing features. Once tickets 1 to 7 ship, that statement is wrong. A wrong list tells users the product is less complete than it is.

### Definition of done
- [ ] The beta page no longer lists gift management as a missing feature.
- [ ] The change goes live in the same release as tickets 1 to 7, not before.

---

## Report

**Tracker:** none — no `ticket_tool` is configured for this run. This run posted no tickets, and no tracker IDs exist. The spec's `Status:` and `Tickets:` fields are unchanged, because there are no IDs to record.

**Initiative:** Gift tracking — provisional. The spec has no `Initiative:` field, and there is no backend grouping ID.

**Tickets created:** none posted. Drafts, in order:
1. Record gifts on a contact page
2. Edit and delete a gift
3. Mark a gift as given through its gift state
4. See all open gift ideas on a vault-wide Gifts page
5. Switch the vault-wide Gifts page on or off
6. Share one gift between several recipients
7. Keep gifts in step with changes to gift states and occasions
8. Remove gift management from the beta page's missing-features list

**File saved:** `output/tickets.md`

**Assumptions made during decomposition:**
- Ticket 1 includes the Gifts section appearing for existing accounts. It is not a separate Chore, because users see the change and the feature makes no sense without it.
- Each ticket carries its own View-access scenario. A separate permissions ticket would let earlier tickets ship without permission checks.
- Deleting a contact who is a gift's only recipient sits in ticket 6, with the other contact-deletion rules. Until ticket 6 ships, the team should make sure deleting a contact also removes their gifts.
- Ticket 8 is a Chore, although the beta page is visible. It changes no product behaviour. It only corrects a statement about the product.
- Ticket 2 says editing an open gift keeps its place in the list. The spec sorts open gifts by date added, so an edit does not move them.
- Ticket 5's link and Edit-access scenarios defer to how Monica already treats other vault pages that are switched off. The spec does not describe these cases directly.
- No Spike. The riskiest assumption (the last state means "given") is a question about users, not about how to build. Validate it with users after release.
- No DB migration Chore. How existing accounts get the Gifts section is the engineering team's choice. Ticket 1 states only the outcome.

**Recommended next step:** The PM confirms the "Gift tracking" initiative name and the 4 working assumptions. Then engineering checks for in-flight conflicts at plan-review. After that, tickets 1 and 4 can start at once.
