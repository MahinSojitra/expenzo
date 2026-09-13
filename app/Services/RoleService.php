<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

final class RoleService
{
    public function save(?int $id, array $data, array $permissionIds, array $actor): int
    {
        $pdo = Database::connection();
        $repo = new RoleRepository();
        $pdo->beginTransaction();
        try {
            // Serialize permission changes and assignments, including last-Super-Admin checks.
            $pdo->query('SELECT id FROM roles ORDER BY id FOR UPDATE')->fetchAll();
            $actor = (new UserRepository())->findWithRole((int)$actor['id']);
            $permission = $id === null ? 'roles.create' : 'roles.edit';
            if (!$actor || $actor['status'] !== 'active' || (!Authorization::allows($actor, $permission) || !Authorization::allows($actor, 'permissions.manage'))) throw new \DomainException('You cannot save this role.');
            $old = $id === null ? null : $repo->find($id);
            if ($id !== null && (!$old || !Authorization::canManageRole($actor, $old))) throw new \DomainException('This role is protected or outside your allowed access.');
            if ($old && $old['is_system'] && ($data['name'] !== $old['name'] || $data['status'] !== 'active')) {
                throw new \DomainException('System roles cannot be renamed or deactivated.');
            }
            $all = array_column($repo->permissions(), 'name', 'id');
            $grantable = Authorization::grantablePermissions($actor);
            foreach ($permissionIds as $permissionId) {
                if (!isset($all[$permissionId]) || !in_array($all[$permissionId], $grantable, true)) {
                    throw new \DomainException('You cannot grant one or more selected permissions.');
                }
            }
            $oldAudit = $old ? $old + ['permission_ids' => $repo->permissionIds($id)] : null;
            $id = $repo->save($id, $data, $permissionIds);
            AuditService::record((int)$actor['id'], $old ? 'update' : 'create', 'roles', $id, $oldAudit, $data + ['permission_ids' => $permissionIds]);
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $id, array $actor): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->query('SELECT id FROM roles ORDER BY id FOR UPDATE')->fetchAll();
            $actor = (new UserRepository())->findWithRole((int)$actor['id']);
            $repo = new RoleRepository();
            $role = $repo->find($id);
            if (!$actor || $actor['status'] !== 'active' || !Authorization::allows($actor, 'roles.delete')
                || !$role || $role['is_system'] || !Authorization::canManageRole($actor, $role)) throw new \RuntimeException('This role cannot be deleted.');
            $s = $pdo->prepare('SELECT COUNT(*) FROM user_roles WHERE role_id=?');
            $s->execute([$id]);
            if ((int)$s->fetchColumn()) throw new \RuntimeException('Reassign all users before deleting this role.');
            $old = $role + ['permission_ids' => $repo->permissionIds($id)];
            $pdo->prepare('DELETE FROM role_permissions WHERE role_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM roles WHERE id=?')->execute([$id]);
            AuditService::record((int)$actor['id'], 'delete', 'roles', $id, $old, null);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}