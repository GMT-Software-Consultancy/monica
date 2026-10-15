# Gift tracking — Tickets
Generated: 15 October 2026
Spec: output/spec.md
Tracker: none (no `ticket_tool` configured — tickets live in this file only)
Initiative: Gift tracking

> **Initiative note:** The spec has no `Initiative:` field. This run is non-interactive, so these tickets use the spec title, "Gift tracking", as the initiative. The PM should confirm or replace it before anyone posts the tickets to a tracker.

## Terms used in these tickets

- **Vault**: a separate space with its own contacts. One or more users share it. Each user has one permission level: manager, editor, or viewer.
- **Gift**: one thing the user plans to give, or has given, to one or more contacts.
- **Recipient**: a contact the gift is for. A gift has one or more recipients, all in the gift's vault.
- **Gift occasion**: the reason for the gift. It comes from the account's list in Settings → Personalize → Gift occasions.
- **Gift state**: how far the gift has progressed. It comes from the account's list in Settings → Personalize → Gift states.
- **Given date**: the date the user gave the gift.
- **Open gift**: a gift with no given date. **Given gift**: a gift with a given date. The given date decides this, not the gift state.
- **Module**: a block on the contact page, such as Loans or Goals. The account's contact templates control which modules appear and on which page.

## Breakdown

| # | Title | Type | Depends on |
| - | ----- | ---- | ---------- |
| 1 | Prepare gift storage for gifts with several recipients | Chore | None |
| 2 | Add gifts from a contact page and list them in a Gifts module | Feature | 1 |
| 3 | Edit a gift from the Gifts module | Feature | 2 |
| 4 | Delete a gift from the Gifts module | Feature | 2 |
| 5 | Fill in the given date when the user picks the last gift state | Feature | 2, 3 |
| 6 | Give existing accounts the Gifts module on their contact templates | Feature | 2 |
| 7 | Show open gifts on a vault Gifts page, grouped by gift occasion | Feature | 2 |
| 8 | Let vault managers hide the Gifts tab | Feature | 7 |
| 9 | Keep gifts correct when a user deletes a recipient contact | Feature | 2, 7 |
| 10 | Keep gifts in the right vault when a contact moves to another vault | Feature | 2, 7 |

Tickets 3, 4, 6, 7, 9, and 10 can run in parallel once ticket 2 ships (9 and 10 also need 7).

---

## 1. Prepare gift storage for gifts with several recipients

**Type:** Chore
**Initiative:** Gift tracking
**Depends on:** None

### Summary
Replace the unused gift storage so that one gift belongs to one vault and has one or more recipient contacts.

### Why now
Every other gift ticket depends on this work. The current gift storage allows only one recipient per gift. Its link between gifts and contacts is also a faulty copy of the loans design. No code has ever written to this storage, so the team can replace it without moving user data. The spec's Technical notes contain a suggested design; the team makes the final choice.

Open question carried from the spec (🟡, owner: Engineering): a self-hosted install may hold gift rows that someone entered by hand or by a script. The working assumption is that none exist. If the team finds a reason to doubt this, raise it before release.

### Definition of done
- [ ] One gift can link to one or more contacts. All of them belong to the gift's vault.
- [ ] A gift can hold every field in the spec: name, gift occasion, gift state, estimated price with currency, link (up to 2,048 characters), note, and given date.
- [ ] When a user deletes a vault, Monica also deletes that vault's gifts.
- [ ] When an account deletes a gift occasion, gift state, or currency in Settings, its gifts remain. That field on those gifts becomes empty.
- [ ] A fresh install runs the release's migrations without errors on MySQL/MariaDB, PostgreSQL, and SQLite.
- [ ] An install that already ran earlier releases upgrades without errors and without manual steps, on the same three databases.
- [ ] Users see no change in the app after this ticket ships.

---

## 2. Add gifts from a contact page and list them in a Gifts module

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 1

### Summary
Users with editor permission can add a gift from a contact's page and see that contact's gifts in a new Gifts module.

