<?php
declare(strict_types=1);
return [
 'host'=>getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1'),
 'port'=>getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306'),
 'database'=>getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? 'expenzo'),
 'username'=>getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? 'root'),
 'password'=>getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? ''),
 'charset'=>'utf8mb4',
];
