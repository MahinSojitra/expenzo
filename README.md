# Expense Tracker — Pure PHP + MySQL

Production-oriented expense tracker built without Laravel or another PHP framework. The provided AdminKit template is used as the visual foundation.

## Requirements
- PHP 8.2+
- MySQL 8+ / MariaDB 10.6+
- Apache with mod_rewrite or Nginx
- PHP PDO, JSON and Fileinfo extensions

## Installation
1. Copy `.env.example` to `.env` and set database credentials.
2. Create the database by running `database/migrations/001_initial.sql`.
3. Run `database/seeders/001_seed.sql`.
4. Point the web server document root to `public/`.
5. Ensure `storage/` is writable by PHP. Receipt files are stored in `storage/receipts/` outside the public web root.
6. Visit `/login`.

## Default administrator
- Email: `admin@example.com`
- Password: `ChangeMe123!`
Change this password immediately in a real deployment.

## Modules
Dashboard, Expenses, Categories, Accounts, Budgets, Reports/CSV export, Users/Roles/Permissions, Settings, Audit logging.

## Architecture
HTTP routing is handled by a lightweight custom router. Controllers coordinate requests, services contain business rules and transactions, repositories own SQL, views contain presentation only, and middleware enforces authentication/authorization.

## Security
PDO prepared statements, password hashing, CSRF protection, strict sessions, server-side validation, role/permission checks, upload MIME validation, randomized receipt names, centralized exception handling, XSS escaping, transactional financial updates and audit logs.

## Testing
Run `composer install` and `composer test` after installing the development dependency set.

## Production
Set `APP_ENV=production` and `APP_DEBUG=false`, use HTTPS, rotate the seeded administrator password, use a dedicated DB user with least privilege, keep `storage/` outside the public root, and configure regular database backups and log rotation.
