# Questions

# Questions for the PM — full contact export

Feature: download a full copy of one contact (not just the vCard) from the contact's page.

## Context notes

- Read: `seed.md`, `docs/product/strategy.md`, `docs/architecture/` (all files), `.claude/skill-config.md` (`ticket_tool`: none, so tickets go to `output/tickets.md`).
- Missing: `docs/product/personas.md`, `CLAUDE.md`, `docs/ticket-creation-context.md`, `docs/iterations/current.md`. Without the personas file I can't use official persona names. Without the iteration file I can't check for in-flight work that might conflict.

## Proposed framing (please confirm or correct)

- **Problem:** a person's full record is stuck in Monica. The vCard download carries only address-book fields.
- **Primary user:** a user who has logged a lot about someone and wants a portable copy they own.
- **Scope boundary:** one contact at a time, started from that contact's page. Not a whole-vault or whole-account export, and not import.
- **In-flight work:** unknown, because the current iteration file is missing.

## Questions (most important first)

1. **Framing and why now.** Is the framing above right? Why is this worth building now? The strategy is about winning the first 30 days and making privacy provable ("your data is yours, and you can take it with you"). Which of those is this meant to serve? Or is there a specific trigger, such as support requests, churn feedback, a competitor, or a data-portability obligation?

2. **Who is this for?** `personas.md` doesn't exist, so I can't use persona names. Who is the main user: a hosted paying customer, a self-hoster, someone about to leave Monica, or someone sharing a record with another person? Is there a persona document I should use, or should I describe the user in plain words?

3. **File format and target tools.** "A file they can keep or use in other tools" can mean a human-readable file (PDF, or a printable page), a machine-readable file (JSON or CSV), or both. Which one comes first? Do you have specific target tools in mind, such as a notes app, a spreadsheet, or another personal CRM? If both formats are wanted, is a readable file Phase 1 and a machine-readable file Phase 2?

4. **What goes in the file?** The seed names notes, relationships, reminders and important dates. A contact also has calls, tasks, loans, pets, goals, gifts, life events, mood logs, addresses, contact information, quick facts, labels, groups, job information, photos and documents. Which of these are in Phase 1? Does "everything" mean all of them? Should photos and documents be in the file itself (so it becomes a bundle, such as a zip), or just listed by name?

5. **Relationships reveal other people.** A relationship points to another contact, for example "Sister: Jane Doe". Should the export only name the related person and the relationship type? Or should it also include details about that person? My default is name and relationship type only, so that exporting one contact does not leak data about other contacts. Is that right?

6. **Who can export in a shared vault?** Vaults can be shared, with viewer, editor and manager access levels. Can a viewer-only member download the full export, or only editors and managers? The vCard download is currently open to anyone who can see the contact. Should notes written by other vault members be included, and should the file show who wrote each one?

7. **Relationship to the vCard download.** Should the new download replace the "Download vCard" action, sit next to it as a second option, or should the vCard be included inside the full export? Is it acceptable that the existing vCard download stays exactly as it is?

8. **Out of scope.** Please confirm these are out of scope for this spec: (a) exporting many or all contacts at once, (b) importing the file back into Monica or another Monica instance, (c) scheduled or automatic exports, (d) an API for export. Anything else you want to rule out explicitly?

9. **Success and failure behaviour.** How will you know this worked? For example: number of exports per month, fewer support requests, or it being mentioned in privacy positioning. What should happen when the export can't be produced, for example because the contact has very large attachments or a file is missing from storage? Should the user get a partial file with a warning, or an error?

10. **Designs.** Are Figma designs planned for the download entry point and for any options (such as format or sections)? Or is a single extra item in the contact page's existing actions menu good enough with no design needed?

# Stand-in PM answers

1. The framing is right: one contact, from the contact's page, export only. Users want a copy of what they've recorded about a person that they control, outside Monica. On why now and which strategy goal this serves: No strong view. Use your judgement and write the assumption down.

2. The users are people who keep their relationship data in Monica, including self-hosters. Some want a personal backup, and some want to reuse the data in other tools. On whether there's a persona document: No strong view. Use your judgement and write the assumption down.

3. No strong view. Use your judgement and write the assumption down.

4. No strong view. Use your judgement and write the assumption down.

5. No strong view. Use your judgement and write the assumption down.

6. No strong view. Use your judgement and write the assumption down.

7. No strong view. Use your judgement and write the assumption down.

8. (a) Confirmed out of scope: one contact only. (b) Confirmed out of scope: export only, no import. On (c), (d) and anything else: No strong view. Use your judgement and write the assumption down.

9. Success metrics and tracking are out of scope for this test, so don't add tracking. On failure behaviour: No strong view. Use your judgement and write the assumption down.

10. There are no designs. The option goes on the contact's page. On whether a single item in the existing actions menu is enough: No strong view. Use your judgement and write the assumption down.
