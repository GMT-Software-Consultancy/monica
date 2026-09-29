# Monica — Product Strategy

**Mode:** best guess · create
**Status:** Draft for review · 2026-07-02
**Framework:** Rumelt kernel (diagnosis → guiding policy → coherent action), wrapped in Roger Martin's Playing-to-Win cascade.

> Confidence tags: 🟢 verified from evidence · 🟡 plausible inference · 🔴 assumption to validate.
> This is a best-guess draft built from the README and repo context. Every load-bearing 🔴 is listed in the closing section with a cheap test.

---

## Thinking

- Monica has a clear vision ("help people have more meaningful relationships"), strong values, and a long feature list — but no diagnosis and no how-to-win. This doc supplies those.
- The strongest existing asset is the **"What Monica isn't"** discipline: not social, not surveillance, not a data-mining tool. That is a genuine, defensible position and the whole strategy is built to compound it.
- The hardest live tension is **AI**. The README rules it out on principle. In 2026 that is simultaneously Monica's biggest exposure (AI-native tools can collapse the manual-entry cost) and, handled correctly, its biggest differentiator. This strategy resolves the tension by distinguishing *surveilling* AI from *private, user-controlled* assistance.
- Working assumption throughout: the binding constraint is **adoption/retention**, not features or contributors. The next step is to confirm that from the hosted retention curve before over-investing.

---

## Diagnosis

**Central challenge:** Monica's value compounds only after months of disciplined manual data entry, but the entire cost is paid up front — and self-hosting stacks a *second* up-front cost on top. Most users abandon in the gap before the value arrives, so the vision dies in onboarding, not in the roadmap.

**Contributing factors:**
- 🟡 The core loop (log people, log interactions, get reminded) is high-effort and low-immediate-reward — the classic retention cliff of journaling and PRM tools. An empty vault on day one gives back nothing.
- 🟡 Self-hosting narrows the top of funnel to the technical-and-privacy-motivated minority. The **hosted paid plan** is the real commercial engine that funds the two maintainers, yet it is nearly invisible in how the product is positioned.
- 🔴 "No AI, on principle" is stated as a value, but it forecloses the single capability that could most reduce the manual-entry cost. The principle is right in spirit (no surveillance) but, taken literally, it blocks the fix to the core obstacle.
- 🟢 Capacity is a hard constraint: two core maintainers plus community. Any strategy that assumes a large, fast-moving team is incoherent with reality. Focus is not optional — it is forced.

**This is NOT:**
- Not "we need more features." The feature list already exceeds 20 items; breadth is not the problem, and more of it worsens the maintenance constraint.
- Not "we need more contributors." That is a goal downstream of having a sharp diagnosis worth contributing toward.
- Not "we need more marketing." You cannot market your way past a day-30 retention cliff; it just fills a leaky bucket faster.

---

## Guiding Policy

Win the **first 30 days**, then defend the moat that AI-native incumbents structurally cannot copy. Concretely: collapse time-to-first-value so the product rewards a user *before* the manual-entry cost compounds, and make **provable privacy** (open source + self-host + no surveillance) the source of advantage — including a *private, user-controlled* assist layer that reduces entry cost without ever breaking the no-surveillance promise. Everything else gets pruned or declined.

The actions cohere because each one either (a) shortens time-to-first-value or (b) deepens the privacy advantage — and privacy is precisely the axis on which surveillance-funded competitors cannot follow without cannibalizing their own business model.

---

## Playing-to-Win Cascade

- **Winning aspiration:** Be the tool that privacy-conscious people reach for to remember what matters about the people they care about — chosen *because* the data is theirs alone.

- **Where to play:**
  - **Segment:** deliberate rememberers who won't hand their relationship data to surveillance tools — the already-validated base (introverts, neurodivergent users, people who struggle to recall personal details) *plus* privacy-motivated professionals with large personal networks. Not "everyone who has friends."
  - **Primary channel / commercial engine:** the **hosted plan** at monicahq.com — the funnel that must convert and fund the project. Self-hosting is the *trust anchor and community engine*, not the growth engine.
  - **Occasion:** the moment just before or after a real interaction ("who am I seeing, what do I need to remember, what just happened") — capture and recall, not admin.

- **How to win:** The only PRM that gives you *compounding relationship memory without ever surveilling you* — a promise made structurally credible by open source and self-host, and impossible for AI-native incumbents to match without abandoning their data-driven economics. Paired with the fastest time-to-first-value in the category so the compounding actually begins.

- **Capabilities required:**
  1. **Fast onboarding / time-to-first-value** — import from contacts/other tools, starter templates, guided first-week prompts so the vault is never empty.
  2. **Low-friction capture** — mobile quick-add and fast interaction logging at the moment it happens; friction here is where retention dies.
  3. **Private, user-controlled assistance** — optional, local/self-hostable or explicitly-consented AI that summarizes, suggests reminders, and reduces typing — *without* sending relationship data to third-party surveillance. This is the reframe of the "no AI" principle, not its abandonment.
  4. **Provable trust** — open source, self-host option, and transparent, auditable data handling as a marketed feature, not just an engineering fact.
  5. **A working monetization engine** — hosted-plan conversion and retention healthy enough to sustain and grow maintainer capacity.

