# Questions

# Questions for the PM — Full contact export

## Context notes (read first)

**Files read:** `seed.md`, `docs/product/strategy.md`, `.claude/skill-config.md` (`ticket_tool: none`, so tickets go to `output/tickets.md`), and `docs/architecture/` (README, 00, 01, 02, 04, 07, 08).

**Files missing:** `docs/product/personas.md`, `CLAUDE.md`, `docs/ticket-creation-context.md` and `docs/iterations/current.md`. Because of this, I can't name personas from a canonical list or check for in-flight work. I used the architecture docs in place of `CLAUDE.md` and `ticket-creation-context.md`.

**What I found in the code docs:**
- Today, a user can download a vCard from the contact page (`POST /vaults/{vault}/contacts/{contact}/vcard`). A vCard is the standard address-book file format.
- The docs don't mention any vault-level or account-level export.
- A contact has about 25 kinds of related data (sub-domains under `app/Domains/Contact/`). These include notes, reminders, important dates, relationships, calls, tasks, loans, pets, goals, life events, mood logs, addresses, contact information, quick facts, labels, groups, photos and documents.
- Contact pages need only the `contact-owner` gate, which checks that the contact belongs to the vault. Any vault member can reach them, including viewers.

**My proposed framing (Step 2 frame check):**
- **Problem:** users can't get a complete copy of what they've recorded about one person. The vCard only holds address-book fields.
- **Primary persona:** a privacy-motivated "deliberate rememberer" (strategy.md). This is a guess because `personas.md` is missing.
- **Scope boundary:** one contact, downloaded from that contact's page. No bulk export and no import.
- **In-flight work:** unknown, because `iterations/current.md` is missing.

---

## Questions

1. **Framing and persona.** Is the framing above right? `personas.md` is missing, so which user is this for? Is it the hosted paying user, the self-hoster, or both? And what is the real situation behind the request? For example: a user leaving Monica, a user who wants a personal backup, or a user moving one person's history into another tool.

2. **Why now.** What triggered this request now? For example: a count of support requests or GitHub issues, churn feedback, or a deliberate move to support the strategy's "provable trust" capability (your data is yours and you can take it out). The strategy's main goal is the first 30 days, and it pushes to prune features. So I need a "why now" that is stronger than "users asked for it."

3. **File format.** "Keep it" suggests a file people can read, such as PDF, HTML or Markdown. "Use it in other tools" suggests a file that software can read, such as JSON. Which one matters more for version 1? My suggestion: one JSON file as the primary format. A human-readable format would be out of scope for version 1. Do you agree, or do you need both?

4. **What "everything" means.** The seed lists notes, relationships, reminders and important dates. The contact also has calls, tasks, loans, pets, goals, life events and timeline events, mood logs, addresses, contact information, quick facts, labels, groups, job and company details, and the activity feed. Which of these are in version 1? My suggestion: all text-based data on the contact, and no activity feed.

5. **Photos and documents.** Should the export include uploaded photos and documents? That would probably mean a ZIP file, and the files may be stored with Uploadcare, an external service. The other option is to leave out the files themselves and list only their names and details (metadata). My suggestion: metadata only in version 1.

6. **Relationships.** For each relationship, should the export include only the related person's name and the relationship type (for example, "Sarah — sister")? Or should it also include data about that related contact? My suggestion: name and type only, so the export never pulls in another contact's private data.

7. **Who can export in a shared vault.** A vault can have managers, editors and viewers, and other members may have written some notes. Can a viewer download the full export, or only editors and managers? Should the export include notes written by other vault members? This affects the strategy's privacy review gate.

8. **The vCard button.** Should the new export sit next to the existing vCard download as a separate option? Or should it replace or extend the vCard download? For example, should full data go into custom vCard fields? My suggestion: a separate option, with the vCard left unchanged. vCard is also used for CardDAV sync, so changing it could break sync clients.

9. **What stays out of scope.** Please confirm these are out of scope for version 1:
   - (a) exporting a whole vault or account in bulk,
   - (b) importing the file back into Monica,
   - (c) scheduled or automatic exports,
   - (d) an API endpoint for export.

   If round-trip import is a future goal, the file format needs to be designed for it now. Is it?

10. **Success and design.** How will we know this worked? For example: number of exports per week, fewer support requests, or nothing measured. Also, are Figma designs planned? Or is a menu item or button on the contact page simple enough that designs aren't needed? Finally, is anything in the current iteration touching the contact page or the vCard download?

# Stand-in PM answers

1. Mostly right. The users are people who keep their relationship data in Monica, including self-hosters. Some want a personal backup. Others want to reuse the data in other tools or their own scripts. On other parts of the question (for example, users leaving Monica): No strong view. Use your judgement and write the assumption down.

2. It makes "your data is yours" concrete, and data ownership is the thing Monica stands for. On a specific trigger such as request counts or churn: No strong view. Use your judgement and write the assumption down.

3. Agreed. Use one machine-readable file, and JSON is fine. A human-readable version can come later.

4. Include a full copy: everything recorded about this person, including the address-book details the vCard has. It isn't limited to the four examples in the request. On the activity feed specifically: No strong view. Use your judgement and write the assumption down.

5. Agreed. List photos and documents by name and date so people know they exist. Don't include the actual files for now.

6. Agreed. Show who the other people are and how they're connected, but not their own details. It's this contact's copy, not theirs.

7. Anyone who can view the contact can export it, because they can already see all of it.

8. Agreed. Put it on the contact's page next to the vCard download, and leave the vCard download as it is.

9. (a) Out of scope: one contact only. (b) Out of scope: export only, no import. (c) No strong view. Use your judgement and write the assumption down. (d) Out of scope: the download is enough for now. Import isn't planned. Documenting the file's shape is nice to have but not needed now.

10. Success metrics and tracking are out of scope for this test, so don't add tracking. There are no designs: match the vCard link. On in-flight work in the current iteration: No strong view. Use your judgement and write the assumption down.
