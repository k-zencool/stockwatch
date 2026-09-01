# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

K-Project ("ระบบติดตามการเบิกของ") is a Thai-language internal tool for logging incidents where an
employee needed a supply/tool but didn't requisition it properly. Plain PHP 8.2 + MySQL 8, no framework,
no Composer dependencies, no build step, no test suite.

## Running it

```
docker compose up -d --build   # first run also builds the web image
```

- App: http://localhost:8080 (Apache document root is `public/`, set via `APACHE_DOCUMENT_ROOT` in `php/Dockerfile`)
- phpMyAdmin: http://localhost:8081 (root / rootpassword)
- MySQL exposed on host port 3307 (mapped from container 3306)

The whole repo root is bind-mounted into the container at `/var/www/html`, so edits to `public/` or `src/`
are picked up immediately — no rebuild needed unless `php/Dockerfile` changes.

First run: visiting the app with an empty `users` table redirects to `public/setup.php` to create the
first admin account; after that it redirects to `login.php`. This gate is just `SELECT COUNT(*) FROM users`.

There is no linter, formatter, or test runner configured — verify changes by running the app and clicking
through the flow, or with `php -l <file>` for a syntax check.

## Database

`db/init.sql` is the full current schema (mounted read-only into
`/docker-entrypoint-initdb.d/init.sql`), so it **only runs automatically the first time the `db_data`
volume is created**. `db/migration_002_items_external.sql`, `db/migration_003_reasons.sql`, and
`db/fix_items_encoding.sql` are historical migrations already folded into `init.sql` — they're kept for
reference, not auto-applied. If you change the schema, update `init.sql` directly (fresh volumes) and add
a new migration file for anyone running against an existing `db_data` volume (apply manually via
phpMyAdmin or `docker exec -i kproject_db mysql -uroot -prootpassword stock_tracker < db/your_migration.sql`).

Core tables: `users` (admin/manager login), `employees`, `item_categories`, `items`, `reasons`,
`incidents`. An `incidents` row is tied to *either* an internal `employee_id` *or* a free-text
`external_name`/`external_department` pair (for people outside the tracked employee list) — never both.

## Architecture

There's no MVC framework, router, or ORM. Each file in `public/` is both the controller and the view for
one page, following this shape:

```php
require_once __DIR__ . '/../src/auth.php';
require_login();
// handle POST actions inline, branching on $_POST['action'], then redirect() back to self (PRG pattern)
// run the SELECT queries needed for the page directly with PDO, inline SQL, prepared statements
$pageTitle = '...'; $activeNav = '...';
require __DIR__ . '/../src/views/header.php';
// page HTML with inline PHP
require __DIR__ . '/../src/views/footer.php';
```

`src/` holds only shared bootstrap, no business-logic layer:
- `config.php` — opens the PDO connection (reads `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS` env vars, set in
  `docker-compose.yml`), exceptions on error
- `session.php` — starts the PHP session
- `auth.php` — `current_user()`, `require_login()`, `login_user()`, `logout_user()` (session-based, no
  tokens/JWT)
- `helpers.php` — `e()` (htmlspecialchars wrapper, use for **all** output), `redirect()`, `flash()`
  (session-backed one-shot flash messages), `thai_date()` (Gregorian → Buddhist Era display), `icon()`
  (inline Feather-style SVG icon set, referenced by name string)
- `views/header.php` / `views/footer.php` — the only shared templates: sidebar nav, topbar, flash message
  rendering, bottom mobile nav. Every other page's markup lives directly in its `public/*.php` file.

Frontend has no bundler: `public/assets/css/style.css`, `public/assets/js/app.js`, Chart.js loaded from
jsdelivr CDN in `header.php` for the dashboard/report charts. Dark/light theme is a `data-theme` attribute
driven by `localStorage('kp_theme')`, applied via a small inline script duplicated at the top of every
page's `<head>` (before CSS loads, to avoid a flash of the wrong theme).

All UI text and seed data is Thai. When adding user-facing strings, match the existing Thai copy rather
than introducing English.
