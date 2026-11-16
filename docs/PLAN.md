# NexMind AI — Phase 1: Architecture & Plan

## 1. Environment audit (this workspace)

| Item | Status |
|---|---|
| Project directory | Empty (nothing existed) |
| PHP | 8.3.6 — meets Laravel 12 (needs 8.2+) |
| PHP extensions | ctype, curl, fileinfo, json, mbstring, openssl, PDO, pdo_mysql, tokenizer, xml, zip present. **`bcmath` not listed** (Laravel does not require it, but check on your machine) |
| Composer | 2.x binary installed |
| Node / NPM | v22.23.4 / 10.9.9 — fine for Vite |
| Git | 2.43.0 |
| MySQL server | **Not installed** (only PostgreSQL client + SQLite driver) |
| Packagist (repo.packagist.org) | **Blocked by the sandbox proxy (HTTP 403)** |
| npm registry | Reachable |

### Consequences

1. **Laravel cannot be installed in this sandbox** (`composer create-project` needs Packagist). The Laravel backend therefore has to be created and run on your own machine, or in an environment with Packagist access.
2. **No MySQL here**, so backend tests in this sandbox could not run against MySQL anyway. Tests should use SQLite in-memory (Laravel default for testing); production/dev uses MySQL.
3. The Vue frontend *can* be scaffolded and built here, since npm works.

Nothing has been installed or run for the backend yet; no backend test has been executed.

## 2. Architecture overview

```
Browser (Vue 3 SPA)  ──cookie session + CSRF──▶  Laravel 12 API (/api/v1)
                                                   │
                                  Controllers (thin) ─▶ Form Requests ─▶ Services
                                                   │
                       ┌───────────────┬───────────┴────────┬───────────────┐
                       ▼               ▼                    ▼               ▼
                   AIManager      CreditService       AnalyticsService   DocumentService
                   (provider      (transactional,     (dashboard +       (validate, store,
                    registry)      row locking)        admin stats)       extract text)
                       │
              AIServiceInterface ◀── OpenAIService (replaceable)
                       │
                  Queue jobs (long summaries, credit renewal, retention cleanup)
                                                   │
                                                 MySQL
```

Key decisions:

- **Auth**: Sanctum **SPA cookie mode** (no tokens in localStorage). Frontend and API must share a registrable domain (e.g. `app.example.com` + `api.example.com`, or dev `localhost:5173` + `localhost:8000`). Needs `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, CORS `supports_credentials=true`, and `/sanctum/csrf-cookie`.
- **Roles/permissions**: `spatie/laravel-permission` (provides `roles`, `permissions`, `model_has_roles` tables from the spec).
- **AI layer**: all tools go through `AIManager::driver()->generate(AIRequest)`; prompts live in `PromptTemplateService`; every call logged to `usage_logs`.
- **Credits**: reserve-then-commit inside a DB transaction with `lockForUpdate()` on the user's credit balance; charge only after a successful AI response, so failed calls never cost credits. Cost always read from `ai_tools.credit_cost`, never from the request.
- **Secrets**: API keys only in `.env`; admin AI-settings API returns masked values (`sk-••••1234`) and never plaintext.
- **Documents**: validate MIME + extension + size (default 5 MB), store on private disk under a random name, extract text (`smalot/pdfparser`, `phpoffice/phpword`), delete per retention setting; document text is passed to the model as clearly delimited untrusted data.
- **Frontend**: Vite + Vue 3 (Composition API, JS), Vue Router with role guards, Pinia stores (`auth`, `credits`, `ui`), Axios instance with `withCredentials`, Tailwind, lucide-vue-next, Chart.js.

## 3. Database relationships

```
users 1─∞ ai_generations ∞─1 ai_tools
users 1─∞ conversations 1─∞ messages
users 1─∞ subscriptions ∞─1 plans
users 1─∞ credit_transactions   (ref: ai_generation_id nullable, admin_id nullable)
users 1─∞ usage_logs ∞─1 ai_tools
users 1─∞ saved_contents ─(nullable)─ ai_generations
users 1─∞ uploaded_documents ─(nullable)─ ai_generations
users 1─∞ payments ∞─1 subscriptions
users 1─∞ notifications
users 1─∞ activity_logs (actor)
plans ∞─∞ ai_tools          (plan_ai_tool pivot: which tools a plan includes)
blog_categories 1─∞ blog_posts ∞─1 users (author)
faqs (category, sort_order)      contact_messages      settings (key/value, encrypted flag)
roles ∞─∞ users, roles ∞─∞ permissions (spatie)
```

Notable columns: `users.credits_balance`, `users.credits_reset_at`, `users.status` (active/suspended), soft deletes on users/blog_posts; `credit_transactions.type` (grant, renewal, usage, admin_add, admin_deduct, expiry) with `balance_after`; indexes on `(user_id, created_at)`, `ai_tool_id`, `status`, `slug` (unique).

Extra tables beyond the 22 required: `plan_ai_tool`, `password_reset_tokens`, `sessions`, `jobs`, `failed_jobs`, `cache`.

## 4. Folder structure

As in the specification (`backend/`, `frontend/`, `docs/`, `README.md`), plus:
`backend/app/Services/{AI,Credit,Analytics,Document}`, `backend/app/AI/Prompts`, `frontend/src/components/{ui,dashboard,admin,tools}`.

## 5. Dependencies

**Backend (Composer):** laravel/framework ^12, laravel/sanctum, spatie/laravel-permission, smalot/pdfparser, phpoffice/phpword; dev: phpunit, mockery, laravel/pint.
**Frontend (npm):** vue, vue-router, pinia, axios, tailwindcss, @tailwindcss/vite (or postcss), lucide-vue-next, chart.js, vue-chartjs; dev: vite, @vitejs/plugin-vue, vitest, @vue/test-utils, jsdom.
**External:** MySQL 8+, an `OPENAI_API_KEY` (needed only for live AI calls; the app must boot without it and show a configuration message), SMTP/mail driver for verification and reset emails (log driver in development).

## 6. Roadmap

| Phase | Scope | Where it can run |
|---|---|---|
| 2 | Laravel 12 skeleton, Sanctum SPA auth, roles, 22+ migrations, models, seeders, API scaffolding | Needs Packagist (your machine) |
| 3 | Vite/Vue/Tailwind/Router/Pinia/Axios, auth state, layouts | Sandbox or your machine |
| 4 | Public site (home, features, tools, pricing, about, contact, blog, FAQ) | Same |
| 5 | Dashboard shell, overview, profile, credits UI | Same |
| 6 | AI services, 7 tools, credit integration, document pipeline | Backend on your machine |
| 7 | Admin panel (all 14 modules) | Same |
| 8 | PHPUnit + Vitest, bug fixing, perf, responsive checks | Tests must actually be run before being reported as passing |
| 9 | README, API docs, DB docs, install + deployment guide | Anywhere |

## 7. Spec notes & decisions to confirm

- Pricing/payments: plans are configurable; paid checkout is **not** implemented and the UI will say paid plans are "coming soon" until a gateway is integrated.
- Testimonials/stats on the landing page will be clearly labelled demo content.
- Tool-to-category map for `/ai-tools`: Writing (Content Writer, Email), Ecommerce (Product Description), Marketing (Social Media), SEO (SEO Assistant), Productivity (Chat, Document Summarizer).
- Default credit costs (admin-editable): Chat 1, Email 2, Social 2, Product 3, SEO 3, Content Writer 5, Document Summary 5.
