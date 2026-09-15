<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
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
        $data = (new RoleRepository())->all((int)$r->query('page', 1));
        $rows = $data['rows'];
        View::render('roles/index', compact('rows', 'data'));
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
            $permission['description'] = $permission['description'] ?? $this->permissionDescription($permission['name']);
            if ($permission['description'] === '') $permission['description'] = $this->permissionDescription($permission['name']);
            $groups[explode('.', $permission['name'])[0]][] = $permission;
        }
        View::render('roles/'.(isset($record['id']) ? 'edit' : 'create'), compact('record', 'errors', 'groups', 'selectedPermissions'));
    }

    private function permissionDescription(string $name): string
    {
        $descriptions = [
            'dashboard.view' => 'Open the dashboard and see the user or system overview.',
            'expenses.view' => 'View expense records allowed by ownership and finance scope.',
            'expenses.create' => 'Add new expense records.',
            'expenses.edit' => 'Update existing expense records.',
            'expenses.delete' => 'Delete expense records.',
            'categories.view' => 'View available expense categories.',
            'categories.create' => 'Create categories within the permitted ownership scope.',
            'categories.assign_owner' => 'Assign category owners on create or edit. Requires categories.create or categories.edit.',
            'categories.edit' => 'Edit categories the user is allowed to manage.',
            'categories.delete' => 'Delete categories the user is allowed to manage.',
            'categories.customize' => 'Change category display icon, color and personal appearance.',
            'categories.view_all' => 'View categories across all users and global categories.',
            'categories.manage_global' => 'Create and manage global categories available to everyone.',
            'categories.manage_all' => 'Manage categories owned by other users.',
            'finance.view_all' => 'View finance records and totals across all users.',
            'categories.badge.view' => 'View category badges and icon metadata.',
            'categories.badge.edit' => 'Edit category badges and icon metadata.',
            'accounts.view' => 'View payment accounts.',
            'accounts.create' => 'Create payment accounts.',
            'accounts.edit' => 'Update payment account details and status.',
            'accounts.delete' => 'Delete payment accounts.',
            'budgets.view' => 'View budget limits, usage and warning status.',
            'budgets.create' => 'Create budget limits for allowed users and categories.',
            'budgets.edit' => 'Update budget limits and status.',
            'budgets.delete' => 'Delete budget limits.',
            'reports.view' => 'Open reports and view filtered expense rows.',
            'reports.analytics' => 'View report summaries, insights and charts.',
            'reports.customize' => 'Change report breakdowns, chart styles and report filters.',
            'reports.export' => 'Legacy permission for exporting reports.',
            'reports.export_csv' => 'Export filtered expense and insight data as CSV files.',
            'reports.export_visuals' => 'Export report charts as image files.',
            'users.view' => 'View user accounts.',
            'users.create' => 'Create user accounts.',
            'users.edit' => 'Update user profile, role and status details.',
            'users.delete' => 'Delete user accounts.',
            'users.assign_role' => 'Assign roles to users.',
            'roles.view' => 'View roles and assigned access.',
            'roles.create' => 'Create roles.',
            'roles.edit' => 'Update role details and assigned permissions.',
            'roles.delete' => 'Delete roles that are safe to remove.',
            'permissions.view' => 'View available application permissions.',
            'permissions.manage' => 'Grant or remove permissions on roles.',
            'settings.view' => 'View application settings.',
            'settings.edit' => 'Update application settings.',
            'audit.view' => 'View audit logs and change history.',
        ];
        return $descriptions[$name] ?? 'Controls access to this application action.';
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
        if ($errors) {
            Session::flash('error', implode(' ', array_values(array_filter($errors))));
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
