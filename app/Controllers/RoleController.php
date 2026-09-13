<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Middleware\PermissionMiddleware;
use App\Repositories\RoleRepository;
use App\Services\Authorization;
use App\Services\RoleService;

final class RoleController
{
    use CrudForm;

    public function index(Request $r): void
    {
        PermissionMiddleware::require('roles.view');
        $rows = (new RoleRepository())->all();
        View::render('roles/index', compact('rows'));
    }

    public function create(Request $r): void
    {
        PermissionMiddleware::require('roles.create');
        PermissionMiddleware::require('permissions.manage');
        $this->form();
    }

    public function edit(Request $r, string $id): void
    {
        PermissionMiddleware::require('roles.edit');
        PermissionMiddleware::require('permissions.manage');
        $record = $this->missing((new RoleRepository())->find((int)$id));
        if (!Authorization::canManageRole(auth_user(), $record)) $this->forbidden();
        $this->form($record);
    }

    private function form(array $record = [], array $errors = []): void
    {
        if ($errors) http_response_code(422);
        $repo = new RoleRepository();
        $selectedPermissions = isset($_POST['permissions']) && is_array($_POST['permissions'])
            ? array_map('intval', $_POST['permissions'])
            : ($_SERVER['REQUEST_METHOD'] === 'POST' ? [] : (isset($record['id']) ? $repo->permissionIds((int)$record['id']) : []));
        $grantable = Authorization::grantablePermissions(auth_user());
        $groups = [];
        foreach ($repo->permissions() as $permission) {
            $permission['grantable'] = in_array($permission['name'], $grantable, true);
            $groups[explode('.', $permission['name'])[0]][] = $permission;
        }
        View::render('roles/'.(isset($record['id']) ? 'edit' : 'create'), compact('record', 'errors', 'groups', 'selectedPermissions'));
    }

    private function submit(Request $r, ?array $record): void
    {
        $data = [
            'name' => trim((string)$r->input('name', '')),
            'description' => trim((string)$r->input('description', '')),
            'status' => (string)$r->input('status', ''),
        ];
        $errors = [];
        if ($data['name'] === '' || preg_match_all('/./us', $data['name']) > 80) $errors['name'] = 'Enter a role name of up to 80 characters.';
        if (strlen($data['description']) > 16000) $errors['description'] = 'Use a shorter description.';
        if (!in_array($data['status'], ['active', 'inactive'], true)) $errors['status'] = 'Choose a valid status.';
        $raw = $r->input('permissions', []);
        $ids = [];
        if (!is_array($raw)) $errors['permissions'] = 'Choose valid permissions.';
        else {
            foreach ($raw as $value) {
                if (!is_scalar($value) || !ctype_digit((string)$value)) $errors['permissions'] = 'Choose valid permissions.';
                else $ids[] = (int)$value;
            }
        }
        if (!$errors) {
            try {
                (new RoleService())->save($record ? (int)$record['id'] : null, $data, array_values(array_unique($ids)), auth_user());
                $this->saved('roles', $record ? 'Role updated successfully.' : 'Role created successfully.');
            } catch (\DomainException $e) {
                $errors['permissions'] = $e->getMessage();
            } catch (\PDOException $e) {
                $errors = $this->persistenceError($e, 'name');
            }
        }
        $this->form($record ?? [], $errors);
    }

    public function store(Request $r): void
    {
        PermissionMiddleware::require('roles.create');
        PermissionMiddleware::require('permissions.manage');
        verify_csrf();
        $this->submit($r, null);
    }

    public function update(Request $r, string $id): void
    {
        PermissionMiddleware::require('roles.edit');
        PermissionMiddleware::require('permissions.manage');
        verify_csrf();
        $record = $this->missing((new RoleRepository())->find((int)$id));
        if (!Authorization::canManageRole(auth_user(), $record)) $this->forbidden();
        $this->submit($r, $record);
    }

    public function delete(Request $r, string $id): void
    {
        PermissionMiddleware::require('roles.delete');
        verify_csrf();
        $record = $this->missing((new RoleRepository())->find((int)$id));
        if ($record['is_system'] || !Authorization::canManageRole(auth_user(), $record)) $this->forbidden();
        $this->removeRecord('roles', fn() => (new RoleService())->delete((int)$id, auth_user()), 'Role deleted successfully.');
    }
}