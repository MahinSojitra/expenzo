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
        if (!$user || $user['status'] !== 'active' || !in_array($permission, $user['permissions'] ?? [], true)) {
            http_response_code(403);
            View::render('errors/403', [], 'app');
            exit;
        }
        // Keep role-sensitive controller checks and navigation consistent with current permissions.
        Session::put('user', $user);
        Session::put('permissions', $user['permissions']);
    }
}