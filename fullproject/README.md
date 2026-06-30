# WhatsApp CRM Platform — Full Project Code (All 5 Milestones)

Hand-written Laravel 11 application code covering the full commercial scope. Composer
packages must be installed on your own machine/server (this sandbox can't reach
packagist.org), so treat this as a complete, structured codebase to drop into a fresh
Laravel install, not a pre-built running app.

## Setup

1. `composer create-project laravel/laravel crm-platform` (Laravel 11)
2. Copy this package's `app/`, `database/`, `resources/`, `routes/`, `config/` contents
   into the new project, merging folders.
3. Required composer packages beyond the default Laravel install:
   ```
   composer require barryvdh/laravel-dompdf maatwebsite/excel guzzlehttp/guzzle
   ```
4. Merge `config/services_additions.php` into your `config/services.php`.
5. Append the contents of `.env.additions.example` into your real `.env` and fill in
   actual credentials (WhatsApp, Anthropic, GHL, ERP, Twilio — see notes below).
6. Register middleware per `MIDDLEWARE_REGISTRATION.php`.
7. Register `app/Console/Commands/RunBackupCommand.php` if not auto-discovered, and
   schedule it as shown in that same file.
8. `php artisan migrate && php artisan db:seed --class=RbacSeeder`
9. Log in at `/login` with `admin@example.com` / `ChangeMe123!` — change immediately.

## Module map

| Milestone | Folder/files |
|---|---|
| M1 Foundation & RBAC | `app/Models/{User,Role,Permission}.php`, `app/Http/Middleware/EnsureUserHas*`, `app/Http/Controllers/{DashboardController,UserManagementController}.php` |
| M2 WhatsApp / Inbox | `app/Services/WhatsApp/*`, `app/Http/Controllers/{InboxController,Api/WhatsAppWebhookController}.php`, `resources/views/inbox/*` |
| M3 CRM/Automation/AI | `app/Models/{Lead,LeadStage,Workflow*,KnowledgeBaseArticle,Appointment}.php`, `app/Services/{AI,Automation,Integrations}/*`, `app/Http/Controllers/{LeadController,AppointmentController}.php` |
| M4 Voice/Reports/Security | `app/Services/Voice/VoiceCallService.php`, `app/Http/Controllers/ReportController.php`, `app/Exports/LeadsExport.php`, `app/Http/Middleware/SecurityHeaders.php`, `app/Console/Commands/RunBackupCommand.php` |
| M5 Deployment | See "Deployment checklist" below |

## Bugs found and fixed in this pass

