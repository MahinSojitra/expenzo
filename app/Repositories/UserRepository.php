<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;
use App\Services\Authorization;

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $s = Database::connection()->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
        $s->execute([$email]);
        return $s->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $s = Database::connection()->prepare('SELECT * FROM users WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function findWithRole(int $id): ?array
    {
        $s = Database::connection()->prepare('SELECT u.*,r.id role_id,r.name role_name,r.status role_status,r.system_key
            FROM users u LEFT JOIN user_roles ur ON ur.user_id=u.id LEFT JOIN roles r ON r.id=ur.role_id WHERE u.id=?');
        $s->execute([$id]);
        $user = $s->fetch();
        if (!$user) return null;
        $user['permissions'] = $user['role_status'] === 'active' ? (new RoleRepository())->permissionNames((int)$user['role_id']) : [];
        return $user;
    }

    public function all(string $q = '', int $page = 1, int $per = 10, array $actor = []): array
    {
        $where = ['1=1'];
        $params = [];
        if (!Authorization::superAdmin($actor)) $where[] = 'COALESCE(r.system_key,"")<>"super_admin"';
        if ($q !== '') { $where[] = '(u.name LIKE ? OR u.email LIKE ?)'; $params = ["%$q%", "%$q%"]; }
        $joins = ' FROM users u LEFT JOIN user_roles ur ON ur.user_id=u.id LEFT JOIN roles r ON r.id=ur.role_id WHERE '.implode(' AND ', $where);
        $pdo = Database::connection();
        $s = $pdo->prepare('SELECT COUNT(*)'.$joins);
        $s->execute($params);
        $total = (int)$s->fetchColumn();
        $offset = ($page - 1) * $per;
        $s = $pdo->prepare('SELECT u.*,r.id role_id,r.name role_names,r.system_key'.$joins.' ORDER BY u.created_at DESC,u.id DESC LIMIT '.(int)$per.' OFFSET '.(int)$offset);
        $s->execute($params);
        return ['rows' => $s->fetchAll(), 'total' => $total, 'page' => $page, 'per' => $per];
    }

    public function create(array $d): int
    {
        $s = Database::connection()->prepare('INSERT INTO users(name,email,password_hash,status,created_at,updated_at) VALUES(?,?,?,?,NOW(),NOW())');
        $s->execute([trim($d['name']), trim($d['email']), password_hash($d['password'], PASSWORD_DEFAULT), $d['status'] ?? 'active']);
        return (int)Database::connection()->lastInsertId();
    }

    public function update(int $id, array $d): void
    {
        $sql = 'UPDATE users SET name=?,email=?,status=?,updated_at=NOW()';
        $params = [trim($d['name']), trim($d['email']), $d['status']];
        if (!empty($d['password'])) { $sql .= ',password_hash=?'; $params[] = password_hash($d['password'], PASSWORD_DEFAULT); }
        $params[] = $id;
        Database::connection()->prepare($sql.' WHERE id=?')->execute($params);
    }

    public function setRole(int $userId, int $roleId): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$userId]);
        $pdo->prepare('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)')->execute([$userId, $roleId]);
    }

    public function roleNameById(int $id): ?string
    {
        return (new RoleRepository())->find($id)['name'] ?? null;
    }

    public function roleIdByName(string $name): ?int
    {
        $s = Database::connection()->prepare('SELECT id FROM roles WHERE name=? AND status="active"');
        $s->execute([$name]);
        $id = $s->fetchColumn();
        return $id ? (int)$id : null;
    }

    public function roles(array $actor = []): array
    {
        return array_values(array_filter((new RoleRepository())->all(), fn(array $role) => Authorization::canAssignRole($actor, $role)));
    }
}