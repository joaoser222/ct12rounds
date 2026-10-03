---
name: documentation-and-adrs
description: Records decisions and documentation. Use when you need to document an architecture decision (ADR) or the reasoning behind a design choice, when changing public APIs, shipping features, or when you need to record context that future engineers and agents will need to understand the codebase.
---

# Documentation and ADRs

> **Project scope.** Create documentation only when the user explicitly asks (`AGENTS.md` baseline rule 5). This repository keeps a single doc, `docs/ACCESS_CONTROL.md`; match that convention before introducing `docs/decisions/`, a README, or a CHANGELOG. This project is Laravel, Inertia, Vue, and Vuetify: no React, TypeScript, or ORM-generator examples.


## Overview

Document decisions, not just code. The most valuable documentation captures the *why* — the context, constraints, and trade-offs that led to a decision. Code shows *what* was built; documentation explains *why it was built this way* and *what alternatives were considered*. This context is essential for future humans and agents working in the codebase.

## When to Use

- Making a significant architectural decision
- Choosing between competing approaches
- Adding or changing a public API
- Shipping a feature that changes user-facing behavior
- Onboarding new team members (or agents) to the project
- When you find yourself explaining the same thing repeatedly

**When NOT to use:** Don't document obvious code. Don't add comments that restate what the code already says. Don't write docs for throwaway prototypes.

## Architecture Decision Records (ADRs)

ADRs capture the reasoning behind significant technical decisions. They're the highest-value documentation you can write.

### When to Write an ADR

- Choosing a framework, library, or major dependency
- Designing a data model or database schema
- Selecting an authentication strategy
- Deciding on an API architecture (REST vs. GraphQL vs. tRPC)
- Choosing between build tools, hosting platforms, or infrastructure
- Any decision that would be expensive to reverse

### Match the existing convention first

Before creating an ADR, inspect the available repository context for an established convention — existing ADRs, project instructions, and ADR-related configuration or tooling (e.g. an `.adr-dir` file). An established convention overrides the defaults below. Match:

- **Location and format** — e.g. `docs/adr/*.md`, `Documentation/Decisions/*.rst`, a MADR layout, or an `adr-tools` setup. Match the existing directory, file extension, and markup (Markdown vs reStructuredText).
- **Numbering and naming** — continue the existing sequence and filename pattern (`ADR-004-Title.rst`, `0004-title.md`, …); don't restart at 001 or introduce a second scheme.
- **Section headings** — reuse the project's heading set rather than imposing this template's.

If the available evidence conflicts, surface the conflict rather than silently introducing another scheme. Only when no convention can be established do you apply the default below.

### ADR Template

Store ADRs in `docs/decisions/` with sequential numbering (unless the project already uses another location — see above):

```markdown
# ADR-001: Access-controlled modules extend base module controllers

## Status
Proposed | Accepted | Superseded by ADR-XXX | Deprecated

## Date
2025-01-15

## Context
Every business screen needs the same index/show/store/update/destroy behavior plus
permission checks and a list configuration. Key requirements:
- One place that defines access module, model class, and route registration
- Two shapes: full CRUD and read-only
- New modules must not repeat the access check

## Decision
Extend `CrudModuleController` or `ReadOnlyModuleController`, declare
`accessModule()` and `modelClass()`, and register routes with `Route::module()`.

## Alternatives Considered

### Traits per concern
- Pros: Composable, no inheritance
- Cons: Behavior becomes invisible at the call site; visibility rules spread across files
- Rejected: A module controller must read top-down

### A base controller plus per-module traits
- Pros: Shared CRUD without hiding anything
- Cons: Two mechanisms to learn and two places to look for a behavior
- Rejected: The base controller plus explicit overrides is simpler to trace

## Consequences
- New CRUD module means one controller, one enum case, one route registration
- Custom behavior overrides a protected hook instead of replacing the flow
- Shared components (`TablePage.vue`, `DetailsPage.vue`) stay generic
```

