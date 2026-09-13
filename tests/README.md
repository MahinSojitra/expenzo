# CRUD regression tests

The CRUD smoke test runs real browser requests against a randomly named, isolated MySQL database. It imports the existing initial schema and seed, uses test-only passwords, and removes that database on completion. It does not modify the configured application database.

Requirements: PHP with PDO MySQL, Node.js, Google Chrome, and database credentials from `.env` with permission to create and drop a temporary database. Port 18765 must be available.

From the project root in PowerShell:

```powershell
npm.cmd install --prefix storage/cache/crud-browser --no-save --package-lock=false playwright
$env:PLAYWRIGHT_PATH = Join-Path (Get-Location) 'storage/cache/crud-browser/node_modules/playwright'
$env:PHP_BINARY = 'C:/xampp/php/php.exe'
node tests/crud-smoke.cjs
```

Coverage includes separate list/create/edit pages, successful saves and redirects, unchanged account balances, budget updates, personal category appearance, user role restrictions, ownership checks, CSRF rejection, validation with retained input, secure deletion, user search, cancel links, sidebar state, and responsive layouts at 320, 375, 768, and 1440 pixels. Screenshots are saved under the ignored `storage/cache/` directory.

The test server uses `tests/crud-router.php` only for local verification. Production still uses `public/index.php`.

PHP syntax check:

```powershell
Get-ChildItem app, config, public, resources, routes, tests -Recurse -Filter *.php | ForEach-Object {
    & C:/xampp/php/php.exe -l $_.FullName
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax check failed: $($_.FullName)" }
}
```