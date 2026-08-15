<?php
declare(strict_types=1);
return [
 'env'=>getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production'),
 'debug'=>(bool)(getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? false)),
 'url'=>getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? 'http://localhost'),
 'timezone'=>getenv('APP_TIMEZONE') ?: ($_ENV['APP_TIMEZONE'] ?? 'Asia/Kolkata'),
 'currency'=>getenv('APP_CURRENCY') ?: ($_ENV['APP_CURRENCY'] ?? 'INR'),
 'session_name'=>getenv('SESSION_NAME') ?: ($_ENV['SESSION_NAME'] ?? 'expenzo_session'),
 'session_lifetime'=>(int)(getenv('SESSION_LIFETIME') ?: ($_ENV['SESSION_LIFETIME'] ?? 7200)),
];
