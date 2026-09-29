# Test feature: exploratory document run

*29 Sep 2026 · one run per arm · spec, tickets and plan only · before the intent reference
exists*

## What ran

This is the same harness and model (`claude-opus-5-5`) as pilot run 1, with the test-feature
seed and a deliberately thin answer sheet. The sheet answered only the basics: users, the
contact page, one contact, export only, no tracking, all plans. Scope, format and privacy
questions all got the fixed "No strong view" reply. Arm 0 is prompt plus plan mode (0b), not
straight to code.

With no intent reference yet, blind scorers built a **decision inventory**. For each of 18
open points, they recorded what each run decided, and whether it asked, flagged the choice,
or decided silently.

## Results

| Arm | How it read "full copy" | Silent product decisions | Build details in spec | Time | Cost |
|---|---|---|---|---|---|
| 0: Prompt + plan | **Narrow:** the four named items plus identity. Contact details and addresses **left out** | 10 | n/a (plan) | 3.4 min | $1.01 |
| A: No architecture | **Everything:** about 20 data types | 13 | 0 | 4.9 min | $1.50 |
| B: Architecture, no fence | **Everything:** 21 sections | 14 | ~19 | 6.3 min | $2.17 |
| C: Architecture, fenced | **Everything:** 18 categories | 15 | 0 | 5.5 min | $1.91 |

Each arm asked 10 questions. The thin sheet deferred 6 to 10 of them.

## What it tells us

1. **This feature separates the arms. The pilot didn't.** The biggest split is scope. Arm 0
   read "full copy" narrowly and left out the contact's phone numbers, emails and addresses.
   The pipeline arms took everything. That is exactly the kind of consequential, hard-to-undo
   decision we want to measure.
2. **Every arm makes 10 to 15 product decisions silently.** The pipeline doesn't reduce the
   count here. The pipeline arms cover more scope, so they make more decisions. Silent counts
   mean little until the intent reference says which of these decisions matter.
3. **The fence result repeats a third time.** Arm B's spec named about 19 build details:
   routes, permission gates, `BaseService`, Laravel, Inertia. Arms A and C named none.
4. **A new failure to count: false authority.** Arm B's tickets say "The PM decided" about
   relationships and photos, which the PM deferred. An agent's own call was relabelled as the
   stakeholder's. That is worse than silent, because it passes the human gate as approved.
5. **The answer sheet's generosity is a design decision in itself.** A generous sheet hands
   over answers (the pilot got 22 of 22). A thin sheet makes everything the agent's call. For
   the main round, the sheet should hold what a real PM would know, and nothing more.

## Where the arms disagree: agenda for the intent reference

These are the decisions to settle with Garry. Each one is where the arms diverged, or where
nobody decided.

| Decision | What the arms did | Needs a right answer |
|---|---|---|
| Scope: named four, or everything? | Arm 0: four. A, B, C: everything | **Yes, the headline decision** |
| Address-book details (phones, emails, addresses) | Arm 0 excluded them. A, B, C included them | Yes |
| Journal posts about the contact | A included them, handling others' privacy. B and C excluded them ("belong to the vault"). Arm 0 excluded them | Yes |
| Gifts, mood tracking, timeline, avatar | Mixed or unmentioned. Nobody handled the avatar | Yes: a complete list |
| Other contacts (relationships, loans) | All: name only (A, C for privacy; B for scope) | Probably name only. Confirm |
| Note authors from other vault members | 0, B, C: name included. A: unresolved | Yes: a privacy call |
| Who can export the full record | All: viewers too | Confirm. Notes are more sensitive than vCard |
| Format | All: JSON (not fixed by the seed) | Confirm |
| Format version in the file | 0, B, C: yes. A: no | Minor |
| Filename | 0: name. A, B, C: name plus date | Minor |
| Reminder recurrence, past addresses | Nobody decided | Yes, or exclude |
| Release all at once | A, B, C: hide until every ticket ships | Mechanism: record only |

## Caveats

- One run per arm, documents only, and no answer key yet. So there's no right or wrong here,
  only divergence.
- Arm B's leakage count is the scorer's estimate.
- The scorers are the same model family as the generator.
