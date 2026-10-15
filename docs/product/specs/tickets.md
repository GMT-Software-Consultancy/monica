# Gift tracking — Tickets

Generated: 15 October 2026
Spec: output/spec.md
Tracker: none (this project keeps tickets in this file only)
Initiative: Gift tracking

## Terms used in these tickets

- **Vault viewer, vault editor, vault manager:** the three permission levels a user can have in a vault. A viewer can only look. An editor can also add, change and delete data. A manager can also change vault settings.
- **Account administrator:** a user who can change account-wide Settings, including gift stages, gift occasions and contact page templates.
- **Gift stages:** the account's ordered list of steps a gift moves through. The defaults are Idea, Searched, Found, Bought and Offered.
- **Gift occasions:** the account's ordered list of reasons for a gift. The defaults are Birthday, Anniversary, Christmas, Just because and Wedding.
- **Final stage:** the last stage in the Gift stages list in Settings. With the default stages, this is "Offered".
- **Open gift / given gift:** a gift is **given** when it has a given date. A gift is **open** when it has no given date.
- **Module:** a section of the contact page, such as Loans or Goals. A **template** decides which modules appear on which **template page** (tab) of a contact.
- **Contact history:** the list of recent activity on a contact page.

## Ticket order

| #   | Title                                                     | Type    | Depends on |
| --- | --------------------------------------------------------- | ------- | ---------- |
| 1   | Prepare gift data storage for the Phase 1 gift fields     | Chore   | None       |
| 2   | Record a gift idea from a contact page                    | Feature | 1          |
| 3   | Edit and delete a gift from a contact page                | Feature | 2          |
| 4   | Mark a gift as given by moving it to the final stage      | Feature | 3          |
| 5   | Show gift activity in the contact history                 | Feature | 2, 4       |
| 6   | Keep gifts correct when a recipient contact is deleted    | Feature | 2          |
| 7   | List all open gifts on a vault Gifts page                 | Feature | 2, 4       |
| 8   | Let vault managers show or hide the vault Gifts page      | Feature | 7          |
| 9   | Add the Gifts module to existing accounts automatically   | Feature | 2          |

---

## 1. Prepare gift data storage for the Phase 1 gift fields

**Type:** Chore
**Initiative:** Gift tracking
**Depends on:** None

### Summary

Make Monica able to store every Phase 1 gift field, with one or more recipient contacts per gift.

### Why now

Every other ticket in this set needs a place to keep gifts. Monica already has gift storage from an earlier, unfinished design. No part of the app writes to it, and it does not match the Phase 1 fields. The spec's riskiest assumption is that this storage is empty on every instance, hosted and self-hosted. This ticket must not lose user data if that assumption is wrong.

### Definition of done

- [ ] Monica can store a gift with these fields: name, note, link, rough price with a currency, gift occasion, gift stage and given date.
- [ ] Monica can link one gift to one or more contacts in the same vault.
- [ ] Deleting a vault also deletes every gift in that vault.
- [ ] Deleting a gift occasion, a gift stage or a currency keeps each affected gift. Only that one field on the gift becomes empty.
- [ ] The fields from the earlier design that Phase 1 does not use (gift type, received date and bought date) no longer exist.
- [ ] On an instance with no existing gift data, the upgrade finishes with no errors and needs no manual steps.
- [ ] On an instance that already holds gift data, the upgrade stops before it changes that data. It shows a message that explains why it stopped.
- [ ] Users see no change in the app after this ticket ships.
- [ ] The existing automated tests pass.

---

## 2. Record a gift idea from a contact page

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 1

### Summary

A vault editor can add a gift for one or more contacts from a new Gifts module on the contact page, and every vault user can see it.

### Job to be done

When a gift idea comes to mind for someone, the deliberate rememberer needs to record it on that person's contact page, so they can find it before the next birthday or holiday.

### Navigation context