- **Management systems:**
  - A **retention cohort dashboard** (day 1 / 7 / 30 / 90) as the top-line health metric, with an explicit **time-to-first-value** measure.
  - A **privacy review gate** every feature must pass ("does this weaken the no-surveillance promise?") — turns the principle into an enforced system.
  - A **feature-pruning ritual** — a standing bias to remove, given the two-maintainer constraint.
  - **Contributor onboarding** aimed at the capabilities above, so community effort compounds the strategy instead of scattering.

---

## Trade-offs (Can't / Won't)

- **Won't** add surveillance-based or third-party-data-mining AI, even if it demonstrably lifts retention. The no-surveillance promise is the moat; breaking it to grow is suicide by success.
- **Won't** chase enterprise / team / sales CRM. That is a different where-to-play with a different buyer and would shatter focus against the capacity constraint.
- **Won't** become social or networked — reaffirming the existing (correct) principle.
- **Won't** compete on feature count. The product will *prune*; breadth is a liability, not a selling point, for two maintainers.
- **Won't** let the free self-host user's every request override the hosted paying segment that funds development. Community is the trust engine; hosted conversion is the fuel. When they conflict, fuel wins.

---

## Stress Test

**Anti-pattern scan:**
- **Fluff** — not fired; claims are concrete and testable.
- **Failure to face the challenge** — not fired; the obstacle (cost-before-value + self-host tax) is named and central.
- **Mistaking goals for strategy** — not fired; the win condition is a *how* (privacy moat + time-to-value), not "be #1."
- **Feature factory** — *guarded against* by the explicit pruning trade-off, but this is the most likely relapse given community feature requests. Watch it.
- **Metrics theater** — guarded: the one top metric is retention/time-to-value, not a vanity MAU number.
- ⚠️ **Solution smuggling risk** — the "private AI assist" capability is the most likely place to smuggle in a predetermined solution. It must stay subordinate to the diagnosed obstacle (entry cost), not become a feature built because AI is fashionable.

**Coherence audit:** Every capability maps to the obstacle — onboarding, capture, and private-assist all attack *time-to-first-value*; provable trust and monetization deepen the *privacy moat* that funds and defends the whole thing. No capability here addresses only the vision-level goal without touching the obstacle. Coherent.

**Pre-mortem (18 months out, it failed):**
1. **The private-AI line was never drawn cleanly, and trust cracked.** In trying to reduce entry cost, an assist feature quietly sent data to a third-party model. The one thing Monica sells — "your data, only yours" — was compromised, the community revolted, and the moat evaporated overnight.
2. **Time-to-first-value work lost to feature requests.** Community pressure kept the roadmap on breadth (more field types, more integrations) instead of onboarding. The day-30 cliff never moved. Growth stayed flat despite a busy changelog — the feature-factory relapse the trade-off was meant to prevent.
3. **Hosted monetization stayed an afterthought and capacity starved.** Because self-host remained the emotional center of gravity, hosted conversion never became a real engine. Patreon plateaued, the two maintainers burned down, and the project slowed — the vision died of under-resourcing, not bad product.

---

## Decisions, Assumptions, Next Step

**Decisions:**
- Make **winning the first 30 days** the organizing objective; treat retention/time-to-first-value as the top-line metric.
- Reframe the AI principle from "no AI" to "**no surveilling AI**" — permit optional, private, user-controlled assistance as an explicit capability.
- Treat the **hosted plan as the commercial engine** and self-host as the trust anchor; resolve conflicts in favor of the paying segment.
- Adopt an explicit **privacy review gate** and a **feature-pruning** bias as management systems.
- Decline enterprise CRM, social features, and feature-count competition — in writing.

**Assumptions to validate (prioritized):**
- 🔴 The binding obstacle is retention/adoption, not features. *Test:* pull hosted-cohort retention (day 1/7/30/90) and find the cliff — you already have this data.
- 🔴 A private/local AI assist can materially cut entry cost while staying credibly non-surveilling. *Test:* prototype one flow (e.g. summarize a logged interaction locally) and put it in front of 5 privacy-motivated users.
- 🔴 "No AI" is currently a liability, not an asset, with the target segment. *Test:* 5 lapsed-user interviews — count how many cite manual-entry effort as the reason they stopped.
- 🟡 Hosted conversion can become a sufficient engine. *Test:* look up current MRR, Patreon split, and self-host→paid ratio — one number each.
- 🟡 The target segment (privacy-motivated + high-relationship-load) is large enough to sustain the project. *Test:* check where existing paying users came from and why they cite choosing Monica.

**Next step:** Pull the hosted-account retention curve, find the exact day the cohort drops off, and confirm-or-kill the cost-before-value diagnosis — one chart decides whether this entire strategy stands.
