<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Middleware\PermissionMiddleware;
use App\Repositories\UserRepository;
use App\Services\Authorization;
use App\Services\CrudValidation;
use App\Services\UserManagementService;

final class UserController
{
    use CrudForm;

    public function index(Request $r): void
    {
        PermissionMiddleware::require('users.view');
        $repo = new UserRepository();
        $data = $repo->all((string)$r->query('q', ''), max(1, (int)$r->query('page', 1)), pagination_size(), auth_user());
        foreach ($data['rows'] as &$row) $row['may_edit'] = Authorization::canManageUser(auth_user(), $repo->findWithRole((int)$row['id']));
        unset($row);
        View::render('users/index', compact('data'));
    }

    private function target(int $id): array
    {
        $target = $this->missing((new UserRepository())->findWithRole($id));
        if (!Authorization::canManageUser(auth_user(), $target)) $this->forbidden();
        return $target;
    }

    public function create(Request $r): void
    {
        PermissionMiddleware::require('users.create');
        PermissionMiddleware::require('users.assign_role');
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
        View::render('users/'.(isset($record['id']) ? 'edit' : 'create'), compact('record', 'errors', 'roles'));
    }

    private function submit(Request $r, ?array $record): void
    {
        $data = $r->all();
        if ($record && !can('users.assign_role')) {
            if (isset($data['role_id']) && (int)$data['role_id'] !== (int)$record['role_id']) $this->forbidden();
            $data['role_id'] = $record['role_id'];
        }
        $errors = CrudValidation::validate('users', $data, $record !== null);
        $existing = (new UserRepository())->findByEmail(trim((string)($data['email'] ?? '')));
        if ($existing && (int)$existing['id'] !== (int)($record['id'] ?? 0)) $errors['email'] = 'This email address is already in use.';
        if (!$errors) {
            try {
                (new UserManagementService())->save($record ? (int)$record['id'] : null, $data, auth_user());
                $this->saved('users', $record ? 'User updated successfully.' : 'User created successfully.');
            } catch (\DomainException $e) {
                $errors['role_id'] = $e->getMessage();
            } catch (\PDOException $e) {
                $errors = $this->persistenceError($e, 'email');
            }
        }
        $this->form($record ?? [], $errors);
    }

    public function store(Request $r): void
    {
        PermissionMiddleware::require('users.create');
        PermissionMiddleware::require('users.assign_role');
        verify_csrf();
        $this->submit($r, null);
    }

    public function update(Request $r, string $id): void
    {
        PermissionMiddleware::require('users.edit');
        verify_csrf();
        $this->submit($r, $this->target((int)$id));
    }
}