### Job to be done
When a gift idea for someone comes to mind, a deliberate rememberer needs to record it on that person's page, so they can find it before the next birthday or holiday.

### Navigation context
**Entry point(s):** Vault → Contacts → a contact's page → Gifts module. In new accounts, the module sits on the "Information" page of the contact template, right after the Loans module.
**Exit point(s):** After saving, the user stays on the same contact page. The new gift appears in the Gifts module.
**Screen/component:** The Gifts module on the contact page and its add-gift form.

### Context
Monica has no place to record gifts, and users name gifts as one of the main missing features. Gifts give users a reason to return before birthdays and holidays. This supports the retention goal in the product strategy. The gift occasions and gift states that accounts already manage in Settings → Personalize feed this form.

The Gifts module lists gifts in two sections. "Ideas" holds open gifts. "Given" holds given gifts. One gift with several recipients is one gift: it appears on every recipient's page.

### Designs
No new designs needed. Reuse the layout of the Loans module.

### Acceptance criteria

**Scenario: New accounts show the Gifts module after Loans**
Given a user has just created a new account and a contact named Anna
When the user opens the "Information" page of Anna's contact page
Then the Gifts module appears directly after the Loans module
And the Gifts module shows empty "Ideas" and "Given" sections

**Scenario: The add-gift form offers every gift field**
Given a user with editor permission is on Anna's contact page
When the user clicks the button to add a gift in the Gifts module
Then the form shows these fields: name, recipients, gift occasion, gift state, estimated price with currency, link, note, and given date
And the recipients field already contains Anna
And the gift occasion list shows the account's gift occasions in the order set in Settings
And the gift state list shows the account's gift states in the order set in Settings
And the currency list shows only the account's active currencies

**Scenario: Add one gift for two recipients**
Given a user with editor permission is on Anna's contact page
And the vault also contains the contact Ben
When the user saves a gift named "Cookbook" with recipients Anna and Ben
Then "Cookbook" appears in the "Ideas" section of Anna's Gifts module, with the text "Also for: Ben"
And when the user opens Ben's contact page, "Cookbook" appears once in the "Ideas" section, with the text "Also for: Anna"

**Scenario: A gift row shows its details**
Given a user is on Anna's contact page
And Anna has an open gift "Lamp" with gift occasion Christmas, gift state Found, and an estimated price of 40 EUR
When the user looks at the Gifts module
Then the "Lamp" row shows Christmas, Found, and the price formatted as 40 EUR in the user's number format

**Scenario: Ideas and Given sections follow their sort order**
Given Anna has open gifts "Mug" (created 1 October 2026) and "Book" (created 10 October 2026)
And Anna has given gifts "Scarf" (given 10 December 2025) and "Pen" (given 1 March 2026)
When the user opens Anna's contact page
Then the "Ideas" section lists "Book" above "Mug"
And the "Given" section lists "Pen" above "Scarf"

**Scenario: A gift saved with a given date goes straight to Given**
Given a user with editor permission is on Anna's add-gift form
When the user saves a gift named "Watch" with the given date 1 September 2026
Then "Watch" appears in the "Given" section of Anna's Gifts module
And "Watch" does not appear in the "Ideas" section

**Scenario: Saving without a name shows an error**
Given a user with editor permission is on Anna's add-gift form
When the user saves the form with the name field empty
Then the form shows an error that the name is required
And no new gift appears in Anna's Gifts module

**Scenario: Saving without any recipient shows an error**
Given a user with editor permission is on Anna's add-gift form
And the user has removed Anna from the recipients field
When the user saves the form
Then the form shows an error that the gift needs at least one recipient
And no new gift appears in Anna's Gifts module

**Scenario: An invalid link shows an error**
Given a user with editor permission is on Anna's add-gift form
When the user saves the form with "not a link" in the link field
Then the form shows an error that the link must be a valid web address
And no new gift appears in Anna's Gifts module

