# Questions

# Questions before planning the "full contact export" feature

Context: right now the contact page has a single `POST .../contacts/{contact}/vcard` download (`ContactVCardController`). That route sends the serialized vCard back through an Inertia flash. There is no other export in the app yet, at the account, vault or contact level.

1. **File format.** Which format should the download use? Options: (a) JSON, which keeps the full structure and is easy for other tools to read; (b) a human-readable document such as HTML, PDF or Markdown; (c) a ZIP holding the vCard, a `.ics` file for dates and reminders, and a JSON or Markdown file for everything else. Which "other tools" do people have in mind? That will decide the answer.

2. **Scope beyond the four named items.** The request names notes, relationships, reminders and important dates. A contact also has goals and streaks, loans, tasks, calls, pets, addresses, contact information, job and company, religion, labels, groups, quick facts, life/timeline events, documents and photos. Should the export cover everything recorded on the contact, or only the four named items for a first version?

3. **Documents, photos and avatar.** If files are in scope, should the actual binary files be included (which means a ZIP and possibly a large download), or only their metadata or links?

4. **Relationships and other contacts.** A relationship points to another contact. How much of that contact should appear: only the name and relationship type, or a summary of their details as well? The same question applies to contacts mentioned in shared life events, loans and group memberships.

5. **Re-import.** Is this only for keeping a copy and reading it elsewhere, or should Monica, now or later, be able to import the file back? For example, to restore a deleted contact or move it to another instance. If import is expected, the format needs to be stable and versioned, and the plan changes a lot.

6. **Relationship to the existing vCard download.** Should the new download sit next to the current "Download as vCard" option, replace it, or include the vCard inside it?

7. **Who can export.** Any vault member who can view the contact can currently download the vCard, because the route only uses `can:contact-owner`. Should the full export be open to every viewer too, or only to editors and managers? Should exports be logged or audited, given how personal notes can be?

8. **Content visibility.** Are there records a viewer can see in the UI that should *not* go into a file? For example, notes or journal entries written by other vault members, or items tied to modules that are turned off in the contact's template.

9. **Language and dates.** Should labels and values such as relationship type names, reminder frequencies and date formats follow the downloading user's locale and date settings, or should the file use a fixed format (for example ISO dates and English keys) so tools can read it reliably?

10. **Bulk export.** Is a per-contact download enough, or do people also expect to export several contacts, or a whole vault, in the same format? If so, it should be designed for now even if it ships later.

# Stand-in PM answers

1. One machine-readable file; JSON is fine. A human-readable version can come later. Some users want a personal backup, and some want to reuse the data in other tools or their own scripts.

2. A full copy of everything recorded about this person, including the address-book details the vCard has. Don't limit it to the four examples in the request.

3. List photos and documents by name and date so people know they exist. Don't include the actual files for now.

4. Show who they are and how they're connected to this contact, but not their own details. It's this contact's copy, not theirs.

5. No, export only.

6. Put it on the contact's page, next to the vCard download. Keep the vCard download as it is.

7. Anyone who can view the contact can export, since they can already see all of it. On logging or auditing: No strong view. Use your judgement and write the assumption down.

8. Viewers can already see all of it, and the export should be a full copy of everything recorded. On modules turned off in the template: No strong view. Use your judgement and write the assumption down.

9. No strong view. Use your judgement and write the assumption down.

10. No, one contact.
