# Gift tracking — Tickets

Generated: 15 October 2026
Spec: output/spec.md
Tracker: none (no tickets posted; this file is the final ticket set)
Initiative: Gift tracking (working name — the spec has no `Initiative:` field; see "Decomposition notes")

---

## Terms used in these tickets

- **Vault:** a shared space of contacts and their data. A vault can have several members. Each member has view, edit, or manage permission.
- **Gift:** something the user plans to give, or has given, to one or more contacts.
- **Recipient:** a contact a gift is for. A gift has one or more recipients, all in the same vault.
- **Gift stage:** one item from the gift stages list in Settings. The defaults are Idea, Searched, Found, Bought, and Offered.
- **Given stage:** the last gift stage in the user's ordered list. By default this is "Offered".
- **Open gift:** a gift whose stage is not the given stage.
- **Given gift:** a gift whose stage is the given stage.
- **Gift history:** the list of given gifts on a contact's page.
- **Occasion:** one item from the gift occasions list in Settings. The defaults are Birthday, Anniversary, Christmas, Just because, and Wedding.
- **Deliberate rememberer:** the primary user segment from the product strategy. These people use Monica to remember what matters about the people they care about.

---

## Breakdown

| # | Title | Type | Depends on |
| - | ----- | ---- | ---------- |
| 1 | Record a gift idea on a contact's page | Feature | None |
| 2 | Edit and delete a gift from a contact's page | Feature | 1 |
| 3 | Add occasion, price, link, and note to a gift | Feature | 1 |
| 4 | Move a gift through gift stages and keep a gift history | Feature | 1 |
| 5 | Give one gift to several recipients | Feature | 2, 4 |
| 6 | Keep gifts correct when a user changes gift stages or occasions in Settings | Feature | 3, 4 |
| 7 | See all open gifts in a vault on the Gifts page | Feature | 3, 4, 5 |
| 8 | Switch the vault Gifts page on or off | Feature | 7 |

All tickets belong to the initiative **Gift tracking**.

---

## 1. Record a gift idea on a contact's page

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** None

### Summary
A user can add a gift idea for a contact from that contact's page and see it in a new Gifts section.

### Job to be done
When a deliberate rememberer thinks of a gift for someone, they need to write it down on that person's page, so they can find it when the occasion comes.

### Navigation context
**Entry point(s):** A contact's page, reached from the vault's contact list or from search.
**Exit point(s):** The user stays on the contact's page. The new gift shows in the Gifts section.
**Screen/component:** The Gifts section on the contact page, and the form for adding a gift.

### Context
Monica's beta sign-up page names gift management as one of the two most important missing features. Today users write gift ideas in notes, which have no stage and no shared list. This ticket gives every contact a place for gift ideas. Later tickets add details, stages, and the vault-wide Gifts page.

### Designs
Follow the existing contact page sections, such as Loans. A designer does a light review before build.

### Acceptance criteria

**Scenario: User with edit permission adds a gift with only "what it is"**
Given a user with edit permission is on a contact's page
When they add a gift with only "what it is" filled in and save
Then the gift appears in that contact's Gifts section as an open gift
And its gift stage shows as the first stage in the account's gift stages list
And that contact shows as its only recipient

**Scenario: User saves a gift with "what it is" empty**
Given a user with edit permission is adding a gift on a contact's page
When they save with "what it is" empty
Then Monica does not save the gift
And Monica shows a message next to the "what it is" field that names the missing field

**Scenario: User with view permission cannot add a gift**
Given a user with view permission is on a contact's page in a shared vault
When they look at the Gifts section
Then they see the contact's gifts
And they see no option to add a gift

**Scenario: Existing account sees the Gifts section without setup**
Given an account created before this feature was released
And a user from that account is on a contact's page
When the page loads
Then the user sees the Gifts section
And they did not have to change their contact page templates in Settings

### Out of scope
- Editing and deleting gifts (ticket 2).
- Occasion, price, link, and note (ticket 3).
- Changing the gift stage and the gift history (ticket 4).
- More than one recipient (ticket 5).
- The vault Gifts page (ticket 7).
- Gifts the user receives, reminders, private gifts, and photos.

