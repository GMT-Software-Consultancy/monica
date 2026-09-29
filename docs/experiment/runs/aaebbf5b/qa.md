# Questions

# Questions for the PM — "Download a contact as JSON"

## Context notes

- **Read:** `seed.md`, `docs/product/strategy.md`, `.claude/skill-config.md` (ticket tool: none, so tickets go to `output/tickets.md`).
- **Missing:** `docs/product/personas.md`, `CLAUDE.md`, `docs/ticket-creation-context.md`, `docs/iterations/current.md`. Because the personas file is missing, I have no persona names to use, and I can't check for work already in flight.
- **Guard-rail check of the product today:**
  - The contact page has a "Download as vCard" link in its side menu. Any vault member who can open the contact can use it.
  - The vCard contains only part of what Monica stores about a contact: names, gender, labels, contact information (email, phone, and so on), addresses, work information, important dates, and timestamps.
  - It leaves out notes, relationships, reminders, activities, life events, goals, photos, and documents.
  - Monica has no public way to get contact data out as JSON today.

## Proposed framing (please confirm or correct)

- **Problem:** People who want to use a contact's data in other tools or their own scripts can only get it as a vCard, which is hard to work with.
- **Primary user:** Technical users, probably self-hosters and power users on the hosted plan, who write scripts or connect Monica to other tools.
- **Scope boundary:** One contact at a time, downloaded as a JSON file from the same menu as "Download as vCard". No bulk export, no import, no API.
- **In-flight work:** Unknown, because `docs/iterations/current.md` is missing.

## Questions

1. **Framing.** Does the framing above match what you want? If not, what would you change: the problem, the user, or the scope?

2. **Why now.** The strategy favours pruning and says Monica "won't compete on feature count". Why does this request earn a place now? One strong answer is the strategy's "provable trust" capability: your data is yours, and you can take it out in a usable form. Is that the reason, or is there a specific trigger such as a count of requests, a GitHub issue, or a hosted customer? Do the requests come from hosted users, self-hosters, or both?

3. **Who the user is.** `personas.md` is missing. Which people are we building this for, and what do they do today? For example, do they download the vCard and convert it with a script, or copy fields by hand? If you have persona names, please share them.

4. **What the file contains. This is the biggest scope decision.** Should the JSON file contain:
   - (a) the same fields as the vCard (names, contact information, addresses, work, important dates, labels, gender), or
   - (b) everything Monica holds about the contact, including notes, relationships, reminders, activities, life events, and goals?

   Option (a) is smaller. Option (b) is more useful for scripts, but it is much larger and adds more sensitive data. If you choose (b), is anything excluded?

5. **Photos and documents.** Should the file include the contact's avatar, photos, and documents? The options are:
   - leave them out
   - include only their names and descriptions (metadata)
   - include links to the files
   - embed the files themselves

   Note that links would need a login to open, or would have to expire.

6. **A stable format.** People will write scripts against this file. Do we promise a documented, versioned format that we won't change without notice, or is the shape "best effort, may change"? If we make that promise, who writes and maintains the documentation?

7. **Out of scope.** Please confirm we are **not** building:
   - bulk or whole-vault JSON export
   - importing a contact from JSON
   - a JSON endpoint in the public API
   - scheduled or automatic exports
   - JSON export for groups (groups can be downloaded as vCard today)

   Are any of these actually in scope, or planned as a later phase?

8. **Who may download, and privacy.**
   - Today any vault member who can see a contact can download its vCard. Should the same rule apply to JSON, including members with view-only access?
   - If the file includes notes or other private content (see question 4), does this pass the strategy's privacy review gate? For example, should we show a warning that the file holds sensitive data?

9. **Design and success.**
   - Is a second link beside "Download as vCard" (for example, "Download as JSON") enough, so that no Figma design is needed?
   - How will we know the feature worked? For example: usage of the new link, fewer related GitHub requests, or no measure at all.

# Stand-in PM answers

1. Mostly. Widen the user: it's people who keep their relationship data in Monica, including self-hosters. Some of them are developers who want to reuse the data in scripts or other tools. The scope is right: one contact, export only, no API, placed next to the vCard download on the contact page.

2. No strong view. Use your judgement and write the assumption down.

3. It's people who keep their relationship data in Monica, including self-hosters. Some are developers who want to reuse the data in scripts or other tools. For persona names and what they do today: No strong view. Use your judgement and write the assumption down.

4. (a). Use the same details the vCard download includes, nothing more. Notes, relationships, reminders and other Monica data are not in this one; maybe later. For dates, include only the birthday, like vCard. If a birthday has no year, keep what we know and don't make up a year. Empty fields must not fail. Empty is fine, just be consistent.

5. Leave them out.

6. Documenting the shape is nice to have, not needed now. Don't use a specific JSON standard such as jCard; a plain, readable shape is fine. On promising a versioned, stable format: No strong view. Use your judgement and write the assumption down.

7. Confirmed as out of scope:
   - Bulk or whole-vault export: no, one contact.
   - Importing: no, export only.
   - A JSON API endpoint: no, downloading the file is enough.
   - Groups: no, one contact.
   - Scheduled or automatic exports: No strong view. Use your judgement and write the assumption down.

8. Anyone who can view the contact may export it, the same as the vCard download. Notes and other private content are not included (see 4). On a sensitive-data warning: No strong view. Use your judgement and write the assumption down.

9. Yes. There are no designs; match the vCard link. Success metrics and tracking are out of scope for this test, so don't add tracking.
