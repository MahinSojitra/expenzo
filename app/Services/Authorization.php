<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\RoleRepository;

final class Authorization
{
    public static function allows(array $actor, string $permission): bool
    {
        return in_array($permission, $actor['permissions'] ?? [], true);
    }

    public static function superAdmin(array $actor): bool
    {
        return ($actor['system_key'] ?? '') === 'super_admin' && ($actor['role_status'] ?? '') === 'active';
    }

    public static function categoryVisible(array $actor, array $category): bool
    {
        return $category['owner_id'] === null || (int)$category['owner_id'] === (int)$actor['id']
            || self::allows($actor, 'categories.view_all');
    }

    public static function categoryManageable(array $actor, array $category): bool
    {
        return $category['owner_id'] === null
            ? self::allows($actor, 'categories.manage_global')
            : ((int)$category['owner_id'] === (int)$actor['id'] || self::allows($actor, 'categories.manage_all'));
    }

    public static function categoryCustomizable(array $actor, array $category): bool
    {
        return $category['owner_id'] === null || (int)$category['owner_id'] === (int)$actor['id'];
    }

    public static function grantablePermissions(array $actor): array
    {
        $repo = new RoleRepository();
        if (self::superAdmin($actor)) return array_column($repo->permissions(), 'name');
        $permissions = $actor['permissions'] ?? [];
        // Operations Admin may delegate the protected User role's personal-finance permissions.
        // This does not grant the Admin those actions on their own account.
        if (($actor['system_key'] ?? '') === 'admin') {
            $userRole = $repo->findSystem('user');
            if ($userRole) $permissions = array_merge($permissions, $repo->permissionNames((int)$userRole['id']));
        }
        return array_values(array_unique($permissions));
    }

    public static function canAssignRole(array $actor, array $role): bool
    {
        if ($role['status'] !== 'active' || !self::allows($actor, 'users.assign_role')) return false;
        if (self::superAdmin($actor)) return true;
        if (($role['system_key'] ?? '') === 'super_admin') return false;
        return !array_diff((new RoleRepository())->permissionNames((int)$role['id']), self::grantablePermissions($actor));
    }

    public static function canManageRole(array $actor, array $role): bool
    {
        if (($role['system_key'] ?? '') === 'super_admin') return false;
        if (self::superAdmin($actor)) return true;
        if ($role['is_system']) return false;
        return !array_diff((new RoleRepository())->permissionNames((int)$role['id']), self::grantablePermissions($actor));
    }

    public static function canManageUser(array $actor, array $target): bool
    {
        if (self::superAdmin($actor)) return true;
        if (($target['system_key'] ?? '') === 'super_admin') return false;
        $permissions = empty($target['role_id']) ? [] : (new RoleRepository())->permissionNames((int)$target['role_id']);
        return !array_diff($permissions, self::grantablePermissions($actor));
    }
}