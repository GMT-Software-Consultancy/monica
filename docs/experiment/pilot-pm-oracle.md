# Pilot feature: PM answer sheet (the "oracle")

*Draft · 29 Sep 2026 · frozen together with the intent reference*

Whoever answers the agent's questions at a gate (a person or a stand-in agent) uses **only**
this sheet. Log every question asked and the answer given.

**Rule for anything not on this sheet:** reply exactly:

> No strong view. Use your judgement and write the assumption down.

That keeps every unplanned decision visible in the transcript, so we can score it as surfaced
or silent.

---

| If asked about… | Answer |
|---|---|
| Who the users are | People who keep their relationship data in Monica, including self-hosters. Some are developers who want to reuse the data in scripts or other tools |
| Who may export | Anyone who can view the contact, same as the vCard download |
| Which fields | The same details the vCard download includes. Nothing more is needed |
| Notes, relationships, reminders, other Monica data | Not in this one. Maybe later |
| Photo or avatar | Leave it out |
| Birthdays with no year | Keep what we know. Don't make up a year |
| Other important dates | Only the birthday, like vCard |
| Empty fields | Must not fail. Empty is fine, just be consistent |
| A specific JSON standard (jCard etc.) | No. A plain, readable shape is fine |
| Documenting the shape | Nice to have, not needed now |
| Where the option goes | Next to the vCard download on the contact page |
| Filename | Named after the contact, like the vCard file |
| More than one contact at once | No, one contact |
| Importing JSON | No, export only |
| An API or endpoint for scripts | No. Downloading the file is enough |
| Pretty-printed or compact | No strong view |
| Success metrics or tracking | Out of scope for this test. Don't add tracking |
| Paid plans | Doesn't apply. Everyone gets it |
| Designs or mock-ups | None. Match the vCard link |
