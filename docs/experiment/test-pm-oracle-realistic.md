# Test feature: PM answer sheet, realistic version

*Draft · 29 Sep 2026 · what a real PM for Monica would plausibly know, grounded in
`docs/product/strategy.md`. Finer calls are left to the fallback on purpose. This is not the
intent reference.*

Whoever answers the agent's questions uses **only** this sheet, and logs every question and
answer.

**Rule for anything not on this sheet:** reply exactly:

> No strong view. Use your judgement and write the assumption down.

---

| If asked about… | Answer |
|---|---|
| Who the users are | People who keep their relationship data in Monica, including self-hosters. Some want a personal backup; some want to reuse the data in other tools or their own scripts |
| Why now | It makes "your data is yours" concrete. Data ownership is the thing Monica stands for |
| Format | One machine-readable file (JSON is fine). A human-readable version can come later |
| Which data | A full copy: everything recorded about this person, including the address-book details the vCard has. Not just the four examples in the request |
| Photos and documents | List them (name and date) so people know they exist. Don't put the actual files in for now |
| Other people in the record (relationships and similar) | Show who they are and how they're connected, but not their own details. It's this contact's copy, not theirs |
| Who can export | Anyone who can view the contact. They can already see all of it |
| Where the option goes | On the contact's page, next to the vCard download. Keep the vCard download as it is |
| More than one contact at once | No, one contact |
| Importing the file back | No, export only |
| An API or endpoint for scripts | No, the download is enough for now |
| Documenting the file's shape | Nice to have, not needed now |
| Size or speed | Normal contacts are small. Don't over-engineer it |
| Designs or mock-ups | None. Match the vCard link |
| Success metrics or tracking | Out of scope for this test. Don't add tracking |
| Paid plans | Doesn't apply. Everyone gets it |
