---
name: saas-php-laravel
description: "Use this skill whenever building, reviewing, or refactoring a PHP SaaS feature in this project. It covers Laravel 13 backend architecture, Eloquent and migrations, authorization, tenant or organization data isolation, billing and external integrations, queues, scheduled work, reports, Inertia.js 3 with Vue 3 and Vuetify 3 interfaces, and PHPUnit quality verification. Trigger it for requests involving SaaS modules, subscriptions, plans, invoices, accounts, organizations, roles, permissions, dashboards, CRUD screens, API integrations, background jobs, or production hardening, even when the user asks only for one layer of the feature."
license: MIT
metadata:
  author: project
  stack: PHP 8.3, Laravel 13, Inertia.js 3, Vue 3, Vuetify 3
---

# PHP SaaS Development

Build SaaS features as complete, maintainable application workflows rather than isolated snippets. Inspect the repository first, preserve established conventions, and make the smallest coherent change that covers persistence, authorization, user experience, and verification.

## Stack and Source of Truth

- Use PHP 8.3 and Laravel 13 conventions.
- Use Inertia.js 3 with Vue 3 for application pages and Vuetify 3 for UI components. Do not introduce React or Tailwind patterns.
- Use PHPUnit 12 test classes, Larastan-compatible PHP, and Pint formatting.
- Search version-specific Laravel and Inertia documentation before relying on unfamiliar APIs.
- Read sibling files and existing project skills before creating a new controller, service, component, test, or shared UI pattern. Existing application conventions override generic preferences.
- Pair this skill with `ct12rounds-project-patterns` for repository-specific modules and `laravel-best-practices` for detailed Laravel rules. Pair with `inertia-vue-development` and `vuetify-development` for frontend work.

## Delivery Workflow

1. Map the request to the affected domain, tenant boundary, route, permission, model, service, page, and test files.
2. Inspect routes, migrations, models, policies, access-control enums, services, layouts, and nearby tests before editing.
3. Confirm the data lifecycle: creation, updates, visibility/status transitions, deletion, auditability, and failure behavior.
4. Implement backend boundaries first: migration, model relationships/casts, Form Requests, policy or access control, service/use case, controller, and route.
5. Implement the Inertia/Vuetify experience using existing layouts and shared table/detail/form components. Keep server authorization authoritative; frontend permission checks are only UX safeguards.
6. Add focused PHPUnit coverage for success, validation/authorization failure, tenant isolation, duplicate or idempotent requests, and external-service failure when relevant.
7. Run the narrowest meaningful tests, Pint on changed PHP files, and relevant static or frontend checks. Report environment blockers instead of hiding them.

## SaaS Domain Design

### Tenants and Organizations

- Treat tenant or organization isolation as a security boundary, not merely a query convenience.
- Identify the current tenant from the authenticated context or established project mechanism; never trust a tenant ID supplied by the browser when it can be derived server-side.
- Scope every tenant-owned read, write, relation, job, export, report, and integration callback to the authorized tenant.
- Use policies, scoped bindings, or explicit tenant scopes consistently with the repository. Avoid a global scope if it would hide important administrative or cross-tenant behavior without an explicit escape hatch.
- Test both positive access and cross-tenant denial. Include indirect paths such as nested relations, bulk actions, queued jobs, and exports.
- Keep truly global records, such as plan catalog definitions, separate from tenant-owned subscription or billing records.

### Identity, Roles, and Permissions

- Authorize every state-changing action on the server using the project's access-control or policy conventions.
- Keep page visibility and action permissions distinct where the application distinguishes `view`, `create`, `update`, `delete`, or visibility operations.
- Do not rely on hidden buttons, route names, or client-side permission checks for security.
- Return consistent authorization failures and avoid leaking whether another tenant owns a record.

### Billing and Subscription Workflows

- Model billing as a stateful workflow: plan selection, subscription state, invoice/payment state, retries, cancellation, renewal, and provider reconciliation.
- Keep provider-specific code behind contracts or adapters. Application services should coordinate business rules and persistence without embedding gateway HTTP details.
- Make webhook handling idempotent using provider event identifiers and a durable processed-event record or the existing project equivalent.
- Verify webhook signatures, validate payloads, authorize the affected account, and process state changes transactionally where appropriate.
- Use explicit money representation and currency handling; never use floating-point arithmetic for monetary values.
- Queue slow or retryable provider calls and configure timeouts, backoff, rate limits, and failure handling. Avoid making users wait for work that can safely happen asynchronously.
- Never log secrets, full payment credentials, webhook signatures, or unnecessary personal data.