**Entry point(s):** Vault → Contacts → a contact → the template page that holds the Gifts module. In a new account's default template, this is the "Information" page, where Gifts sits directly after Loans.
**Exit point(s):** The user stays on the same contact page. The new gift appears under "Open ideas" in the Gifts module.
**Screen/component:** The Gifts module on the contact page, and its add-gift form.

### Context

Users who move from the old Monica lose gift tracking, and the app's beta page names it as a top missing feature. Today, users keep gift ideas in notes or another app, with no stage or occasion. This ticket delivers the first useful step: record an idea and see it later. The year-end holidays start in about 10 weeks.

A gift has these fields:

- Name (what the gift is). Required.
- Recipients: one or more contacts in the same vault. Required. The contact whose page the user is on is a recipient by default.
- Occasion, from the account's gift occasions. Optional.
- Stage, from the account's gift stages. New gifts default to the first stage.
- Rough price, with a currency from the account's active currencies. Optional.
- Link: a web address the user pastes in. Optional.
- Note: free text. Optional.

### Designs

No designs needed. Reuse the layout and the add/edit form pattern of the Loans module on the contact page. Ask the PM before building any new pattern.

### Acceptance criteria

**Scenario: Vault editor adds a gift for two recipients**
Given a vault editor is on Anna's contact page, and Anna and Ben are contacts in the same vault
When they add a gift named "Cookbook" with recipients Anna and Ben, occasion "Wedding" and no stage chosen
Then "Cookbook" appears under "Open ideas" in the Gifts module on Anna's contact page, with the stage "Idea"
And "Cookbook" also appears under "Open ideas" on Ben's contact page, with the stage "Idea".

**Scenario: Vault editor adds a gift with every optional field**
Given a vault editor is on Anna's contact page, and the account has Euro as an active currency
When they add a gift named "Scarf" with occasion "Birthday", stage "Found", price 40 EUR, a link and a note
Then "Scarf" appears under "Open ideas" with the occasion "Birthday" and the stage "Found"
And it shows the price in the same format the Loans module uses for amounts
And it shows the link and the note exactly as the editor entered them.

**Scenario: Saving a gift with no name shows an error**
Given a vault editor has the add-gift form open on Anna's contact page
When they save the form with the name field empty
Then the form shows an error that says a name is required
And no new gift appears in the Gifts module.

**Scenario: Saving a gift with no recipients shows an error**
Given a vault editor has the add-gift form open on Anna's contact page
When they remove every recipient and save the form
Then the form shows an error that says at least one recipient is required
And no new gift appears in the Gifts module.

**Scenario: Vault viewer sees gifts but cannot add them**
Given a vault viewer is on Anna's contact page, and Anna has the open gift "Cookbook"
When the page loads
Then they see "Cookbook" in the Gifts module
And they see no control to add a gift.

**Scenario: Vault viewer cannot add a gift by sending a request directly**
Given a vault viewer is signed in to the vault that holds Anna's contact
When they send a request to create a gift for Anna directly, without the page
Then Monica answers with HTTP 403 (forbidden)
And no new gift appears in Anna's Gifts module when the page reloads.

**Scenario: Only contacts from the same vault can be recipients**
Given a vault editor has the add-gift form open on Anna's contact page, and Carla is a contact in a different vault
When they search the recipient list for "Carla"
Then Carla is not offered as a recipient.

**Scenario: A request that names a recipient from another vault is refused**
Given a vault editor is signed in to the vault that holds Anna's contact, and Carla is a contact in a different vault
When they send a request directly to create a gift with recipients Anna and Carla
Then Monica refuses the request
And no new gift appears on Anna's contact page.

**Scenario: New accounts get the Gifts module in the default template**
Given a user has just created a new account and a vault
When they open the "Information" page of any contact in that vault
Then the Gifts module appears directly after the Loans module.

**Scenario: Account administrator adds the Gifts module to a template**
Given an account administrator is on a template page in Settings → Templates, and the page does not hold the Gifts module
When they add the Gifts module to that template page
Then the Gifts module appears on that page for every contact that uses the template.