---

## 2. Edit and delete a gift from a contact's page

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 1

### Summary
A user can change or remove a gift from the Gifts section on a contact's page.

### Job to be done
When a deliberate rememberer's gift idea changes or no longer makes sense, they need to update or remove it, so their list stays accurate.

### Navigation context
**Entry point(s):** The Gifts section on a contact's page.
**Exit point(s):** The user stays on the contact's page. The Gifts section shows the change, or no longer shows the deleted gift.
**Screen/component:** The Gifts section on the contact page, and the form for editing a gift.

### Context
Gift ideas change as users learn more about what someone wants. Without edit and delete, the Gifts section fills with stale ideas and users stop trusting it.

### Designs
Follow the existing contact page sections, such as Loans. A designer does a light review before build.

### Acceptance criteria

**Scenario: User with edit permission changes "what it is"**
Given a user with edit permission is on a contact's page with one gift in the Gifts section
When they change the gift's "what it is" and save
Then the Gifts section shows the new "what it is"

**Scenario: User with edit permission deletes a gift**
Given a user with edit permission is on a contact's page with one gift in the Gifts section
When they delete the gift
Then the gift no longer appears in the Gifts section

**Scenario: User clears "what it is" while editing**
Given a user with edit permission is editing a gift on a contact's page
When they clear "what it is" and save
Then Monica does not save the change
And Monica shows a message next to the "what it is" field that names the missing field
And the gift keeps its previous "what it is" after the user cancels

**Scenario: User with view permission cannot edit or delete a gift**
Given a user with view permission is on a contact's page that has a gift
When they look at the gift in the Gifts section
Then they see no option to edit or delete it

**Scenario: User cancels an edit**
Given a user with edit permission is editing a gift on a contact's page
When they change "what it is" and cancel
Then the Gifts section shows the gift unchanged

### Out of scope
- Editing occasion, price, link, and note (ticket 3).
- Changing the gift stage (ticket 4).
- Changing recipients (ticket 5).
- Undoing a deletion.

---

## 3. Add occasion, price, link, and note to a gift

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 1

### Summary
A user can record an occasion, a rough price, a link, and a note on a gift.

### Job to be done
When a deliberate rememberer finds a gift idea, they need to note why, where to buy it, and roughly what it costs, so they can act on it later without searching again.

### Navigation context
**Entry point(s):** The Gifts section on a contact's page, when adding or editing a gift.
**Exit point(s):** The user stays on the contact's page. The gift shows its details in the Gifts section.
**Screen/component:** The form for adding and editing a gift, and the gift as shown in the Gifts section.

### Context
The occasion list already exists in Settings but nothing uses it yet. The occasion is what groups gifts on the vault Gifts page (ticket 7). Price and link turn a vague idea into something the user can buy. Monica must keep gift data private, so it never visits the link.

### Designs
Follow the existing contact page sections, such as Loans. A designer does a light review before build.

### Acceptance criteria

**Scenario: User adds all optional details to a gift**
Given a user with edit permission is adding a gift on a contact's page
And they have filled in "what it is" and chosen the occasion "Birthday"
And they have entered a price of 40 in an enabled currency
And they have entered a web address as the link and written a note
When they save
Then the gift in the Gifts section shows the occasion "Birthday"
And it shows the price 40 with the chosen currency
And it shows the link as a web address the user can open
And it shows the note

**Scenario: User enters a price that is not a whole amount**
Given a user with edit permission is adding a gift on a contact's page
When they enter 39.50 as the price and save
Then Monica does not save the gift
And Monica shows a message next to the price field that says the price must be a whole amount

**Scenario: User enters a link that is not a web address**
Given a user with edit permission is adding a gift on a contact's page
When they enter "not a link" as the link and save
Then Monica does not save the gift
And Monica shows a message next to the link field that says the link must be a web address

**Scenario: Occasion choices match the occasions list in Settings**
Given the account has added the occasion "Graduation" to its gift occasions list in Settings
And a user with edit permission is adding a gift on a contact's page
When they open the occasion choices
Then they see every occasion from the list, including "Graduation", in the list's order

