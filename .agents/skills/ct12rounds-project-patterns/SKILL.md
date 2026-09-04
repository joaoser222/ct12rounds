---
name: ct12rounds-project-patterns
description: "Use for Ct12rounds project-specific implementation patterns: module controllers, access control, reports, payment gateways, Inertia Vue pages, Vuetify tables/forms, services, commands, tests, and naming conventions in this repository."
license: MIT
metadata:
  author: project
---

# Ct12rounds Project Patterns

## When to Apply

Activate this skill whenever working on application code in this repository, especially when:

- Creating or refactoring Laravel controllers, services, commands, requests, or module routes.
- Adding or changing access-controlled modules and permissions.
- Working on reports under `app/Reports` or `app/Services/ReportService.php`.
- Working on payment gateway code under `app/PaymentGateways` or `GatewayBillingService`.
- Creating or modifying Inertia Vue pages, Vuetify tables, details pages, forms, menus, or shared frontend components.
- Adding tests for module access, command behavior, services, reports, or persistence flows.

Pair this skill with `laravel-best-practices` for backend PHP changes, `inertia-vue-development` for Inertia Vue pages, and `vuetify-development` for UI work.

## Backend Architecture

- Prefer project-specific base controllers over traits for module behavior.
- Full CRUD modules extend `App\Http\Controllers\CrudModuleController`.
- Read-only modules extend `App\Http\Controllers\ReadOnlyModuleController`.
- Custom module-like controllers that do not match CRUD or read-only flow extend `App\Http\Controllers\AbstractModuleController`.
- All module controllers define `accessModule(): AccessModule` and `modelClass(): string`.
- Standard module routes use `Route::module(Controller::class)`.
- Read-only modules should register only `index` and `show` routes manually.
- Keep public controller action signatures broad with `Illuminate\Http\Request` when inheriting from base module controllers.
- Use protected `storeRequestClass()` and `updateRequestClass()` hooks to select Form Request validation classes.
- If a custom action needs validated data, call `$this->validatedRequestData($request, SomeRequest::class)` instead of type-narrowing the public method parameter.
- If overriding base controller helpers, preserve visibility compatibility: do not make inherited protected methods private.

## Module Controllers

- CRUD modules inherit standard actions: `index`, `create`, `show`, `store`, `update`, `destroy`, and `changeVisibility`.
- CRUD index queries filter by `visibility` and default to visible records.
- Read-only modules inherit only `index` and `show`; they do not use visibility filtering.
- Use `$fields`, `$searchableFields`, `$sortableFields`, `$fieldsMapping`, and `$joins` properties to configure generic list queries.
- Use `moduleIndexProps(Request $request)` for index page options.
- Use `moduleDetailsProps(?Model $model = null)` for details/create page options.
- Use `newModelQuery()` for reusable model scoping, such as payables/receivables by `operation_type`.
- Use `parent::getModuleRoutes()` when adding extra routes to the generic module route payload.
- Use `parent::validatedRequestData()` when adjusting generic validated data in custom modules.

## Access Control

- Modules are declared in `App\AccessControl\AccessModule`.
- Actions are declared in `App\AccessControl\AccessAction`.
- Role defaults are declared in `App\AccessControl\RolePermissionMap`.
- Run or update `access-control:sync` behavior when adding module/action permissions.
- Frontend permission names follow `{module}.{action}`, using enum values such as `clients.view` or `gateway_payments.view`.
- Standard detail pages use `*.view` for `GET /module/{id}`.
- Update/save actions use `*.update` and are separate from page access.
- Read-only modules should usually expose only `*.view`.

## Services

- Application use-case services live directly in `app/Services` and use the `*Service` suffix.
- Keep domain/catalog structures outside `app/Services` when they are not services.
- Examples of application services include `BillingInvoiceService`, `BillableItemService`, `GatewayBillingService`, `StockRecalculationService`, and `ReportService`.
- Prefer constructor injection for services in controllers.
- Keep service methods explicit and narrowly scoped; do not put HTTP/Inertia rendering logic in services.

## Reports