### External Integrations

- Encapsulate each integration behind a small interface or adapter when the provider can change or needs to be faked.
- Configure explicit connection and request timeouts, retries with backoff for transient failures, and response status handling.
- Persist enough correlation and idempotency information to safely retry operations.
- Use Laravel HTTP fakes and prevent stray requests in tests. Test malformed responses, timeouts, non-success responses, and duplicate callbacks.

## Laravel Backend Patterns

- Use migrations for schema changes and do not rewrite migrations that may already have run in production.
- Add indexes for tenant keys, foreign keys, status filters, provider identifiers, and common ordering/search paths.
- Define typed relationships and casts; use mass-assignment protection and validated request data only.
- Prefer Form Requests for non-trivial validation and policies or the project's access-control layer for authorization.
- Keep controllers thin. Put transactional business workflows in focused services or action classes with dependency injection.
- Use route model binding, scoped bindings, and named routes. Do not build authorization around user-supplied model IDs alone.
- Prevent N+1 queries with deliberate eager loading and use pagination for user-facing collections. Select only needed columns for expensive screens.
- Wrap related state changes in transactions, but do not place external network calls inside a database transaction unless the existing design explicitly requires it.
- Use jobs for slow, retryable, or provider-dependent work. Make jobs serializable, tenant-aware, idempotent where retries are possible, and observable on failure.
- Use scheduler protections such as `withoutOverlapping()` or equivalent when recurring billing/reconciliation can overlap.
- Validate and whitelist report filters, sorting, and export columns. Never interpolate browser input into SQL.

## Inertia, Vue, and Vuetify

- Use a single-root Vue component and follow the existing page/layout structure under `resources/js/pages` and `resources/js/layouts`.
- Prefer existing `TablePage.vue`, `DetailsPage.vue`, `ReadOnlyDetailsPage.vue`, forms, dialogs, and composables before creating new abstractions.
- Use Inertia forms or the project's established form helper, preserve server validation errors, and show loading/disabled states during submissions.
- Keep permission-aware navigation and actions consistent with the backend, but do not treat them as authorization.
- Use Vuetify components and props for layout, density, validation, responsive behavior, and feedback. Use Tabler icon names already used by the project.
- Represent asynchronous or deferred SaaS data with an explicit loading, empty, error, and retry state. Do not render undefined financial totals or status values as if they were final.
- For billing and destructive actions, show the current state, consequences, confirmation, and recoverable error feedback.
- Keep save actions last and on the far right of form action bars, after secondary actions.

## Testing and Verification

Every change needs programmatic verification appropriate to its risk:

- Feature tests for routes, authentication, permissions, validation, tenant isolation, persistence, redirects/Inertia props, and status transitions.
- Unit tests for pure value objects, adapters, service decisions, registry logic, and idempotency behavior when database setup is unnecessary.
- Use factories and existing factory states; do not hand-build production models in tests when a factory exists.
- Fake mail, notifications, events, queues, and HTTP after arranging the relevant records and inputs.
- Assert both allowed and denied behavior, including a neighboring tenant or unauthorized role.
- Test duplicate webhook/job delivery and transient provider failures whenever the feature can be retried.
- Run `php artisan test --compact` with a focused file or filter first. Run `vendor/bin/pint --dirty --format agent` after PHP changes. Run frontend lint/build checks when Vue files change.

## Completion Checklist

Before considering the task complete, verify:

- The implementation matches nearby project patterns and does not add an unnecessary dependency.
- Tenant ownership and authorization are enforced on every relevant path.
- Validation, database constraints, indexes, and state transitions are coherent.
- External calls have timeouts, safe retries, idempotency, and no secret leakage.
- The UI handles loading, empty, success, validation, authorization, and integration failure states.
- Focused tests cover the happy path and the highest-risk failure paths.
- Formatting and relevant checks pass, or any environment limitation is stated precisely.