**Scenario: Price offers only the account's enabled currencies**
Given the account has only two currencies enabled
And a user with edit permission is adding a gift on a contact's page
When they open the currency choices for the price
Then they see only those two currencies

**Scenario: Link shows without a preview**
Given a gift on a contact's page has a link
When a user views the gift in the Gifts section
Then they see the web address only
And they see no title, image, or summary taken from that web page

### Out of scope
- Linking an occasion to a contact's actual date, such as their birthday.
- Totals, budgets, or spending reports.
- Link previews of any kind.
- Photos on gifts.
- Grouping gifts by occasion (ticket 7).

---

## 4. Move a gift through gift stages and keep a gift history

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 1

### Summary
A user can change a gift's stage, and a gift that reaches the given stage moves to the contact's gift history with a date given.

### Job to be done
When a deliberate rememberer buys and gives a gift, they need to mark its progress and keep a record of it, so they can see next year what they gave before.

### Navigation context
**Entry point(s):** The Gifts section on a contact's page.
**Exit point(s):** The user stays on the contact's page. The gift shows in the open list or in the gift history, according to its stage.
**Screen/component:** The Gifts section on the contact page, with its open list and its gift history, and the gift form.

### Context
Users cannot easily see what they gave someone last year. The gift stages list already exists in Settings but nothing uses it yet. The last stage in that list means "given", so the default list works without setup.

### Designs
Follow the existing contact page sections, such as Loans. A designer does a light review before build.

### Acceptance criteria

**Scenario: User moves an open gift to a middle stage**
Given a user with edit permission is on a contact's page with an open gift at the stage "Idea"
When they change the gift's stage to "Bought"
Then the gift shows the stage "Bought"
And the gift stays in the open list

**Scenario: User moves a gift to the given stage**
Given a user with edit permission is on a contact's page with an open gift that has no date given
When they change the gift's stage to the given stage
Then the gift moves from the open list to the gift history
And its date given shows today's date

**Scenario: User changes the date given**
Given a user with edit permission is on a contact's page with a given gift dated today
When they change its date given to 3 March 2026 and save
Then the gift history shows the gift with the date given 3 March 2026

**Scenario: User with view permission cannot change a gift's stage**
Given a user with view permission is on a contact's page with an open gift
When they look at the gift in the Gifts section
Then they see the gift's stage
And they see no option to change it

**Scenario: Gift that already has a date given keeps it**
Given a user with edit permission is on a contact's page with an open gift whose date given is 1 May 2026
When they change the gift's stage to the given stage
Then the gift moves to the gift history
And its date given still shows 1 May 2026

**Scenario: User adds a gift directly at the given stage**
Given a user with edit permission is adding a gift on a contact's page
When they choose the given stage and save
Then the gift appears in the gift history
And its date given shows today's date

**Scenario: User moves a given gift back to an earlier stage**
Given a user with edit permission is on a contact's page with a given gift
When they change its stage to "Found"
Then the gift moves from the gift history back to the open list

### Out of scope
- Changing the stage from the vault Gifts page (users open the gift to change it).
- Changes to the gift stages list in Settings (ticket 6).
- Gift history across several recipients (ticket 5).
- Reminders or notifications of any kind.

---

## 5. Give one gift to several recipients

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 2, 4

### Summary
A user can give one gift to several contacts, and the gift shows on each recipient's page.

### Job to be done
When a deliberate rememberer plans one gift for a couple or a family, they need to record it once for everyone, so it shows on each person's page and stays correct everywhere.

### Navigation context
**Entry point(s):** The Gifts section on a contact's page, when adding or editing a gift.
**Exit point(s):** The user stays on the contact's page. The gift shows all its recipients.
**Screen/component:** The recipients field in the gift form, and the Gifts section on each recipient's contact page.

### Context
Many gifts go to more than one person, such as a wedding gift for a couple. The spec decides that such a gift is one gift, not a copy per person. It shows on each recipient's page and stays when one recipient is deleted.

### Designs
Follow the existing contact page sections, such as Loans. A designer does a light review before build.

### Acceptance criteria