**Scenario: Gifts module works on a phone screen**
Given a vault editor is on Anna's contact page on a phone-sized screen
When they add a gift named "Cookbook"
Then they can complete the add-gift form without scrolling sideways
And "Cookbook" appears under "Open ideas" without scrolling sideways.

### Out of scope

- Editing and deleting a gift (ticket 3).
- Marking a gift as given and the "Past gifts" list (ticket 4).
- Contact history entries (ticket 5).
- The vault Gifts page (ticket 7).
- Adding the module to templates in existing accounts automatically (ticket 9).
- Gifts the user receives, private gifts, recipients who are not vault contacts, photos, budgets and totals.
- Shop links, product lookups or previews. The link is plain text that the user pastes in.
- Search results, the public API, import from the old Monica, and CardDAV/CalDAV sync.

---

## 3. Edit and delete a gift from a contact page

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2

### Summary

A vault editor can change any field of a gift, including its recipients, and can delete a gift, from the Gifts module on a contact page.

### Job to be done

When a gift idea changes or is no longer wanted, the deliberate rememberer needs to update or remove it, so they can trust that their gift list is current.

### Navigation context

**Entry point(s):** Vault → Contacts → a contact → the template page that holds the Gifts module → a gift in the module.
**Exit point(s):** The user stays on the same contact page. The Gifts module shows the updated gift, or no longer shows a deleted gift.
**Screen/component:** The Gifts module on the contact page, and its edit-gift form.

### Context

Gift ideas change. A price changes, a second person joins a shared present, or the user drops an idea. Without edit and delete, the gift list goes out of date and users stop trusting it. Changes apply to the gift itself, so every recipient's contact page shows the same gift.

### Designs

No designs needed. Reuse the edit and delete pattern of the Loans module on the contact page.

### Acceptance criteria

**Scenario: Vault editor changes the details of a gift**
Given a vault editor is on Anna's contact page, and "Cookbook" is an open gift for Anna and Ben
When they change the price of "Cookbook" to 30 EUR and the occasion to "Christmas", and save
Then "Cookbook" shows the price 30 EUR and the occasion "Christmas" on Anna's contact page
And Ben's contact page shows the same new price and occasion.

**Scenario: Vault editor adds a recipient to an existing gift**
Given a vault editor is on Anna's contact page, and "Cookbook" is a gift for Anna only
When they add Ben as a recipient of "Cookbook" and save
Then "Cookbook" appears in the Gifts module on Ben's contact page.

**Scenario: Vault editor removes one recipient from a gift**
Given a vault editor is on Anna's contact page, and "Cookbook" is a gift for Anna and Ben
When they remove Ben as a recipient of "Cookbook" and save
Then "Cookbook" no longer appears on Ben's contact page
And "Cookbook" still appears on Anna's contact page with Anna as its only recipient.

**Scenario: Vault editor deletes a gift**
Given a vault editor is on Anna's contact page, and "Cookbook" is a gift for Anna and Ben
When they delete "Cookbook"
Then "Cookbook" no longer appears on Anna's contact page
And "Cookbook" no longer appears on Ben's contact page.

**Scenario: Clearing the name shows an error and keeps the old gift**
Given a vault editor has the edit form for "Cookbook" open on Anna's contact page
When they clear the name and save
Then the form shows an error that says a name is required
And the Gifts module still shows "Cookbook" with its earlier details.

**Scenario: Removing every recipient shows an error**
Given a vault editor has the edit form for "Cookbook" open on Anna's contact page
When they remove every recipient and save
Then the form shows an error that says at least one recipient is required
And "Cookbook" still appears with its earlier recipients.

**Scenario: Vault viewer sees no edit or delete controls**
Given a vault viewer is on Anna's contact page, and Anna has the gift "Cookbook"
When the page loads
Then they see "Cookbook"
And they see no control to edit or delete it.

**Scenario: Vault viewer cannot change or delete a gift by sending a request directly**
Given a vault viewer is signed in to the vault that holds the gift "Cookbook"
When they send a request directly to update or delete "Cookbook"
Then Monica answers with HTTP 403 (forbidden)
And "Cookbook" is unchanged when they reload Anna's contact page.

