<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\UserRepository;

final class AuthService
{
    public function login(string $email, string $password, bool $remember = false): bool
    {
        return $this->attempt($email, $password, $remember)['ok'];
    }

    public function attempt(string $email, string $password, bool $remember = false): array
    {
        $repo = new UserRepository();
        $user = $repo->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'message' => 'Invalid email or password.'];
        }

        if ($user['status'] !== 'active') {
            return ['ok' => false, 'message' => 'Your account is inactive. Please contact the administrator.'];
        }

        $full = $repo->findWithRole((int) $user['id']);
        if (!$full || $full['role_status'] !== 'active') {
            return ['ok' => false, 'message' => 'Your assigned role is inactive. Please contact the administrator.'];
        }

        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        Session::put('user', $full);
        Session::put('permissions', $full['permissions'] ?? []);
        Session::put('remember', $remember);

        return ['ok' => true, 'message' => null];
    }

    public function logout(): void
    {
        Session::destroy();
    }
}
