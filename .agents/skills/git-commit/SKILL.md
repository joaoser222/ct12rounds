---
name: git-commit
description: >
  RESTRICTED USE: activate ONLY when user types exactly "commitar" (with
  double 'm' and double 't'). DO NOT activate for "commit", "comitar",
  "fazer commit", "git commit", "git push", "subir", "pronto",
  "finalizar". The only keyword that triggers this skill is the literal
  string "commitar". When activated, it analyzes changed files via git
  diff, groups changes into logical commits following Angular Convention
  with Portuguese descriptions, displays proposed commits with "Aguardando
  confirmacao de commit" and only applies (with git push) after user
  confirmation.
compatibility:
  - git
  - conventional-commits
  - angular-convention
---

# Commit Skill — Ct12rounds

## When to use

Activate this skill **exclusively** when the user types **"commitar"** in
the chat. Do NOT activate for variations like "comitar", "commit",
"fazer commit", "gerar commit", "push". Only the exact string "commitar"
should trigger this skill.

## Prerequisites

- Git configured with `user.name` and `user.email`
- Remote `origin` configured with push permissions
- Previous commits already follow Angular Convention (check history)

## Commit format

```
<tipo>(<escopo>): <descrição em português>
```

### Types

| Type | When to use |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `refactor` | Code refactoring with no behavior change |
| `chore` | Infrastructure, CLI, config, build tasks |
| `docs` | Documentation |
| `test` | Tests |
| `style` | Code formatting, linting |

### Scope

The scope must reflect the affected module or domain area of the
Ct12rounds project. Use one of the following (snake_case, matching the
module slugs in `app/AccessControl/AccessModule.php`):

- **Pessoas**: `clients`, `trainers`, `suppliers`
- **Catálogo**: `products`, `modalities`, `plans`, `plan_categories`
- **Faturamento**: `contracts`, `coupons`, `sales`, `purchases`,
  `direct_lessons`
- **Financeiro**: `cost_centers`, `financial_categories`, `payables`,
  `receivables`, `movements`, `transfers`
- **Gateway de Pagamentos**: `gateway_payments`, `gateway_transfers`,
  `gateway_postbacks`, `gateway_customers`, `gateway_credit_cards`,
  `gateway_invoices`, `gateway_accounts`
- **Avançado**: `financial_accounts`, `users`, `settings`
- **Visualização**: `dashboard`, `reports`
- **Transversal**: `frontend`, `tests`, `database`, `access-control`,
  `commands`, `config`, `docs`, `root`

Use the most specific scope possible. If changes affect multiple contexts,
use the main context scope or create separate commits.

### Description

- **Always in Portuguese**
- Start with a verb in the present indicative (e.g., "adiciona", "corrige",
  "remove", "atualiza", "refatora", "cria", "implementa")
- Max 72 characters
- Do not start with uppercase
- Do not end with a period

## Execution flow

### Step 1: Analyze changes

Run `git diff --name-status HEAD` to list all modified, added, and deleted
files since the last commit.

Example output:
```
M       app/Http/Controllers/ClientController.php
A       app/Models/Client.php
A       database/migrations/2026_07_31_000001_create_clients_table.php
M       resources/js/pages/clients/Details.vue
```

Files already covered by `.gitignore` (`vendor/`, `node_modules/`,
`public/build`, `.env`, etc.) never appear in `git diff` and must not be
committed.

### Step 2: Group into logical commits

Analyze the changed files and group them following this logic:

1. **Same module + same type** → one commit
2. **Same module + different types** → separate commits (e.g., one `feat`
   and one `fix` in the same module become two commits)
3. **Different modules** → separate commits per module
4. **Very small changes** (1-2 trivial files) → can be grouped into a single
   commit if part of the same feature
5. **Config/database/frontend infra** → `chore(config)`, `chore(database)`,
   `chore(frontend)`, etc.

Use good judgment: the goal is cohesive commits where each commit is an
atomic logical unit.

For each commit, generate the message in Angular format.

### Step 3: Display proposed commits

Show the user the list of commits to be created:

```
========================================
🤖 Proposed commits:
========================================

1/3: feat(clients): adiciona CRUD de clientes
  Files:
    M app/Http/Controllers/ClientController.php
    A app/Models/Client.php
    A database/migrations/2026_07_31_000001_create_clients_table.php

2/3: feat(gateway_payments): adiciona listagem de pagamentos
  Files:
    M resources/js/pages/gateway_payments/Index.vue
    M routes/web.php

3/3: chore(database): cria migração de campos de faturamento
  Files:
    A database/migrations/2026_07_29_000001_add_invoicing_to_gateway_accounts.php

========================================
Aguardando confirmação de commit
========================================
```

Display the exact message **"Aguardando confirmação de commit"** at the end.

### Step 4: Wait for confirmation

Do NOT run any git commands yet. Only display the proposed commits and
wait for the user to respond.

If the user confirms (e.g., "sim", "pode comitar", "ok", "confirmo",
"vamos"), proceed to Step 5.

If the user rejects or asks for changes (e.g., "não", "espera", "altera
tal commit"), adjust according to feedback and display again.

### Step 5: Apply commits

For each proposed commit (in the order displayed):

1. **Stage the files**:
   ```bash
   git add <file1> <file2> ...
   ```

2. **Create the commit**:
   ```bash
   git commit -m "<tipo>(<escopo>): <descrição>"
   ```

   For commits with a body (when additional justification is needed):
   ```bash
   git commit -m "<tipo>(<escopo>): <descrição>" -m "<body>"
   ```

3. **Only after ALL commits are created**, push:
   ```bash
   git push origin <current-branch>
   ```

   Discover the current branch with `git branch --show-current`.

### Step 6: Report result

After successful push, display:

```
✅ Commits enviados com sucesso para <branch>!
```

If there is an error in any step, display the error message and stop.

## Examples

### Example 1: Simple change in one module

**Changed files:**
```
M app/Http/Controllers/ClientController.php
A app/Models/Client.php
A database/migrations/2026_07_31_000001_create_clients_table.php
A resources/js/pages/clients/Index.vue
A resources/js/pages/clients/Details.vue
M routes/web.php
```

**Generated commit:**
```
feat(clients): adiciona CRUD de clientes
```

### Example 2: Multiple contexts

**Changed files:**
```
M app/PaymentGateways/Adapters/AsaasPaymentGatewayAdapter.php
M resources/js/pages/gateway_payments/Index.vue
M routes/web.php
```

**Generated commits:**
```
1/2: feat(gateway_payments): adiciona processamento de pagamentos
2/2: feat(frontend): adiciona listagem de pagamentos do gateway
```

`routes/web.php` (route registration) should be grouped into the commit
whose feature depends on the new routes.

### Example 3: Fix + chore in the same batch

**Changed files:**
```
M app/Http/Controllers/FinancialCategoryController.php
M resources/js/layouts/AuthenticatedLayout.vue
```

**Generated commits:**
```
1/2: fix(financial_categories): corrige validação de descrição da categoria
2/2: chore(frontend): adiciona item de menu de categorias financeiras
```

## Important rules

- Commit description must be at most **72 characters**
- Use the verb in the **present indicative** in Portuguese ("adiciona",
  not "adicionado")
- Multi-word scopes use snake_case: `gateway_payments`,
  `financial_categories`
- If there is only one changed file and it belongs to a different context
  than expected, use the file's directory context
