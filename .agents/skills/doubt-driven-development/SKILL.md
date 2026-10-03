---
name: doubt-driven-development
description: Cross-examines every non-trivial decision adversarially, in the current conversation, before it stands. Use when you want assumptions cross-examined before proceeding, when stress-testing a plan for hidden failure modes, when correctness matters more than speed, when working in unfamiliar code, when stakes are high (production auth, security-sensitive logic, a high-stakes migration, irreversible operations), or any time a confident output would be cheaper to verify now than to debug later.
---

# Doubt-Driven Development

## Overview

A confident answer is not a correct one. Long sessions accumulate context that quietly turns assumptions into "facts" without anyone noticing. Doubt-driven development is the discipline of attacking your own reasoning — biased to **disprove**, not approve — before any non-trivial output stands.

This project does not spawn subagents. The reviewer is a fresh adversarial reading you perform yourself: restate the artifact and its contract without your reasoning, then argue against them.

This is not a final review. A final review is a verdict on a finished artifact. This is an in-flight posture: non-trivial decisions get cross-examined while course-correction is still cheap.

## When to Use

A decision is **non-trivial** when at least one of these is true:

- It introduces or modifies branching logic
- It crosses a module or service boundary
- It asserts a property the type system or compiler cannot verify (thread safety, idempotence, ordering, invariants)
- Its correctness depends on context the future reader cannot see
- Its blast radius is irreversible (production deploy, data migration, public API change)

Apply the skill when:

- About to make an architectural decision under uncertainty
- About to finish non-trivial work
- About to claim a non-obvious fact ("this is safe", "this scales", "this matches the spec")
- Working in code you don't fully understand

**When NOT to use:**

- Mechanical operations (renaming, formatting, file moves)
- Following a clear, unambiguous user instruction
- Reading or summarizing existing code
- One-line changes with obvious correctness
- Pure tooling operations (running tests, listing files)
- The user has explicitly asked for speed over verification

If you doubt every keystroke, you ship nothing. The skill applies only to non-trivial decisions as defined above.

## Loading Constraints

This skill runs entirely in the current conversation. `AGENTS.md` forbids role-based agents, orchestration chains, and subagent handoffs, so there is no reviewer to spawn and no cross-model CLI to invoke.

- Write ARTIFACT and CONTRACT down before arguing against them, so the review works from the text and not from your memory of the reasoning.
- Do not review the CLAIM you are trying to disprove. Reading your own conclusion first biases the review toward agreement.
- When the reasoning is long enough that you cannot honestly claim a fresh reading, say so in the report and ask the user for a second pair of eyes.

## The Process

Copy this checklist when applying the skill:

```
Doubt cycle:
- [ ] Step 1: CLAIM — wrote the claim + why-it-matters
- [ ] Step 2: EXTRACT — isolated artifact + contract, stripped reasoning
- [ ] Step 3: DOUBT — ran an adversarial reading against the artifact text
- [ ] Step 4: RECONCILE — classified every finding against the artifact text
- [ ] Step 5: STOP — met stop condition (trivial findings, 3 cycles, or user override)
```

### Step 1: CLAIM — Surface what stands

Name the decision in two or three lines:

```
CLAIM: "The new caching layer is thread-safe under the
        read-heavy workload described in the spec."
WHY THIS MATTERS: a race here corrupts user data and is
                  hard to detect in QA.
```

If you can't write the claim that compactly, you have a vibe, not a decision. Surface it before scrutinizing it.

### Step 2: EXTRACT — Smallest reviewable unit

The review needs the **artifact** and the **contract**, not the journey.

- Code: the diff or the function — not the whole file
- Decision: the proposal in 3–5 sentences plus the constraints it has to satisfy
- Assertion: the claim plus the evidence that supposedly supports it (kept distinct from the Step 1 CLAIM block, which is the hypothesis under scrutiny)

Strip your reasoning. Conclusions in hand become confirmed conclusions. The unit must be small enough to hold in mind in one read — if it's a 500-line diff, decompose first.

### Step 3: DOUBT — Argue against the artifact

Write the review down with an adversarial frame. Framing decides the answer.

```
Adversarial review. Find what is wrong with this artifact.
Assume the author is overconfident. Look for:
- Unstated assumptions
- Edge cases not handled
- Hidden coupling or shared state
- Ways the contract could be violated
- Existing conventions this might break
- Failure modes under unexpected input

Do NOT validate. Do NOT summarize. Find issues, or state
explicitly that you cannot find any after thorough examination.

ARTIFACT: <paste artifact>
CONTRACT: <paste contract>
```

Work through that list against the artifact text, not against your memory of writing it. Check each finding against the existing code before keeping it: an artifact that breaks a convention another module already follows is a finding, not a style preference.

