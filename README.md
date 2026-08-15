# Expenzo - Pure PHP + MySQL Expense Tracker

Production-oriented debit/expense tracker built without Laravel or another PHP framework. AdminKit remains the visual foundation.

## Requirements
- PHP 8.2+
- MySQL 8+ / MariaDB 10.6+
- Apache with mod_rewrite or Nginx
- PHP PDO, JSON and Fileinfo extensions

## Installation
1. Copy `.env.example` to `.env` and set database credentials.
2. Run `database/migrations/001_initial.sql` against MySQL. This is the complete fresh-install schema.
3. Run `database/seeders/001_seed.sql`. This is the complete fresh-install seed.
4. Point the web server document root to `public/`.
5. Ensure `storage/` is writable by PHP. Receipts are stored in `storage/receipts/` outside the public web root.
6. Visit `/login`.

## Seed Credentials
All seeded accounts use password `ChangeMe123!` for local development only.

- Super Admin: `admin@expenzo.com`
- Admin: `ops.admin@expenzo.com`
- User: `user@expenzo.com`

Change seeded passwords before any production deployment.

## Role Model
- Super Admin: all permissions.
- Admin: operational administration, users, system categories, reports and read-only finance visibility; cannot assign or modify Super Admins.
- User: own expenses, accounts, budgets, reports and category badge customization.

## Ownership Rules
Accounts, expenses, budgets and user category settings are user-owned. Repositories and services enforce `user_id` scopes, so hidden UI links are not the only protection. Category definitions are system-wide, while badge/icon/color preferences are isolated in `user_category_settings`.

## Modules
Dashboard, Expenses, Categories with user badges, Accounts, Budgets, Reports/CSV export, Users, Settings and Audit logging.

## Architecture
Controllers coordinate requests, services contain business rules and transactions, repositories own SQL, views contain presentation, and middleware enforces authentication/authorization.

## Security
PDO prepared statements, password hashing, CSRF protection, session regeneration, inactive-user prevention, permission checks, ownership checks, upload MIME validation, randomized receipt names, XSS escaping, transactional account balance updates and audit logs.

## Testing
Run PHP syntax checks with:

```bash
C:\xampp\php\php.exe -l path\to\file.php
```

Or lint the project with PowerShell:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
```

## Production
Set `APP_ENV=production` and `APP_DEBUG=false`, use HTTPS, rotate seeded passwords, use a least-privilege database user, keep `storage/` outside the public root, and configure backups plus log rotation.