**Scenario: User adds a second recipient**
Given a user with edit permission is editing a gift on Contact A's page
When they add Contact B as a recipient and save
Then the gift shows Contact A and Contact B as recipients
And the same gift appears in the Gifts section on Contact B's page

**Scenario: Edit on one recipient's page shows on the other**
Given a gift has two recipients, Contact A and Contact B
And a user with edit permission is on Contact A's page
When they change the gift's "what it is" and save
Then Contact B's page shows the gift with the new "what it is"

**Scenario: User removes every recipient**
Given a user with edit permission is editing a gift on a contact's page
When they remove every recipient and save
Then Monica does not save the change
And Monica shows a message next to the recipients field that names the missing field

**Scenario: Deleting one recipient keeps the gift for the other**
Given a gift has two recipients, Contact A and Contact B
When a user with edit permission deletes Contact A
Then the gift still appears on Contact B's page
And Contact B shows as its only recipient

**Scenario: Recipient choices come only from the same vault**
Given a user with edit permission is editing a gift on a contact's page
When they search for a contact that exists only in a different vault
Then that contact does not appear as a recipient choice

**Scenario: Given gift appears in every recipient's gift history**
Given a gift has two recipients, Contact A and Contact B
And a user with edit permission is on Contact A's page
When they change the gift's stage to the given stage
Then the gift appears in the gift history on Contact A's page
And it appears in the gift history on Contact B's page

**Scenario: Deleting a gift removes it for every recipient**
Given a gift has two recipients, Contact A and Contact B
And a user with edit permission is on Contact A's page
When they delete the gift
Then the gift no longer appears on Contact A's page
And it no longer appears on Contact B's page

### Out of scope
- Recipients outside the gift's vault.
- Gifts the user receives.
- Copying a gift as separate gifts per recipient.

---

## 6. Keep gifts correct when a user changes gift stages or occasions in Settings

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 3, 4

### Summary
Users can still rename, reorder, add, and delete gift stages and occasions in Settings, and existing gifts show those changes.

### Job to be done
When a deliberate rememberer customises their gift stages or occasions, they need their existing gifts to follow the change, so their lists stay accurate without fixing each gift by hand.

### Navigation context
**Entry point(s):** Settings → Gift stages, and Settings → Gift occasions.
**Exit point(s):** The user stays on the Settings page. Contact pages and the vault Gifts page show the changes.
**Screen/component:** The gift stages and gift occasions pages in Settings.

### Context
Both lists were editable before gifts used them, and users must keep that control. Because the last stage means "given", reordering stages changes which gifts are given. Users need to know this before they reorder. The spec's working rule blocks deleting a stage that gifts still use.

### Designs
No new designs. Add a short explanation to the existing gift stages Settings page.

### Acceptance criteria

**Scenario: User renames a gift stage**
Given a user is on the gift stages page in Settings
And a gift on a contact's page has the stage "Found"
When they rename "Found" to "Picked out" and save
Then the gift shows the stage "Picked out" on the contact's page

**Scenario: User renames an occasion**
Given a user is on the gift occasions page in Settings
And a gift on a contact's page has the occasion "Christmas"
When they rename "Christmas" to "Holidays" and save
Then the gift shows the occasion "Holidays" on the contact's page

**Scenario: Gift stages page explains the given stage**
Given a user has opened Settings
When they open the gift stages page
Then they see a short explanation that the last stage in the list means "given"

**Scenario: User reorders gift stages so a different stage is last**
Given a user is on the gift stages page in Settings with "Offered" as the last stage
And one gift is at "Bought" and another gift is at "Offered"
When they move "Bought" to the end of the list
Then the gift at "Bought" shows in the gift history on its recipients' pages
And the gift at "Offered" shows in the open list on its recipients' pages

**Scenario: User tries to delete a gift stage that gifts use**
Given a user is on the gift stages page in Settings
And at least one gift is at the stage "Searched"
When they try to delete "Searched"
Then Monica does not delete the stage
And Monica shows a message telling the user to move those gifts to another stage first

