# Questions

# Questions for the requester: "Download contact as JSON"

Context: today the contact page's sidebar has a "Download as vCard" link (`resources/js/Pages/Vault/Contact/Show.vue:272`). It posts to `contact.vcard.download` (`routes/web.php:257`), which builds the file with `ExportVCard` in `ContactVCardController`. The app has no JSON representation of a contact yet. The API has only `UserResource` and `VaultResource`.

1. **Which data should the JSON include?** Should it contain only what the vCard has today (names, nickname, birthday, gender, emails/phones, addresses, photo, etc.)? Or should it be a fuller export that also covers what vCard can't hold, such as notes, tasks, reminders, important dates, relationships, pets, goals, loans, life events, groups, labels, and quick facts?

2. **Who should see a contact's private or sensitive data in the export?** For example, notes and journal-linked data. Any user who can *view* the vault can see the contact page today. Should a viewer-level user be able to download everything, or should some fields require editor/manager rights?

3. **How should the JSON be structured?** Do the users have a format in mind, such as jCard (RFC 7095, the JSON form of vCard) or the JSON shape of an existing tool? Or is a Monica-specific structure (`{ "id": ..., "first_name": ..., "contact_information": [...] }`) fine?

4. **Should the format be stable over time?** People will write scripts against it. Should it include a `version`/schema field and be treated as a public contract, so fields aren't renamed or removed without notice? Should we document it?

5. **How should related records be represented?** Take relationships to other contacts, groups, labels, and so on. Should the JSON include just IDs/names, or nested objects? Should referenced contacts be included in any depth?

6. **Should the photo/avatar and attached files be included?** Options are embedding them as base64, linking them by URL (which requires being logged in), or leaving them out.

7. **Should IDs and timestamps be included?** Examples are the contact UUID, vault ID, and `created_at`/`updated_at`. If so, should date-only values like birthdays use ISO 8601 strings, and should the export keep partial dates such as a birthday with no year?

8. **Is this only a single-contact download from the contact page?** Or do the users also want it for several contacts, e.g. a whole vault or a group, or from the contact list? Should the same JSON also be available through the Sanctum API, so scripts can fetch it without clicking a download link?

9. **How should it appear in the UI?** Should it be a second link next to "Download as vCard" (e.g. "Download as JSON"), or a single "Download" menu with a choice of format? Should the file be named like the vCard (`{contact-name-slug}.json`)?

10. **Is there a round-trip expectation?** Should users be able to *import* this JSON back into Monica later, e.g. to move contacts between instances or vaults? If so, the format needs to be designed for that from the start, even if import comes later.

# Stand-in PM answers

1. The same details the vCard download includes. Nothing more is needed. Notes, relationships, reminders and other Monica data are not in this one. Maybe later.

2. Anyone who can view the contact can export, same as the vCard download. Notes and similar data aren't included anyway.

3. No specific standard such as jCard. A plain, readable shape is fine.

4. Documenting the shape is nice to have, not needed now. On a version field and treating it as a stable contract: No strong view. Use your judgement and write the assumption down.

5. Relationships and other Monica data are not in this one. Maybe later.

6. Leave the photo/avatar out.

7. For birthdays with no year, keep what we know and don't make up a year. Include only the birthday, like vCard, and no other dates. On IDs, timestamps and ISO 8601 formatting: No strong view. Use your judgement and write the assumption down.

8. Only one contact. No API or endpoint, because downloading the file is enough.

9. Put it next to the vCard download on the contact page. There are no designs, so match the vCard link. Name the file after the contact, like the vCard file.

10. No, export only.
