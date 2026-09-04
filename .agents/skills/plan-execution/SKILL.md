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
2. Load `direct-implementation` and the domain skills needed for the first executable step.
3. Inspect existing code, implement the step, and run its focused verification.
4. Continue with dependent steps only after their prerequisites are complete.
5. Stop and report a genuine blocker instead of silently skipping a failed step.
6. Finish with Pint after PHP changes and the narrowest meaningful test suite for the changed areas.

## Constraints

- Work directly; never invoke a planner, commander, executor, or subagent pipeline.
- Preserve the plan's scope unless the user approves a change.
- Report the completed steps, changed files, test results, and pending manual actions in Portuguese.
