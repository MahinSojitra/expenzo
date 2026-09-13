<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;

final class RoleRepository
{
    public function all(): array
    {
        return Database::connection()->query('SELECT r.*,(SELECT COUNT(*) FROM user_roles ur WHERE ur.role_id=r.id) user_count FROM roles r ORDER BY r.is_system DESC,r.name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $s = Database::connection()->prepare('SELECT * FROM roles WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function findSystem(string $key): ?array
    {
        $s = Database::connection()->prepare('SELECT * FROM roles WHERE system_key=?');
        $s->execute([$key]);
        return $s->fetch() ?: null;
    }

    public function permissions(): array
    {
        return Database::connection()->query('SELECT * FROM permissions ORDER BY name')->fetchAll();
    }

    public function permissionIds(int $id): array
    {
        $s = Database::connection()->prepare('SELECT permission_id FROM role_permissions WHERE role_id=?');
        $s->execute([$id]);
        return array_map('intval', $s->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function permissionNames(int $id): array
    {
        $s = Database::connection()->prepare('SELECT p.name FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id WHERE rp.role_id=?');
        $s->execute([$id]);
        return $s->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function save(?int $id, array $data, array $permissionIds): int
    {
        $pdo = Database::connection();
        if ($id === null) {
            $pdo->prepare('INSERT INTO roles(name,description,status) VALUES(?,?,?)')->execute([$data['name'], $data['description'], $data['status']]);
            $id = (int)$pdo->lastInsertId();
        } else {
            $pdo->prepare('UPDATE roles SET name=?,description=?,status=?,updated_at=NOW() WHERE id=?')->execute([$data['name'], $data['description'], $data['status'], $id]);
        }
        $pdo->prepare('DELETE FROM role_permissions WHERE role_id=?')->execute([$id]);
        $insert = $pdo->prepare('INSERT INTO role_permissions(role_id,permission_id) VALUES(?,?)');
        foreach ($permissionIds as $permissionId) $insert->execute([$id, $permissionId]);
        return $id;
    }
}