**Scenario: Moving a gift between stages that are not final keeps it open**
Given a vault editor has the edit form for "Cookbook" open, and "Cookbook" is in the stage "Idea"
When they change the stage to "Bought" and save
Then "Cookbook" shows the stage "Bought"
And it stays under "Open ideas".

### Out of scope

- Moving a gift into or out of the final stage, and the given date (ticket 4).
- Contact history entries for edits or deletes. The spec rules these out for Phase 1.
- Undo for a deleted gift.

---

## 4. Mark a gift as given by moving it to the final stage

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 3

### Summary

A vault editor can mark a gift as given by moving it to the final stage, and the Gifts module then lists it under "Past gifts" with its given date.

### Job to be done

When the deliberate rememberer gives a gift, they need to record that it was given and when, so they can check past gifts later and avoid giving the same thing twice.

### Navigation context

**Entry point(s):** Vault → Contacts → a contact → the template page that holds the Gifts module → the edit form of an open gift.
**Exit point(s):** The user stays on the same contact page. The gift moves from "Open ideas" to "Past gifts", or back.
**Screen/component:** The Gifts module on the contact page, its edit-gift form, and the "Past gifts" list.

### Context

Users cannot answer "what did I give Anna last year?" today. The given rule answers it:

- Moving a gift into the final stage sets its given date to today. The editor can change that date to an earlier day, but not to a future day.
- Moving a gift out of the final stage clears the given date, so the gift is open again.
- An account administrator can later reorder, rename or delete stages. Existing gifts keep their given date, so their open or given status does not change.

The Gifts module shows "Open ideas" first, then "Past gifts". "Past gifts" lists given gifts by given date, newest first.

### Designs

No designs needed. Reuse the Loans module pattern for the second list and for the date field.

### Acceptance criteria

**Scenario: Vault editor marks a gift as given today**
Given a vault editor has the edit form open for "Cookbook", an open gift for Anna and Ben
When they change the stage to "Offered" and save without changing the date
Then "Cookbook" appears under "Past gifts" on Anna's and Ben's contact pages, with today's date as its given date
And "Cookbook" no longer appears under "Open ideas" on either page.

**Scenario: Past gifts list the newest given date first**
Given Anna has the given gift "Scarf" with a given date of 12 March 2026
When a vault editor marks "Cookbook" for Anna as given today
Then "Cookbook" appears above "Scarf" under "Past gifts" on Anna's contact page.

**Scenario: Vault editor logs a gift that was given in the past**
Given a vault editor has the edit form open for "Watch", an open gift for Anna
When they change the stage to "Offered", set the given date to 24 December 2025, and save
Then "Watch" appears under "Past gifts" with the given date 24 December 2025.

**Scenario: A given date in the future shows an error**
Given a vault editor has the edit form open for "Cookbook", with the stage set to "Offered"
When they set the given date to tomorrow and save
Then the form shows an error that says the given date cannot be in the future
And "Cookbook" keeps the stage and date it had before they opened the form.

**Scenario: Moving a gift out of the final stage makes it open again**
Given "Cookbook" is a given gift for Anna, in the stage "Offered"
When a vault editor changes its stage to "Bought" and saves
Then "Cookbook" appears under "Open ideas" on Anna's contact page
And it no longer shows a given date.

**Scenario: The final stage follows the account's own stage list**
Given an account administrator has added the stage "Wrapped" at the end of the Gift stages list in Settings
When a vault editor moves the open gift "Cookbook" to the stage "Wrapped" and saves
Then "Cookbook" appears under "Past gifts" with today's date as its given date.

**Scenario: Reordering stages does not change a gift's given or open status**
Given Anna has the given gift "Scarf" in the stage "Offered" and the open gift "Cookbook" in the stage "Bought"
When an account administrator moves "Bought" to the end of the Gift stages list in Settings
Then Anna's contact page still shows "Scarf" under "Past gifts" with its given date
And it still shows "Cookbook" under "Open ideas".

