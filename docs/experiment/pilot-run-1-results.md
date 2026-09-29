# Pilot run 1: document stages

*29 Sep 2026 · one run per arm · pilot feature (JSON export) · results don't count*

## What ran

Each arm ran from the stakeholder seed to its documents: a plan for arm 0, and a spec and
tickets for arms A to C. No code was written. The code stage needs Monica's test
environment.

- **Model:** every stage used `claude-opus-5-5`, confirmed from each run's own log.
- **Isolation:** each run started from a clean copy of `exp-base-v1`, with none of Mike's
  personal settings, skills or memory.
- **Questions:** a stand-in PM answered each arm's questions from the pilot answer sheet only.
- **Scoring:** four blind scorers each scored one run against the pilot intent reference.

## Results

| Arm | Intent diff (22 decisions) | Silent product changes | Build details in spec | Time | Cost (API-equivalent) |
|---|---|---|---|---|---|
| 0: Prompt | 22 matched | 0 | n/a (plans name code) | 3.2 min | $0.97 |
| A: No architecture | 21 matched, 1 changed silently | 1 | 0 | 5.1 min | $1.63 |
| B: Architecture, no fence | 22 matched | 0 | ~40 | 5.9 min | $2.00 |
| C: Architecture, fenced | 15 matched, 7 missing | 0 | 0 | 3.8 min | $1.35 |

Arm A's one silent change adds a link to the contact's Monica page inside the file.

## What it tells us

1. **The harness works.** The whole pilot cost about $6 and took 6 minutes wall-clock with
   the arms running in parallel. The main round's document stages will take minutes, not
   hours.
2. **The fence result repeats.** Without the fence, arm B's spec named about 40 build
   details: file paths, classes, routes, database columns. With the fence, arm C named none.
   This matches the June document experiment.
3. **The fence has a cost.** Arm C kept the spec at product level ("the same details as the
   vCard, nothing more") and left the file's shape to engineering. That left 7 decisions
   unsettled, such as whether each phone number carries its type. For an export, the file's
   shape *is* the product. So is fencing good here? The code stage will tell us whether
   those gaps turn into drift.
4. **Architecture without the fence gave the most complete spec.** Arm B settled all 22
   decisions, but only by writing the build into the spec.
5. **The prompt-only arm is a strong rival.** Arm 0 asked sharp questions, matched all 22
   decisions, and was the cheapest. Kill criterion 2 (the prompt matches the pipeline) is a
   real risk, not a formality.
6. **The pilot feature is too easy, as expected.** Silent changes were close to zero
   everywhere. Also, our answer sheet gives away most answers to any arm that asks. The test
   feature needs decisions an agent is less likely to ask about.

## Caveats

- One run per arm, documents only, on the easy feature.
- The scorers are the same model family as the generator.
- The run-to-arm mapping was known to the person who launched the scorers, but not to the
  scorers themselves.

## Next

1. Run the code stage for these four runs once the test environment is ready. Then score
   the intent diff on the code.
2. Write the test feature's intent reference and answer sheet. Decide how generous the
   answer sheet should be.
3. Decide with Garry: does a fenced spec that leaves the file's shape to engineering count
   as "missing", or as correct product altitude?
