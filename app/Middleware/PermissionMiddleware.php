<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\Session;
use App\Core\View;
use App\Repositories\UserRepository;

final class PermissionMiddleware
{
    public static function require(string $permission): void
    {
        AuthMiddleware::require();
        $user = (new UserRepository())->findWithRole((int)Session::get('user_id'));
        $active = $user && $user['status'] === 'active' && $user['role_status'] === 'active';
        Session::put('user', $user);
        Session::put('permissions', $active ? $user['permissions'] : []);
        if (!$active || !in_array($permission, $user['permissions'], true)) {
            http_response_code(403);
            View::render('errors/403', [], 'app');
            exit;
        }
    }
}