**Scenario: User deletes a gift stage that no gift uses**
Given a user is on the gift stages page in Settings
And no gift is at the stage "Searched"
When they delete "Searched"
Then "Searched" no longer appears in the list
And it no longer appears as a stage choice in the gift form

**Scenario: User deletes an occasion that gifts use**
Given a user is on the gift occasions page in Settings
And a gift on a contact's page has the occasion "Wedding", a price, and a note
When they delete "Wedding"
Then the gift shows no occasion on the contact's page
And it still shows its price, note, and every other detail

### Out of scope
- Moving gifts in bulk from one stage to another.
- Changing the date given when a reorder makes a gift given.
- Grouping of gifts with no occasion on the vault Gifts page (ticket 7).

---

## 7. See all open gifts in a vault on the Gifts page

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 3, 4, 5

### Summary
A user can open a Gifts page from the vault's main navigation and see every open gift in the vault, grouped by occasion.

### Job to be done
When an occasion such as Christmas is coming, a deliberate rememberer needs to see every open gift idea in one place, so they can plan without opening each contact.

### Navigation context
**Entry point(s):** The vault's main navigation → Gifts.
**Exit point(s):** The user stays on the Gifts page. Selecting a gift opens it, where users with edit permission can change it. The design decides where the gift opens.
**Screen/component:** The vault Gifts page, a new page.

### Context
Today a user must open each contact to find gift ideas before a birthday or holiday. This page is the main reason for users to return to Monica before an occasion. That supports the strategy goal of keeping users active through their first 30 days and beyond. Whether users return without reminders is the spec's riskiest assumption. The working target: 20% of active hosted vaults record at least one gift within 60 days of release.

### Designs
Designs to be created. The vault Gifts page is a new page layout and needs a design before build.

### Acceptance criteria

**Scenario: User sees open gifts grouped by occasion**
Given a vault has open gifts with the occasions "Birthday" and "Christmas", one open gift with no occasion, and one given gift
And a member of the vault is on any vault page
When they choose Gifts in the vault's main navigation
Then the Gifts page shows one group for "Birthday", one for "Christmas", and one called "No occasion"
And each open gift appears once, in its occasion's group, with its recipients' names
And the given gift does not appear

**Scenario: User who is not a member of the vault opens the Gifts page address**
Given a user is not a member of a vault
When they open the address of that vault's Gifts page
Then Monica shows the same no-access response it shows for other pages in that vault
And they see no gifts

**Scenario: Vault has no open gifts**
Given the Gifts page is switched on for a vault and the vault has no open gifts
When a member opens the vault's Gifts page
Then the page shows a message that there are no open gift ideas
And the message tells the user they can add a gift from any contact's page

**Scenario: Gift with two recipients appears once**
Given a vault has one open gift with two recipients, Contact A and Contact B
When a member opens the vault's Gifts page
Then the gift appears once
And it shows the names of Contact A and Contact B

**Scenario: Gift whose only recipient was deleted**
Given a vault has an open gift whose only recipient is Contact A
And a user with edit permission has deleted Contact A
When a member opens the vault's Gifts page
Then the gift does not appear

**Scenario: User with view permission sees gifts but cannot change them**
Given a user with view permission is on the vault's Gifts page
When they select a gift
Then they see the gift's details
And they see no option to edit, delete, or change its stage

