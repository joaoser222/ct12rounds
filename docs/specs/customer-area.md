# Spec: Customer Area (`customer-area`)

## Objective

The client accesses their own read-only area at `customer-area/`, entering through a
**magic link sent to the registered email** — no password. The area shows the
information the client needs: graduations, loyalty, contract, upcoming direct lessons,
and financials (invoices and lessons, with amounts in reais).

Success = an active client with an open contract gets in within 1 minute of requesting
the link, without staff confusing the area for their own, and without reaching any staff
route.

### User stories

- As a client in good standing, I want to receive a link by email and get in without
  typing a password.
- As a client, I want to see how much I owe and when it is due, in reais.
- As a client, I want to see my graduations and Fidelidade (loyalty).
- As a client in arrears, I want to **be able to get in** to see and pay what I owe.
- As staff, I want to change a client's email when they mistype it, and have them regain
  access through the new email (that flow is mine, not the system's).

### Out of scope (decided)

- Password, password reset, 2FA, OAuth.
- Any data write through the area. The only `POST` is the link request.
- Email change by the client — done by the front desk.
- API / Sanctum for clients.
- Legal representative email (does not exist in the schema; minors use the account
  holder's email).
- Session revocation (the 120 min session already bounds exposure).

---

## Tech Stack

PHP 8.3 · Laravel 13 · PostgreSQL · Inertia.js v3 · Vue 3 · Vuetify 3 · Tabler icons
New `client` guard, separate from `sanctum` (staff). No new dependency.

---

## Commands

```bash
# PHP — host has no PHP; everything runs in the container
docker compose exec app php artisan test --compact tests/Feature/CustomerAreaAccessTest.php
docker compose exec app php artisan test --compact --filter=customer_area

# Lint / types / format
docker compose exec app vendor/bin/pint --dirty
docker compose exec app composer types:check      # phpstan
yarn lint:check && yarn format:check && yarn types:check

# Manual verification
yarn dev            # or yarn build to reflect
php artisan migrate
```

Test environment (`phpunit.xml`): `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`,
`SESSION_DRIVER=array`, `DB_DATABASE=ct12rounds_test`. Email assertion is possible via
the `Mail::assertSent` facade.

---

## Project Structure

```
app/
  Http/Controllers/CustomerArea/     # plain controllers (NOT CrudModuleController)
    AccessController.php             # login, send, verify
    DashboardController.php
    ProfileController.php
  Http/Requests/CustomerArea/
    ClientAccessRequest.php          # validates email; throttled
  Services/CustomerArea/
    ClientAccessLinkService.php      # generates token + sends email
    CustomerAreaDataService.php      # read-only allowlist per block
resources/js/
  layouts/CustomerAreaLayout.vue     # lean drawer, no usePermissions()
  pages/customer_area/
    Login.vue  Dashboard.vue  Profile.vue
resources/views/emails/
  customer-area-access-link.blade.php
routes/
  customer-area.php                  # new root, this area only
database/migrations/
  ..._create_password_reset_tokens_table.php
tests/Feature/
  CustomerAreaAccessTest.php
  CustomerAreaIsolationTest.php      # the most important
  CustomerAreaDataTest.php
```

`config/auth.php` → `clients` provider, `client` guard, `clients` broker.

---

## Code Style

The area's controller is **flat**. The contrast with the module pattern is deliberate:

```php
// app/Http/Controllers/CustomerArea/DashboardController.php
final class DashboardController extends Controller
{
    public function __construct(private readonly CustomerAreaDataService $data) {}

    public function __invoke(Request $request): Response
    {
        return Inertia::render('customer_area/Dashboard', [
            ...$this->data->for($request->user()),
        ]);
    }
}
```

Rules:

- `final class`, constructor promotion, `private readonly`.
- Typed returns everywhere; a status enum, never a loose string.
- No `AccessAction`, no `AccessModule`, no `Route::module()`.
- Inertia page = `customer_area/ScreenName` (snake_case, like the rest).
- Every page declares `defineOptions({ layout: CustomerAreaLayout })`.
- UI in Portuguese; code/identifiers in English.

---

## Testing Strategy

PHPUnit 12, `tests/Feature/`. No test depends on real production data.

| File | Covers |
|---|---|
| `CustomerAreaAccessTest` | sends link · anti-enumeration · duplicate blocked · ineligible blocked (`INACTIVE`, `LOCKED`, no `OPEN` contract) · token valid / invalid / expired / reuse · throttle |
| `CustomerAreaIsolationTest` | `client` session blocked at `/dashboard`, `/clients`, `/users`, `/reports` · staff session does **not** enter the area · logout works |
| `CustomerAreaDataTest` | allowlist: `annotations`, `financial_account_id`, `external_reference`, `visibility` **absent** · amounts in reais present · only the client's own record |

Exception to encode explicitly: `OVERDUE` **does** get into the area (they need to see
the charge).

---

## Boundaries

**Always**
- Run `pint` + focused test before commit.
- Explicit field allowlist — never a raw `->toArray()`.
- Generic response on eligibility failure (anti-enumeration).
- `throttle` on the link request.

**Ask first**
- Schema change outside the spec.
- New dependency.
- Changing the global `SESSION_LIFETIME` (affects staff).

**Never**
- Reusing the `sanctum` guard for the client, or `CrudModuleController` in the area.
- Creating `AccessModule`/`AccessAction` for the client area.
- Logging token or email in an application log.
- Enabling data writes through the area.

---

## Success Criteria

1. `GET customer-area/login` renders with no session and no `permissions_version` in
   props.
2. `POST customer-area/login/send` with an eligible email sends 1 email; with a
   nonexistent, ineligible, or duplicate email it responds **identically** and sends
   **zero** emails.
3. 6 requests in the same hour ⇒ the 6th is `429`.
4. `GET customer-area/login/verify?token=…` with a valid token creates a `client` guard
   session and redirects to the dashboard.
5. Invalid, expired, or already-used token ⇒ **does not** create a session.
6. `GET customer-area/dashboard` authenticated as a client responds `200`; with **no**
   session responds `302` to `customer-area/login`.
7. A client with a session gets `403`/`302` at `/dashboard`, `/clients`, `/users`,
   `/reports`.
8. Staff with a session get `302` to `customer-area/login` when accessing
   `customer-area/dashboard`.
9. Dashboard props do **not** contain `annotations`, `financial_account_id`,
   `external_reference`, `visibility`.
10. Invoices display amount in reais, title, status, and due date.
11. "My data" displays all of the client's registration fields, including the full
    `document` and address, unmasked.
12. `client` `OVERDUE` gets in; `INACTIVE` and `LOCKED` do not.
13. `pint`, `phpstan`, `eslint`, `prettier`, `vue-tsc` clean.

---

## Guard Traps (implement together)

| Where | What breaks |
|---|---|
| `HandleInertiaRequests.php:48` | `permissionsVersion()` does not exist on `Client` ⇒ **500 on every page** |
| `bootstrap/app.php:29` `redirectGuestsTo` | logged-out client lands on the **staff** login |
| `bootstrap/app.php:30` `redirectUsersTo` | **logged-in** client lands on the **staff** dashboard |
| `resources/js/app.ts:27` | page without `defineOptions({ layout })` inherits the **staff** layout |

The first three ship as a single block, branching per guard.

---

## Data Visibility

Decided: **the client sees everything that is theirs.** Document, address, birthdate,
phone, email, graduations, loyalty — unmasked.

The allowlist keeps existing, but for a different reason — **this is not LGPD, it is
leaking internal staff data.** These columns are internal-use and never belonged to the
client:

| Column | Why it stays out |
|---|---|
| `annotations` | team note about the client |
| `financial_account_id` | internal cost center |
| `external_reference` | gateway reference |
| `visibility` | internal ACL |

Dropping the allowlist is not "showing the client more data" — it is showing the client
the record the team works from. Kept.

## Legal Basis (LGPD)

Resolved by the business: access requires a real registration with an active contract,
meaning the area only responds to those who already have a relationship with the academy.
No additional consent gate goes into the code. What remains mandatory is the audit in
Open Question 2.

## Business Rules (confirmed)

- **Eligible**: `status ∈ {ACTIVE, OVERDUE}` ∧ contract `BillableStatus::OPEN` ∧ email
  not duplicated.
- **Ineligible**: `INACTIVE`, `LOCKED`, no `OPEN` contract, or duplicate `clients.email`.
- **Upcoming lessons**: `direct_lessons.status = OPEN` ∧ `lesson_date >= today` (status
  is `BillableStatus`, not a free string).
- **Active contract**: `OPEN`. `COMPLETED`, `CANCELED`, `RETURNED` do not grant access.
- **Loyalty**: `loyalty_level_id`, `loyalty_streak_months`, `loyalty_since` in `clients`.
- **Token**: query string `?token=`, expires in 30 min, single use, session lasts 120 min.

---

## Open Questions

1. **SMTP + `queue:work` in production** — `MAIL_MAILER=log` by default; without real
   config the link never arrives. Blocks go-live, not the code.
2. **Audit of duplicate `clients.email` in production** — the email has no unique
   constraint in the database and duplicates may exist. Duplicate ⇒ login fails silently
   ⇒ the client needs the front desk. Run the duplicate count before enabling.

Closed in this revision: LGPD (legal basis resolved by the contract) and `document`
visibility (full, unmasked).