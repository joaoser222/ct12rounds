---
name: direct-implementation
description: >
  Implements an application change directly in the current Ct12rounds workspace.
  Use this skill whenever the user asks to create, modify, fix, refactor, test,
  or execute a Laravel, Vue, Vuetify, Inertia, or project configuration change,
  including implementation of an existing plan. It performs the work end-to-end
  in the current conversation and never delegates it through planner, commander,
  executor, or subagent pipelines.
---

# Direct Implementation

Implement the requested scope in the current workspace. This skill replaces
role-based execution pipelines with one direct, traceable workflow.

Communicate with the user in Portuguese. Keep source code, identifiers, and
this skill's instructions in English.

## Workflow

1. Identify the affected domain and load only its relevant domain skills.
2. Inspect sibling code and an existing example before creating or changing files.
3. Make the smallest coherent change that satisfies the request.
4. Use Laravel generators with `--no-interaction` when applicable.
5. Add or update focused PHPUnit tests for changed behavior, using existing factories.
6. Run `vendor/bin/pint --dirty --format agent` after PHP changes.
7. Run the narrowest meaningful verification, normally `php artisan test --compact` with an appropriate filter.
8. Report changed files, verification results, and any unresolved blocker directly to the user.

## Scope Rules

- Do not delegate tasks, create execution plans, or transfer work to another role.
- Do not change unrelated files or undo unrelated worktree changes.
- Do not add dependencies, base directories, or documentation unless explicitly requested.
- Stop only when the request is complete, verified, or blocked by information the user must provide.
