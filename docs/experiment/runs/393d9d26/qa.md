# Questions

# Questions for the PM — Full contact export

Seed: "Export everything about one contact (notes, relationships, reminders, important dates) as a file, downloaded from the contact's page. The vCard download only covers address-book details."

## Orientation notes

- **Context read:** `docs/product/strategy.md`, `docs/architecture/*`, `.claude/skill-config.md` (ticket_tool: none; tickets go to `output/tickets.md`).
- **Missing:** `docs/product/personas.md`, `CLAUDE.md`, `docs/ticket-creation-context.md`, `docs/iterations/current.md`. I can't use persona names, and I can't check for in-flight work that might conflict.
- **Relevant facts from the architecture docs:**
  - The existing vCard download is `POST /vaults/{vault}/contacts/{contact}/vcard`. It skips the Inertia middleware and needs the `contact-owner` gate. That gate only checks that the contact belongs to the vault. It does not check the user's permission level.
  - Contact data is spread across about 25 subdomains under `app/Domains/Contact/`, including notes, reminders, important dates, relationships, calls, tasks, loans, goals, pets, life events, mood tracking, addresses, contact information, photos, documents, quick facts and the activity feed.
  - Photos and documents can live on local storage or on Uploadcare (an external file-hosting service).
  - The strategy's "provable trust" capability fits a "your data is yours to take" feature. The same strategy also sets a feature-pruning bias and a privacy review gate.

## Proposed framing (please confirm or correct)

- **Problem:** users can't get a complete, portable copy of what they've recorded about one person.
- **Primary user:** a hosted or self-hosted Monica user who keeps detailed records on a contact. `personas.md` is missing, so I don't have a persona name yet.
- **Scope boundary:** one contact at a time, started from that contact's page, download only. No import, no vault-wide export.
- **In-flight work:** unknown, because `docs/iterations/current.md` is missing.

## Questions

1. **Does the framing above match your intent?** In particular, is this strictly one contact per download? Can I list vault-wide or bulk export and re-import into Monica as out of scope?

2. **What file format?** "Keep it" points to something a person can read, such as PDF, HTML or Markdown. "Use in other tools" points to structured data, such as JSON or CSV. Do you want one format, both, or a choice? Are there specific "other tools" in mind, for example Obsidian, a spreadsheet or another PRM? My suggestion: a single JSON file, plus a readable Markdown or HTML version only if you name a concrete tool that needs it.

3. **Exactly which data goes in?** The seed names notes, relationships, reminders and important dates. The contact page also holds calls, tasks, loans, gifts, goals, pets, life events, mood entries, addresses, contact information, quick facts, labels, groups, job/company and the activity feed. Should the export be "everything on the contact page" or only the four named types? Should it also include the vCard fields (name, phones, emails) so one file is complete?

4. **Photos and documents: include the files themselves or not?** Including them means a ZIP archive. It could be large, and files stored on Uploadcare would have to be fetched. Leaving them out means listing only file names or links, or leaving them out entirely.

5. **How much detail for relationships?** Should the file show just the related person's name and the relationship type (for example "Sister: Anna Smith")? Or should it include data about the related contact too? My assumption: name and type only, with no data from the other contact.

6. **Who can export, and whose content is included?** Today the vCard route needs only `contact-owner`, so any vault member, including a Viewer (lowest permission), can download it. Should a Viewer be able to export all notes? That includes notes written by other members of a shared vault. Or should export need Editor or Manager permission?

7. **Why now?** What prompted this request? For example: a count of support requests, churn or exit interviews where users cited lock-in, a hosted-plan trust or marketing push, or a compliance request such as a GDPR data-access request. I need a concrete trigger for the "why now" section. "Users asked for it" won't pass the quality gate.

8. **How do we measure success?** Is there a number you'd watch, such as export usage, fewer support tickets, or conversion on the hosted plan? Or is this a trust feature where success means "it exists and works"?

9. **How should it relate to the existing vCard download?** Should it be a separate "Export full contact" action next to the vCard button, or replace it? Should each export be recorded in the contact's activity feed, in line with the strategy's transparency and privacy-review goals?

10. **Designs:** Are Figma designs planned, or is a single menu item or button on the contact page simple enough to need no designs?

# Stand-in PM answers

1. Yes, the framing matches. The users are people who keep their relationship data in Monica, including self-hosters. Some want a personal backup, and some want to reuse the data in other tools. It's strictly one contact per download, started from the contact's page. Export only, with no import back into Monica.

2. No strong view. Use your judgement and write the assumption down.

3. No strong view. Use your judgement and write the assumption down.

4. No strong view. Use your judgement and write the assumption down.

5. No strong view. Use your judgement and write the assumption down.

6. No strong view. Use your judgement and write the assumption down. (For paid plans: that doesn't apply, and everyone gets it.)

7. Users want a copy of what they've recorded about a person that they control, outside Monica. On a specific trigger beyond that: No strong view. Use your judgement and write the assumption down.

8. Success metrics and tracking are out of scope for this test. Don't add tracking.

9. It goes on the contact's page. On whether it's separate from the vCard download or replaces it, and on the activity feed: No strong view. Use your judgement and write the assumption down.

10. There are no designs or mock-ups.
