<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\View;
use App\Middleware\PermissionMiddleware;
use App\Repositories\UserRepository;
use App\Services\CrudValidation;

final class UserController
{
    use CrudForm;

    public function index(Request $r): void
    {
        PermissionMiddleware::require('users.view');
        $data = (new UserRepository())->all((string)$r->query('q', ''), max(1, (int)$r->query('page', 1)), 10, auth_user());
        View::render('users/index', compact('data'));
    }

    private function target(int $id): array
    {
        $target = $this->missing((new UserRepository())->findWithRole($id));
        if ($target['role_name'] === 'Super Admin' && !has_role('Super Admin')) $this->forbidden();
        $target['role_id'] = (new UserRepository())->roleIdByName($target['role_name'] ?? '');
        return $target;
    }

    public function create(Request $r): void
    {
        PermissionMiddleware::require('users.create');
        $this->form();
    }

    public function edit(Request $r, string $id): void
    {
        PermissionMiddleware::require('users.edit');
        $this->form($this->target((int)$id));
    }

    private function form(array $record = [], array $errors = []): void
    {
        if ($errors) http_response_code(422);
        $roles = (new UserRepository())->roles(auth_user());
        View::render('users/' . (isset($record['id']) ? 'edit' : 'create'), compact('record', 'errors', 'roles'));
    }

    private function validate(array $data, ?int $id = null): array
    {
        $errors = CrudValidation::validate('users', $data, $id !== null);
        $repo = new UserRepository();
        $role = $repo->roleNameById((int)($data['role_id'] ?? 0));
        if (!$role || ($role === 'Super Admin' && !has_role('Super Admin'))) {
            $errors['role_id'] = 'Choose a role you are allowed to assign.';
        }
        $existing = $repo->findByEmail(trim((string)($data['email'] ?? '')));
        if ($existing && (int)$existing['id'] !== $id) $errors['email'] = 'This email address is already in use.';
        return $errors;
    }

    private function persist(array $data, ?int $id): void
    {
        $pdo = Database::connection();
        $repo = new UserRepository();
        $pdo->beginTransaction();
        try {
            if ($id === null) $id = $repo->create($data);
            else {
                unset($data['password']);
                $repo->update($id, $data);
            }
            $repo->setRole($id, (int)$data['role_id']);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function store(Request $r): void
    {
        PermissionMiddleware::require('users.create');
        verify_csrf();
        $errors = $this->validate($r->all());
        if (!$errors) {
            try {
                $this->persist($r->all(), null);
                $this->saved('users', 'User created successfully.');
            } catch (\PDOException $e) {
                $errors = $this->persistenceError($e, 'email');
            }
        }
        $this->form([], $errors);
    }

    public function update(Request $r, string $id): void
    {
        PermissionMiddleware::require('users.edit');
        verify_csrf();
        $record = $this->target((int)$id);
        $errors = $this->validate($r->all(), (int)$id);
        if (!$errors) {
            try {
                $this->persist($r->all(), (int)$id);
                $this->saved('users', 'User updated successfully.');
            } catch (\PDOException $e) {
                $errors = $this->persistenceError($e, 'email');
            }
        }
        $this->form($record, $errors);
    }
}