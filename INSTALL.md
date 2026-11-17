# NexMind AI — Install (single folder)

Everything is in this one Laravel 12 project. Needs: PHP 8.2+ (extensions: mbstring, xml, zip, curl, fileinfo, pdo_mysql, openssl), Composer 2, MySQL 8, Node 20+ (only to edit the Vue code).
In Laragon enable the `zip` PHP extension (Menu → PHP → Extensions → zip) — DOCX summarizing needs it.

## Steps (PowerShell, inside this folder)

```powershell
composer install
copy .env.example .env
php artisan key:generate
```
Create an empty database `nexmind` (HeidiSQL in Laragon), then edit `.env`:

```
DB_DATABASE=nexmind
DB_USERNAME=root
DB_PASSWORD=
OPENAI_API_KEY=your-key
SEED_ADMIN_PASSWORD=choose-a-strong-password
SEED_DEMO_PASSWORD=choose-another
```
Then:
```powershell
php artisan migrate --seed
php artisan storage:link
php artisan test          # optional: run the backend tests
php artisan serve
```
Open http://localhost:8000 — the site and the API share this one address, so login cookies just work.
Admin login: `admin@nexmind.test` + the SEED_ADMIN_PASSWORD you set.

## Google sign-in (optional)

In Google Cloud Console, create an OAuth 2.0 Web application client and add the exact callback URL under **Authorized redirect URIs**. For a local Laragon setup, the callback is `http://localhost:8000/auth/google/callback`. Put the client ID and secret in `.env` as `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`; set `GOOGLE_REDIRECT_URI` to the same callback URL. Keep the client secret server-side and never add it to a `VITE_*` setting.

`public/build` already contains the compiled frontend. To change Vue code:
```powershell
npm install
npm run dev      # live reload (keep running next to php artisan serve)
npm run build    # compile again for production
```
Optional background work: `php artisan queue:work` and `php artisan schedule:work` (monthly credit renewal, document cleanup).

## Production
`APP_ENV=production`, `APP_DEBUG=false`, HTTPS, `SESSION_SECURE_COOKIE=true`, `SANCTUM_STATEFUL_DOMAINS=your-domain`, `FRONTEND_URL=https://your-domain`, `APP_URL` likewise, real SMTP `MAIL_*`, `php artisan config:cache route:cache`, queue worker under Supervisor, cron `* * * * * php artisan schedule:run`.

## Not included
No payment gateway (Pro/Business plans are placeholders) and no social auto-publishing.
