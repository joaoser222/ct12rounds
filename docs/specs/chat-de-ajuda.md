# Spec: System Help Chat (`chat-de-ajuda`)

## Objective

The internal chat answers **how to use the system** instead of executing functions
against the database. When the answer involves data, it returns a link already filtered
to the screen that shows that data. The human executes; the system enforces permission.

Success = the chat answers usage questions having sent zero function schema to the LLM
provider, and no answer writes to the database.

### Measured Evidence (basis for the decision)

Measured with `APP_ENV=testing` across `Ct12roundsServer` classes, bypassing the
permission gate:

| Metric | Value |
|---|---|
| Exposed classes | 44 tools + 23 resources + 7 prompts |
| Schema payload | 32,007 bytes |
| Tokens resent per message | ~8,000 |
| Worst case (6-iteration loop) | ~48,000 tokens |
| Max user question size | 4,000 characters (~1,000 tokens) |

The schema is ~8x larger than the question, and `ChatService::ask()` resends it on every
iteration (`app/Services/Mcp/ChatService.php:193`). All of that cost exists to power
things the user never asked for.

### User stories

- As front desk staff, I ask "how do I terminate a contract?" and get the procedure,
  with no risk of the system terminating it without me intending to.
- As billing staff, I ask "how do I register a partial payment?" and get the steps.
- As front desk staff, I ask "which clients have overdue contracts?" and land on the
  receivables screen already filtered, in one click.

### Out of scope (decided)

- **Any write tool in the chat.** It is the source of the cost and the risk.
- **Answers containing numeric values.** The filtered screen shows the number. Answering
  in chat reopens the door to function calling.
- **Embeddings, vector DB, or RAG.** The documentation fits in the system prompt. A new
  dependency for a gain that does not exist at this problem size.
- **Keeping `/mcp/ct12rounds` for compatibility.** An empty server is worse than a
  removed one: it misleads whoever tries to integrate against it.
- **Migrating the 62 `Action`s.** They are already the business layer shared with the
  web controllers.
- **Enumerating all 34 modules.** 34 documents is the mistake that kills the idea. Write
  about the ~10 flows that front desk and billing perform daily.

---

## Assumptions to Validate

The first assumption decides whether the whole idea works. The other three are
verifiable before any code is written.

- [ ] **The user wants to be navigated to a screen, not shown the number.** This is the
      central bet and it is **unvalidated**: `chat_messages` has zero rows, so there is
      no usage data. How to test: ship the MVP and count the questions that ask for a
      value.
- [ ] **No external consumer of `/mcp/ct12rounds`.** No reference in docs, frontend, or
      env, but absence of evidence is not evidence of absence. How to test: check route
      access logs, or remove it and observe.
- [ ] **Written docs cost less than 44 schemas.** A flow guide changes when the flow
      changes, not when a field is added. Plausible, not measured.
- [ ] **The concept→URL map hits the right rows.** See "Guard Traps" below:
      `searchField` must be explicit, and the value must be the enum value rather than
      the label.

---

## Evidence That the Deep Link Already Works

Verified in code, **not a hypothesis**:

| Layer | Where | What |
|---|---|---|
| Query string | `app/Http/Controllers/CrudModuleController.php:45-48` | reads `search`, `searchField`, `visibility`, `sortBy` |
| Inertia props | `CrudModuleController::index()` | passes them through in `filters` |
| Frontend | `resources/js/components/TablePage.vue:165` | `search.value = page.props.filters?.search ?? ''` |

`GET /receivables?searchField=status&search=overdue` already opens the table with the
search applied. **Zero frontend change.**

---

## Tech Stack

PHP 8.3 · Laravel 13 · PostgreSQL · Inertia.js v3 · Vue 3 · Vuetify 3

No new dependency. `laravel/mcp` leaves `composer.json` and gets no replacement, because
the chat becomes a plain HTTP call to the LLM provider, which already exists in
`app/Services/Mcp/ChatService.php`.

---

## Commands