**Scenario: The recipients field offers only contacts from this vault**
Given the user's account has two vaults, "Family" and "Work"
And the user is on the add-gift form on a contact page in "Family"
When the user searches the recipients field for a contact that exists only in "Work"
Then the recipients field shows no match for that contact

**Scenario: A viewer sees gifts but cannot add them**
Given a user with viewer permission is on Anna's contact page
And Anna has an open gift "Cookbook"
When the user looks at the Gifts module
Then the user sees "Cookbook" in the "Ideas" section
And the user sees no button to add a gift

### Out of scope
- Editing a gift (ticket 3) and deleting a gift (ticket 4).
- Filling in the given date from the last gift state (ticket 5).
- The Gifts module in accounts that existed before this release (ticket 6).
- The vault Gifts page (ticket 7).
- Gifts the user receives, private gifts, reminders, totals or budgets, photos, the API, DAV export, AI suggestions, search results, and contact feed entries for gifts.

---

## 3. Edit a gift from the Gifts module

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2

### Summary
Users with editor permission can edit any field of a gift from any recipient's Gifts module.

### Job to be done
When a deliberate rememberer buys or gives a gift, they need to update it in Monica, so they can see what is still open and what they already gave.

### Navigation context
**Entry point(s):** Vault → Contacts → a recipient's contact page → Gifts module → the edit action on a gift row.
**Exit point(s):** After saving, the user stays on the same contact page. The Gifts module shows the updated gift.
**Screen/component:** The Gifts module on the contact page and its edit-gift form.

### Context
Users record ideas first and then progress them: they find, buy, and give the gift. Setting the given date marks a gift as given. Because a gift with several recipients is one gift, an edit on one recipient's page must show on every recipient's page.

### Designs
No new designs needed. Reuse the edit pattern of the Loans module.

### Acceptance criteria

**Scenario: An edit shows on every recipient's page**
Given a user with editor permission is on Anna's contact page
And the gift "Cookbook" has recipients Anna and Ben
When the user renames "Cookbook" to "Italian cookbook" and saves
Then Anna's Gifts module shows "Italian cookbook"
And when the user opens Ben's contact page, its Gifts module shows "Italian cookbook"

**Scenario: Setting a given date moves the gift to Given**
Given a user with editor permission is on Anna's contact page
And Anna has an open gift "Cookbook" and a given gift "Scarf" with the given date 10 December 2025
When the user edits "Cookbook", sets the given date to 15 October 2026, and saves
Then Anna's Gifts module shows "Cookbook" in the "Given" section, above "Scarf"
And "Cookbook" no longer appears in the "Ideas" section

**Scenario: Clearing the given date moves the gift back to Ideas**
Given a user with editor permission is on Anna's contact page
And Anna has a given gift "Scarf"
When the user edits "Scarf", clears the given date, and saves
Then "Scarf" appears in the "Ideas" section
And "Scarf" no longer appears in the "Given" section

**Scenario: Removing the name shows an error and keeps the old gift**
Given a user with editor permission is on the edit form for Anna's gift "Cookbook"
When the user clears the name and saves
Then the form shows an error that the name is required
And Anna's Gifts module still shows "Cookbook" with its earlier values

**Scenario: Removing every recipient shows an error**
Given a user with editor permission is on the edit form for Anna's gift "Cookbook"
When the user removes every recipient and saves
Then the form shows an error that the gift needs at least one recipient
And Anna's Gifts module still shows "Cookbook"

**Scenario: Removing the current contact as a recipient**
Given a user with editor permission is on Anna's contact page
And the gift "Cookbook" has recipients Anna and Ben
When the user edits "Cookbook", removes Anna from the recipients, and saves
Then "Cookbook" no longer appears in Anna's Gifts module
And when the user opens Ben's contact page, "Cookbook" appears there with no "Also for" text

**Scenario: A gift whose gift state was deleted in Settings**
Given Anna has a gift "Lamp" with the gift state "Searched"
And an account administrator has deleted the gift state "Searched" in Settings → Personalize → Gift states
When a user opens Anna's contact page
Then "Lamp" still appears in the Gifts module with no gift state
And the edit form for "Lamp" shows the gift state field empty

