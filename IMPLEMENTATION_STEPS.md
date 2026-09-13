# IMPLEMENTATION STEPS — Modalidades: remover upload de ícone + card estilizado por cor

## Objetivo
Remover o suporte a upload/ícone de modalidades (frontend, controller, model, DTO,
actions, migration) e substituir por um card estilizado que exibe o nome da
modalidade usando a cor definida.

---

## ✅ Concluído

- [x] **Migration para dropar a coluna `icon`** de `modalities`
  - `database/migrations/2026_08_29_210000_remove_icon_from_modalities_table.php`
  - Já aplicada no ambiente (`php artisan migrate --force`).
- [x] **Remover `icon` e `icon_url` do model `Modality`**
  - `app/Models/Modality.php`: removido `icon` de `$fillable`, `$appends` e o accessor.
- [x] **Remover handling de ícone e logs de debug do `ModalityController`**
  - `app/Http/Controllers/ModalityController.php`: removidos `storeIcon`, `deleteIcon`,
    validação/regra de `icon`, `remove_icon` e os `Log::debug` (`MODALITY-STORE-RAW` /
    `MODALITY-UPDATE-RAW`).
- [x] **Remover `icon` dos DTOs**
  - `app/DTOs/Modalities/CreateModalityDTO.php` e `UpdateModalityDTO.php`.
- [x] **Remover `icon` das Actions**
  - `app/Actions/Modalities/CreateModalityAction.php` e `UpdateModalityAction.php`.
- [x] **Criar componente `ModalityCard.vue`**
  - `resources/js/components/ModalityCard.vue`: card estilizado que recebe `name` e
    `color` e renderiza o nome com a cor (bolinha + nome, fundo tintado via `color-mix`).
- [x] **Atualizar `Details.vue` (formulário)**
  - `resources/js/pages/modalities/Details.vue`: removido `ImageUploadField`/estado de
    ícone; mantidos apenas `name` e `color`; adicionado **preview ao vivo** do
    `ModalityCard` conforme o usuário digita o nome e escolhe a cor.
- [x] **Atualizar `Index.vue` (listagem)**
  - `resources/js/pages/modalities/Index.vue`: removida a coluna e o slot de `icon`.
- [x] **Remover rota de diagnóstico e exceção CSRF temporárias**
  - `routes/web.php` (`__diag_multipart`) e `bootstrap/app.php` (entrada `__diag_multipart`).
- [x] **Reverter mudanças Docker do upload**
  - `compose.develop.yaml`, `Dockerfile` e removido `docker/php/uploads.ini`
    (não mais necessários).
- [x] **Atualizar testes**
  - `tests/Feature/ModalityStoreTest.php`: removidos os casos de ícone; mantidos/ajustados
    os de `name`/`color`.
  - `tests/Feature/Actions/CreateModalityActionTest.php` e `UpdateModalityActionTest.php`:
    casos de `color` sem `icon`.
  - `database/seeders/ModalitySeeder.php` e `tests/Feature/ModalitySeederTest.php`:
    sem ícone.
- [x] **Verificações**
  - `vendor/bin/pint --dirty --format agent` → passed.
  - `php artisan test --compact --filter='Modality'` → **19 passed** (43 assertions).
  - `npx vue-tsc --noEmit` → sem erros.
  - `npx vite build` → build de produção gerado (servido via Caddy).
- [x] **Remover componente não utilizado** `resources/js/components/inputs/ImageUploadField.vue`
  - Já não existia no projeto (nem no histórico git) quando verificado.
- [x] **Remover PNGs de seed não utilizados** em `database/seeders/assets/modalities/`
  - Diretório removido no commit `82d4f9c`; verificado ausente.
- [x] **Rodar a suíte completa de testes** (`php artisan test --compact`) via Docker
  - Serviço `test` (`docker compose -f compose.yaml -f compose.develop.yaml --profile test run --rm test`).
  - Resultado: **408 passed, 1 failed (1899 assertions)**.
  - Falha única: `Tests\Feature\Mcp\ServerRegistrationTest::test_server_registers_prompts_for_permitted_user`
    — espera 5 prompts e recebe 4 (o prompt `register-sale` não está registrado).
    Pré-existente e **fora do escopo de modalidades**.

---

## ⏳ Pendente

- [ ] **Verificação manual no UI**: abrir criar/editar modalidade, digitar nome e escolher
      cor, conferir o preview do card e o salvamento.
- [ ] **Decidir correção da falha MCP** `ServerRegistrationTest` (prompt `register-sale`): ou criar a
      classe de prompt faltante, ou ajustar a expectativa do teste — fora do escopo atual.
