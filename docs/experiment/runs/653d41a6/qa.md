# Questions

# Questions for the PM: full contact export

## Context read

- Read: `seed.md`, `docs/product/strategy.md`, `docs/architecture/` (README, data model, external interfaces, cross-cutting, glossary), `.claude/skill-config.md` (ticket_tool: none, so tickets go to `output/tickets.md`).
- Missing: `docs/product/personas.md`, `CLAUDE.md`, `docs/ticket-creation-context.md`, `docs/iterations/current.md`. Because the personas file is missing, I can't name personas from it, and I can't check for work already in flight.

## My working frame (please confirm or correct)

- **Problem:** A user can't get everything they've recorded about one person out of Monica. The vCard download only covers address-book details.
- **Primary user:** the person who owns the Monica account and has built up a history on a contact.
- **Scope:** download one contact at a time from that contact's page. The download covers what the user has recorded, not just the address-book fields.
- **Strategic fit:** this supports "provable trust" (users own their data) in `strategy.md`. It doesn't work on the main problem the strategy names, which is time-to-first-value.

## Questions

1. **Framing:** Is the frame above right? If not, what would you change?

2. **Why now:** What triggered this request? For example: support tickets, a number of requests, feedback from people leaving hosted accounts, a data-access request from a user, or a deliberate "provable trust" move. The strategy puts the first 30 days first and prefers cutting features to adding them. So I need a specific reason to put this ahead of that work.

3. **Main purpose:** What is the file mainly for?
   - (a) a personal copy to keep and read later
   - (b) moving the data into another tool
   - (c) printing or sharing, for example before a memorial or a family event

   The answer decides the format. My guess is (a) first and (b) second. Is that right?

4. **Format:** Should the file be readable by a person (for example PDF, HTML or Markdown), readable by a program (for example JSON or CSV), or both? The seed says "use in other tools". Are there specific tools in mind? Is re-importing the file into Monica in scope? I'd propose to leave it out of scope.

5. **What data to include:** The seed lists notes, relationships, reminders and important dates. A contact can also have addresses and contact details, calls, gifts, loans, tasks, life events, pets, goals, mood entries, quick facts, and the activity history. Should the first version include everything that appears on the contact's page, or only the four types in the seed? Should journal posts that mention the contact be included?

6. **Photos and documents:** Should the download include the photos and files attached to the contact? That makes the download much bigger and turns it into a bundle of files. Or should it only list the files by name?

7. **Shared vaults and permissions:** A vault is a shared space, and users in it can have manager, editor or viewer access.
   - Can a viewer download a full contact, or only editors and managers?
   - In a shared vault, should the file include notes written by other members? If yes, should it show who wrote each note?

8. **Other people in relationships:** A relationship points to another contact, for example "sister of Anna". Should the file include only the related person's name and the type of relationship? Or should it also include details about that person? Including details would mean the file holds data about third parties.

9. **Scope limits and the existing vCard button:**
   - Can we list "export many contacts or a whole vault at once" as out of scope for this spec?
   - On the contact page, should the new download sit next to the vCard download, replace it, or become one option in a single "Download" menu?

10. **Success and design:** How will we know this worked? For example: fewer support requests, or how many people use it. Do Figma designs exist, are they planned, or does this not need designs because it's only a button and a file?

# Stand-in PM answers

1. Mostly right. Users are people who keep their relationship data in Monica, including self-hosters. Some want a personal backup, and some want to reuse the data in other tools or their own scripts. The download is a full copy of everything recorded about the person, not just the address-book fields. The strategic fit is data ownership: this makes "your data is yours" concrete.

2. It makes "your data is yours" concrete, and data ownership is the thing Monica stands for.

3. Both (a) and (b): some users want a personal backup, and some want to reuse the data in other tools or their own scripts. On (c): No strong view. Use your judgement and write the assumption down.

4. One machine-readable file; JSON is fine. A human-readable version can come later. Specific tools: No strong view. Use your judgement and write the assumption down. Re-import is out of scope; this is export only.

5. A full copy: everything recorded about this person, including the address-book details the vCard has, not just the four examples. On journal posts: No strong view. Use your judgement and write the assumption down.

6. List photos and documents by name and date so people know they exist. Don't include the actual files for now.

7. Anyone who can view the contact can export it, since they can already see all of it. The file is a full copy of everything recorded about the person. On showing who wrote each note: No strong view. Use your judgement and write the assumption down.

8. Show who the related people are and how they're connected, but not their own details. It's this contact's copy, not theirs.

9. Yes, one contact only; many contacts at once is out of scope. Put the new option on the contact's page next to the vCard download, and keep the vCard download as it is.

10. Success metrics and tracking are out of scope for this test, so don't add tracking. There are no designs; match the vCard link.