**Scenario: Deleting a gift's stage leaves the gift with "No stage"**
Given Anna has the given gift "Scarf" in the stage "Offered", with a given date of 12 March 2026
When an account administrator deletes the stage "Offered" in Settings
Then Anna's contact page shows "Scarf" under "Past gifts" with the given date 12 March 2026
And "Scarf" shows "No stage".

**Scenario: An account with no gift stages cannot mark gifts as given**
Given an account administrator has deleted every gift stage in Settings
When a vault editor adds the gift "Cookbook" on Anna's contact page
Then "Cookbook" appears under "Open ideas" and shows "No stage"
And the edit form offers no stage, so the editor cannot mark the gift as given.

### Out of scope

- The "gift given" contact history entry (ticket 5).
- Removing given gifts from the vault Gifts page (ticket 7).
- A hint in the Gift stages settings that the last stage means "given". The spec leaves this for later.
- Links between a gift and a specific important date, such as Anna's birthday.

---

## 5. Show gift activity in the contact history

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2, 4

### Summary

Each recipient's contact history shows an entry when someone adds a gift for them and another when someone marks that gift as given.

### Job to be done

When the deliberate rememberer looks back at a contact's recent activity, they need to see gifts that were planned and given, so they can recall the relationship's history in one place.

### Navigation context

**Entry point(s):** Vault → Contacts → a contact → the contact history on the contact page.
**Exit point(s):** The user stays on the contact page. No navigation happens.
**Screen/component:** The contact history on the contact page.

### Context

The contact history is where users already see what happened with a person. Gifts belong there because a gift is a moment in the relationship. The spec limits Phase 1 to two kinds of entry: "gift added" and "gift given". Each recipient of a gift gets their own entry.

### Designs

No designs needed. Use the existing contact history entry style.

### Acceptance criteria

**Scenario: Adding a gift creates a "gift added" entry for each recipient**
Given a vault editor is on Anna's contact page, and Anna and Ben are contacts in the same vault
When they add the gift "Cookbook" with recipients Anna and Ben
Then Anna's contact history shows one "gift added" entry for "Cookbook"
And Ben's contact history shows one "gift added" entry for "Cookbook".

**Scenario: Marking a gift as given creates a "gift given" entry for each recipient**
Given "Cookbook" is an open gift for Anna and Ben
When a vault editor moves "Cookbook" to the final stage and saves
Then Anna's contact history shows one "gift given" entry for "Cookbook"
And Ben's contact history shows one "gift given" entry for "Cookbook".

**Scenario: A failed save creates no history entry**
Given a vault editor has the add-gift form open on Anna's contact page
When they save the form with the name field empty
Then the form shows an error
And Anna's contact history shows no new entry.

**Scenario: Editing or deleting a gift creates no history entry**
Given "Cookbook" is an open gift for Anna
When a vault editor changes its price and saves, then deletes it
Then Anna's contact history shows no new entry for either action.

**Scenario: Changing the given date of a given gift creates no new entry**
Given "Cookbook" is a given gift for Anna, and Anna's contact history has one "gift given" entry for it
When a vault editor changes the given date to an earlier day and saves
Then Anna's contact history still shows exactly one "gift given" entry for "Cookbook".

**Scenario: Marking a reopened gift as given again adds a second entry**
Given "Cookbook" was given, then moved back to the stage "Bought"
When a vault editor moves "Cookbook" to the final stage again and saves
Then Anna's contact history shows a second "gift given" entry for "Cookbook".

### Out of scope

- Entries for editing or deleting a gift. The spec rules these out for Phase 1.
- Gift entries on the vault dashboard, reminders or notifications.

---

## 6. Keep gifts correct when a recipient contact is deleted

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2

### Summary

When a vault editor deletes a contact, Monica removes that contact from each of their gifts and deletes any gift that has no recipients left.

### Job to be done

When the deliberate rememberer deletes a contact, they need that contact's gifts to stay correct for everyone else, so they can trust the gift lists on other contact pages and on the vault Gifts page.

### Navigation context