**Scenario: A viewer cannot edit a gift**
Given a user with viewer permission is on Anna's contact page
And Anna has an open gift "Cookbook"
When the user looks at the "Cookbook" row
Then the user sees no edit action

### Out of scope
- Filling in the given date from the last gift state (ticket 5).
- Editing gifts from the vault Gifts page.
- Deleting a gift (ticket 4).

---

## 4. Delete a gift from the Gifts module

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2

### Summary
Users with editor permission can delete a gift from any recipient's Gifts module.

### Job to be done
When a deliberate rememberer drops a gift idea or added one by mistake, they need to remove it, so their list stays accurate.

### Navigation context
**Entry point(s):** Vault → Contacts → a recipient's contact page → Gifts module → the delete action on a gift row.
**Exit point(s):** The user stays on the same contact page. The gift no longer appears in the Gifts module.
**Screen/component:** The Gifts module on the contact page.

### Context
Without delete, the "Ideas" section fills with ideas the user no longer wants. Deleting a gift removes it for every recipient, because a gift with several recipients is one gift.

### Designs
No new designs needed. Reuse the delete pattern of the Loans module, including its confirmation step.

### Acceptance criteria

**Scenario: Deleting a gift removes it for every recipient**
Given a user with editor permission is on Anna's contact page
And the gift "Cookbook" has recipients Anna and Ben
When the user deletes "Cookbook" and confirms
Then "Cookbook" no longer appears in Anna's Gifts module
And when the user opens Ben's contact page, "Cookbook" does not appear there

**Scenario: Cancelling the delete keeps the gift**
Given a user with editor permission is on Anna's contact page
And Anna has an open gift "Cookbook"
When the user starts to delete "Cookbook" and cancels the confirmation
Then "Cookbook" still appears in Anna's Gifts module

**Scenario: A viewer cannot delete a gift**
Given a user with viewer permission is on Anna's contact page
And Anna has an open gift "Cookbook"
When the user looks at the "Cookbook" row
Then the user sees no delete action

### Out of scope
- Restoring a deleted gift.
- Deleting gifts from the vault Gifts page.
- What happens to gifts when a user deletes a contact (ticket 9).

---

## 5. Fill in the given date when the user picks the last gift state

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2, 3

### Summary
The gift form fills in today's date as the given date when the user picks the account's last gift state and the given date is empty.

### Job to be done
When a deliberate rememberer marks a gift as offered, they need it to count as given without an extra step, so their "Ideas" list stays accurate.

### Navigation context
**Entry point(s):** Vault → Contacts → a contact's page → Gifts module → the add-gift form or the edit-gift form.
**Exit point(s):** The user stays in the form. After saving, the user stays on the contact page.
**Screen/component:** The add-gift and edit-gift forms in the Gifts module.

### Context
The given date, not the gift state, decides whether a gift is open or given. Users can rename, reorder, and delete gift states, so no single state name can mean "given". This behaviour keeps the two in step for most users. "Last" means the gift state with the highest position in Settings, whatever its name. The spec names this the riskiest assumption; the PM plans to check it with 3–5 users after release.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: Picking the last gift state fills today's date**
Given the account's gift states are, in order, Idea, Searched, Found, Bought, Offered
And a user with editor permission is on the add-gift form on Anna's contact page, with the given date empty
When the user picks the gift state "Offered"
Then the given date field shows today's date

**Scenario: The rule follows position, not name**
Given an account administrator has reordered the gift states so that "Bought" is last
And a user with editor permission is on the edit form for an open gift, with the given date empty
When the user picks the gift state "Bought"
Then the given date field shows today's date

**Scenario: An existing given date stays unchanged**
Given a user with editor permission is on the edit form for a gift with the given date 1 March 2026
When the user picks the last gift state
Then the given date field still shows 1 March 2026

