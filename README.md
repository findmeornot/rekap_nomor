# Rekap Nomor

A role-based Laravel application to collect, manage, and monitor contact numbers from multiple teams (`sub_leader -> leader -> superadmin`) with import/export support.

## Why This Project Exists
`Rekap Nomor` is built for operational teams that need a single source of truth for contact collection. The app helps:
- keep phone numbers unique globally,
- track which contacts are already reached,
- provide visibility per leader/sub leader,
- speed up data entry via bulk import.

## Core Features
- Authentication (Laravel Breeze)
- Role-based authorization (`superadmin`, `leader`, `sub_leader`)
- Sub leader contact input:
- manual (single/multiple numbers)
- bulk file import (`csv`, `txt`, `xlsx`, `xls`)
- Leader monitoring dashboard:
- filter by period (`all`, `7d`, `30d`, `custom`)
- search + status filter (`contacted` / `uncontacted`)
- pagination controls
- CSV export
- one-click WhatsApp redirect + auto mark as contacted
- Superadmin control center:
- create leader/sub leader accounts
- assign or reassign sub leader to leader
- global contact recap + filtering

## Tech Stack
- Backend: Laravel 13, PHP 8.3+
- Frontend: Blade, Tailwind CSS, Vite, Alpine.js
- Database: MySQL/MariaDB (default Laravel-compatible RDBMS)
- File processing: `phpoffice/phpspreadsheet`

## Roles and Access Matrix
| Role | Main Capabilities |
|---|---|
| `superadmin` | Manage users (leader/sub leader), assign leader, view all contacts recap |
| `leader` | View contacts under own hierarchy, apply filters, export CSV, open WhatsApp and mark contacted |
| `sub_leader` | Input contacts (manual/import) tied to assigned leader |

## End-to-End Flow
1. Superadmin creates `leader` and `sub_leader` accounts.
2. Superadmin assigns each `sub_leader` to one `leader`.
3. Sub leader uploads/inputs contacts.
4. System normalizes phone values (non-digit stripped), validates, and skips duplicates.
5. Leader reviews data, contacts people via WhatsApp route, and status is recorded.
6. Superadmin reviews aggregate metrics across all leaders.

## Database Overview
Detailed schema: [docs/SCHEMA.md](docs/SCHEMA.md)

High-level tables:
- `users`: stores account + role + parent leader relation.
- `contacts`: stores normalized phone, optional name, owner relation, and contacted metadata.

## Route Overview
- `/dashboard` -> role-aware dashboard summary
- `/superadmin/users` -> user management
- `/superadmin/contacts` -> global contacts recap
- `/leader/contacts` -> leader contact list
- `/leader/contacts/export` -> CSV export
- `/leader/contacts/{contact}/whatsapp` -> redirect + mark contacted
- `/sub-leader/contacts` -> sub leader input list/form
- `/sub-leader/contacts/import` -> bulk import

## Local Development Setup
### 1. Clone repository
```bash
git clone https://github.com/Revaldoo24/rekap_nomor.git
cd rekap_nomor
```

### 2. Install dependencies
```bash
composer install
npm install
```

### 3. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

Set at least these values in `.env`:
- `APP_NAME`
- `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

### 4. Run migration
```bash
php artisan migrate
```

### 5. Run app
```bash
composer run dev
```
This runs web server, queue listener, log tailing, and Vite concurrently.

## Useful Commands
```bash
# run tests
composer test

# code style (if needed)
./vendor/bin/pint

# production frontend build
npm run build
```

## Import Rules (Important)
- Max upload size: `5 MB`
- Allowed file types: `csv`, `txt`, `xlsx`, `xls`
- Header aliases supported:
- Phone: `phone`, `nomor`, `no hp`, `nohp`, `number`
- Name: `name`, `nama`, `contact_name`, `kontak`
- Phone numbers are normalized to digits only.
- Duplicate phone numbers are skipped globally.

## Contact Status Logic
- A contact is considered **contacted** when leader opens WhatsApp endpoint:
- `contacted_at` is set once.
- `contacted_by_leader_id` is recorded.
- Subsequent opens do not overwrite first contact timestamp.

## Project Structure (Key Areas)
- `app/Http/Controllers/SuperAdmin/UserManagementController.php`
- `app/Http/Controllers/Leader/ContactController.php`
- `app/Http/Controllers/SubLeader/ContactController.php`
- `app/Http/Middleware/EnsureRole.php`
- `app/Models/User.php`
- `app/Models/Contact.php`
- `routes/web.php`
- `resources/views/superadmin/*`
- `resources/views/leader/*`
- `resources/views/subleader/*`

## Conventions For Next Developers
- Keep role checks centralized via middleware (`role:*` route middleware).
- Preserve global uniqueness of `contacts.phone`.
- Any new contact ingestion path must reuse normalization/dedup rules.
- Keep list filters consistent between Leader and Superadmin (date/status/search/per_page).
- Add feature tests for every new business rule touching import/export/status transitions.

## Suggested Backlog
- Add seeders for demo roles/users.
- Add dedicated service classes for import and filters (reduce controller size).
- Add audit trail for create/update/delete contact events.
- Add policy classes for fine-grained authorization.
- Add API layer if mobile integration is planned.

## Deployment Notes
Minimal checklist:
1. Set production `.env` values (`APP_ENV=production`, DB, cache, queue).
2. Run `composer install --no-dev --optimize-autoloader`.
3. Run `php artisan migrate --force`.
4. Run `npm ci && npm run build`.
5. Configure queue worker process for background jobs.

## License
Internal project. Define organizational license/policy before external distribution.