**Entry point(s):** Vault → Contacts → a contact → the option to delete the contact.
**Exit point(s):** The user lands where Monica sends them today after a contact is deleted. Other recipients' contact pages show the updated gift.
**Screen/component:** Contact deletion, and the Gifts module on the remaining recipients' contact pages.

### Context

A gift can have several recipients, such as a wedding present for a couple. If one recipient is deleted, the gift still matters to the other recipients. A gift with no recipients has no place to appear, so Monica deletes it.

### Designs

No designs needed.

### Acceptance criteria

**Scenario: Deleting one recipient keeps the gift for the others**
Given "Cookbook" is a gift for Anna and Ben
When a vault editor deletes Anna's contact
Then Ben's contact page still shows "Cookbook"
And "Cookbook" shows Ben as its only recipient.

**Scenario: Deleting the last recipient deletes the gift**
Given "Cookbook" is an open gift for Ben only, after Anna's contact was deleted
When a vault editor deletes Ben's contact
Then "Cookbook" no longer appears on the vault Gifts page.

**Scenario: Vault viewer cannot delete a recipient and the gift stays unchanged**
Given a vault viewer is signed in to the vault, and "Cookbook" is a gift for Anna and Ben
When they send a request directly to delete Anna's contact
Then Monica answers with HTTP 403 (forbidden)
And "Cookbook" still shows Anna and Ben as recipients on Ben's contact page.

**Scenario: Deleting a contact leaves other contacts' gifts alone**
Given "Scarf" is a gift for Ben only, and "Cookbook" is a gift for Anna only
When a vault editor deletes Anna's contact
Then Ben's contact page still shows "Scarf" with no change.

### Out of scope

- Restoring a deleted contact's gifts.
- A contact history entry for the change to the gift.

---

## 7. List all open gifts on a vault Gifts page

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2, 4

### Summary

Every vault user can open a Gifts page from the vault's main navigation and see every open gift in the vault, grouped by occasion.

### Job to be done

When a birthday or holiday is coming, the deliberate rememberer needs to see every open gift idea for the vault in one place, so they can decide what to buy without opening each contact.

### Navigation context

**Entry point(s):** The "Gifts" link in the vault's main navigation, next to links such as Tasks, Files and Journal.
**Exit point(s):** The user stays on the Gifts page, or follows a recipient's name to that recipient's contact page.
**Screen/component:** The vault Gifts page.

### Context

Ideas recorded on contact pages are only useful if the user can find them before an occasion. One list per vault answers "what am I still planning to give?". This gives users a reason to open Monica before every birthday and holiday, which supports the strategy goal of bringing users back when their stored memory is useful. Given gifts stay on contact pages only.

### Designs

No designs needed. Reuse the layout of the vault Tasks page. Ask the PM before building any new pattern.

### Acceptance criteria

**Scenario: Open gifts are grouped by occasion in Settings order**
Given the account's gift occasions are Birthday then Christmas in Settings order
And the vault has one open Christmas gift, one open Birthday gift and one open gift with no occasion
When a vault user opens the Gifts page from the vault's main navigation
Then the page shows three groups in this order: "Birthday", "Christmas", "No occasion"
And each group holds its one open gift.

**Scenario: Each gift shows its details and links to its recipients**
Given the vault has the open gift "Cookbook" for Anna and Ben, in the stage "Found", with a price of 30 EUR
When a vault user opens the Gifts page
Then "Cookbook" appears once, with its name, both recipients, the stage "Found" and the price 30 EUR
And each recipient's name links to that recipient's contact page.

**Scenario: Given gifts do not appear**
Given the vault has the open gift "Cookbook" and the given gift "Scarf"
When a vault user opens the Gifts page
Then the page shows "Cookbook"
And the page does not show "Scarf".

**Scenario: A gift disappears from the page once it is given**
Given "Cookbook" appears on the vault Gifts page
When a vault editor moves "Cookbook" to the final stage on Anna's contact page and saves
Then "Cookbook" no longer appears on the vault Gifts page.

