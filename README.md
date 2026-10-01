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
All seeded accounts use password `Expenzo@1603!` for local development only.

- Super Admin: `admin@expenzo.com`
- User: `user@expenzo.com`

Change seeded passwords before any production deployment.

## Role Model
- Super Admin: all permissions.
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

## Account transactions
Use **Accounts → Add Transaction** or **Transactions → Add Transaction** to record income, transfers, or balance adjustments. Opening balances remain the starting point. Expenses retain their existing entry workflow and appear in transaction history.

- Income increases the selected account. Transfers atomically debit one owned account and credit another; they do not change expense reports or budgets.
- Adjustments increase or decrease the balance by the entered amount; enter a reason after comparing the account with your bank statement. The amount is the difference, not the desired final balance.
- Posted transactions are immutable. Reverse an incorrect entry with a reason and post its replacement. Reversals cannot overdraw an account and remain visible in history.
- Only active owned accounts accept new postings. Reversals may restore balances on inactive accounts. Accounts referenced by transactions cannot be deleted.
- Permissions: `transactions.view`, `transactions.create` (income/transfers), `transactions.adjust`, and `transactions.reverse`. Reversing adjustments requires both adjust and reverse. The protected User and Super Admin roles receive these permissions in the existing seed; custom roles can be configured under Roles.
- `finance.view_all` broadens transaction history visibility only; it never grants posting or reversal rights on another user's accounts.
- Audit records, integer-cent calculations, account row locks, and unique submission tokens protect balance changes.

The existing `001_initial.sql` and `001_seed.sql` include this feature for fresh installations. **Do not rerun the initial migration on an existing installation: it drops tables.** For an existing installation, apply only the `CREATE TABLE account_transactions` statement and the transaction permission entries from those files, then grant them to the intended roles. No new migration or seeder files are needed.

Run `C:/xampp/php/php.exe tests/transactions.php` for isolated database and HTTP regression tests. It uses a randomly named temporary database, starts a temporary PHP server on port 18766, and removes both afterward. Requires PDO MySQL, cURL, mbstring, and database create/drop privileges.

### Transaction details and administrator actions
Every income, transfer, or adjustment has a View page using the expense-details layout, with account ownership, original movements, timestamps, and actor history. Reversal requires a reason and confirmation and preserves the original transaction.

Administrators receive transactions.create_all and transactions.reverse_all in addition to the normal action permissions. The existing seed grants these to system Admin and Super Admin roles only. Custom roles require explicit grants under Roles. Cross-user reversal also requires transactions.view and finance.view_all to access the record; adjustments require transactions.adjust. Read access alone never permits posting or reversing another user's transactions.

When posting for another user, select the account labeled with that owner's name. The transaction belongs to the account owner; the audit records the acting administrator. Transfers stay within one owner's accounts, and destination choices disable other owners. No new migration or seeder files were added.