**Scenario: Picking another gift state leaves the date empty**
Given a user with editor permission is on the add-gift form, with the given date empty
When the user picks a gift state that is not the last one
Then the given date field stays empty

**Scenario: The user clears the filled date before saving**
Given a user with editor permission is on Anna's add-gift form
And the form has filled today's date after the user picked the last gift state
When the user clears the given date and saves the gift as "Cookbook"
Then "Cookbook" appears in the "Ideas" section of Anna's Gifts module, showing the last gift state

### Out of scope
- Clearing the given date when the user later picks a different gift state. The spec does not ask for this.
- Changing the gift state when the user sets a given date.

---

## 6. Give existing accounts the Gifts module on their contact templates

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2

### Summary
Accounts that existed before this release get the Gifts module on every contact template, with no setup by users.

### Job to be done
When an existing hosted or self-hosted user upgrades, a deliberate rememberer needs the Gifts module to be already on their contact pages, so they can start recording gifts without changing settings.

### Navigation context
**Entry point(s):** Vault → Contacts → a contact's page, in an account created before this release. Also Settings → Templates, where administrators manage contact templates.
**Exit point(s):** The user stays on the contact page or template page and sees the Gifts module there.
**Screen/component:** Contact pages and the template editor in Settings.

### Context
Ticket 2 adds the Gifts module only to new accounts. Most hosted users have existing accounts, and they must get the feature without setup. Existing accounts may have changed their templates, so the module goes next to Loans where possible.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: The module appears on the template page that holds Loans**
Given an account created before this release has a contact template with the Loans module on its "Information" page
When a user opens a contact page that uses that template, after the upgrade
Then the Gifts module appears on the "Information" page, on the same page as the Loans module

**Scenario: Every template in the account gets the module**
Given an account created before this release has three contact templates
When an account administrator opens each template in Settings → Templates, after the upgrade
Then each template shows the Gifts module on one of its pages

**Scenario: A template without Loans gets the module on its last page**
Given an account created before this release has a contact template with no Loans module on any page
When an account administrator opens that template in Settings → Templates, after the upgrade
Then the Gifts module appears on the template's last page, by position

**Scenario: A template with no pages does not block the upgrade**
Given an account created before this release has a contact template with no pages
When the account administrator opens Settings → Templates, after the upgrade
Then the template still shows no pages and no error
And the administrator can add the Gifts module to that template once it has a page

### Out of scope
- Moving the Gifts module for accounts that later rearrange their templates.
- The Gifts tab in existing vaults (ticket 7 covers it).

---

## 7. Show open gifts on a vault Gifts page, grouped by gift occasion

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2

### Summary
Every vault gets a Gifts tab that opens a read-only page of all open gifts, grouped by gift occasion.

### Job to be done
When a holiday or birthday season comes, a deliberate rememberer needs to see every open gift idea in one place, so they can plan without opening each contact.

### Navigation context
**Entry point(s):** The vault's main navigation → "Gifts" tab. This works in every vault, new and existing.
**Exit point(s):** Clicking a recipient's name opens that contact's page. The user edits gifts there.
**Screen/component:** The vault Gifts page.

### Context
Gift ideas are spread across contacts. A single list lets users plan before an occasion and look across contacts. The page is read-only to keep this version small. Any user who can see the vault can open it, including viewers.

### Designs
No new designs needed. Reuse the layout of the vault Tasks page.

### Acceptance criteria

**Scenario: Open gifts appear grouped by gift occasion**
Given the account's gift occasions are, in order, Birthday, Anniversary, Christmas, Just because, Wedding
And the vault has open gifts "Book" (Birthday), "Lamp" (Christmas), and "Mug" (no gift occasion)
And the vault has a given gift "Pen" (Birthday)
When the user opens the vault Gifts page
Then the page shows three groups in this order: Birthday with "Book", Christmas with "Lamp", No occasion with "Mug"
And the page does not show "Pen"
And the page shows no Anniversary, Just because, or Wedding group

