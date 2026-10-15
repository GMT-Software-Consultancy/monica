# Gift tracking

Last updated: 15 October 2026
Status: Ready
Tickets: (fill in once created — ticket_tool is "none", so tickets go to `output/tickets.md`)

---

## Problem statement

Monica users have nowhere to record gift ideas for a contact, track them until given, or look back at past gifts.

## Why now

Monica's own beta sign-up page says gift management is one of the two most important features missing compared to the previous version of Monica. Users moving from that version lose something they had, and that gap is visible on the first page they read. Gifts also give people a reason to open Monica before every birthday and holiday. That supports the strategy's top goal: keeping users active through their first 30 days and beyond.

## Users affected

`docs/product/personas.md` does not exist, so this spec uses the segment named in `docs/product/strategy.md`.

- **Deliberate rememberers (primary).** These are people who use Monica to remember what matters about the people they care about. Gifts are a moment where that memory pays off. They record ideas on a contact's page and check the vault's Gifts page before an occasion.
- **Other members of a shared vault (secondary).** A vault (a shared space of contacts and their data) can have several users. Each member has view, edit, or manage permission. Members see gifts under the same rules as everything else in the vault. This includes a partner who may be the gift's recipient.
- **Hosted and self-hosted users.** Both get the same feature. No plan limits apply.
- **Not affected:** contacts themselves. Monica never sends anything to the people recorded in it.

## Background

Today a user who has a gift idea must write it in a note on the contact, or keep it outside Monica. Notes have no stage, no occasion, and no single list across the vault. When a birthday comes, the user must open each contact to find ideas, and they cannot easily see what they gave last year.

Settings already contain two lists that nothing uses yet:

- **Gift stages:** the steps a gift moves through. The defaults are Idea, Searched, Found, Bought, and Offered. Users can rename, reorder, add, and delete stages.
- **Gift occasions:** the reasons for a gift. The defaults are Birthday, Anniversary, Christmas, Just because, and Wedding. Users can rename, reorder, add, and delete occasions.

This feature puts both lists to use.

### Terms used in this spec

- **Gift:** something the user plans to give, or has given, to one or more contacts.
- **Recipient:** a contact a gift is for. A gift has one or more recipients.
- **Gift stage:** one item from the gift stages list in Settings.
- **Given stage:** the last gift stage in the user's ordered list. By default this is "Offered".
- **Open gift:** a gift whose stage is not the given stage.
- **Given gift:** a gift whose stage is the given stage.
- **Occasion:** one item from the gift occasions list in Settings.

---

## Scope

### In scope

