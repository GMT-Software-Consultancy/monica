# Questions

# Questions for the requester: full contact export

Context: today the contact page has a "Download as vCard" action (`POST vaults/{vault}/contacts/{contact}/vcard`, `ContactVCardController`). It only returns the vCard fields. The contact has many more modules (notes, relationships, reminders, important dates, calls, tasks, loans, goals, life events, pets, documents/photos, mood tracking, quick facts, labels, groups, addresses, job, religion, pronouns). There is no data export anywhere else in the app yet.

1. **File format.** "Keep or use in other tools" can mean two different things. Which one matters most?
   - (a) Machine-readable for other tools or scripts: JSON.
   - (b) Human-readable for keeping: PDF, HTML or Markdown.
   - (c) Both, e.g. a ZIP containing `contact.json`, the `.vcf`, and a readable summary.
   My default would be JSON (with the vCard embedded or alongside it).

2. **Scope beyond the four named items.** You listed notes, relationships, reminders and important dates. Should "everything they have recorded" also cover calls, tasks, loans, goals, life events/timeline, pets, mood tracking, quick facts, labels, groups, addresses, job, religion and pronouns? Or should the first version stick to the four named items?

3. **Files and photos.** Contacts can have uploaded documents, photos and an avatar. Should the export include the actual files, which means a ZIP download that could be large? Or only their metadata and links?

4. **Relationships.** For each related contact, is the related person's name, the relationship type and a link/ID enough? Or should their details be included too? My default is name, type and ID only, so one export doesn't pull in other contacts' data.

5. **Round-trip import.** Does the file need to be importable back into Monica (another vault, another instance, or the future stable version)? If yes, the format needs to be a documented, versioned schema, and we would probably also need an importer. If no, a best-effort readable dump is enough.

6. **Who can export.** Should any vault member who can view the contact be able to download it (same as the vCard today)? Or should it be limited to vault editors/managers because it's a bulk copy of private notes?

7. **User-specific data.** Some data is per-user (for example, which users get notified for a reminder, and who wrote each note). Should the export include author names and timestamps on each item? Should it include the reminder notification settings, or only the reminder itself (label, date, frequency)?

8. **Where the button goes.** Should this be a second action next to "Download as vCard" on the contact page, or should it replace it with a menu offering the vCard and the full export? Does the button need to be hidden or disabled for archived contacts?

9. **Size and delivery.** Is an immediate download in the browser acceptable? The vCard currently comes back through an Inertia flash message. Or do you expect very large contacts (many notes/files) where we should build the export in a background job and email or notify the user when it's ready?

10. **Other surfaces and bulk.** Is this contact-page only for now? Or should the same export also be available through the API and/or for several contacts at once (a whole vault)? That affects whether the export logic is built as a reusable service from day one.

# Stand-in PM answers

1. No strong view. Use your judgement and write the assumption down.

2. No strong view. Use your judgement and write the assumption down.

3. No strong view. Use your judgement and write the assumption down.

4. No strong view. Use your judgement and write the assumption down.

5. No. It's export only.

6. No strong view. Use your judgement and write the assumption down.

7. No strong view. Use your judgement and write the assumption down.

8. It goes on the contact's page. For the rest (next to or replacing the vCard action, archived contacts): No strong view. Use your judgement and write the assumption down.

9. No strong view. Use your judgement and write the assumption down.

10. It's for one contact only, from the contact's page. There's no bulk or whole-vault export. On the API: No strong view. Use your judgement and write the assumption down.