- [ ] **Ajustar container de testes** (opcional): montar `.env` no serviço `test` do
      `compose.develop.yaml` para eliminar os 405 warnings de `file_get_contents(/var/www/html/.env)`.

---

## Notas
- O ambiente Docker não precisou de restart para as mudanças de código PHP (não houve
  mudança de config no container). A migration já foi aplicada.
- Durante a verificação foi criado o `develop.env` local (ignorado pelo git, derivado do
  `.env` com `DB_HOST=db` e variáveis `POSTGRES_*`), necessário para o
  `compose.develop.yaml`. Nenhum arquivo versionado fora alterado além deste documento.
- Sobra volume antigo do stack MySQL (`ct12rounds_mysql_data`), candidato a limpeza.
- Não houve commit nem push destas alterações (aguarda aprovação).

---

# IMPLEMENTATION STEPS — Painel dedicado de edição da Landing (GrapesJS)

## Objetivo
Criar um painel de edição **separado** da área autenticada do sistema para editar a
landing pública, usando o **GrapesJS** (page builder visual), com **autenticação
independente** (`landing_admin_users`), **imagens em upload local** e fluxo de
**rascunho/publicar** (draft/publish). A landing pública continua servida via SSR
e só exibe o `published_html`.

## Decisões de escopo (confirmadas pelo usuário)
- Editor: **GrapesJS** (page builder visual drag-and-drop embutido no painel).
- Autenticação: **independente** do ERP (`landing_admin_users` + guard `landing_admin`).
- Publicação: **rascunho → publicar** (botão separado; pública só exibe `published_html`).
- Acessos: rota própria `/landing-admin` (login, editor, API de conteúdo/upload).
- Blog: apenas a landing page é editável (sem blog público).
- Dependências novas (aprovadas): `grapesjs`, `grapesjs-preset-webpage`.
- Deploy: sem infra nova; imagens no volume `laravel_storage` existente (disk `public`).

---

## Estrutura de rotas do painel

| Método | Rota | Controle | Descrição |
| --- | --- | --- | --- |
| GET | `/landing-admin/login` | `landing-admin/auth/login` | página de login (Inertia) |
| POST | `/landing-admin/login` | `landing-admin/auth/login` | autentica com rate-limit |
| POST | `/landing-admin/logout` | `landing-admin/auth/logout` | encerra sessão do painel |
| GET | `/landing-admin` | `landing-admin/editor` | editor Inertia (GrapesJS) |
| PUT | `/landing-admin/api/content` | `landing-admin/api.content` | salva rascunho (projeto + `draft_html`) |
| POST | `/landing-admin/api/publish` | `landing-admin/api.publish` | publica (`draft_html` → `published_html`) |
| POST | `/landing-admin/api/images` | `landing-admin/api.images` | upload de imagem (multipart) |
| GET | `/storage/landing/{path}` | `landing.storage` | entrega pública das imagens (sem symlink) |

---

## 🔲 Passos de implementação

### 1. Autenticação do painel (`landing_admin`)
- [x] **Migração** `create_landing_admin_users_table`
  - Colunas: `name`, `email` (unique), `password`, timestamps.
  - `database/migrations/2026_09_11_000000_create_landing_admin_users_table.php`
- [x] **Model** `app/Models/LandingAdminUser.php`
  - `HasFactory`; `$fillable` = name, email, password; `Hidden::password`; cast `hashed`.
- [x] **Guard/provider** em `config/auth.php`
  - `guards.landing_admin` (driver `session`, provider `landing_admin_users`).
  - `providers.landing_admin_users` (model `App\Models\LandingAdminUser`).
- [x] **Middleware** `auth:landing_admin` (alias padrão do Laravel, sem criação nova).
- [x] **Comando** `app/Console/Commands/CreateLandingAdminUser.php`
  - `landing-admin:create-user {email} {--name=} {--password=} {--force}`.
- [x] **`LandingAdmin\Auth\LoginController`**
  - `create()` → `Inertia::render('landing-admin/Login')`.
  - `store()` → validação + `Auth::guard('landing_admin')->attempt(...)`
    com **rate-limit** (`LoginRequest` prefixo `landing_admin:`) e redirect p/ `/landing-admin`.
  - `destroy()` → `Auth::guard('landing_admin')->logout()` + invalida sessão.

### 2. Conteúdo e publicação (`landing_contents`)
- [x] **Migração** `create_landing_contents_table`
  - Colunas: `project` (json, dados do GrapesJS), `draft_html` (text), `published_html`
    (text, nullable), `status` enum `['draft','published']` default `draft`,
    `published_at` (nullable), timestamps. Singleton controlado pelo `firstOrCreate`.
  - `database/migrations/2026_09_11_000001_create_landing_contents_table.php`
