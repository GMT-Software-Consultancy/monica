## Run 3ede8faf: blind score against the intent reference

| # | Score | Evidence |
|---|---|---|
| 1 | matched | "on the contact page, next to the existing vCard download" |
| 2 | matched | "Download as JSON" |
| 3 | matched | "Anyone who can view the contact"; separate AC for a view-only member |
| 4 | matched | Non-vault user gets "does not return the file". Signed-out users are covered only by "Nobody else can" |
| 5 | matched | No new exclusions; applies to any contact the user can view |
| 6 | matched | By rule: "same details as the vCard download … and nothing more". The list itself is left to engineering as an open question (flagged) |
| 7 | matched | "nothing more"; no extra identity fields |
| 8 | missing (M) | IDs and timestamps not addressed. The vCard rule would bring in last-changed implicitly |
| 9 | missing | Nothing says contact details carry their type |
| 10 | missing | Address type and separate parts not specified |
| 11 | missing | Nothing says gender and labels are readable names. "same value" as vCard only implies it |
| 12 | matched | "shows the day and the month … the year is empty … no invented year"; birthday is the only date |
| 13 | matched | Notes, relationships, reminders, tasks, photo/avatar and "other Monica data" excluded |
| 14 | matched | Name-only contact exports; "each empty single value is `null` and each empty list is `[]`" |
| 15 | missing (partial) | Well-formed is covered ("standard JSON parser reads the file"). UTF-8 and non-Latin characters are not mentioned |
| 16 | matched | "downloads one file"; ticket exit point "stays on the contact's page" |
| 17 | matched | "same as the vCard file name … ends in `.json`" |
| 18 | missing (acceptable) | Empty-filename fallback not addressed; the reference doesn't require one |
| 19 | matched | "One contact at a time" |
| 20 | matched | "No API … or any other programmatic endpoint" |
| 21 | matched | "Any change to the existing vCard download" is out of scope; regression AC in tickets |
| 22 | missing (partial) | Only "must not add tracking". Nothing says the data goes only to the browser |

Totals: 15 matched, 0 changed and flagged, 0 changed silently, 7 missing (1 of them mechanism-side: #8).

Side note: the spec treats the photo as a vCard detail it is leaving out ("except the photo"). The reference says the vCard has no photo, so this is a factual slip with no effect on the result.

## Possible scope creep

Nothing material.
- **Pretty-printing:** made a requirement, with a ticket AC ("line breaks and indentation"). It is a format choice and is flagged as an assumption.
- **Paid-plan AC:** "no prompt to upgrade" in tickets. It comes from the PM answer, so it isn't creep.
- **Phase 2 and full account export:** mentioned as "possible, not committed". Not creep.

## Build-detail leakage

**Spec: 3**, all repo file paths in process notes, none describing the product:
- `docs/product/personas.md`
- `docs/iterations/current.md`
- `output/tickets.md`

The spec also uses `null` and `[]`. I did not count these: they describe what the user sees in the file.

**Tickets: 1**, the header metadata `output/spec.md`. The access-denial AC avoids naming a route ("open the address of that contact's JSON download"). No classes, tables, frameworks or API shapes appear in either document.

## Answer-sheet fallbacks turned into decisions

| Question | Decision taken | Flagged? |
|---|---|---|
| Q1 framing | Data ownership / "provable trust" | Partly: the Why-now section is labelled an assumption, the problem framing is not |
| Q2 who asked, how many | "community channels, such as GitHub issues. We do not know how many" | Yes, as an assumption. But Background says "Users have asked…" as fact |
| Q3 why now | Provable-trust argument | Yes: "This reasoning is an assumption. The PM had no strong view." |
| Q6 pretty vs compact | Pretty-printed | Yes (Assumptions), also listed under Decisions made |
| Q7 privacy review | Not needed | Yes: riskiest assumption plus an open question owned by the PM |
| Q8 full account export | Possible later, not committed | Yes |

Also flagged: the tickets assign the "Provable trust" initiative themselves and ask the PM to confirm it.

## Summary

The documents match the reference on all access, scope, birthday, empty-field, filename and no-API decisions, and every fallback they turned into a decision is flagged. The gaps are in how the file is shaped: contact-detail types (#9), address types and parts (#10), readable gender and labels (#11), UTF-8 (#15) and where the data goes (#22) are left unsettled. Product-side "changed silently" count: **0**.