If a finding needs a second pair of eyes you do not have, surface it to the user with the artifact and your specific concern. Do not invent a reviewer.

### Step 4: RECONCILE — Fold findings back

Your own findings are data, not verdict. Re-read the artifact text against each finding before classifying — rubber-stamping is the same failure mode as ignoring it.

For each finding, classify in this **precedence order** (first matching class wins):

1. **Contract misread** — the finding exists because the CONTRACT was unclear or incomplete. Fix the contract first, re-classify on the next cycle.
2. **Valid + actionable** — real issue requiring a change to the artifact. Change it, re-loop.
3. **Valid trade-off** — issue is real but cost of fixing exceeds cost of accepting. Document the trade-off explicitly so the user sees it.
4. **Noise** — you flagged something that's actually correct under context you dropped when you wrote the contract. Note it, move on, and ask: would adding that context to the contract have prevented the false flag?

Self-review has the blind spot any reviewer would: what you never considered, you never flagged. Don't defer to a finding just because you found it.

### Step 5: STOP — Bounded loop, not recursion

Stop when:

- The next pass returns only trivial or already-considered findings, **or**
- 3 cycles completed (escalate to user, don't grind a fourth alone), **or**
- User explicitly says "ship it"

If after 3 cycles the review still surfaces substantive issues, the artifact may not be ready. Surface this to the user — three unresolved cycles is information about the artifact, not a reason to keep looping.

If 3 cycles is "obviously insufficient" because the artifact is large: the artifact is too big — return to Step 2 and decompose. Do not lift the bound.

## Common Rationalizations

| Rationalization | Reality |
|---|---|
| "I'm confident, skip the doubt step" | Confidence correlates poorly with correctness on novel problems. Moments of certainty are exactly when blind spots hide. |
| "Self-review is theatre; I already know it works" | It is theatre when you argue from memory instead of from the artifact text. That is the whole skill. |
| "The review will just nitpick" | Only if unscoped. Constrain it to "issues that would make this fail under the contract." |
| "I'll doubt at the end instead" | Doubt-driven catches wrong directions early, when course-correction is cheap. Review at the end is a different, later check. |
| "If I doubt every step I'll never ship" | The skill applies to non-trivial decisions, not every keystroke. Re-read "When NOT to Use." |
| "A second opinion is always better" | Not when it produces noise from missing context. Reconcile, don't defer. |
| "Cross-model review is always better" | This project has no external reviewer step. Ask the user when you actually want their eyes on the artifact. |

## Red Flags

- Running a full doubt cycle on a one-line rename or formatting change
- Arguing from memory instead of re-reading the artifact text
- Looping >3 cycles without escalating to the user
- Asking "is this good?" instead of "find issues"
- Skipping doubt under time pressure on a high-stakes decision
- Repeating the cycle on an unchanged artifact (you'll get the same findings; you're stalling)
- **Doubt theater (checkable signal)**: across 2 or more cycles where substantive findings surfaced, zero findings were classified as actionable. You are validating, not doubting. Stop and escalate.
- Doubting only after finishing the change — that is review, not doubt-driven development
- Stripping the contract from the review
- Reviewing the CLAIM you are trying to disprove (biases toward agreement)

## Interaction with Other Skills

- **`code-review-and-quality`**: complementary. That skill reviews a finished change; doubt-driven is the in-flight check per decision.
- **`source-driven-development`**: SDD verifies *facts about frameworks* against official docs. Doubt-driven verifies *your reasoning about the artifact*. SDD checks the API exists; doubt-driven checks you used it correctly under the contract.
- **`test-driven-development`**: TDD's RED step is doubt made concrete — a failing test is a disproof attempt. When TDD applies, that failing test *is* the doubt step for behavioral claims.
- **`debugging-and-error-recovery`**: when the review surfaces a real failure mode, drop into the debugging skill to localize and fix.
- **Repo orchestration rules** (`AGENTS.md`): this skill runs in the current conversation and never hands work to another role — see Loading Constraints above.

## Verification

After applying doubt-driven development:

- [ ] Every non-trivial decision (per the definition above) was named explicitly as a CLAIM before standing
- [ ] At least one adversarial reading per non-trivial artifact (a failing test produced by TDD's RED step satisfies this for behavioral claims, per Interaction with Other Skills)
- [ ] The review worked from the artifact text plus the contract, without the CLAIM and without your reasoning
- [ ] The review frame was adversarial ("find issues"), not validating ("is it good")
- [ ] Findings were checked against the existing code and classified using the precedence: contract misread / actionable / trade-off / noise
- [ ] A stop condition was met (trivial findings, 3 cycles, or user override)
- [ ] Anything that could not be settled alone was surfaced to the user instead of resolved by guesswork