- A Gifts section on each contact's page. It lists the contact's open gifts and their given gifts (gift history).
- Adding, editing, and deleting a gift from a contact's page.
- These gift fields:
  - What it is (required)
  - Recipients (required, one or more contacts in the same vault)
  - Occasion (optional, from the occasions list)
  - Gift stage (optional on entry; defaults to the first stage in the list)
  - Rough price (optional, a whole amount in one of the account's enabled currencies)
  - Link (optional, a web address)
  - Note (optional, free text)
  - Date given (optional; filled in automatically when the gift reaches the given stage, and editable)
- Moving a gift between gift stages.
- A Gifts page for each vault, reached from the vault's main navigation. It lists all open gifts in the vault, grouped by occasion.
- A setting in vault settings to switch the Gifts page on or off, like the other switchable vault pages (for example, Tasks and Journal).

### Out of scope

- Gifts the user receives. This version covers only gifts the user gives.
- Reminders or notifications of any kind about gifts.
- Private gifts that other vault members cannot see.
- Linking a gift to a contact's actual date (for example, their birthday). An occasion is only a label.
- Sorting the Gifts page by whose occasion comes next.
- A Gifts widget on the vault dashboard.
- Totals, budgets, or spending reports.
- Photos on gifts.
- Link previews. Monica does not visit or fetch the gift link.
- Changing a gift's stage directly from the vault Gifts page. Users open the gift to change it.
- Importing gifts from the previous version of Monica.

### Phasing

- Phase 1 (this spec): record, track, and review gifts given, on the contact page and the vault Gifts page.
- Phase 2 (possible future work, not committed): dashboard widget, sorting by next occasion, gifts received, and private gifts.

### Vertical slice check

If this shipped alone, a user could record a gift idea on a contact's page, track it until they give it, see it in that contact's gift history, and see all open ideas in one place before an occasion.

---

## Figma

Designs to be created before build. The vault Gifts page is a new page layout and needs a design. The Gifts section on the contact page and the gift form should follow the existing contact page sections, such as Loans, so they need only a light review.

---

## Acceptance criteria

**AC-1**
GIVEN a user with edit permission is on a contact's page
WHEN they add a gift with only "what it is" filled in, and save
THEN the gift appears in that contact's Gifts section as an open gift
AND its gift stage is the first stage in the account's gift stages list
AND that contact is its only recipient.

**AC-2**
GIVEN a gift has two recipients, Contact A and Contact B
WHEN a user views Contact A's page and then Contact B's page
THEN the same gift appears in the Gifts section of both pages
AND an edit made on one page shows on the other page
AND if a user deletes Contact A, the gift still appears on Contact B's page with Contact B as its only recipient.

**AC-3**
GIVEN an open gift with no date given
WHEN a user moves the gift to the given stage
THEN the gift moves from the open list to the gift history on every recipient's page
AND its date given is set to today's date
AND the user can change the date given afterwards.

**AC-4**
GIVEN a vault has open gifts with the occasions "Birthday" and "Christmas", one open gift with no occasion, and one given gift
WHEN a user opens the vault's Gifts page
THEN the page shows one group for "Birthday", one for "Christmas", and one called "No occasion"
AND each open gift appears once, in its occasion's group, with its recipients' names
AND the given gift does not appear.

**AC-5 (unhappy path)**
GIVEN a user is adding or editing a gift
WHEN they save with "what it is" empty, or with no recipient
THEN Monica does not save the gift
AND Monica shows a message next to each empty required field that names the missing field.

**AC-6 (empty state)**
GIVEN the Gifts page is switched on for a vault and the vault has no open gifts
WHEN a user opens the vault's Gifts page
THEN the page shows a message that there are no open gift ideas
AND the message tells the user they can add a gift from any contact's page.

---

## Assumptions

- Gifts give users a reason to return before birthdays and holidays, even without reminders. (Riskiest — see below.)
- The last stage in the user's ordered gift stages list means "given". The default list already ends with "Offered", so this works without setup.
- A gift for several recipients appears in the gift history of each recipient.
- Gifts follow the existing vault permissions. Members with view permission can see gifts but cannot add, edit, or delete them. Members with edit or manage permission can do all three.
- The Gifts page is switched on by default for new and existing vaults.
- The Gifts section appears on contact pages for existing accounts without users editing their contact page templates in Settings.
- If a user deletes the only recipient of a gift, Monica deletes the gift too.
- If a user deletes an occasion, its gifts keep everything else and move to "No occasion".
- Users accept that other vault members, including a gift's recipient, can see gift ideas. The PM confirmed this rule. Private gifts are future work.
- No baseline number exists for demand. This spec sets a working success target: 20% of active hosted vaults record at least one gift within 60 days of release. The PM should review this target after release.

---

## Technical notes

- **Privacy:** Gift data must stay inside Monica. Monica must not fetch the gift link or send gift data to any outside service. This keeps the feature within the strategy's privacy review gate.
- **Same behaviour on hosted and self-hosted.** The feature must work the same way on both, with no plan limits.
- **Settings lists stay editable.** Users must still be able to rename, reorder, add, and delete gift stages and occasions after gifts use them. Renaming a stage or occasion must update how existing gifts display.
- **Reordering stages changes the given stage.** If a user moves a different stage to the end of the list, gifts in that stage become given gifts. Gifts in the old last stage become open gifts. The Settings page for gift stages must say that the last stage means "given".
- **Existing flows:** Deleting a contact must keep the gift for its other recipients (AC-2). Switching off the Gifts page hides only the vault Gifts page. The Gifts section on contact pages stays, as with other switchable vault pages.
- **Currencies:** The price field offers only the currencies the account already has enabled.

---

## Open questions

- 🟡 What happens when a user deletes a gift stage that gifts are using? Working assumption: Monica blocks the deletion and tells the user to move those gifts to another stage first. — PM, with engineering at plan review
- 🟡 Is "last stage = given" clear enough for users who customise their stages? Working assumption: yes, with a short explanation on the gift stages Settings page. — PM
- 🟡 Should the Gifts section be added automatically to existing accounts' contact page templates? Working assumption: yes, so existing users find the feature without setup. — PM, with engineering at plan review
- 🟢 Importing gifts from the previous version of Monica is out of scope. This depends on general data import from that version, which does not exist yet. — PM

---

## Before you build

**Decisions made:**

- Gifts use the gift stages and occasions that users already manage in Settings.
- This version covers only gifts the user gives, not gifts received.
- Anyone who can see a vault can see its gifts. There are no private gifts in this version.
- A gift with several recipients is one gift. It shows on each recipient's page and stays when one recipient is deleted.
- The vault Gifts page groups open gifts by occasion and is switchable in vault settings.
- No reminders, notifications, budgets, totals, photos, or dashboard widget.

**Riskiest assumption:**
Users will return to the Gifts page before birthdays and holidays without any reminder to prompt them.

**Next step:**
Create the vault Gifts page design. At plan review, engineering should resolve the two 🟡 questions about deleting stages and contact page templates. Then generate tickets.