- Report metadata lives under `app/Reports`.
- Report definitions live under `app/Reports/Definitions`.
- Report execution lives in `app/Services/ReportService.php`.
- Generate new report definition classes with `php artisan make:report ReportName`.
- Report classes expose a static `definition(): ReportDefinition` method.
- Register report definitions in `ReportRegistry::all()`.
- Keep report definition classes focused on metadata: key, label, description, filters, and columns.
- Keep execution logic in `ReportService` or future dedicated collaborators, not in the static definition metadata.
- Use `ReportFilter`, `ReportColumn`, `ReportDefinition`, and `ReportResult` to keep report payloads consistent.
- Avoid free-form SQL from user input. Validate and whitelist filters/columns before executing reports.

## Payment Gateways

- Payment gateway infrastructure lives under `app/PaymentGateways`.
- Payment gateway contracts live under `app/PaymentGateways/Contracts`.
- Adapters live under `app/PaymentGateways/Adapters`.
- Gateway configuration definitions live under `app/PaymentGateways/Definitions`.
- Use explicit names with the `PaymentGateway` prefix/suffix, such as `PaymentGatewayAdapter`, `PaymentGatewayManager`, and `PaymentGatewayDefinition`.
- `GatewayBillingService` remains in `app/Services` because it is an application use-case service that coordinates billing and gateway interactions.
- Bind `PaymentGatewayAdapter` to the concrete adapter in `AppServiceProvider`.
- Gateway account settings should use definition classes to describe configurable fields and sensitive password handling.

## Frontend Structure

- Inertia pages live in `resources/js/pages` using lowercase/snake_case module folders.
- Layouts live in `resources/js/layouts`.
- Shared components live in `resources/js/components`.
- Use `AuthenticatedLayout` for authenticated pages.
- Use `TablePage.vue` for index/list pages when possible.
- Use `DetailsPage.vue` for editable CRUD detail/create pages.
- Use `ReadOnlyDetailsPage.vue` for read-only detail pages.
- Use `module` props matching permission/module names, such as `clients`, `plan_categories`, or `gateway_payments`.
- For read-only list pages, pass `hide-selection`, `hide-visibility-filter`, and a permission map disabling `create`, `delete`, and `visibility`.
- Format dates, currency, and labels using existing plugins/helpers such as `formatDate`, `formatDateTime`, `formatCurrency`, `findLabel`, and `findOption`.
- Vuetify chips for status values should use colors from shared enum options: `:color="findOption(options, value)?.color"` with `variant="tonal"`.
- Icons use Tabler names, for example `ti ti-plus`, `ti ti-pencil`, and `ti ti-report-analytics`.

## Menus And Navigation

- Main navigation is configured in `resources/js/layouts/AuthenticatedLayout.vue`.
- Menu item visibility is permission-based through `usePermissions()`.
- Add read-only user-facing modules to the appropriate top-level group, not necessarily Avançado.
- Administrative configuration modules belong under Avançado.
- Payment gateway operational read-only pages belong under Gateway de Pagamentos.

## Commands

- Artisan commands live in `app/Console/Commands`.
- Use PHP attributes `#[Signature(...)]` and `#[Description(...)]` for commands.
- Prefer `php artisan make:command` when adding commands.
- Use `$this->components->info()`, `$this->components->error()`, and `$this->components->twoColumnDetail()` for command output when appropriate.
- Custom make commands should avoid overwriting files unless a `--force` option is provided.

## Testing

- Tests use PHPUnit classes, not Pest.
- Use `php artisan make:test --phpunit` for feature tests and `--unit` for unit tests.
- Add focused tests for new service objects, commands, and module behavior.
- Prefer unit tests for pure PHP value objects and registry/service scaffolding to avoid unnecessary database usage.
- Some feature tests use SQLite in-memory and may fail locally if the SQLite driver is unavailable; still run focused tests and report environment blockers clearly.
- Run `vendor/bin/pint --dirty --format agent` after PHP changes.

## Naming Conventions

- Use descriptive English class names and method names.
- Existing UI labels are usually Portuguese; preserve that convention in labels/messages.
- Models are singular, controllers end in `Controller`, services end in `Service`.
- Access modules use plural snake_case enum values when matching route/frontend modules.
- Read-only gateway model pages use camelCase props from read-only module controllers, such as `gatewayPayment`.
- CRUD generic module pages use kebab route props, such as `plan-category` or `financial-account`, matching existing generic behavior.

## Safety Notes

- Do not introduce Tailwind patterns; this project uses Vuetify.
- Do not generate React examples or React components; this project uses Vue for Inertia.
- Do not change dependencies without explicit approval.
- Do not create documentation files unless explicitly requested.
- Keep changes minimal and aligned with existing module conventions.