```bash
# PHP — host has no PHP; everything runs in the container
docker compose exec app php artisan test --compact
docker compose exec app vendor/bin/pint --dirty --format agent
```

---

## Project Structure

### Removed

```
app/Mcp/                              74 classes (44 tools, 23 resources, 7 prompts)
app/Services/Mcp/ChatToolSchemaProvider.php
app/Services/Mcp/ChatPromptProvider.php     (only if the prompts are not repurposed)
routes/ai.php
composer.json                                    → remove laravel/mcp
```

### Kept (untouched)

```
app/Actions/                          62 classes — business layer, used by the web
app/Http/Controllers/*                including those that call the Actions
```

Of the 62 Actions, 17 are not exposed by any tool, and `CreateClientAction`,
`CreateContractAction`, and `MarkReceivablePaid` are called **also** by the web
controllers. Dropping MCP deletes thin adapters and **zero business logic**.

### Added

```
docs/help/*.md                        ~10 flow guides, read at runtime
app/Services/Help/HelpIndex.php       concept→URL index
app/Services/Help/HelpService.php     system prompt + guide lookup
```

---

## Boundaries

**Always**
- `php artisan test --compact` + `pint` before commit.
- Keep help read-only: no writes, not even indirect ones.
- Links from the chat pass through the same permission gates as the target screen.

**Ask first**
- Rewriting an already-published guide in `docs/help/`.
- Extending the link map beyond the initial 5 flows.

**Never**
- Reintroduce a write tool "just for one case".
- Have the chat answer numeric values.
- Turn the chat back into an agent with `max_tool_iterations` > 0.
- Leave meta-documentation (specs, plans) inside `docs/help/` — the chat does keyword
  lookup in that directory and would leak internal content to end users.

---

## Success Criteria

1. No request to the LLM provider sends `tools` or `functions`.
2. Schema payload per message = 0 bytes.
3. One iteration per message: no tool-call loop.
4. `GET /receivables?searchField=status&search=overdue` keeps working.
5. A known usage question ("how do I terminate a contract?") returns instructions as
   text.
6. A known data question ("overdue contracts") returns a link to the filtered screen.
7. `app/Mcp/` no longer exists; `laravel/mcp` is out of `composer.json`.
8. `app/Actions/` intact at 62 classes; the suite passes without modification.
9. Removing `laravel/mcp` breaks neither `php artisan test` nor `pint`.
10. A user without permission who follows a link gets the same 403/302 as before.

---

## Guard Traps (implement together)

| Where | What breaks |
|---|---|
| `AbstractModuleController.php:98` | `defaultSearchField()` returns `$searchableFields[0]`. For `ReceivableController` that is `due_date`, **not** `status`. A link without an explicit `searchField` searches the wrong column and returns the whole list. |
| `ReceivableController.php:43` | `searchableFields = ['due_date', 'payment_date', 'status']` — the value must be the enum value (`InvoiceStatus`), not the Portuguese label shown on screen. |
| `Chat.vue` / `ChatPromptProvider` | The 7 prompts are the UI's "suggested questions" today. If they are deleted along with MCP, the chips disappear. Decide: repurpose as suggested questions, or remove them from the UI. |
| `routes/web.php` | `/mcp/ct12rounds` sits in the `auth:sanctum` group. Removing the route is a breaking change for any external client — confirm the logs first. |
| Inertia SSR | `Http::preventStrayRequests()` does not work in a test that renders a page: SSR posts to `127.0.0.1:<port>/render`. Already the case in `ChatControllerTest` and `PublicHiringLeadStoreTest`. |

---

## Open Questions

- Where the guides live at runtime: `docs/help/*.md` read per request, or bundled into
  the build? Reading per request is simpler; bundling avoids I/O in production.
- Does the chat keep requiring `chat.view`, or does help become open to any
  authenticated user?
- Do the 7 prompts become the UI's suggested questions, or do they die with MCP?
- Is guide lookup simple keyword search, or do the 10 guides fit entirely in the system
  prompt with no index at all?
