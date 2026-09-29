# Questions

# Questions for the PM — Full contact export

Seed: "Let people download a full copy of one contact from the contact's page, beyond the vCard: notes, relationships, reminders and important dates."

## Orientation notes

- **Read:** `.claude/skills/create-spec/SKILL.md`, `seed.md`, `docs/product/strategy.md`, `.claude/skill-config.md` (ticket_tool: none, so tickets go to `output/tickets.md`).
- **Missing:** `docs/product/personas.md`, `CLAUDE.md`, `docs/ticket-creation-context.md`, `docs/iterations/current.md`. Without them I can't use official persona names or check for in-flight work. I skimmed the codebase instead, only as a guard-rail.
- **What the product already does:**
  - Each contact page has a single-contact vCard download. It covers address-book details only.
  - I found no account-wide or vault-wide export in the web app.
  - A contact holds much more than the four data types in the seed: calls, tasks, goals, loans, life events, pets, labels, groups, job details, addresses, contact information, mood entries, photos and documents.
  - Vaults can be shared with other users at view, edit or manage level.
- **Strategy fit:** This supports capability 4, "Provable trust" ("your data is yours alone"). It doesn't directly move the top metric, which is first-30-day retention and time-to-first-value. The strategy also warns against piling on features. So "why now" has to be clear.

## Questions

1. **Why now?** What triggered this request? For example: support tickets, hosted users who are leaving, a data-portability or privacy request, or a competitor. Is there a number behind it? The strategy puts retention first and warns against feature bloat. So I need a stronger reason than "users asked." My suggestion is to frame it as proof that users own their data ("Provable trust"). Does that match what's driving it?

2. **Who is the primary user?** `personas.md` is missing. My guess is a hosted-plan user who cares about privacy and wants a personal backup of their records about someone. Is that right? Are self-hosted users an equal audience, or a secondary one?

3. **What will people do with the file?** The seed says "keep or use in other tools." Those two goals point to different formats:
   - A human-readable document (PDF, HTML or Markdown) that you can read or print.
   - A structured file (such as JSON or CSV) that another program can read.

   Which one matters most? Or do we need both? If "other tools" means specific products (Obsidian, Notion, another CRM), please name them.

4. **What is "everything"?** The seed lists notes, relationships, reminders and important dates. A contact can also hold calls, tasks, goals, loans, life events, pets, labels, groups, job details, addresses, contact methods and mood entries. Should Phase 1 include every data type, or only the four in the seed? Should the vCard's address-book details also be in the same file, so one download is complete?

5. **Photos and documents:** Should uploaded photos and documents be in the export, maybe as a zip file? Or should they be left out for now? Including them changes the file size and format a lot.

6. **Other people's data in relationships:** A relationship links this contact to other contacts. Should the export show only the related person's name and the relationship type (for example "Sister: Jane Doe")? Or should it include some of that person's details too? I recommend name and type only, so exporting one contact doesn't leak data about others. This would also pass the strategy's privacy review gate.

7. **Who can export in a shared vault?** Should a user with view-only access be able to download a full copy of a contact? That copy could include notes that other vault members wrote. Or should export need edit or manage permission? My default would be to let anyone who can view the contact export what they can already see. Please confirm or override.

8. **Out of scope:** Please confirm these are out of scope for this spec, and add anything else:
   - Exporting many contacts, a whole vault or a whole account at once.
   - Importing the file back into Monica, or into another Monica instance.
   - Scheduled or automatic exports.
   - An API endpoint for exports.

   If a whole-account export or re-import is planned for later, tell me and I'll describe the phasing.

9. **Success and error behaviour:** How will we know this worked? For example: a count of exports, fewer support requests, or a drop in churn-related data requests. What should happen when a contact has nothing beyond basic details? Options: export the file with empty sections, or warn the user first.

10. **Design and placement:** Are there Figma designs, or should designs be done before build? Where does the new option go? I suggest putting it next to the existing vCard download on the contact page. Should the vCard download stay as a separate option? Also, is any work in flight on the contact page or the vCard export that this might conflict with? (`docs/iterations/current.md` is missing, so I can't check.)

# Stand-in PM answers

1. It makes "your data is yours" concrete. Data ownership is the thing Monica stands for.

2. People who keep their relationship data in Monica, including self-hosters. Some want a personal backup; some want to reuse the data in other tools or their own scripts.

3. One machine-readable file (JSON is fine). A human-readable version can come later.

4. A full copy: everything recorded about this person, including the address-book details the vCard has. Not just the four examples in the request.

5. List them (name and date) so people know they exist. Don't put the actual files in for now.

6. Show who they are and how they're connected, but not their own details. It's this contact's copy, not theirs.

7. Anyone who can view the contact. They can already see all of it.

8. Out of scope: more than one contact at once (one contact only), importing the file back (export only), and an API or endpoint (the download is enough for now). Scheduled or automatic exports: No strong view. Use your judgement and write the assumption down.

9. Success metrics are out of scope for this test. Don't add tracking. Empty-contact behaviour: No strong view. Use your judgement and write the assumption down.

10. No designs; match the vCard link. Put it on the contact's page, next to the vCard download, and keep the vCard download as it is. In-flight work: No strong view. Use your judgement and write the assumption down.