- [x] **Model** `app/Models/LandingContent.php`
  - `$fillable`; `$casts` (project → array, published_at → datetime).
  - Registro único criado sob demanda via `EditorController::content()` (`firstOrCreate`).
- [x] **`LandingAdmin\EditorController`**
  - `index()` → `Inertia::render('landing-admin/Editor')` com `project` atual e URLs
    (GrapesJS fica **fora do SSR** via import dinâmico — não desabilita SSR do painel).
  - `saveDraft()` (PUT) → valida (`project` array, `html` string, `css` nullable) e grava
    `draft_html = <style>{css}</style>{html}`.
  - `publish()` (POST) → copia `draft_html` → `published_html`, `status=published`,
    `published_at=now()`.
  - `uploadImage()` (POST multipart) → valida (`image`, `max:5120`), salva em
    `storage/app/public/landing/{uuid}.{ext}` (disk `public`), retorna `{ url }`.
- [x] **Rota pública de storage** `GET /storage/landing/{path}` (fora do guard)
  - `app/Http/Controllers/LandingStorageController.php`.
  - Proteção anti-path-traversal (`basename` + regex `[A-Za-z0-9._-]+`), 404 se ausente,
    `Cache-Control: public, max-age=31536000, immutable`.
  - Racional: Caddy serve `./public` (mount) sem `storage:link`; entrega via Laravel evita symlink.

### 3. Renderização pública (SSR)
- [x] **`HomeController@index`**
  - Se `LandingContent` com `published_html` (não vazio) → passa `contentHtml` à página;
    senão `null` (fallback estático atual).
- [x] **`resources/js/pages/public/Landing.vue`**
  - Nova prop `contentHtml?: string|null`.
  - Template: `v-if` com `v-html` → `<div class="landing-published">`; senão layout atual.
  - CSS isolado: resets globais (`:root`, `*`, `html`, `body`, scrollbar) + `<style scoped>`
    para o design — evita conflito com o HTML publicado.
  - SSR: `v-html` é renderizado no servidor (sem JS).

### 4. Frontend do painel
- [x] **`resources/js/pages/landing-admin/Login.vue`**
  - `defineOptions({ layout: null })`; formulário Vuetify com `useForm` (email/senha)
    e redirect pós-login (`/landing-admin/login`).
- [x] **`resources/js/pages/landing-admin/Editor.vue`**
  - `defineOptions({ layout: null })`; chrome controlada por Vue
    (chip de status, **Salvar rascunho**, **Publicar**, upload, ver site, sair).
  - GrapesJS carregado **dinamicamente** via `onMounted` (`import('grapesjs')`,
    `import('grapesjs-preset-webpage')`, `import('grapesjs/dist/css/grapes.min.css')`).
    O chunk `grapes-*.js/css` fica fora do bundle SSR (`bootstrap/ssr/ssr.js`).
  - Config: `fromElement: false`, `container` dedicado, `storageManager: false`.
  - Salvamento automático (debounce 1.5s) + botão explícito → axios `PUT /landing-admin/api/content`.
  - Publicar: `POST /landing-admin/api/publish` com diálogo de confirmação (`useConfirm`).
  - Upload: botão + `input[type=file]` → `POST /landing-admin/api/images` → `AssetManager.add`.

### 5. Dependências e bundling
- [x] **`npm install grapesjs grapesjs-preset-webpage`** (aprovado pelo usuário).
  - grapesjs ^0.23.6, grapesjs-preset-webpage ^1.0.3.
- [x] **`vite.config.ts`** — nada além do import dinâmico necessário (já verificado no build).
- [x] Importar stylesheet do GrapesJS apenas no componente Editor (`grapes.min.css`).

### 6. Fabricações/Seed (testes)
- [x] **Factory** `database/factories/LandingAdminUserFactory.php`
- [x] **Factory** `database/factories/LandingContentFactory.php` (estado `published()`)
- [x] **Usuário inicial** via comando `landing-admin:create-user` (seed inicial em produção).

### 7. Testes (PHPUnit)
- [x] `tests/Feature/LandingAdmin/AuthTest.php`
  - login válido → redirect `/landing-admin`; inválido → erro; rate-limit; logout;
    **isolamento**: usuário do guard `web` não acessa `/landing-admin`.
- [x] `tests/Feature/LandingAdmin/EditorTest.php`
  - rotas exigem auth `landing_admin` (redirect);
  - `saveDraft` grava `project` + `draft_html`; validação de campos;
  - `publish` copia para `published_html` e altera `status`;
  - `uploadImage` armazena e retorna URL; rejeita não-imagem;
  - path traversal bloqueado na rota pública de storage.
