---
name: using-agent-skills
description: Discovers and invokes agent skills. Use when starting a session, or when you need to decide which skill or workflow applies to the piece of work at hand. This is the meta-skill that governs how all other skills are discovered and invoked.
---

# Using Agent Skills

## Overview

Agent Skills is a collection of engineering workflow skills organized by development phase. Each skill encodes a specific process that senior engineers follow. This meta-skill helps you discover and apply the right skill for your current task.

## Project Overrides

This project is not a phase pipeline. `AGENTS.md` wins over anything below.

- The skill selection table in `AGENTS.md` is the source of truth. Load only the skills that table maps to the request, plus an upstream skill when the request clearly needs that process.
- Never chain skills automatically and never spawn subagents. Discovery happens in the current conversation.
- Planning skills (`interview-me`, `idea-refine`, `spec-driven-development`, `planning-and-task-breakdown`, `project-planning`) run only when the user asks to plan, design, or organize first.
- Implementation runs through `direct-implementation`, or `plan-execution` when the user says to proceed with an existing plan. Domain skills load alongside it: `laravel-best-practices`, `ct12rounds-project-patterns`, `saas-php-laravel`, `vuetify-development`, `asaas-payment-gateway`, `mcp-development`.
- Commits run only through `git-commit`, and only when the user types exactly `commitar`.
- Tests are PHPUnit. The command is `php artisan test --compact` with a filter, not `npm test`, `vitest`, or `pytest`.
- The frontend is Vue 3 with Vuetify 3. React, Tailwind, and Storybook examples in upstream skills do not apply.

## Skill Discovery

The tree below is the generic upstream view. The `AGENTS.md` table overrides it.

```
Task arrives
    │
    ├── Don't know what you want yet? ──────→ interview-me
    ├── Have a rough concept, need variants? → idea-refine

```
Task arrives
    │
    ├── Don't know what you want yet? ──────→ interview-me
    ├── Have a rough concept, need variants? → idea-refine
    ├── New project/feature/change? ──→ spec-driven-development
    ├── No quality bar written down? ──→ constraint-driven-development
    ├── Have a spec, need tasks? ──────→ planning-and-task-breakdown
    ├── Implementing code? ────────────→ incremental-implementation
    │   ├── UI work? ─────────────────→ frontend-ui-engineering
    │   ├── API work? ────────────────→ api-and-interface-design
    │   ├── Need better context? ─────→ context-engineering
    │   ├── Need doc-verified code? ───→ source-driven-development
    │   └── Stakes high / unfamiliar code? ──→ doubt-driven-development
    ├── Writing/running tests? ────────→ test-driven-development
    │   └── Browser-based? ───────────→ browser-testing-with-devtools
    ├── Something broke? ──────────────→ debugging-and-error-recovery
    ├── Reviewing code? ───────────────→ code-review-and-quality
    │   ├── Too complex? ─────────────→ code-simplification
    │   ├── Security concerns? ───────→ security-and-hardening
    │   └── Performance concerns? ────→ performance-optimization
    ├── Committing/branching? ─────────→ git-workflow-and-versioning
    ├── CI/CD pipeline work? ──────────→ ci-cd-and-automation
    ├── Deprecating/migrating? ────────→ deprecation-and-migration
    ├── Writing docs/ADRs? ───────────→ documentation-and-adrs
    ├── Adding logs/metrics/alerts? ───→ observability-and-instrumentation
    └── Deploying/launching? ─────────→ shipping-and-launch
