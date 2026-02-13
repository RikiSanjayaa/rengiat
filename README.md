# Rengiat

Internal web app for logging daily activities per unit and generating official PDF reports by date range.

## Stack

- Laravel 12 (compatible with Laravel 11 architecture expectations)
- Inertia.js + React + Vite
- SQLite
- shadcn-style UI components
- PDF: Browsershot (preferred) with dompdf fallback

## Core Features

- Daily entry logging per unit (no stored report entity)
- Role-based access:
  - `super_admin` / `admin`: manage users + units, full entry access, export PDF
  - `operator`: CRUD entries for own subdit only (all data remains readable for all roles)
  - `viewer`: read-only + export PDF
- Report generator with:
  - Start date + optional end date
  - Optional unit filter (grouped by subdit)
  - Optional keyword filter in `description` (uraian)
  - Multi-day paper preview grouped by subdit + unit
- PDF export:
  - A4 landscape
  - Dynamic title/header
  - Subdit column shown at left and grouped rows per day
- Audit logs for entry create/update/delete (`audit_logs`)
- Optional image attachments (feature-flagged)

## Quick Start

1. Install dependencies:

```bash
composer install
npm install
```

2. Prepare environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Create SQLite file:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
```

4. Run migrations + seed demo data:

```bash
php artisan migrate --seed
```

5. Create public storage symlink (for attachment thumbnails):

```bash
php artisan storage:link
```

6. Generate Wayfinder files:

```bash
php artisan wayfinder:generate --with-form
```

7. Run app:

```bash
php artisan serve
npm run dev
```

## Demo Accounts

All demo users use password: `password`

- `superadmin` (`super_admin`)
- `admin` (`admin`)
- `viewer` (`viewer`)
- `operator_subdit1` (`operator` untuk `Subdit 1 Perempuan`)
- `operator_subdit2` (`operator` untuk `Subdit 2 Anak`)
- `operator_subdit3` (`operator` untuk `Subdit 3 TTPO`)

## PDF Engine Notes

### Preferred: Browsershot

Browsershot requires:

- Node.js installed
- Chromium/Chrome available on server

Local install example:

```bash
npx playwright install chromium
```

If Chromium is not available, export automatically falls back to dompdf.

### Fallback: dompdf

Installed and enabled by default in this project. Used automatically when Browsershot fails.

## Attachments Feature Flag

Set in `.env`:

```env
ENABLE_ATTACHMENTS=false
```

When enabled:

- Accepts `jpg/jpeg/png/webp`, max 5 MB
- Image is resized to max width 1600px and compressed
- UI displays thumbnails in daily input page
- PDF only shows `[LAMPIRAN]` marker (no embedded image)

## Tests

Run full suite:

```bash
php artisan test
```

Targeted Rengiat authorization/audit test:

```bash
php artisan test tests/Feature/RengiatAuthorizationTest.php
```
