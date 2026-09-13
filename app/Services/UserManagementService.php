<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

final class UserManagementService
{
    public function save(?int $id, array $data, array $actor): void
    {
        $pdo = Database::connection();
        $repo = new UserRepository();
        $pdo->beginTransaction();
        try {
            $pdo->query('SELECT id FROM roles ORDER BY id FOR UPDATE')->fetchAll();
            $actor = $repo->findWithRole((int)$actor['id']);
            if (!$actor || $actor['status'] !== 'active' || !Authorization::allows($actor, $id === null ? 'users.create' : 'users.edit')) {
                throw new \DomainException('You cannot save this user.');
            }
            $old = $id === null ? null : $repo->findWithRole($id);
            if ($id !== null && (!$old || !Authorization::canManageUser($actor, $old))) throw new \DomainException('You cannot modify this user.');
            $roleId = (int)($data['role_id'] ?? ($old['role_id'] ?? 0));
            $role = (new RoleRepository())->find($roleId);
            $roleChanged = !$old || (int)$old['role_id'] !== $roleId;
            if (!$role || ($roleChanged && !Authorization::canAssignRole($actor, $role))) {
                throw new \DomainException('Choose an active role you are allowed to assign.');
            }
            if ($old && ($old['system_key'] ?? '') === 'super_admin'
                && ($data['status'] !== 'active' || ($role['system_key'] ?? '') !== 'super_admin')) {
                $s = $pdo->prepare('SELECT COUNT(*) FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id
                    WHERE r.system_key="super_admin" AND r.status="active" AND u.status="active" AND u.id<>?');
                $s->execute([$id]);
                if ((int)$s->fetchColumn() === 0) throw new \DomainException('Keep at least one active Super Admin.');
            }
            if ($old && $old['role_status'] !== 'active' && $data['status'] === 'active' && !$roleChanged) {
                throw new \DomainException('Assign an active role before activating this user.');
            }
            $safe = ['name' => $data['name'], 'email' => $data['email'], 'status' => $data['status']];
            if ($id === null) $id = $repo->create($safe + ['password' => $data['password']]);
            else $repo->update($id, $safe);
            if ($roleChanged) $repo->setRole($id, $roleId);
            $oldAudit = $old ? array_intersect_key($old, array_flip(['name', 'email', 'status', 'role_id'])) : null;
            AuditService::record((int)$actor['id'], $old ? 'update' : 'create', 'users', $id, $oldAudit, $safe + ['role_id' => $roleId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}