**Scenario: A vault with no open gifts shows an empty state**
Given the vault has no open gifts
When a vault user opens the Gifts page
Then the page shows an empty-state message
And the message explains how to add a gift from a contact page.

**Scenario: A user without access to the vault cannot open its Gifts page**
Given a signed-in user has no access to Anna's vault
When they open the address of that vault's Gifts page
Then Monica refuses access
And the user sees none of that vault's gifts.

**Scenario: Vault viewer sees every open gift**
Given a vault viewer is in a vault with three open gifts
When they open the Gifts page
Then they see all three open gifts
And they see no control to add, edit or delete a gift.

**Scenario: A gift whose occasion was deleted moves to "No occasion"**
Given the vault has the open gift "Cookbook" with the occasion "Wedding"
When an account administrator deletes the occasion "Wedding" in Settings
Then the Gifts page shows "Cookbook" in the "No occasion" group.

**Scenario: Groups follow a new occasion order**
Given the vault has open gifts for "Birthday" and "Christmas", in that Settings order
When an account administrator moves "Christmas" above "Birthday" in Settings
Then the Gifts page shows the "Christmas" group before the "Birthday" group.

**Scenario: Gifts page works on a phone screen**
Given a vault user is on a phone-sized screen
When they open the Gifts page
Then they can read every group and every gift without scrolling sideways.

### Out of scope

- The vault setting that shows or hides the Gifts page (ticket 8).
- Sorting by whose birthday comes next, and links to specific important dates. These are Phase 2.
- Adding, editing or deleting gifts from this page.
- Totals, budgets, a dashboard widget, reminders and notifications.

---

## 8. Let vault managers show or hide the vault Gifts page

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 7

### Summary

A vault manager can switch the vault Gifts page on or off in the vault's tab visibility settings, and it is on by default.

### Job to be done

When a vault does not use gifts, the vault manager needs to hide the Gifts page, so they can keep the vault's navigation short.

### Navigation context

**Entry point(s):** Vault → Vault settings → the tab visibility settings, next to the settings for Tasks, Files, Journal and the other vault pages.
**Exit point(s):** The user stays in vault settings. The vault's main navigation shows or hides the "Gifts" link.
**Screen/component:** The tab visibility settings in vault settings, and the vault's main navigation.

### Context

Monica already lets vault managers hide vault pages they do not use, such as Tasks or Files. The Gifts page should behave the same way. The page is on by default, so existing and new vaults show it with no setup. The setting affects only the vault Gifts page, not the Gifts module on contact pages.

### Designs

No designs needed. Add the setting to the existing tab visibility settings, in the same style as the others.

### Acceptance criteria

**Scenario: Vault manager hides the Gifts page**
Given a vault manager is on the tab visibility settings, and the Gifts page is switched on
When they switch the Gifts page off
Then the "Gifts" link no longer appears in that vault's main navigation.

**Scenario: Vault manager shows the Gifts page again**
Given a vault manager is on the tab visibility settings, and the Gifts page is switched off
When they switch the Gifts page on
Then the "Gifts" link appears in that vault's main navigation.

**Scenario: A vault editor cannot change the setting by sending a request directly**
Given a vault editor who is not a vault manager is signed in to the vault
When they send a request directly to switch the Gifts page off
Then Monica answers with HTTP 403 (forbidden)
And the "Gifts" link still appears in the vault's main navigation.

**Scenario: New and existing vaults show the Gifts page by default**
Given a vault that existed before this release, and a vault created after it
When a vault user opens either vault
Then the "Gifts" link appears in that vault's main navigation.

**Scenario: Hiding the Gifts page leaves the Gifts module on contact pages**
Given a vault manager has switched the Gifts page off, and Anna has the gift "Cookbook"
When a vault user opens Anna's contact page
Then the Gifts module still shows "Cookbook".

**Scenario: The setting applies to one vault only**
Given an account has two vaults, and a vault manager has switched the Gifts page off in the first vault
When a vault user opens the second vault
Then the "Gifts" link still appears in the second vault's main navigation.