**Scenario: Each gift row shows its details and links to recipients**
Given the vault has an open gift "Cookbook" for Anna and Ben, with gift state Found and an estimated price of 30 EUR
When the user opens the vault Gifts page
Then "Cookbook" appears exactly once
And its row shows the name, the recipients Anna and Ben, the gift state Found, and the price 30 EUR
And each recipient name links to that contact's page

**Scenario: Gifts in a group are newest first**
Given the vault has two open Birthday gifts: "Mug" (created 1 October 2026) and "Book" (created 10 October 2026)
When the user opens the vault Gifts page
Then the Birthday group lists "Book" above "Mug"

**Scenario: A given gift leaves the page**
Given the vault has an open gift "Cookbook"
And a user has set the given date of "Cookbook" on Anna's contact page
When the user opens the vault Gifts page
Then "Cookbook" does not appear

**Scenario: Empty state when there are no open gifts**
Given a vault with no open gifts
When the user opens the vault Gifts page
Then the page shows a message that explains how to add a gift from a contact page
And the page shows no groups

**Scenario: A gift whose gift occasion was deleted moves to No occasion**
Given the vault has an open gift "Lamp" with the gift occasion Christmas
And an account administrator has deleted Christmas in Settings → Personalize → Gift occasions
When the user opens the vault Gifts page
Then "Lamp" appears in the "No occasion" group

**Scenario: The page is read-only**
Given a user with editor permission is on the vault Gifts page
When the user looks at any gift row
Then the user sees no add, edit, or delete action

**Scenario: A viewer can open the page**
Given a user with viewer permission is in a vault with open gifts
When the user clicks the "Gifts" tab in the vault's main navigation
Then the vault Gifts page opens and shows the open gifts

**Scenario: Existing vaults show the tab after the upgrade**
Given a vault created before this release
When a user opens that vault after the upgrade
Then the vault's main navigation shows the "Gifts" tab

### Out of scope
- Editing, adding, or deleting gifts from this page.
- Sorting by upcoming dates, such as the next birthday (Phase 2).
- Filtering by gift occasion.
- Showing given gifts on this page.
- Hiding the tab (ticket 8).

---

## 8. Let vault managers hide the Gifts tab

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 7

### Summary
Vault managers can turn the Gifts tab on or off in vault settings, like the Tasks and Journals tabs.

### Job to be done
When a vault does not use gifts, a vault manager needs to hide the Gifts tab, so the vault's navigation shows only what its users need.

### Navigation context
**Entry point(s):** Vault → vault settings → tab visibility section.
**Exit point(s):** The user stays on vault settings. The vault's main navigation reflects the change.
**Screen/component:** The tab visibility section of vault settings.

### Context
Vault managers can already hide other tabs, such as Tasks and Journals. The Gifts tab must work the same way. The switch is on by default, including in vaults that existed before this release.

### Designs
No new designs needed. Reuse the existing tab visibility switches.

### Acceptance criteria

**Scenario: The Gifts switch exists and is on in existing vaults**
Given a vault manager is in a vault created before this release
When the manager opens the tab visibility section of vault settings
Then the section shows a Gifts switch
And the Gifts switch is on

**Scenario: Turning the switch off hides the tab**
Given a vault manager is on the tab visibility section of vault settings
When the manager turns the Gifts switch off
Then the vault's main navigation no longer shows the "Gifts" tab

**Scenario: Turning the switch back on shows the tab**
Given a vault manager is on the tab visibility section of vault settings, with the Gifts switch off
When the manager turns the Gifts switch on
Then the vault's main navigation shows the "Gifts" tab again

**Scenario: Hiding the tab leaves gifts on contact pages**
Given a vault manager has turned the Gifts switch off
When a user opens Anna's contact page in that vault
Then the Gifts module still shows Anna's gifts

**Scenario: Only managers can change the switch**
Given a user with editor permission is in the vault
When the user tries to open vault settings
Then Monica does not let the user change the Gifts switch, as for the other tab switches