### ADR Lifecycle

```
PROPOSED → ACCEPTED → (SUPERSEDED or DEPRECATED)
```

- **Don't delete old ADRs.** They capture historical context.
- When a decision changes, write a new ADR that references and supersedes the old one.

## Inline Documentation

### When to Comment

Comment the *why*, not the *what*:

```php
// BAD: restates the code
// Increment the counter.
$counter++;

// GOOD: explains non-obvious intent
// The window slides on the request boundary, not on a fixed schedule, so a
// burst at the edge cannot slip through under a half-expired window.
if ($now->greaterThan($windowStart->copy()->addSeconds($windowSeconds))) {
    $counter = 0;
}
```

### When NOT to Comment

```php
// Self-explanatory code needs no comment.
public function calculateTotal(array $items): float
{
    return array_reduce($items, fn (float $carry, array $item): float => $carry + $item['price'] * $item['quantity'], 0.0);
}

// Don't leave a TODO for work that should just be done now.

// Don't leave commented-out code — git has the history.
```

### Document Known Gotchas

```php
/**
 * Runs before the gateway adapter resolves a customer.
 * Calling it after resolution reuses a stale Asaas customer id and
 * creates duplicates on the gateway side.
 *
 * See ADR-003 for the full design rationale.
 */
public function prepareCustomer(Customer $customer): void
{
}
```

## Contract Documentation

Document the interfaces this project owns — the Asaas and other external HTTP
contracts, webhook payloads, and module service boundaries. PHPDoc on the class
or method is enough; do not introduce a documentation generator for them.

```php
/**
 * Creates a customer on the gateway.
 *
 * @param  array<string, mixed>  $input  Name and document required, email optional
 * @return array{id: string, name: string}
 *
 * @throws \App\Exceptions\GatewayException  When the gateway rejects the payload
 */
public function createCustomer(array $input): array
{
}
```

## README And Changelog

Neither exists in this repository, and `AGENTS.md` forbids creating documentation
without an explicit request. When the user does ask for one, keep it short and
accurate:

- **README**: what the project does, how to install it, the commands that matter
  (`composer install`, `npm install`, `php artisan serve` or the Compose files,
  `php artisan test --compact`), and a short architecture overview.
- **Changelog**: released features only, grouped by Added / Fixed / Changed,
  derived from the commit history.

## Documentation for Agents

Special consideration for AI agent context:

- **`AGENTS.md`** — this repository's rules file; keep it accurate
- **`docs/ACCESS_CONTROL.md`** — the one existing doc; keep it in sync when the
  access-control model changes
- **ADRs** — help agents understand why past decisions were made, preventing
  re-deciding the same question
- **Inline gotchas** — prevent agents from falling into known traps

## Common Rationalizations

| Rationalization | Reality |
|---|---|
| "The code is self-documenting" | Code shows what. It doesn't show why, what alternatives were rejected, or what constraints apply. |
| "We'll write docs when the API stabilizes" | APIs stabilize faster when you document them. The doc is the first test of the design. |
| "Nobody reads docs" | Agents do. Future engineers do. Your 3-months-later self does. |
| "ADRs are overhead" | A 10-minute ADR prevents a 2-hour debate about the same decision six months later. |
| "Comments get outdated" | Comments on *why* are stable. Comments on *what* get outdated — that's why you only write the former. |

## Red Flags

- Architectural decisions with no written rationale
- Contract methods with no PHPDoc
- README that doesn't explain how to run the project, when one exists
- Commented-out code instead of deletion
- TODO comments that have been there for weeks
- Documentation that restates the code instead of explaining intent

## Verification

After documenting:

- [ ] The user explicitly asked for the documentation before it was written
- [ ] Any ADR explains why, records the alternatives, and continues the existing
      numbering and location
- [ ] Contract methods carry parameter and return PHPDoc
- [ ] Known gotchas are documented inline where they matter
- [ ] No commented-out code remains
- [ ] `AGENTS.md` is still current and accurate
