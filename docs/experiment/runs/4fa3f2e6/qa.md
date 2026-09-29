# Questions

# Questions for the PM: Contact JSON export

## Orientation notes

**Context read:**
- `seed.md`
- `docs/product/strategy.md`
- `docs/architecture/` (README, external interfaces, data model, data flow, cross-cutting)
- `.claude/skill-config.md` (`ticket_tool: none`, so tickets will go to `output/tickets.md`)

**Missing context files:**
- `docs/product/personas.md`: no persona names to reference
- `CLAUDE.md`
- `docs/ticket-creation-context.md`
- `docs/iterations/current.md`: no view of work in flight

**What the architecture already tells us:**
- The vCard download today is `POST /vaults/{vault}/contacts/{contact}/vcard`. It sits behind the `contact-owner` gate, which only checks that the contact belongs to the vault. The route bypasses the Inertia middleware.
- The JSON API (`routes/api.php`) is deliberately minimal. It covers users and vaults only, with no contact endpoints.
- A contact has many related records: notes, calls, reminders, tasks, relationships, pets, loans, goals, life events, mood tracking, photos and documents. A vCard carries only a small part of this.

**Proposed framing (please confirm or correct in Q1):**
- **Problem:** people who want to reuse a contact's data in other tools or in their own scripts cannot easily parse vCard.
- **Primary persona:** a technically capable user, probably a self-hoster or power user. I can't confirm this because `personas.md` is missing.
- **Scope boundary:** one contact at a time, downloaded as a JSON file from the same control as the vCard download, in the web app.
- **In-flight work:** unknown, because there is no `current.md`.

---

## Questions

1. **Framing and persona.** Is the framing above right? With no `personas.md`, who is the primary user? My working guess is "a technical user, often a self-hoster, who scripts against their own data." Is there a named persona I should use, and are hosted-plan users also part of this?

2. **Why now.** What triggered this request now? For example, the number of requests, a GitHub issue or discussion thread, or churn feedback. The strategy doc favours pruning features and focusing on the first 30 days, so the spec needs a reason that gets past that. One candidate is "Provable trust: your data is yours and portable." Is that the angle you want, or is there a harder trigger?

3. **What data goes in the file.** This decides the size of the spec. Which of these do you want?
   - (a) The same fields as the vCard (names, contact information, addresses, important dates), just in JSON.
   - (b) The full contact record, including notes, calls, reminders, tasks, relationships, pets, loans, goals, life events and mood tracking.
   - (c) Something in between. If so, which parts?

   My recommendation is (a) for phase 1, with (b) as phase 2.

4. **Photos, documents and avatar.** Should the JSON leave out file attachments, list them as metadata only (name, type, size), or include download URLs? Note that Uploadcare URLs may be public links, which matters for the privacy review.

5. **A stable format for scripts.** The seed mentions "their own scripts". Does the JSON structure need to be a documented, versioned contract that users can rely on between releases? That would mean a `schema_version` field and a published field reference. Or is "best effort, may change" acceptable for phase 1? Also, should the file include internal IDs (contact UUID, vault ID), and should lookup values show as human-readable labels (e.g. gender "Woman") or as internal IDs?

6. **Web download only, or an API endpoint too?** Scripts usually want an authenticated API call rather than a button click. Should this spec cover only the download in the web app, as the seed says, or also a `GET` endpoint under `/api` using the existing Sanctum tokens? My recommendation is web download only, with the API listed as out of scope and phase 2.

7. **Other scope limits.** Please confirm these are out of scope for this spec:
   - Exporting a whole vault or several contacts at once
   - Importing contacts from JSON
   - Other formats (CSV, and so on)
   - Any change to the existing vCard download

   Is anything on that list actually in scope?

8. **Who can download.** The vCard route only checks that the contact belongs to the vault (`contact-owner`). Should JSON export use the same permission, so any vault member including viewers can export? Or should it need a higher role, since a full-record export (option b in Q3) could include private notes? Should an export add an entry to the contact's activity feed?

9. **Edge cases and design.** For contacts with sparse data, should empty fields show as `null` or be left out of the file? Can users export unlisted or partially filled contacts? For design: can we reuse the existing vCard download control with a second option (no Figma needed), or do you want a design pass first?

10. **Success measure.** How will we know this worked? For example: fewer related support requests or issues, a count of JSON downloads after launch, or "shipped, and the request thread closed." If there is no metric, I'll state that as an assumption in the spec.

# Stand-in PM answers

1. Mostly right. The users are people who keep their relationship data in Monica, including self-hosters. Some are developers who want to reuse the data in scripts or other tools. Hosted users are included, because paid plans don't apply and everyone gets it. The scope is one contact at a time, placed next to the vCard download on the contact page. On a named persona: No strong view. Use your judgement and write the assumption down.

2. No strong view. Use your judgement and write the assumption down.

3. (a). Use the same details the vCard download includes, and nothing more. For dates, include only the birthday, as the vCard does. If a birthday has no year, keep what we know and don't make up a year. Notes, relationships, reminders and other Monica data are not in this one. Maybe later.

4. Leave out the photo and avatar. Other Monica data, such as documents, is not in this one.

5. There's no specific JSON standard. A plain, readable shape is fine. Documenting the shape is nice to have, not needed now. On versioning, internal IDs and labels versus IDs: No strong view. Use your judgement and write the assumption down.

6. Web download only. No API or endpoint; downloading the file is enough.

7. Several contacts at once is out: one contact only. Import is out: export only. On other formats and changes to the vCard download: No strong view. Use your judgement and write the assumption down.

8. Anyone who can view the contact can export, the same as the vCard download. On the activity feed entry: No strong view. Use your judgement and write the assumption down.

9. Empty fields must not cause a failure. Empty is fine, just be consistent. There are no designs or mock-ups, so match the vCard link. Name the file after the contact, like the vCard file. On unlisted or partially filled contacts: No strong view. Use your judgement and write the assumption down.

10. Success metrics are out of scope for this test. Don't add tracking.
