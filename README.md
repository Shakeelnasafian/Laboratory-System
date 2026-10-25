# Laboratory Information System (LIS)

A multi-tenant Laboratory Information System for diagnostic labs in Pakistan. It covers patient registration, order intake, sample workflow, worklists, results, billing, report release, and PDF report printing.

**Repository:** [github.com/Shakeelnasafian/laboratory-system](https://github.com/Shakeelnasafian/laboratory-system)

## Live Demo

**🔗 [laboratory-system-ruiv.onrender.com](https://laboratory-system-ruiv.onrender.com/)**

Hosted on Render (Docker) with a Neon PostgreSQL database. Sign in with any account from [Default Credentials](#default-credentials) — e.g. Super Admin `admin@labsystem.pk` / `admin@12345`.

> Note: the instance may cold-start (Render free tier autosuspend + Neon scale-to-zero), so the first request after idle can take a few seconds.

## Tech Stack

- Backend: Laravel 12, PHP 8.3 (FrankenPHP)
- Frontend: Livewire 4, Alpine.js (via Livewire), Tailwind CSS (built with Vite)
- Database: PostgreSQL (Neon in production)
- Packaging: one self-contained Docker image
- Packages: Spatie Laravel Permission, barryvdh/laravel-dompdf, Laravel Breeze

## Quick Start (Docker)

The whole app ships as **one image**. Composer packages, the Node/Vite asset build, and the PHP extensions (Postgres, GD, intl, …) are all baked in at build time. On start the container sets itself up and runs migrations — you only provide the database.

```bash
git clone https://github.com/Shakeelnasafian/laboratory-system.git
cd laboratory-system

# Local try-out: app + throwaway Postgres, with demo data
RUN_SEEDERS=true docker compose up --build
# open http://localhost:8080   (health check: /up)
```

Sign in with the [Default Credentials](#default-credentials). Drop `RUN_SEEDERS=true` for an empty database (only roles/migrations).
If port 8080 is taken: `APP_PORT=8099 docker compose up --build`.

### Run the image against your own database

```bash
docker build -t laboratory .

docker run -d --name laboratory -p 8080:8080 \
  -e APP_URL=https://lab.example.com \
  -e DB_URL='postgresql://USER:PASSWORD@HOST/DBNAME' \
  -e APP_KEY='base64:...' \
  laboratory
```

### What happens on container start

`docker/entrypoint.sh` runs, in order:

1. **`.env`** is created from the non-secret defaults baked into the image (`docker/env.defaults`: production mode, stderr logging, `database` session/cache/queue drivers, …).
2. **`APP_KEY`** — if you did not pass one, a new key is generated. Real environment variables you pass with `-e` / compose always override `.env`.
3. **Migrations** — waits for the database (up to 60 s by default) and runs `php artisan migrate --force`.
4. **Seeding** (optional) — only if `RUN_SEEDERS=true`.
5. **Caches** — config, routes, views, events are cached; `storage:link` is created.
6. **Supervisor** starts the web server (FrankenPHP), the queue worker, and the scheduler.

> **Keep `APP_KEY` stable.** A generated key lives only inside that container. If the container is recreated, sessions and any encrypted data tied to the old key are lost. For anything beyond a trial, generate one once and pass it in:
> `docker run --rm laboratory php artisan key:generate --show`

### Configuration

Provide these at run time (env vars, compose `environment`, or your platform's dashboard):

| Variable | Required | Notes |
| --- | --- | --- |
| `DB_URL` | yes* | Full connection string, e.g. Neon **pooled** URL (host contains `-pooler`). *Or use `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| `APP_URL` | recommended | Public URL of the app |
| `APP_KEY` | recommended | Auto-generated if missing (see warning above) |
| `DB_SSLMODE` | no | Defaults to `require` (Neon). Use `disable` for a plain local Postgres |
| `RUN_MIGRATIONS` | no | `true` (default). Set `false` to skip migrations on start |
| `RUN_SEEDERS` | no | `false` (default). `true` seeds the demo dataset |
| `DB_WAIT_RETRIES` | no | Attempts, 2 s apart, to reach the DB before failing (default `30`) |
| `PORT` | no | Port the web server listens on (default `8080`) |
| `MAIL_*`, `AWS_*`, … | no | Any other Laravel env var works the same way |

Anything not listed falls back to `docker/env.defaults`.

### Image layout

| File | Purpose |
| --- | --- |
| `Dockerfile` | Multi-stage build: Vite assets → Composer (no-dev) → FrankenPHP runtime |
| `docker/entrypoint.sh` | Start-up bootstrap: `.env`, app key, migrations, caches |
| `docker/env.defaults` | Non-secret defaults copied to `.env` on first start |
| `docker/Caddyfile` | FrankenPHP config; binds to `$PORT` |
| `docker/supervisord.conf` | Runs web + `queue:work` + `schedule:work` |
| `docker/php.ini` | Production PHP + OPcache settings |
| `docker-compose.yml` | Local run: the same image + a throwaway Postgres |
| `.env.docker` | Local compose settings (no real secrets) |
| `render.yaml` | Render blueprint (service, env vars, health check `/up`) |

## Deploy on Render + Neon

The live demo runs this same image on [Render](https://render.com) with a [Neon](https://neon.tech) database. Create a Web Service from the repo (Docker runtime; `render.yaml` is picked up as a Blueprint) and set:

| Key | Value |
| --- | --- |
| `APP_KEY` | output of `php artisan key:generate --show` |
| `APP_URL` | `https://<your-service>.onrender.com` |
| `DB_URL` | Neon pooled connection string |

Migrations run automatically on every deploy via the entrypoint — no pre-deploy hook is needed, so this also works on the free plan.

Each migration sets `public $withinTransaction = false;` so statements auto-commit individually instead of being wrapped in one transaction. This avoids the `SQLSTATE[25P02]` failure that occurs when multi-statement DDL transactions run through Neon's pooled (PgBouncer) endpoint.

## Local Development (without Docker)

Requirements: PHP 8.2+, Composer, Node.js + npm, and a database (SQLite, MySQL, or Postgres).

```bash
git clone https://github.com/Shakeelnasafian/laboratory-system.git
cd laboratory-system

composer install
npm install

cp .env.example .env
php artisan key:generate
# edit DB_* in .env, then:
php artisan migrate --seed

npm run dev          # terminal 1 (or `npm run build` once)
php artisan serve    # terminal 2
```

## Features

### Super Admin

- Manage multiple labs
- Activate or deactivate labs
- Review the in-app changelog page

### Lab Admin

- Manage staff users and assign roles
- Manage test categories and test catalog
- Update lab settings and report branding
- Review changelog entries from inside the lab shell

### Lab Operations

- Patients: register, search, edit, and view history
- Orders: create orders, add tests, take payments, and print reports
- Samples: collection queue, receive queue, rejected/recollect queue
- Worklists: assign items, start work, and track due times
- Results: enter, draft, verify, and release results
- Billing: invoices, paid totals, balance, and outstanding amounts
- Dashboard: queue counts, revenue summary, and recent orders

## Roles

| Role | Access |
| --- | --- |
| `superadmin` | Admin panel across all labs |
| `lab_admin` | Full lab access including settings and staff |
| `lab_incharge` | Patients, orders, samples, worklists, results, billing |
| `receptionist` | Patients, orders, samples, billing |
| `technician` | Sample receive, worklists, results |

## Seeders

`DatabaseSeeder` runs:

- `RolesAndPermissionsSeeder`
- `DemoLabsSeeder`
- `DemoLabShowcaseSeeder`

In Docker, enable with `RUN_SEEDERS=true`; locally use `php artisan migrate --seed`.

### What gets seeded

- 1 super admin account
- 2 demo labs: City Diagnostic Lab, Prime Care Laboratory
- 4 staff accounts per demo lab: `lab_admin`, `lab_incharge`, `receptionist`, `technician`
- Per demo lab showcase data:
  - 10 patients
  - 6 test categories
  - 10 tests
  - 8 orders
  - 8 invoices
  - workflow coverage for pending collection, rejected sample, collected, received/unassigned, processing, draft result, verified result, and released result

The demo seeders are idempotent. Running them again updates the same demo records instead of creating duplicates.

## Default Credentials

### Super Admin

| Role | Email | Password |
| --- | --- | --- |
| Super Admin | `admin@labsystem.pk` | `admin@12345` |

### Demo Lab Accounts

| Lab | Role | Email | Password |
| --- | --- | --- | --- |
| City Diagnostic Lab | Lab Admin | `admin.city@labsystem.demo` | `password123` |
| City Diagnostic Lab | Lab Incharge | `incharge.city@labsystem.demo` | `password123` |
| City Diagnostic Lab | Receptionist | `reception.city@labsystem.demo` | `password123` |
| City Diagnostic Lab | Technician | `tech.city@labsystem.demo` | `password123` |
| Prime Care Laboratory | Lab Admin | `admin.prime@labsystem.demo` | `password123` |
| Prime Care Laboratory | Lab Incharge | `incharge.prime@labsystem.demo` | `password123` |
| Prime Care Laboratory | Receptionist | `reception.prime@labsystem.demo` | `password123` |
| Prime Care Laboratory | Technician | `tech.prime@labsystem.demo` | `password123` |

> Change these passwords before exposing any real deployment.

## URL Structure

```text
/login                          Login page
/admin/dashboard                Super admin dashboard
/admin/labs                     Labs list
/admin/labs/create              Create new lab
/admin/changelog                Application changelog

/lab/dashboard                  Lab dashboard
/lab/patients                   Patient list
/lab/patients/create            Register patient
/lab/orders                     Orders list
/lab/orders/create              New order
/lab/orders/{order}             Order detail
/lab/orders/{order}/report      PDF report
/lab/samples                    Collection queue
/lab/samples/receive            Receive queue
/lab/samples/rejected           Recollect queue
/lab/worklists                  Bench worklists
/lab/results                    Result entry
/lab/results/release            Release queue
/lab/invoices                   Billing and invoices
/lab/changelog                  Application changelog
/lab/test-categories            Test categories
/lab/tests                      Test catalog
/lab/users                      Staff management
/lab/settings                   Lab settings
```

## Multi-Tenancy

This project uses login-based multi-tenancy. Most lab-owned models carry a `lab_id`, and the `BelongsToLab` trait applies an automatic Eloquent scope for authenticated lab users so one lab cannot access another lab's records.

## Tests

```bash
php artisan test
php artisan test tests/Feature/DemoSeedersTest.php   # seeder coverage only
```

## Project Structure

```text
app/
  Http/Controllers/ReportController.php   PDF generation
  Livewire/Admin/                         Super admin components
  Livewire/Lab/                           Lab user components
  Models/                                 Eloquent models
  Services/LabWorkflowService.php         Workflow transitions
  Traits/BelongsToLab.php                 Multi-tenancy scope trait
database/
  migrations/                             Table migrations
  seeders/                                Roles, demo labs, showcase data
docker/                                   Entrypoint, env defaults, Caddy, Supervisor, php.ini
resources/
  views/layouts/                          Admin and lab shells
  views/livewire/                         Livewire views
  views/reports/order.blade.php           PDF report template
routes/web.php                            Application routes
Dockerfile                                Single production image
docker-compose.yml                        Local app + Postgres
```

## License

MIT
