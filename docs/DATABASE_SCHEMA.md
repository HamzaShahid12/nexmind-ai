# NexMind AI — Database Schema

Source of truth: `backend/database/migrations/`. Spatie Permission adds `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` (published by `scripts/setup-backend.sh`). Laravel adds `sessions`, `cache`, `jobs`, `failed_jobs`, `password_reset_tokens`.

Target: MySQL 8 (utf8mb4). Tests use SQLite in-memory.

## Tables

| Table | Purpose | Key columns / notes |
|---|---|---|
| `plans` | Subscription plans (admin-editable) | `slug` unique, `monthly_credits`, `price`, `is_active`, `is_default` (free plan), `features` JSON |
| `ai_tools` | The 7 AI tools | `slug` unique, `category`, `credit_cost`, `is_active`, `daily_limit`, `max_output_tokens`, `temperature` |
| `plan_ai_tool` | Which plans include which tools | pivot, FKs cascade |
| `users` | Accounts (Laravel base + extras) | `plan_id` (nullOnDelete), `status` active/suspended, `credits_balance` unsigned (never negative), `credits_reset_at`, `language`, `tone`, `notify_email`, `deletion_requested_at`, soft deletes |
| `conversations` | Chat threads | `user_id` cascade, `last_message_at`; index (user_id, last_message_at) |
| `messages` | Chat messages | `conversation_id` cascade, `role` user/assistant, `content`, `tokens` |
| `ai_generations` | Every successful generation | `user_id`, `ai_tool_id` (restrict), nullable `conversation_id`, `input` JSON, `output`, `output_format` text/json, `credits_used`, `model`, token counts |
| `credit_transactions` | Ledger of every balance change | `type` signup/renewal/usage/admin_add/admin_deduct/expiry, signed `amount`, `balance_after`, optional `ai_generation_id`, `admin_id` |
| `usage_logs` | Provider call log (success and error) | provider, model, tokens, `status`, `error_code` (no prompts, no keys), `duration_ms`, `credits_charged` |
| `saved_contents` | User-saved outputs | `user_id` cascade, optional `ai_generation_id`, `title`, `content`, `category` |
| `uploaded_documents` | Summarizer text | `extracted_text` (hidden from API), `char_count`, `truncated`, `expires_at` (purged by `documents:purge`); raw files are never retained |
| `subscriptions` | **Structure only** — no payment gateway is wired | `user_id`, `plan_id`, `status`, `starts_at`, `ends_at` |
| `payments` | **Structure only** — nothing writes real payments | provider, `provider_reference` unique, amount, currency, status |
| `blog_categories` | Blog categories | `slug` unique |
| `blog_posts` | Blog | `status` draft/published, `published_at`, SEO meta fields, soft deletes |
| `faqs` | FAQ entries | `category`, `sort_order`, `is_published` |
| `contact_messages` | Contact form inbox | `is_read`, `replied_at` |
| `notifications` | Laravel notifications table | standard |
| `settings` | Admin-editable key/value (AI provider/model/timeout, site settings) | `key` unique |
| `activity_logs` | Audit trail of admin/security actions | `user_id`, `action`, `properties` JSON, `ip_address` |

## Relationships

- `plans` 1—N `users`; `plans` N—N `ai_tools` (via `plan_ai_tool`)
- `users` 1—N `conversations` 1—N `messages`
- `users` 1—N `ai_generations` N—1 `ai_tools`; `ai_generations` N—1 `conversations` (nullable)
- `users` 1—N `credit_transactions` (optionally linked to an `ai_generation` and the acting `admin`)
- `users` 1—N `saved_contents`, `uploaded_documents`, `subscriptions`, `payments`
- `blog_categories` 1—N `blog_posts`; `users` (author) 1—N `blog_posts`

## Integrity rules

- `users.credits_balance` is unsigned: the database itself refuses a negative balance. Only `CreditService` changes it, inside a transaction with `lockForUpdate`.
- Each generation, its credit charge and its usage log are written in one transaction; a failed AI call writes only an error usage log and charges nothing.
- Deleting a user cascades to their conversations, generations, saved content and documents; ledger rows cascade too. Users and blog posts use soft deletes.
