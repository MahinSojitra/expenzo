<?php
declare(strict_types=1);
// Only creates/removes randomly named, isolated databases for the CRUD smoke test.
require dirname(__DIR__).'/app/Core/Autoloader.php';
App\Core\Env::load(dirname(__DIR__).'/.env');
$name = $argv[2] ?? '';
if (!preg_match('/^expenzo_crud_test_[a-f0-9]{12}$/D', $name)) throw new RuntimeException('Invalid test database name.');
$config = require dirname(__DIR__).'/config/database.php';
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['host'], $config['port']),
    $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
if (($argv[1] ?? '') === 'drop') {
    $pdo->exec('DROP DATABASE IF EXISTS `'.$name.'`');
    echo "Test database removed.\n";
    exit;
}
if (($argv[1] ?? '') !== 'create') throw new RuntimeException('Use create or drop.');
$pdo->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
foreach (['migrations/001_initial.sql', 'seeders/001_seed.sql'] as $file) {
    $sql = ltrim(file_get_contents(dirname(__DIR__).'/database/'.$file), "\xEF\xBB\xBF");
    $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS expenzo[^;]*;/', '', $sql);
    $sql = str_replace('USE expenzo;', 'USE `'.$name.'`;', $sql);
    $pdo->exec($sql);
}
$pdo->prepare('UPDATE users SET password_hash=?')->execute([password_hash('CrudTest123!', PASSWORD_DEFAULT)]);
$pdo->exec("INSERT INTO users(name,email,password_hash,status) VALUES('Other User','other@example.test','unused','active')");
$other = (int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO user_roles(user_id,role_id) VALUES ($other,3)");
$pdo->exec("INSERT INTO accounts(user_id,name,type,opening_balance,current_balance,status) VALUES($other,'Other account','Cash',10,10,'active')");
$foreignAccount = (int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO budgets(user_id,start_date,end_date,budget_amount,warning_threshold,status) VALUES($other,'2026-01-01','2026-12-31',100,80,'active')");
echo json_encode(['foreignAccount' => $foreignAccount, 'foreignBudget' => (int)$pdo->lastInsertId()]);