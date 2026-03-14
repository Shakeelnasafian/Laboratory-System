# Laboratory Information System (LIS)

A multi-tenant Laboratory Information System for diagnostic labs in Pakistan. It covers patient registration, order intake, sample workflow, worklists, results, billing, report release, and PDF report printing.

## Tech Stack

- Backend: Laravel 12, PHP 8.2+
- Frontend: Livewire 4, Alpine.js (via Livewire), Tailwind CSS
- Database: MySQL
- Packages: Spatie Laravel Permission, barryvdh/laravel-dompdf, Laravel Breeze

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

## Setup

### Requirements

- PHP 8.2+
- MySQL
- Node.js and npm
- Composer

### Installation

```bash
git clone <repo-url>
cd Laboratory

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configure your `.env`:

```env
DB_DATABASE=lab_system
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations and seed the full demo dataset:

```bash
php artisan migrate --seed
```

Build assets:

```bash
npm run build
```

Start the server:

```bash
php artisan serve
```

### Development

```bash
npm run dev
# In a separate terminal:
php artisan serve
```

## Seeders

`DatabaseSeeder` now runs:

- `RolesAndPermissionsSeeder`
- `DemoLabsSeeder`
- `DemoLabShowcaseSeeder`

### What gets seeded

- 1 super admin account
- 2 demo labs:
  - City Diagnostic Lab
  - Prime Care Laboratory
- 4 staff accounts per demo lab:
  - `lab_admin`
  - `lab_incharge`
  - `receptionist`
  - `technician`
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

## Demo Showcase Notes

After seeding, each demo lab dashboard includes working queue examples for:

- pending collection
- rejected sample and recollection
- collected and received samples
- worklist-ready and in-processing items
- draft, verified, and released results
- paid, partial, and unpaid invoices

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

Seeder coverage is included in:

- `tests/Feature/DemoSeedersTest.php`

Run the seeder tests with:

```bash
php artisan test tests/Feature/DemoSeedersTest.php
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
  seeders/DatabaseSeeder.php              Main seeder entrypoint
  seeders/DemoLabsSeeder.php              Demo labs and staff
  seeders/DemoLabShowcaseSeeder.php       Demo patients, tests, orders, invoices, results
resources/
  views/layouts/                          Admin and lab shells
  views/livewire/                         Livewire views
  views/reports/order.blade.php           PDF report template
routes/web.php                            Application routes
tests/
  Feature/DemoSeedersTest.php             Seeder coverage
```

## License

MIT
