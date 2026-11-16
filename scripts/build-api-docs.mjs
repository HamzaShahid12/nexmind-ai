// Generates docs/API_DOCUMENTATION.md and docs/NexMind-AI.postman_collection.json from ONE endpoint list,
// then verifies that list matches backend/routes/api.php (fails loudly if they drift).
// Usage: node scripts/build-api-docs.mjs
import { readFileSync, writeFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = join(dirname(fileURLToPath(import.meta.url)), '..')
const P = 'public', U = 'user', A = 'admin'

// [method, path, access, description, example body (optional)]
const endpoints = [
  ['GET', 'plans', P, 'Active plans with monthly credit allowance'],
  ['GET', 'faqs', P, 'Published FAQs grouped by category'],
  ['GET', 'blog/categories', P, 'Blog categories'],
  ['GET', 'blog/posts', P, 'Published posts. Query: search, category, page'],
  ['GET', 'blog/posts/{slug}', P, 'One published post'],
  ['POST', 'contact', P, 'Submit the contact form (5/min)', { name: 'Sam', email: 'sam@example.com', subject: 'Hello', message: 'I have a question about plans.' }],
  ['POST', 'register', P, 'Create an account and sign in (10/min)', { name: 'Ada', email: 'ada@example.com', password: 'Password123', password_confirmation: 'Password123' }],
  ['POST', 'login', P, 'Sign in with a session cookie (10/min)', { email: 'demo@nexmind.test', password: '<your password>' }],
  ['POST', 'forgot-password', P, 'Email a reset link (5/min). Same response for unknown emails', { email: 'ada@example.com' }],
  ['POST', 'reset-password', P, 'Set a new password using the emailed token', { token: '<token>', email: 'ada@example.com', password: 'NewPassword123', password_confirmation: 'NewPassword123' }],
  ['GET', 'email/verify/{id}/{hash}', P, 'Signed link from the verification email; redirects to the SPA'],

  ['POST', 'logout', U, 'End the session'],
  ['GET', 'user', U, 'Current user with plan, credits and roles'],
  ['POST', 'email/verification-notification', U, 'Resend the verification email'],
  ['GET', 'dashboard', U, 'Plan, credits, totals, recent activity, top tools'],
  ['GET', 'dashboard/analytics', U, 'Generations and credits per day. Query: days (1-365)'],
  ['GET', 'profile', U, 'Profile'],
  ['PUT', 'profile', U, 'Update name, email, language, tone, notify_email. Avatar: POST multipart with _method=PUT', { name: 'Ada L.', language: 'English', tone: 'friendly', notify_email: true }],
  ['POST', 'profile', U, 'Multipart profile update (avatar upload). Send _method=PUT'],
  ['PUT', 'profile/password', U, 'Change password', { current_password: '<current>', password: 'NewPassword123', password_confirmation: 'NewPassword123' }],
  ['POST', 'profile/deletion-request', U, 'Record an account deletion request for admin review'],
  ['GET', 'credits', U, 'Balance, plan, next renewal, tool costs'],
  ['GET', 'credits/transactions', U, 'Own credit history. Query: type, page'],
  ['GET', 'history', U, 'Own generations. Query: search, tool, from, to, per_page, page'],
  ['GET', 'history/{generation}', U, 'Full output of an own generation'],
  ['DELETE', 'history/{generation}', U, 'Delete an own generation'],
  ['GET', 'saved-content', U, 'Own saved content. Query: search, category'],
  ['POST', 'saved-content', U, 'Save a generation (content is taken from the server) or custom content', { ai_generation_id: 1 }],
  ['DELETE', 'saved-content/{savedContent}', U, 'Delete own saved content'],
  ['GET', 'conversations', U, 'Own chat conversations'],
  ['GET', 'conversations/{conversation}', U, 'Messages of an own conversation'],
  ['PATCH', 'conversations/{conversation}', U, 'Rename a conversation', { title: 'Marketing ideas' }],
  ['DELETE', 'conversations/{conversation}', U, 'Delete a conversation'],
  ['GET', 'documents', U, 'Own uploaded documents (metadata only)'],
  ['DELETE', 'documents/{document}', U, 'Delete an own document'],
  ['GET', 'ai/tools', U, 'Tools available to the user with credit costs'],
  ['POST', 'ai/chat', U, 'Send a chat message (20/min AI rate limit)', { message: 'Give me 3 newsletter subject lines', conversation_id: null }],
  ['POST', 'ai/content-writer', U, 'Generate content', { content_type: 'blog article', topic: 'Benefits of local SEO', tone: 'professional', length: 'medium', language: 'English' }],
  ['POST', 'ai/product-description', U, 'Generate product copy', { product_name: 'Trail Water Bottle', features: 'Insulated\n750 ml', tone: 'friendly' }],
  ['POST', 'ai/social-media', U, 'Generate social posts', { platform: 'instagram', topic: 'Summer sale', tone: 'friendly', variations: 3 }],
  ['POST', 'ai/seo', U, 'Generate SEO suggestions', { page_title: 'Handmade candles', content: 'Page text here...', main_keyword: 'handmade candles' }],
  ['POST', 'ai/email', U, 'Generate an email', { purpose: 'Introduce our service', recipient_type: 'Prospective client', key_information: 'We offer bookkeeping.', tone: 'professional', length: 'short' }],
  ['POST', 'ai/document-summary', U, 'Upload PDF/TXT/DOCX (multipart field "file", max 5 MB) and summarize'],
  ['POST', 'ai/document-summary/{document}/ask', U, 'Ask a question about an own document', { question: 'What are the main risks?' }],

  ['GET', 'admin/dashboard', A, 'Platform analytics from real data'],
  ['GET', 'admin/users', A, 'Users. Query: search, status, role, page'],
  ['GET', 'admin/users/{user}', A, 'User detail with recent generations and transactions'],
  ['PUT', 'admin/users/{user}', A, 'Edit name, email, plan', { name: 'New name', plan_id: 1 }],
  ['DELETE', 'admin/users/{user}', A, 'Soft-delete a user (never the last active super admin)'],
  ['POST', 'admin/users/{user}/suspend', A, 'Suspend and revoke sessions'],
  ['POST', 'admin/users/{user}/activate', A, 'Reactivate'],
  ['PUT', 'admin/users/{user}/roles', A, 'Set roles (super admin only)', { roles: ['admin'] }],
  ['POST', 'admin/users/{user}/credits', A, 'Add or deduct credits (audited)', { amount: 25, reason: 'Goodwill' }],
  ['GET', 'admin/roles', A, 'Roles and permissions'],
  ['PUT', 'admin/roles/{role}', A, 'Set a role\'s permissions (super admin only)', { permissions: ['manage users'] }],
  ['GET', 'admin/ai-tools', A, 'All tools'],
  ['PUT', 'admin/ai-tools/{tool}', A, 'Edit name, cost, limits, active flag', { credit_cost: 4, is_active: true }],
  ['GET', 'admin/ai-settings', A, 'Provider, model, timeout. API key is masked and never returned'],
  ['PUT', 'admin/ai-settings', A, 'Update provider/model/timeout', { provider: 'openai', model: 'gpt-4o-mini', timeout: 60 }],
  ['GET', 'admin/settings', A, 'General settings'],
  ['PUT', 'admin/settings', A, 'Update general settings', { site_name: 'NexMind AI', registration_enabled: true, documents_retention_days: 7 }],
  ['GET', 'admin/plans', A, 'Plans'],
  ['POST', 'admin/plans', A, 'Create a plan', { name: 'Starter', slug: 'starter', monthly_credits: 300, is_active: true }],
  ['PUT', 'admin/plans/{plan}', A, 'Edit a plan', { name: 'Free', slug: 'free', monthly_credits: 150, is_default: true, is_active: true }],
  ['DELETE', 'admin/plans/{plan}', A, 'Delete a plan (not the default, not one with users)'],
  ['GET', 'admin/credits', A, 'User credit balances. Query: search'],
  ['GET', 'admin/transactions', A, 'Credit transactions. Query: user_id, type, from, to'],
  ['GET', 'admin/generations', A, 'All generations. Query: search, tool, user_id'],
  ['GET', 'admin/generations/{generation}', A, 'One generation with full output'],
  ['GET', 'admin/usage', A, 'AI usage summary and request log. Query: status'],
  ['GET', 'admin/blogs', A, 'Posts. Query: status, search'],
  ['POST', 'admin/blogs', A, 'Create a post (multipart for image)', { title: 'New post', content: 'Body', status: 'draft' }],
  ['GET', 'admin/blogs/{post}', A, 'One post'],
  ['PUT', 'admin/blogs/{post}', A, 'Update a post. Use POST + _method=PUT when uploading an image', { title: 'New post', content: 'Body', status: 'draft' }],
  ['POST', 'admin/blogs/{post}', A, 'Multipart update (featured image). Send _method=PUT with the form fields'],
  ['DELETE', 'admin/blogs/{post}', A, 'Delete (soft) a post'],
  ['POST', 'admin/blogs/{post}/publish', A, 'Publish'],
  ['POST', 'admin/blogs/{post}/unpublish', A, 'Unpublish'],
  ['GET', 'admin/categories', A, 'Blog categories'],
  ['POST', 'admin/categories', A, 'Create a category', { name: 'News' }],
  ['PUT', 'admin/categories/{category}', A, 'Rename', { name: 'Company news' }],
  ['DELETE', 'admin/categories/{category}', A, 'Delete'],
  ['GET', 'admin/faqs', A, 'FAQs'],
  ['POST', 'admin/faqs', A, 'Create', { category: 'General', question: 'Question?', answer: 'Answer.', is_published: true }],
  ['POST', 'admin/faqs/reorder', A, 'Set order', { ids: [3, 1, 2] }],
  ['PUT', 'admin/faqs/{faq}', A, 'Update', { category: 'General', question: 'Question?', answer: 'Answer.', is_published: true }],
  ['DELETE', 'admin/faqs/{faq}', A, 'Delete'],
  ['GET', 'admin/contact-messages', A, 'Messages. Query: unread=1'],
  ['GET', 'admin/contact-messages/{message}', A, 'Open a message (marks it read)'],
  ['PATCH', 'admin/contact-messages/{message}', A, 'Set read flag', { is_read: true }],
  ['POST', 'admin/contact-messages/{message}/reply', A, 'Email a reply to the sender', { body: 'Thanks for getting in touch...' }],
  ['DELETE', 'admin/contact-messages/{message}', A, 'Delete'],
]

// ---- consistency check against the Laravel routes -------------------------------------
const routesSrc = readFileSync(join(root, 'backend/routes/api.php'), 'utf8')
const fromRoutes = new Set()
for (const m of routesSrc.matchAll(/Route::(get|post|put|patch|delete)\(\s*'([^']+)'/g)) {
  const path = m[2].replace(/\{(\w+)\??\}/g, '{$1}')
  fromRoutes.add(`${m[1].toUpperCase()} ${path}`)
}
// Route::match(['put','post'], 'blogs/{post}', ...) and the `ai/*` / `admin/*` prefixes are normalised below.
for (const m of routesSrc.matchAll(/Route::match\(\['(\w+)',\s*'(\w+)'\],\s*'([^']+)'/g)) {
  fromRoutes.add(`${m[1].toUpperCase()} ${m[3]}`); fromRoutes.add(`${m[2].toUpperCase()} ${m[3]}`)
}
// Routes inside prefix groups are written without the prefix in api.php; add them back for comparison.
const aiRoutes = ['chat', 'content-writer', 'product-description', 'social-media', 'seo', 'email', 'document-summary', 'document-summary/{document}/ask']
const normalized = new Set()
for (const r of fromRoutes) {
  const [method, path] = r.split(' ')
  if (aiRoutes.includes(path) && method === 'POST') normalized.add(`POST ai/${path}`)
  else normalized.add(r)
}
// Admin routes: api.php lines inside the admin group are written as 'dashboard', 'users', ... (prefix('admin')).
const userLevel = new Set(endpoints.filter((e) => e[2] !== A).map(([m, p]) => `${m} ${p}`))
const adminDocumented = new Set(endpoints.filter((e) => e[2] === A).map(([m, p]) => `${m} ${p.replace(/^admin\//, '')}`))

const missingDocs = []
const unknownDocs = []
for (const r of normalized) {
  if (!userLevel.has(r) && !adminDocumented.has(r)) missingDocs.push(r)
}
for (const r of [...userLevel, ...[...adminDocumented].map((x) => x)]) {
  if (!normalized.has(r)) unknownDocs.push(r)
}
// An admin path may collide with a user-level one (e.g. both have GET plans); treat as satisfied if present in either set.
const reallyMissing = missingDocs.filter((r) => ![...userLevel].includes(r) && ![...adminDocumented].includes(r))
const reallyUnknown = unknownDocs.filter((r) => !normalized.has(r))
if (reallyMissing.length || reallyUnknown.length) {
  console.error('API docs are out of sync with backend/routes/api.php')
  if (reallyMissing.length) console.error(' Routes missing from docs:', reallyMissing)
  if (reallyUnknown.length) console.error(' Documented but not in routes:', reallyUnknown)
  process.exit(1)
}
console.log(`Verified: ${endpoints.length} documented endpoints match backend/routes/api.php (${normalized.size} route definitions)`)

// ---- Markdown ------------------------------------------------------------------------
const label = { [P]: 'Public', [U]: 'Signed-in user', [A]: 'Admin' }
const section = (title, kind) => {
  const rows = endpoints.filter((e) => e[2] === kind).map(([m, p, , d]) => `| \`${m}\` | \`/api/v1/${p}\` | ${d} |`)
  return `### ${title}\n\n| Method | Path | Description |\n|---|---|---|\n${rows.join('\n')}\n`
}

const md = `# NexMind AI — API Documentation

Base URL: \`{APP_URL}/api/v1\` (for local development: \`http://localhost:8000/api/v1\`)

> This file is generated by \`node scripts/build-api-docs.mjs\`, which also verifies it against \`backend/routes/api.php\`. Do not edit by hand.

## Authentication (Sanctum SPA cookies)

The API uses **cookie-based session authentication**, designed for the Vue SPA. No token is stored in \`localStorage\`.

1. \`GET /sanctum/csrf-cookie\` (note: **no** \`/api/v1\` prefix). This sets the \`XSRF-TOKEN\` cookie.
2. \`POST /api/v1/login\` with the \`X-XSRF-TOKEN\` header (axios does this automatically with \`withXSRFToken: true\`) and \`withCredentials: true\`.
3. Subsequent requests send the session cookie. \`POST /api/v1/logout\` ends the session.

Requirements: the SPA origin must be listed in \`SANCTUM_STATEFUL_DOMAINS\` and \`FRONTEND_URL\` (CORS), and the SPA and API must share a registrable domain (e.g. \`localhost\` / \`localhost\`, or \`app.example.com\` / \`api.example.com\` with \`SESSION_DOMAIN=.example.com\`).

Always send \`Accept: application/json\`.

## Response conventions

| Situation | Status | Body |
|---|---|---|
| Success | 200 / 201 | \`{ "data": ... }\` (collections are paginated: \`data\`, \`links\`/\`meta\` or \`current_page\`, \`last_page\`, \`total\`) |
| Validation error | 422 | \`{ "message": "...", "errors": { "field": ["..."] } }\` |
| Not authenticated | 401 | \`{ "message": "You need to log in to continue." }\` |
| Not allowed / suspended | 403 | \`{ "message": "..." }\` |
| Not found | 404 | \`{ "message": "The requested resource was not found." }\` |
| Insufficient credits | 402 | \`{ "message": "...", "required": 3, "available": 1 }\` |
| Rate limited (or tool daily limit) | 429 | \`{ "message": "..." }\` |
| AI not configured | 503 | \`{ "message": "...", "code": "not_configured" }\` |
| AI timeout | 504 | \`{ "message": "...", "code": "timeout" }\` |
| AI provider error / invalid output | 502 | \`{ "message": "...", "code": "provider_error" \\| "invalid_response" }\` |
| Unexpected error | 500 | \`{ "message": "Something went wrong on our side. Please try again later." }\` (details only in server logs) |

**Credits:** a generation is only charged after the AI call succeeded and its result was stored. Failed requests (402 before the call, 5xx from the provider, timeouts, invalid output) never reduce the balance. The credit cost is read from the database; any cost sent by the client is ignored.

**AI tool responses** (201): \`{ "data": { id, title, tool, credits_used, output, output_format, plain_text, input, model, created_at }, "credits_balance": 88 }\`. For JSON tools \`output\` is an object; for text tools it is a string.

## Endpoints

${section('Public', P)}
${section('Signed-in user', U)}
${section('Admin (role admin or super_admin, enforced server-side)', A)}

Rate limits: \`register\`/\`login\` 10/min, \`forgot-password\`/\`reset-password\`/\`contact\` 5/min, AI endpoints 20/min per user.

A ready-to-import **Postman collection** is in \`docs/NexMind-AI.postman_collection.json\`.
`
writeFileSync(join(root, 'docs/API_DOCUMENTATION.md'), md)

// ---- Postman collection -------------------------------------------------------------
const item = ([method, path, access, desc, body]) => ({
  name: `${method} ${path}`,
  request: {
    method,
    header: [{ key: 'Accept', value: 'application/json' }, { key: 'X-XSRF-TOKEN', value: '{{xsrf}}' }, ...(body ? [{ key: 'Content-Type', value: 'application/json' }] : [])],
    url: { raw: `{{baseUrl}}/api/v1/${path}`, host: ['{{baseUrl}}'], path: ['api', 'v1', ...path.split('/')] },
    description: `${desc}\n\nAccess: ${label[access]}`,
    ...(body ? { body: { mode: 'raw', raw: JSON.stringify(body, null, 2) } } : {}),
  },
})
const folder = (name, kind) => ({ name, item: endpoints.filter((e) => e[2] === kind).map(item) })
const collection = {
  info: { name: 'NexMind AI', schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json', description: 'Run "Get CSRF cookie" first, then copy the XSRF-TOKEN cookie value into the {{xsrf}} variable (URL-decoded). Cookies are handled by Postman automatically.' },
  variable: [{ key: 'baseUrl', value: 'http://localhost:8000' }, { key: 'xsrf', value: '' }],
  item: [
    { name: '0. Get CSRF cookie', request: { method: 'GET', header: [{ key: 'Accept', value: 'application/json' }], url: { raw: '{{baseUrl}}/sanctum/csrf-cookie', host: ['{{baseUrl}}'], path: ['sanctum', 'csrf-cookie'] } } },
    folder('Public & auth', P), folder('User', U), folder('Admin', A),
  ],
}
writeFileSync(join(root, 'docs/NexMind-AI.postman_collection.json'), JSON.stringify(collection, null, 2))
console.log('Wrote docs/API_DOCUMENTATION.md and docs/NexMind-AI.postman_collection.json')