```

## Core Operating Behaviors

These behaviors apply at all times, across all skills. They are non-negotiable, except where `AGENTS.md` or `.agents/guardrails/base.md` says otherwise.

### 1. Surface Assumptions

Before implementing anything non-trivial, explicitly state your assumptions:

```
ASSUMPTIONS I'M MAKING:
1. [assumption about requirements]
2. [assumption about architecture]
3. [assumption about scope]
→ Correct me now or I'll proceed with these.
```

Don't silently fill in ambiguous requirements. The most common failure mode is making wrong assumptions and running with them unchecked. Surface uncertainty early — it's cheaper than rework.

### 2. Manage Confusion Actively

When you encounter inconsistencies, conflicting requirements, or unclear specifications:

1. **STOP.** Do not proceed with a guess.
2. Name the specific confusion.
3. Present the tradeoff or ask the clarifying question.
4. Wait for resolution before continuing.

**Bad:** Silently picking one interpretation and hoping it's right.
**Good:** "I see X in the spec but Y in the existing code. Which takes precedence?"

### 3. Push Back When Warranted

You are not a yes-machine. When an approach has clear problems:

- Point out the issue directly
- Explain the concrete downside (quantify when possible — "this adds ~200ms latency" not "this might be slower")
- Propose an alternative
- Accept the human's decision if they override with full information

Sycophancy is a failure mode. "Of course!" followed by implementing a bad idea helps no one. Honest technical disagreement is more valuable than false agreement.

### 4. Enforce Simplicity

Your natural tendency is to overcomplicate. Actively resist it.

Before finishing any implementation, ask:
- Can this be done in fewer lines?
- Are these abstractions earning their complexity?
- Would a staff engineer look at this and say "why didn't you just..."?

If you build 1000 lines and 100 would suffice, you have failed. Prefer the boring, obvious solution. Cleverness is expensive.

### 5. Maintain Scope Discipline

Touch only what you're asked to touch.

Do NOT:
- Remove comments you don't understand
- "Clean up" code orthogonal to the task
- Refactor adjacent systems as a side effect
- Delete code that seems unused without explicit approval
- Add features not in the spec because they "seem useful"

Your job is surgical precision, not unsolicited renovation.

### 6. Verify, Don't Assume

Every skill includes a verification step. A task is not complete until verification passes. "Seems right" is never sufficient — there must be evidence (passing tests, build output, runtime data).

Per-skill verification is the local check. The project-wide bar that applies to *every* change, regardless of which skill is active, is the **Implementation And Verification** section of `AGENTS.md`: focused PHPUnit tests pass, Pint is clean, no regressions, unresolved blockers and required manual actions are reported. It complements each task's acceptance criteria rather than replacing them.

## Failure Modes to Avoid

These are the subtle errors that look like productivity but create problems:

1. Making wrong assumptions without checking
2. Not managing your own confusion — plowing ahead when lost
3. Not surfacing inconsistencies you notice
4. Not presenting tradeoffs on non-obvious decisions
5. Being sycophantic ("Of course!") to approaches with clear problems
6. Overcomplicating code and APIs
7. Modifying code or comments orthogonal to the task
8. Removing things you don't fully understand
9. Building without a spec because "it's obvious"
10. Skipping verification because "it looks right"

## Skill Rules

1. **Check for an applicable skill before starting work.** Skills encode processes that prevent common mistakes.

2. **Skills are workflows, not suggestions.** Follow the steps in order. Don't skip verification steps.

3. **Multiple skills can apply.** A feature implementation might involve `idea-refine` → `spec-driven-development` → `planning-and-task-breakdown` → `incremental-implementation` → `test-driven-development` → `code-review-and-quality` → `code-simplification` → `shipping-and-launch` in sequence.

4. **When in doubt, ask before planning.** A non-trivial task without a spec is fine in this project: implement it through `direct-implementation` and state your assumptions in the report. Reach for `spec-driven-development` only when the user asks for a spec.

## Lifecycle Sequence

Reference only, not a pipeline to run. Each step is loaded on demand, never chained automatically, and steps 1-4 run only when the user asks to plan first.

```
1.  project-planning             → Plan only when the user asks for one
2.  interview-me                 → Extract what the user actually wants
3.  idea-refine                  → Refine vague ideas
4.  spec-driven-development      → Define what we're building
5.  planning-and-task-breakdown  → Break into verifiable chunks
6.  context-engineering          → Load the right context
7.  source-driven-development    → Verify against official docs
8.  direct-implementation        → Build the change in this conversation
9.  observability-and-instrumentation → Instrument as you build (parallel with 8, not after)
10. doubt-driven-development     → Cross-examine non-trivial decisions in-flight
11. test-driven-development      → Prove each change works
12. code-review-and-quality      → Review before finishing
13. code-simplification          → Reduce unnecessary complexity while preserving behavior
14. git-commit                   → Only when the user types exactly `commitar`
15. documentation-and-adrs       → Document decisions when the user asks
16. deprecation-and-migration    → Retire old systems and move users safely when needed
17. shipping-and-launch          → Deploy safely
```

Not every task needs every skill. A bug fix might only need: `debugging-and-error-recovery` → `test-driven-development` → `code-review-and-quality`.

## Quick Reference

| Phase | Skill | One-Line Summary |
|-------|-------|-----------------|
| Project | direct-implementation | Implement the change in this conversation, no delegation |
| Project | plan-execution | Work through a user-approved plan step by step |
| Project | project-planning | Produce a plan when the user asks for one |
| Project | git-commit | Angular commits in Portuguese, only on the exact word `commitar` |
| Define | interview-me | Surface what the user actually wants before any plan, spec, or code exists |
| Define | idea-refine | Refine ideas through structured divergent and convergent thinking |
| Define | spec-driven-development | Requirements and acceptance criteria before code |
| Plan | planning-and-task-breakdown | Decompose into small, verifiable tasks |
| Build | incremental-implementation | Thin vertical slices, test each before expanding |
| Build | source-driven-development | Verify against official docs before implementing |
| Build | doubt-driven-development | Adversarial self-review of every non-trivial decision |
| Build | context-engineering | Right context at the right time |
| Build | frontend-ui-engineering | Vue 3 and Vuetify UI quality with accessibility |
| Build | api-and-interface-design | Stable interfaces with clear contracts |
| Verify | test-driven-development | Prove behavior with PHPUnit tests |
| Verify | browser-testing-with-devtools | Chrome DevTools MCP for runtime verification |
| Verify | debugging-and-error-recovery | Reproduce → localize → fix → guard |
| Review | code-review-and-quality | Five-axis review with quality gates |
| Review | code-simplification | Preserve behavior while reducing unnecessary complexity |
| Review | security-and-hardening | OWASP prevention, input validation, least privilege |
| Review | performance-optimization | Measure first, optimize only what matters |
| Ship | git-workflow-and-versioning | Branching, tags, history hygiene (commits go through `git-commit`) |
| Ship | ci-cd-and-automation | Automated quality gates, on request only |
| Ship | deprecation-and-migration | Remove old systems and migrate users safely |
| Ship | documentation-and-adrs | Document the why, when the user asks for it |
| Ship | observability-and-instrumentation | Structured logs, RED metrics, traces, symptom-based alerts |
| Ship | shipping-and-launch | Pre-launch checklist, monitoring, rollback plan |