- [x] `tests/Feature/HiringLeads/PublicHiringLandingTest.php` (ajustar)
  - com `published_html` → a landing expõe `contentHtml`;
  - sem `published_html` (e com vazio) → `contentHtml` null (fallback).

### 8. Verificação e deploy
- [x] `vendor/bin/pint --dirty --format agent` → passed (container com bind-mount do repo).
- [x] `npx vue-tsc --noEmit` → sem erros.
- [x] `npm run build` + `npm run build:ssr` → build ok; chunk `grapes-*` fora do SSR;
      `Editor`, `Login` e `Landing` gerados no bundle.
- [x] `php artisan test --compact --filter='LandingAdmin|PublicHiringLanding'`
  → **28 passed (137 assertions)** em container descartável com bind-mount (código real;
  a imagem embutida anterior estava defasada e as execuções iniciais eram inválidas).
- [x] `php artisan test --compact` (suíte completa) → **421 passed, 12 failed**; as 12 falhas
  são **pré-existentes e fora do escopo** (plans/seeders, access-control, MCP, seeder de
  módulos — código não alterado aqui).
- [x] Rebuild de imagem/containers aprovado e executado: `./serve production up -d --build app queue`
  (2x — após as correções `permissionsVersion()` em `LandingAdminUser` e validação `mimes`
  no upload, já que `gd` não está instalado na imagem).
- [x] Aplicar migrations em produção: `php artisan migrate --force` → ambas as tabelas criadas.
- [x] Criar usuário do painel em produção: `php artisan landing-admin:create-user landing@ct12rounds.com --force`.
- [x] Smoke ao vivo via curl: login (302 → `/landing-admin`), editor (200),
  `PUT api/content` (200), `POST api/publish` (200), landing pública com o HTML
  publicado renderizado (`id="smoke-2026"` presente em `http://144.22.141.111/`).

---

## ⏳ Pendente (após implementação)
- [ ] Definir senha inicial do editor e fluxo de troca/recuperação de senha do painel.
- [ ] Builder visual configuravel: templates/presets da landing atual como bloco de partida.
- [ ] Decidir limpeza de `draft_html` histórico (retenção) e backups de `published_html`.

---

## Notas
- O painel não usa o layout/menu autenticado do ERP; as páginas usam `layout: null`.
- O GrapesJS é carregado por **import dinâmico** no `onMounted` do Editor, garantindo que
  não entra no bundle SSR e não roda no servidor — sem precisar desabilitar SSR do painel
  (a landing pública permanece com SSR habilitado).
- Redirects de auth: `bootstrap/app.php` usa `redirectGuestsTo`/`redirectUsersTo` por path
  (`landing-admin/*` → painel; demais → sistema). O middleware `guest:landing_admin`
  redireciona usuário já autenticado no painel de volta a `/landing-admin`.
- A imagem foi reconstruída e o deploy em produção foi executado (rebuild de containers,
  migrations aplicadas, usuário do painel criado e smoke ao vivo via curl + headless).
- **Fix "editor em branco"** (reportado pelo usuário e reproduzido/verificado com Playwright):
  - `v-app-bar` do Vuetify é `fixed` e cobria a toolbar/canvas do GrapesJS (painéis ficavam
    em y=0, atrás da barra). Substituído por `<header>` próprio em fluxo com `flex:1` no
    mount → toolbar, painel de blocos e canvas ficam visíveis sem sobreposição.
  - Botões Salvar/Publicar ficavam **sempre desabilitados**: a variável `editor` não é
    reativa e o binding `:disabled` era avaliado uma única vez. Criado `editorReady` (ref);
    também é o flag usado no template.
  - Canvas vazio: quando não há projeto salvo, o Editor agora "semeia" o canvas com o
    `draft_html` via `editor.setComponents(...)` (prop `draftHtml` adicionada ao
    `EditorController@index`), em vez de mostrar a área em branco.
  - Telemetria do GrapesJS desligada (`telemetry: false`; por padrão envia POST para
    `app.grapesjs.com/api/gjs/telemetry/collect`).
  - Layout/storageManager com `telemetry: false` e `gjs-pn-panels` etc. validados via
    Playwright (E2E: login → botões habilitados → `PUT api/content` 200 → diálogo
    Confirmar → `POST api/publish` 200 → frame com conteúdo semeado).
- Após o smoke inicial, a landing pública ficou com o texto de teste publicado; foi
  **restaurada para o design estático** (`published_html` = null, status draft), mantendo o
  rascunho para edição no painel.