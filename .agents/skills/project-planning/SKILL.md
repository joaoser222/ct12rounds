---
name: project-planning
description: >
  Plans, scopes, and sequences development work in the Ct12rounds Laravel,
  Inertia Vue, and Vuetify project. Use this skill only when the user explicitly
  asks to plan, organize, design, architect, estimate, or discuss an implementation
  before coding. It produces a plan for the current conversation and never delegates
  work to agents or starts implementation without a direct user request.
---
# Ct12rounds Project Planning

Analyze the request and produce a practical implementation plan before code is
written. This is a consultation skill, not an orchestration workflow.

Communicate with the user in Portuguese. Keep this skill's content and technical
terms in English.

## Project Context

This is a Laravel 13 SaaS with Inertia.js v3 + Vue 3 + Vuetify 3 on the frontend.
Backend stack: PHP 8.3, PostgreSQL, CRUD/read-only modules with access control.

## Project Skills

Load only the skills relevant to the requested work:

1. **ct12rounds-project-patterns** — Module controllers (CrudModuleController,
   ReadOnlyModuleController, AbstractModuleController), access control (AccessModule,
   AccessAction), services, reports, payment gateways, frontend (TablePage,
   DetailsPage, ReadOnlyDetailsPage), menus, commands, tests.

2. **saas-php-laravel** — SaaS flows: tenant isolation, billing/subscriptions,
   external integrations, authorization, jobs, queues, idempotent webhooks.

3. **vuetify-development** — Vuetify 3 UI: components, Tabler icons, spacing,
   forms, tables, dialogs.

4. **laravel-best-practices** — Laravel best practices: N+1 prevention, caching,
   Eloquent, validation, security, jobs, queues, HTTP client, testing.

5. **asaas-payment-gateway** — Asaas gateway integration: adapters, definitions,
   postbacks, sync, billing.

## Planning Protocol

### 1. Request Analysis

- Identify the affected domain (e.g., financial, clients, plans, gateway, reports)
- Identify the task type (e.g., new CRUD module, new field, integration, report, command, screen)
- List the skills relevant to the domain/task
- Check if similar modules/services/pages exist as reference

### 2. Plan Structure

Produce plans using this structure:

```markdown
## Plano: <title>

### Domínios Envolvidos
- <domain 1> (relevant skill)
- <domain 2> (relevant skill)

### Etapas
1. **Backend** — Migrations, models, enums, access control, requests, service, controller, routes
2. **Frontend** — Inertia pages, Vuetify components, menus
3. **Tests** — PHPUnit feature/service tests

### Detalhamento
<concrete step list referencing existing patterns>

### Observações
<risks, dependencies, important considerations>
```

### 3. Per-Task Checklists

**New CRUD Module:**
- [ ] Add AccessModule enum case + AccessAction
- [ ] Create table migration
- [ ] Create Model with $fillable, casts, relationships
- [ ] Controller extends CrudModuleController
- [ ] Implement accessModule() and modelClass()
- [ ] Form Requests (if needed)
- [ ] Route::module() registration
- [ ] Permissions in RolePermissionMap
- [ ] Index and Details pages (TablePage/DetailsPage)
- [ ] Menu entry in AuthenticatedLayout
- [ ] Run php artisan access-control:sync

**New Report:**
- [ ] Generate report definition with make:report
- [ ] Define ReportFilter and ReportColumn
- [ ] Register in ReportRegistry
- [ ] Implement logic in ReportService

**Gateway Integration:**
- [ ] Create Definition with settings
- [ ] Implement Adapter (PaymentGatewayAdapter interface)
- [ ] Register in PaymentGatewayManager
- [ ] Bind in AppServiceProvider
- [ ] Add read-only routes
- [ ] Implement postback handler

### 4. Style Rules

- Class names in descriptive English
- UI labels/messages in Portuguese (follow existing convention)
- Use `php artisan make:*` to generate files
- Save button always last on the right in form action bars
- Use Vuetify (never Tailwind)
- Use Vue (never React)
- Do not create documentation files without permission

### 5. Final Output

Present the plan and wait for the user's next instruction. If they ask to
implement it, work directly in the current conversation; do not invoke a
planner, commander, executor, or any other orchestration agent.