### Out of scope
- Hiding the Gifts module on contact pages. Account administrators control modules through contact templates.
- Blocking access to the vault Gifts page by its web address when the tab is hidden.

---

## 9. Keep gifts correct when a user deletes a recipient contact

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2, 7

### Summary
When a user deletes a contact, gifts shared with other recipients stay for them, and gifts with no recipients left disappear.

### Job to be done
When a deliberate rememberer deletes a contact, they need shared gift ideas to remain for the other people, so they do not lose plans for those people.

### Navigation context
**Entry point(s):** Vault → Contacts → a contact's page → delete contact.
**Exit point(s):** After deleting, the user returns to the vault's contact list, as today. The effect shows on the other recipients' contact pages and the vault Gifts page.
**Screen/component:** The contact delete flow, the Gifts module on other recipients' pages, and the vault Gifts page.

### Context
A gift can have several recipients. Deleting one recipient must not delete a gift that still has others. A gift with no recipients left has no meaning, so it must go. Monica cannot restore deleted contacts, so these links never need to come back.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: A shared gift stays for the other recipient**
Given the vault has an open gift "Wedding present" with recipients Anna and Ben
When a user with editor permission deletes the contact Anna
Then Ben's contact page shows "Wedding present" with no "Also for" text
And the vault Gifts page shows "Wedding present" with Ben as its only recipient

**Scenario: A gift with no recipients left disappears**
Given the vault has an open gift "Book" with Anna as its only recipient
When a user with editor permission deletes the contact Anna
Then the vault Gifts page does not show "Book"

**Scenario: The deleted contact's name never appears on gifts**
Given the vault has open gifts shared between Anna and other contacts
When a user deletes the contact Anna and then opens the vault Gifts page
Then no gift on the page lists Anna as a recipient

### Out of scope
- Restoring a deleted contact or its gifts.
- Warning the user about gifts before deleting a contact.

---

## 10. Keep gifts in the right vault when a contact moves to another vault

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2, 7

### Summary
When a user moves a contact to another vault, gifts follow the rule that a gift and all its recipients stay in one vault.

### Job to be done
When a deliberate rememberer moves a contact to another vault, they need that person's own gift ideas to come along, so they still find them in the new vault.

### Navigation context
**Entry point(s):** Vault → Contacts → a contact's page → move the contact to another vault.
**Exit point(s):** After the move, the user sees the contact in the destination vault, as today. The effect shows on contact pages and vault Gifts pages in both vaults.
**Screen/component:** The contact move flow, the Gifts module, and the vault Gifts page in both vaults.

### Context
Gifts must never cross vaults, because each vault can have different users. A gift for only the moving contact belongs with that contact. A gift shared with others stays where the others are. Gift occasions and gift states belong to the account, so they stay valid after a move.

### Designs
No designs needed.

### Acceptance criteria

**Scenario: A gift for only the moving contact moves with them**
Given the vault "Family" has an open gift "Book" with Anna as its only recipient, and gift occasion Birthday
When a user moves Anna to the vault "Work"
Then Anna's contact page in "Work" shows "Book" in the "Ideas" section, still with gift occasion Birthday
And the "Work" vault Gifts page shows "Book" in the Birthday group
And the "Family" vault Gifts page does not show "Book"

**Scenario: A shared gift stays in the old vault without the moving contact**
Given the vault "Family" has an open gift "Wedding present" with recipients Anna and Ben
When a user moves Anna to the vault "Work"
Then Ben's contact page in "Family" shows "Wedding present" with no "Also for" text
And Anna's contact page in "Work" does not show "Wedding present"
And the "Work" vault Gifts page does not show "Wedding present"

**Scenario: Given gifts move with the contact too**
Given the vault "Family" has a given gift "Scarf" with Anna as its only recipient
When a user moves Anna to the vault "Work"
Then Anna's contact page in "Work" shows "Scarf" in the "Given" section

### Out of scope
- Copying shared gifts into the destination vault.
- Asking the user what to do with gifts during the move.