### Out of scope
- Changing a gift's stage directly on the Gifts page.
- Sorting by whose occasion comes next.
- A Gifts widget on the vault dashboard.
- Given gifts on the Gifts page (they appear only in each contact's gift history).
- Totals, budgets, or spending reports.
- Reminders or notifications of any kind.

---

## 8. Switch the vault Gifts page on or off

**Type:** Feature
**Initiative:** Gift tracking
**Depends on:** 7

### Summary
A user can switch the vault Gifts page on or off in vault settings, like the other switchable vault pages such as Tasks and Journal.

### Job to be done
When a vault's members do not use gift planning, a deliberate rememberer needs to hide the Gifts page, so the vault's navigation shows only what they use.

### Navigation context
**Entry point(s):** Vault settings, in the same area as the switches for Tasks and Journal.
**Exit point(s):** The user stays in vault settings. The vault's main navigation shows or hides Gifts.
**Screen/component:** The switchable pages area of vault settings, and the vault's main navigation.

### Context
Monica already lets vaults switch pages such as Tasks and Journal on or off. Gifts should behave the same way so users meet no new pattern. The page is on by default so that new and existing vaults find the feature without setup.

### Designs
No designs needed. Follow the existing switches for Tasks and Journal.

### Acceptance criteria

**Scenario: User switches the Gifts page off**
Given a user with permission to change vault settings is on the vault settings page
And the Gifts page is switched on
When they switch the Gifts page off
Then Gifts no longer appears in the vault's main navigation
And the Gifts section still appears on contact pages in that vault

**Scenario: User opens the Gifts page address while the page is switched off**
Given the Gifts page is switched off for a vault
When a member opens the address of that vault's Gifts page
Then Monica responds the same way it does for a switched-off Tasks or Journal page
And the member sees no gifts list

**Scenario: User switches the Gifts page back on**
Given a user with permission to change vault settings is on the vault settings page
And the Gifts page is switched off
When they switch the Gifts page on
Then Gifts appears again in the vault's main navigation
And the Gifts page shows the same open gifts as before

**Scenario: Gifts page is on by default for existing vaults**
Given a vault created before this feature was released
When a member opens any vault page after release
Then Gifts appears in the vault's main navigation
And the vault settings show the Gifts page as switched on

**Scenario: Gifts page is on by default for new vaults**
Given a user has just created a new vault
When they open any page in that vault
Then Gifts appears in the vault's main navigation

### Out of scope
- Hiding the Gifts section on contact pages (users manage that through contact page templates, as today).
- Switching gifts off for a single member of a vault.

---

## Decomposition notes

**Tracker:** none. `.claude/skill-config.md` sets no tracker, so the PM posts nothing. This file is the final ticket set. Tickets have numbers only, not tracker IDs.

**Initiative:** Gift tracking (working name). The spec has no `Initiative:` field. The skill asks the PM to name one. This run is non-interactive, so these tickets use the spec's title. The PM should confirm or replace it before posting.

**Spec not updated.** The skill changes the spec's `Status` to `Ticketed` and fills `Tickets:` only after posting to a tracker. No tracker exists, so `output/spec.md` stays as it is.

**Open questions treated as decided.** The spec has three 🟡 open questions with working assumptions. This run treats every checkpoint as approved, so the tickets use those working assumptions:
- Deleting a gift stage that gifts use is blocked, with a message (ticket 6).
- A short explanation on the gift stages Settings page makes "last stage = given" clear (ticket 6).
- The Gifts section appears on existing accounts' contact pages without template edits (ticket 1).

Engineering should confirm the first and third at plan review, as the spec asks. If either changes, update tickets 1 and 6 before build.

**Assumptions made during decomposition:**
- `docs/product/personas.md` does not exist. JTBD statements use "deliberate rememberer", the segment named in `docs/product/strategy.md`.
- `docs/iterations/current.md` does not exist, so no in-flight work was checked for conflicts.
- No Chore tickets. Each Feature ticket includes whatever storage and setup it needs, so every ticket ships a change users can see.
- No Spike tickets. The spec leaves no approach unknown.
- Ticket 3 rejects a price that is not a whole amount and a link that is not a web address. The spec says the price is "a whole amount" and the link is "a web address"; the error messages are this run's reading of those rules.
- Ticket 4 moves a given gift back to the open list if a user picks an earlier stage. The spec does not say what happens to its date given in that case, so the ticket does not define it.
- Ticket 6 does not define whether a reorder that makes a gift given also sets its date given. The spec does not cover this.
- Ticket 7 covers "deleting the only recipient deletes the gift". The vault Gifts page is the first place a user can observe that outcome.
- Gift permissions follow existing vault permissions: view can see; edit and manage can add, edit, and delete.

**Recommended next step:** Create the vault Gifts page design before ticket 7 starts. Tickets 1 to 6 do not need it. At plan review, engineering should confirm the two 🟡 working assumptions about stage deletion and contact page templates. Then tickets 1 to 6 are ready for sprint planning.
