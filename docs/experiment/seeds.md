# Seeds

*29 Sep 2026 · approved by Mike, awaiting Garry. A seed is frozen once both approve it.*

Rules: stakeholder's voice, what they want and why. It may say where users expect to find it.
It names no classes, files, routes, tables, libraries or data structures. Under 120 words.

---

## Pilot feature · JSON export

Used to prove the process and the scoring before the real runs.

> People who use Monica have asked for another way to get a contact's details out. Today you
> can download a contact as a vCard. That works for address books, but people who want to use
> their contact data in other tools or in their own scripts find vCard hard to work with. They
> would like to download a contact as JSON too, from the same place they download the vCard.

*Original GitHub issue, kept for the record:* "We noticed Monica exports contacts in VCard
format. We would like to add a simple feature to export a contact in JSON format to improve
system interoperability and extensibility. We plan to create a ContactJsonExportController and
add a button next to the VCard one."

Approved: ☑ Mike (29 Sep) ☐ Garry

---

## Test feature · full record

Used for the main round, the one whose results count.

> People want to take a full copy of a contact out of Monica. The vCard download only gives
> the address-book details. They want everything they have recorded about that person: notes,
> relationships, reminders and important dates. They want it as a file they can keep or use
> in other tools, downloaded from the contact's page.

Approved: ☑ Mike (29 Sep) ☐ Garry

Kept as written, on purpose. Two things are left for the agent to surface or fill in, and the
test-feature intent reference must fix the right answer for each:

1. **Format.** The seed says "a file", not JSON. Does the agent ask or pick?
2. **Scope.** Is "notes, relationships, reminders and important dates" the complete list, or
   examples of "everything"? Monica also records activities, tasks, gifts, pets, goals,
   journal entries, photos and documents. This decides what counts as scope creep.