I reviewed the codebase end-to-end against Laravel 11 conventions and fixed these real
issues (none were caught by execution, since this sandbox has no PHP — they're fixed
based on careful manual review against Laravel 11's actual API surface):

1. **Fatal boot error**: `bootstrap/app.php` called `->withSchedule()`, which is not a
   real method on Laravel 11's `Application::configure()` builder. This would have
   thrown a fatal error on every single request. Fixed by moving the backup schedule
   into `routes/console.php` using the `Schedule` facade — the correct Laravel 11 pattern.
2. **Missing queue/cache tables**: `.env.example` sets `QUEUE_CONNECTION=database` and
   `CACHE_STORE=database`, and `docker-compose.yml` runs a queue worker — but no
   migration created the `jobs`, `cache`, `cache_locks`, `job_batches`, or `failed_jobs`
   tables. Added `0000_01_01_000000_create_cache_and_queue_tables.php`.
3. **Missing service provider registration**: Laravel 11 requires `bootstrap/providers.php`
   to boot at all. It didn't exist. Added it along with a basic `AppServiceProvider`.
4. **Docker networking bug**: `.env.example` had `DB_HOST=127.0.0.1`, which inside Docker's
   network resolves to the app container itself, not the `db` container — this would
   make the Docker deployment path fail to connect to MySQL. Fixed with `DB_HOST=db`
   and an inline comment explaining the Docker-vs-bare-metal difference.
5. **Code cleanliness**: removed a redundant fully-qualified class reference in
   `RunBackupCommand` and stale comments pointing at `app/Console/Kernel.php`, which
   doesn't exist in Laravel 11's slimmed-down skeleton.
6. **Missing explicit dependency**: added `symfony/process` to `composer.json` since
   `RunBackupCommand` uses it directly.

## Things that genuinely need your input before going live

These aren't bugs — they're decisions only the client/you can make, flagged inline in
the code with comments:

- **ERP system**: the spec says "ERP integration" without naming which ERP. `ErpIntegrationService`
  is a generic REST adapter — confirm the actual system (SAP, Odoo, NetSuite, custom) and
  adjust endpoint paths to match its real API docs.
- **GHL auth version**: GoHighLevel has v1 (API key) and v2 (OAuth) APIs with different
  request shapes. `GoHighLevelService` targets v2 — confirm which the client's GHL account uses.
- **Voice provider**: not specified in the contract; `VoiceCallService` targets Twilio's
  REST pattern as the most common choice. Swap if the client uses a different provider.
- **WhatsApp Business verification**: Meta requires business verification and template
  approval before sending template messages or messaging users who haven't messaged you
  first in the last 24h — this is a Meta-side process, not something code can bypass.
- **AI auto-reply**: defaults to OFF (`AI_AUTO_REPLY_ENABLED=false`) so a human always
  triages first conversations until you and the client are confident in its accuracy.
  Turn on per the client's comfort level.

## Deployment (two supported paths)

This package now includes everything needed to actually deploy — not just app code.

### Path A: Docker (recommended)
1. Copy `.env.example` to `.env`, fill in real credentials.
2. `docker compose build`
3. `docker compose up -d`
4. `docker compose exec app php artisan key:generate`
5. `docker compose exec app php artisan migrate --seed`
6. Point your domain's DNS at the server, put real SSL certs in `deploy/ssl/`
   (referenced by `deploy/nginx.conf`), restart the `nginx` container.

### Path B: Bare metal / VPS (no Docker)
1. `composer create-project laravel/laravel crm-platform` then merge this package's
   files in, OR clone this code directly if it's already a git repo with `composer.json`
   at the root (it is, in this package).
2. Copy `.env.example` to `.env`, fill in real credentials.
3. Run `./deploy/deploy.sh` — it installs dependencies, generates the app key,
   runs migrations, and caches config/routes/views in one pass.
4. Install the queue worker via `deploy/supervisor-queue.conf`
   (`supervisorctl reread && supervisorctl update && supervisorctl start crm-queue:*`).
5. Add the cron line from `deploy/crontab.txt` so `php artisan schedule:run`
   (which triggers the nightly backup) actually fires.
6. Point Nginx at `deploy/nginx.conf` (adjust paths/domain), with PHP-FPM running
   on the same host instead of a separate `app` container.

### After either path — always required
- Set the WhatsApp webhook URL in Meta's App Dashboard to
  `https://yourdomain.com/api/webhooks/whatsapp`, using your `WHATSAPP_VERIFY_TOKEN`
  for the handshake.
- Log in with the seeded admin (`admin@example.com` / `ChangeMe123!`) and change
  the password immediately — `/admin/users` lets you create real accounts after.
- Run a real send/receive test with WhatsApp before considering Milestone 2 "live."

## Deployment checklist (Milestone 5, expanded)


- Set `APP_ENV=production`, `APP_DEBUG=false`
- Run `php artisan config:cache route:cache view:cache`
- Set up HTTPS (required by Meta for the WhatsApp webhook URL)
- Point the WhatsApp webhook URL at `https://yourdomain.com/api/webhooks/whatsapp`
  in Meta's App Dashboard, using `WHATSAPP_VERIFY_TOKEN` for the handshake
- Schedule `backup:run` and a queue worker (`php artisan queue:work`) via Supervisor/systemd
- Hand over `.env` credentials securely (not via plain chat/email)

## Honesty note

This code has not been executed against a live PHP/MySQL environment, Docker, or real
WhatsApp/Anthropic/GHL/Twilio credentials — this sandbox has no PHP, no Composer, and no
access to packagist.org, so I could not run `composer install`, lint the PHP, execute
migrations, build the Docker image, or smoke-test any route. Everything here — the app
code, `composer.json`, `bootstrap/app.php`, Docker/Nginx/Supervisor configs, and
`deploy.sh` — is written to be structurally correct and to follow Laravel/Docker
conventions closely, but it has not been verified by actually running it.

Before calling this "deployed," budget real time for:
1. A first run on a staging server (or `docker compose up` locally) to catch any
   typos or missing-import errors that are inevitable in code this size when unrun.
2. Live WhatsApp Cloud API testing — Meta requires business verification and template
   approval, which is a manual process on their side, not something this code can do.
3. The Milestone 4 UAT phase your contract already allocates for exactly this kind
   of verification.

I'm flagging this clearly rather than implying "ready for deployment" means "tested and
working" — it means the deployment *scaffolding* is complete; the verification step is
still yours to run.
