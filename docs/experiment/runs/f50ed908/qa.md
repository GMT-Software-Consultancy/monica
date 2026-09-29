# Questions

# Questions for the PM — full contact export

Context read: `seed.md`, `docs/product/strategy.md`, `.claude/skill-config.md` (ticket_tool: none), `.claude/skills/_shared/writing-style.md`.
Missing: `docs/product/personas.md`, `CLAUDE.md`, `docs/ticket-creation-context.md`, `docs/iterations/current.md`. Because of this, I cannot name personas from a personas file or check for in-flight work. I read the codebase only as a guard-rail.

My working framing (question 1 asks you to confirm it):

- **Problem:** A user cannot take everything they have recorded about one contact out of Monica. The vCard download only covers address-book details.
- **Primary user:** a Monica user who owns or edits a contact in a vault (a vault is a separate, shareable space that holds contacts).
- **Scope boundary:** one contact at a time, started from that contact's page. No bulk or whole-account export.
- **In-flight work:** unknown, because `docs/iterations/current.md` is missing.

---

1. **Framing.** Is the framing above right? In particular, is this strictly a single-contact export from the contact page, with whole-vault or whole-account export out of scope? If you see a different angle, tell me.

2. **Why now.** The strategy makes "win the first 30 days" the main objective, and this feature does not shorten time-to-first-value. The closest fit is "provable trust" (your data is yours and you can take it with you). What triggered this request now: support tickets, users leaving, a hosted-plan sales objection, or a competitor? Give a number or a concrete source if you have one. "Users asked for it" alone will fail the quality gate.

3. **Who, and what they do with the file.** Personas are not documented, so I need you to name the primary user. What does that user do with the file after download? For example: keep a personal backup, move to another tool, or share it with someone. Your answer decides the file format, so please be specific.

4. **File format.** Should the file be readable by people (for example PDF or plain text), readable by other tools (for example JSON or CSV), or both? If you want "use in other tools", name 1 or 2 target tools, or say "no specific tool, just a documented, open format".

5. **Which data is included.** The seed lists notes, relationships, reminders and important dates. A Monica contact can also hold these kinds of data:
   - calls, tasks, loans, goals, life events, pets, addresses, contact information, labels, groups, religion, gender and pronouns, company
   - journal posts that mention the contact
   - uploaded documents and photos

   Should Phase 1 include everything ("a full copy"), or only the 4 types in the seed? If only some, which types go to a later phase? Uploaded documents and photos are the biggest choice: include the files themselves, list them only, or leave them out?

6. **Other people's data inside the export.** Relationships and journal posts name other contacts. Other vault members may also have written notes on this contact. For linked contacts, should the file include only the linked contact's name, or more? Should notes that other vault members wrote be included? This needs a clear answer to pass the strategy's privacy review gate.

7. **Who may export.** Vaults have 3 access levels: view, edit, and manage. Can a user with view-only access export a full copy of a contact? Or is export limited to users who can edit or manage the vault?

8. **Relation to the existing vCard download.** Does the new download sit next to the vCard download as a second option, replace it, or add to it (for example, both files in one bundle)? The vCard download must keep working for users who rely on it today — please confirm.

9. **Hosted and self-hosted.** Must the feature work the same way for hosted-plan users and self-hosted users? Should it be available on every plan, or only on paid hosted plans?

10. **Success and designs.** How will you know this feature worked? For example: a drop in export-related support requests, or a count of exports per month. Also, do Figma designs exist or are they planned? Or is a single new button on the contact page simple enough to need no design?

# Stand-in PM answers

1. Yes. It's one contact at a time, started from the contact's page. Bulk export is out of scope. Importing the file back is also out of scope: this is export only.

2. Users want a copy they control, outside Monica, of what they've recorded about a person. On the trigger and a number: No strong view. Use your judgement and write the assumption down.

3. The users are people who keep their relationship data in Monica, including self-hosters. Some want a personal backup. Some want to reuse the data in other tools.

4. No strong view. Use your judgement and write the assumption down.

5. No strong view. Use your judgement and write the assumption down.

6. No strong view. Use your judgement and write the assumption down.

7. No strong view. Use your judgement and write the assumption down.

8. No strong view. Use your judgement and write the assumption down.

9. Paid plans don't apply. Everyone gets it, and the users include self-hosters.

10. Success metrics and tracking are out of scope for this test, so don't add tracking. There are no designs or mock-ups.