### Out of scope

- Blocking direct visits to the Gifts page address while the page is hidden. The setting behaves like the other tab visibility settings.
- Hiding the Gifts module on contact pages. Account administrators control that through templates in Settings.

---

## 9. Add the Gifts module to existing accounts automatically

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2

### Summary

When an existing instance upgrades to this release, Monica adds the Gifts module to each template, on the same template page as the Loans module.

### Job to be done

When the deliberate rememberer opens Monica after the release, they need to find gifts on their contacts without changing any settings, so they can start using the feature straight away.

### Navigation context

**Entry point(s):** Vault → Contacts → any contact → the template page that holds the Loans module.
**Exit point(s):** No navigation happens. The Gifts module is visible on that template page.
**Screen/component:** The Gifts module on the contact page, and the template settings in Settings → Templates.

### Context

New accounts get the Gifts module from ticket 2. Accounts created before this release would not see it unless an account administrator added it by hand. Most users never change template settings, so without this ticket most existing users would never find gifts. Account administrators can still remove the module from a template in Settings, like any other module.

### Designs

No designs needed.

### Acceptance criteria

**Scenario: An unchanged default template gets the Gifts module next to Loans**
Given an account and vault created before this release, with the default template unchanged
When the upgrade has run and a vault user opens any contact page in that vault
Then the Gifts module appears on the same template page as the Loans module.

**Scenario: A custom template gets the module where it holds Loans**
Given an account administrator had moved the Loans module to the "Life" template page before this release
When the upgrade has run and a vault user opens a contact that uses that template
Then the Gifts module appears on the "Life" page.

**Scenario: A template without the Loans module gets no Gifts module**
Given an account administrator had removed the Loans module from a template before this release
When the upgrade has run and a vault user opens a contact that uses that template
Then the Gifts module does not appear on any page of that contact
And an account administrator can add the Gifts module to that template in Settings → Templates.

**Scenario: The Gifts module appears only once per template**
Given the upgrade has already run on an instance
When the upgrade runs a second time, for example after a failed deployment
Then each contact page still shows the Gifts module only once.

**Scenario: Account administrator removes the module after the upgrade**
Given the upgrade has added the Gifts module to a template
When an account administrator removes the Gifts module from that template in Settings → Templates
Then contacts that use that template no longer show the Gifts module.

### Out of scope

- The "Gifts" link in the vault's main navigation for existing vaults (ticket 8).
- Importing gifts from the old Monica.
- Changing the position of other modules in existing templates.

---

## Decomposition notes

These calls were made without PM input, because this run was non-interactive. Review them before planning.

- **Initiative.** The spec has no `Initiative:` field. These tickets use "Gift tracking", the spec's title. Add the field to the spec, or rename it here.
- **Personas.** `docs/product/personas.md` does not exist. The job statements use the spec's working persona, the deliberate rememberer.
- **Existing gift data (ticket 1).** The spec leaves one question open: should the upgrade stop if it finds existing gift data? Ticket 1 chooses to stop, because that is the only choice that cannot lose user data. The PM and the maintainers should confirm this before ticket 1 starts.
- **Ticket 9 is a Feature, not a Chore.** It changes the data in existing accounts, but users see the result: the Gifts module appears on their contact pages.
- **Ticket 5, repeated "gift given" entries.** The spec says Monica adds an entry when someone marks a gift as given. Ticket 5 reads this as "every time", so a gift that is reopened and given again gets a second entry.
- **Ticket 2, default recipient.** The add-gift form names the current contact as a recipient by default. The spec implies this but does not say it.
- **Success measure.** The spec's success measure (15% of active hosted vaults with at least one gift, 60 days after release) is a working assumption. No ticket builds the count. Add a ticket if the hosted plan cannot already produce it.
- **Open questions.** The spec's three 🟡 questions all have working assumptions, and the spec states that none blocks the build. The question about existing gift data affects ticket 1, as noted above.
- **Work in progress.** `docs/iterations/current.md` does not exist, so these tickets were not checked against current work.
