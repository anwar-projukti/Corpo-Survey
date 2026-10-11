# Corpo Survey Management

A multi-tenant survey platform: a dynamic, schema-driven survey builder, a Typeform-style public respondent experience, door-to-door field collection, response management with a full audit trail, analytics and custom reports, Excel import/export, a REST API, and bilingual (English + বাংলা) support with light/dark theming.

Built on Laravel 12, Alpine.js, and Tailwind CSS — see the full build brief for the product spec this implements.

## Requirements

- PHP 8.3+
- MySQL 8
- Redis (cache, queue, sessions)
- Node.js 20+ (asset build)
- Composer

## Local setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# Point DB_* / REDIS_* in .env at your local MySQL and Redis, then:
php artisan migrate --seed

npm run build   # or `npm run dev` for a live-reloading dev server
php artisan serve
```

A queue worker is required for exports, imports, and other queued jobs:

```bash
php artisan queue:work
```

The scheduler (nightly user-stats refresh and data-retention purge, see `routes/console.php`) needs a cron entry pointed at `php artisan schedule:run` every minute in any real environment.

## Testing

```bash
php artisan test    # Pest, in-memory SQLite — no local DB/Redis needed
composer audit       # dependency vulnerability scan
npm audit --audit-level=high
```

CI (`.github/workflows/ci.yml`) runs the full backend and frontend test/lint/audit suite on every push and pull request.

## Deployment / production readiness

Before pointing a production environment at a fresh deploy:

1. **Environment** — start from `.env.production.example` (or `.env.staging.example`), never `.env.example`. Confirm `APP_DEBUG=false`, `APP_ENV=production`, `TWO_FACTOR_ENFORCED=true`, and that every secret (`APP_KEY`, DB/Redis credentials, mail, CAPTCHA, AWS keys) is injected from a real secrets manager at deploy time, not committed to the file.
2. **Cache the framework** — run after every deploy, before serving traffic:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan event:cache
   ```
   (`php artisan optimize` runs the config/route/view steps in one command.) Skipping this means every request re-parses config and re-resolves routes from scratch.
3. **Migrate**: `php artisan migrate --force`.
4. **Queue workers** — run `php artisan queue:work` under a process supervisor (systemd/Supervisor), not `php artisan serve`'s inline sync driver. Exports, imports, and the nightly stats/retention jobs all depend on a real worker.
5. **Scheduler** — a cron entry (`* * * * * php artisan schedule:run`) is required for the nightly `RefreshUserSurveyStatsJob` and `PurgeExpiredDataJob` (see `routes/console.php`) to ever fire.
6. **Static assets** — `public/build/*` (Vite's fingerprinted output) is served directly by the web server, not through PHP. Configure the web server (nginx/Apache) to send a long-lived, immutable `Cache-Control` header for that path, e.g. for nginx:
   ```nginx
   location /build/ {
       expires 1y;
       add_header Cache-Control "public, immutable";
   }
   ```
   Fingerprinted filenames make this safe — a new deploy produces new filenames, so there's no stale-cache risk.
7. **TLS termination** — the app trusts every proxy in front of it (`bootstrap/app.php`'s `trustProxies(at: '*')`) so `X-Forwarded-Proto`/`X-Forwarded-For` are honored — this assumes the app is never reachable directly from the internet, only through your load balancer/reverse proxy. If that's not true in your topology, narrow this to the proxy's actual CIDR.
8. **Reverse proxy / CDN** should terminate TLS and redirect HTTP → HTTPS; the app itself enforces `https://` URL generation in `production`/`staging` environments (`AppServiceProvider::boot()`) and sends HSTS whenever a request actually arrives over HTTPS (`app/Http/Middleware/SecurityHeaders.php`).

See `docs/api` (Scramble-generated OpenAPI docs, linked from the dashboard's API settings) for the REST API reference.
