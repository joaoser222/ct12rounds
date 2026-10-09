---
name: plan-execution
description: >
  Executes a user-provided or previously agreed implementation plan directly in
  the current Ct12rounds workspace. Use when the user asks to proceed, implement,
  or execute an existing plan. It validates the plan, works through dependencies,
  applies relevant domain skills, and reports results without delegating to agents
  or creating planner, commander, or executor loops.
---

# Direct Plan Execution

Execute the plan in the current conversation. Treat it as a scope checklist,
not as an instruction to orchestrate other agents.

Communicate with the user in Portuguese. Keep source code, identifiers, and
this skill's instructions in English.

## Workflow

1. Read the complete plan and identify dependencies and affected domains.
2. Create the plan branch from `develop` before any code, so `develop` stays
   always publishable: `git switch -c plan/<slug> develop`. The slug is a short
   kebab-case form of the plan title; if the branch already exists, append a
   distinctive suffix (date or index).
3. Load `direct-implementation`, `git-commit`, and the domain skills needed for the first executable step.
4. Inspect existing code, implement the step, and run its focused verification.
5. Continue with dependent steps only after their prerequisites are complete.
6. Stop and report a genuine blocker instead of silently skipping a failed step.
7. Finish with Pint after PHP changes and the narrowest meaningful test suite for
   the changed areas.
8. Publish to `develop` — never automatic. Present a short summary and require the
   exact word `publicar` to publish. On `publicar`:
   - `git switch develop && git pull --ff-only origin develop`
   - `git merge --ff-only plan/<slug>` then `git push origin develop`
   - `git switch develop`
   - If the fast-forward fails because `develop` advanced, do **not** force or
     merge silently: stop and ask the user how to proceed (rebase `plan/<slug>`
     onto `develop` or handle the merge manually).

## Constraints

- Work directly; never invoke a planner, commander, executor, or subagent pipeline.
- Preserve the plan's scope unless the user approves a change.
- Report the completed steps, changed files, test results, and pending manual actions in Portuguese.
- Commits inside the plan are applied via the `git-commit` skill (confirmation word: `commit`).
