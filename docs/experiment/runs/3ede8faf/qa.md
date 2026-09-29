# Questions

# Questions for the PM — Contact JSON download

## Context notes

These context files were missing. I continued without them:

- `docs/product/personas.md`. I can't name personas from it, so question 2 asks you to confirm who the users are.
- `CLAUDE.md`
- `docs/ticket-creation-context.md`
- `docs/iterations/current.md`. I can't check for in-flight work that conflicts with this.

`.claude/skill-config.md` sets `ticket_tool: none`, so tickets will go to `output/tickets.md`.

What I found in the context I did read:

- Any member of a vault can download a contact as a vCard today, including members with view-only access. (A vault is a shared workspace that holds contacts.)
- Monica has no public way to get contact data in a machine-readable format today. The external API only covers users and vaults.
- The strategy says Monica "won't compete on feature count" and should prune features. It also names "provable trust" and "transparent, auditable data handling" as capabilities to build. This feature fits the strategy only if we frame it as data ownership. It does not fit if we frame it as one more feature.

## Proposed framing

- **Problem:** people cannot get a contact's data out of Monica in a format that is easy to use in other tools or scripts.
- **Primary users:** technical, privacy-motivated users who want full control of their own data.
- **Scope boundary:** one contact at a time, downloaded as a JSON file from the same place as the vCard download.
- **In-flight work:** unknown, because `docs/iterations/current.md` is missing.

## Questions

1. **Framing check.** Is the framing above right, or do you want a different angle? One option is to frame this as the first step toward a full data export.

2. **Who asked, and how many?** Who asked for this: hosted (paid) users, self-hosters, or both? Roughly how many asked, and where (GitHub issues, support email, Discord)? Without `personas.md`, I plan to describe the primary user as "a technical, privacy-motivated user who works with their own data in scripts." Does that match?

3. **Why now?** The strategy says the top goal is winning the first 30 days, and it pushes against adding features. What makes this the right thing to build now? I plan to argue that it makes "your data is yours" something users can check for themselves, which supports the "provable trust" capability. Is that your reasoning, or is there another trigger?

4. **What goes in the file? (biggest scope question)** Should the JSON file contain:
   - (a) only what the vCard already contains (names, phone numbers, email addresses, postal addresses, birthday, and similar), or
   - (b) everything Monica stores about the contact, such as notes, reminders, tasks, calls, relationships, life events, pets, loans, goals, mood logs, and quick facts?

   Option (b) is much more useful for scripts, but it is a much larger build. If the answer is (b), is there anything that must be left out?

5. **Photos and documents.** Should files attached to the contact be left out, listed by name only, or included in the download?

6. **Format stability.** Will people's scripts depend on the JSON structure staying the same? If so, we need a documented structure and a promise not to break it without warning (a versioned format). Or is a best-effort format that may change between releases acceptable for this first version?

7. **Who can download.** Should the permission rule match the vCard download, so any vault member (including view-only members) can download? Also: a JSON file can include more than a vCard, such as private notes and details about related people. Does this need to pass the privacy review gate from the strategy before build?

8. **What is out of scope?** I plan to exclude these items. Please confirm or change:
   - downloading many contacts at once, or a whole vault
   - getting contact JSON through the API with a token (for fully automated scripts)
   - importing a contact from JSON

   Is there a later phase you already have in mind, such as a full account export?

9. **Designs.** This probably needs only a second download option next to the existing vCard download. Is that "no designs needed", or should a designer decide how the two options appear?

10. **Success measure.** How will we know this worked? For example: download counts, closing specific GitHub issues, or fewer support requests about getting data out. Or is that not worth measuring for a change this small?

# Stand-in PM answers

1. No strong view. Use your judgement and write the assumption down.

2. The users are people who keep their relationship data in Monica, including self-hosters. Some are developers who want to reuse the data in scripts or other tools. Paid plans don't apply; everyone gets it. On who asked and how many: No strong view. Use your judgement and write the assumption down.

3. No strong view. Use your judgement and write the assumption down.

4. (a). Include the same details the vCard download includes, and nothing more. Notes, relationships, reminders and other Monica data are not in this one. Maybe later. For dates, include only the birthday, like vCard. For a birthday with no year, keep what we know and don't make up a year. Empty fields must not cause a failure. Empty is fine, but be consistent.

5. Leave the photo or avatar out. Other Monica data is not in this one.

6. Don't use a specific JSON standard such as jCard. A plain, readable shape is fine. Documenting the shape is nice to have, not needed now. Pretty-printed or compact: no strong view.

7. Anyone who can view the contact can download, same as the vCard download. The file contains only the vCard details, not notes or relationships. On the privacy review gate: No strong view. Use your judgement and write the assumption down.

8. Confirmed: one contact only, no API or endpoint (downloading the file is enough), and export only, no import. Other Monica data is "maybe later." On a full account export phase: No strong view. Use your judgement and write the assumption down.

9. No designs. Put the option next to the vCard download on the contact page and match the vCard link. Name the file after the contact, like the vCard file.

10. Out of scope for this test. Don't add tracking.
