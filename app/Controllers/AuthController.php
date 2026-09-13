<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Session;
use App\Services\AuthService;
final class AuthController
{
    public function showLogin(Request $r): void
    {
        if (Session::get('user_id'))
            Response::redirect(landing_path());
        View::render('auth/login', [], 'auth');
    }
    public function login(Request $r): void
    {
        verify_csrf();
        $ok = (new AuthService())->login(trim((string) $r->input('email')), (string) $r->input('password'), (bool) $r->input('remember'));
        if (!$ok) {
            Session::flash('error', 'Invalid email or password.');
            Response::redirect('/login');
        }
        Session::flash('success', 'Welcome back!');
        Response::redirect(landing_path());
    }
    public function logout(Request $r): void
    {
        (new AuthService())->logout();
        Response::redirect('/login');
    }
    public function showRegister(Request $r): void
    {
        if (Session::get('user_id'))
            Response::redirect(landing_path());
        View::render('auth/register', [], 'auth');
    }
    public function register(Request $r): void
    {
        if (Session::get('user_id'))
            Response::redirect(landing_path());
        verify_csrf();
        $name = trim((string) $r->input('name', ''));
        $email = trim((string) $r->input('email', ''));
        $password = (string) $r->input('password', '');
        $confirmation = (string) $r->input('password_confirmation', '');
        $error = null;
        if ($name === '' || strlen($name) > 120)
            $error = 'Enter a name of up to 120 characters.';
        elseif (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $error = 'Enter a valid email address.';
        elseif (strlen($password) < 6 || strlen($password) > 72)
            $error = 'Use a password between 6 and 72 bytes.';
        elseif ($password !== $confirmation)
            $error = 'Passwords do not match.';
        if ($error !== null) {
            Session::flash('error', $error);
            Response::redirect('/register');
        }
        $repo = new \App\Repositories\UserRepository();
        if ($repo->findByEmail($email)) {
            Session::flash('error', 'An account with this email already exists. Please sign in.');
            Response::redirect('/register');
        }
        $roleId = $repo->roleIdByName('User');
        if ($roleId === null) {
            Session::flash('error', 'Registration is unavailable until the User role is configured.');
            Response::redirect('/register');
        }
        $pdo = \App\Core\Database::connection();
        $pdo->beginTransaction();
        try {
            $id = $repo->create(['name' => $name, 'email' => $email, 'password' => $password, 'status' => 'active']);
            $repo->setRole($id, $roleId);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            if ($e instanceof \PDOException && (int) ($e->errorInfo[1] ?? 0) === 1062) {
                Session::flash('error', 'An account with this email already exists. Please sign in.');
                Response::redirect('/register');
            }
            throw $e;
        }
        Session::flash('success', 'Account created successfully. Please sign in.');
        Response::redirect('/login');
    }
}
