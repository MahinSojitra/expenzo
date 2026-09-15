<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;
use App\Services\Authorization;
use App\Services\FieldValidationException;

final class CategoryRepository
{
    public function allForUser(int $userId, bool $activeOnly = false, ?int $page = null): array
    {
        $sql = 'SELECT c.*,ucs.badge,COALESCE(ucs.icon,c.icon) display_icon,COALESCE(ucs.color,c.color) display_color
            FROM categories c LEFT JOIN user_category_settings ucs ON ucs.category_id=c.id AND ucs.user_id=?
            WHERE (c.owner_id IS NULL OR c.owner_id=?)';
        if ($activeOnly) $sql .= ' AND c.status="active"';
        return \App\Core\Pagination::query($sql.' ORDER BY c.name,c.id', [$userId, $userId], $page);
    }

    public function all(array $filters = [], ?int $page = null): array
    {
        $where = ['1=1'];
        $params = [];
        if (($filters['scope'] ?? '') === 'global') $where[] = 'c.owner_id IS NULL';
        if (($filters['scope'] ?? '') === 'personal') $where[] = 'c.owner_id IS NOT NULL';
        if (!empty($filters['owner_id'])) { $where[] = 'c.owner_id=?'; $params[] = (int)$filters['owner_id']; }
        if (in_array($filters['status'] ?? '', ['active', 'inactive'], true)) { $where[] = 'c.status=?'; $params[] = $filters['status']; }
        return \App\Core\Pagination::query('SELECT c.*,u.name owner_name,u.email owner_email,NULL badge,c.icon display_icon,c.color display_color
            FROM categories c LEFT JOIN users u ON u.id=c.owner_id WHERE '.implode(' AND ', $where).' ORDER BY c.name,c.id', $params, $page);
    }

    public function owners(): array
    {
        return Database::connection()->query('SELECT DISTINCT u.id,u.name,u.email FROM users u JOIN categories c ON c.owner_id=u.id ORDER BY u.name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $s = Database::connection()->prepare('SELECT c.*,u.name owner_name,u.email owner_email FROM categories c LEFT JOIN users u ON u.id=c.owner_id WHERE c.id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function findForUser(int $id, int $userId, bool $activeOnly = false): ?array
    {
        $sql = 'SELECT * FROM categories WHERE id=? AND (owner_id IS NULL OR owner_id=?)';
        if ($activeOnly) $sql .= ' AND status="active"';
        $s = Database::connection()->prepare($sql);
        $s->execute([$id, $userId]);
        return $s->fetch() ?: null;
    }

    public function ownerOptions(array $actor): array
    {
        $options = [];
        if (Authorization::allows($actor, 'categories.manage_global')) {
            $options['global'] = ['label' => 'Global - available to everyone', 'icon' => 'globe'];
        }
        $options[(int)$actor['id']] = ['label' => 'Me - available only to me', 'icon' => 'user'];
        if (Authorization::allows($actor, 'categories.assign_owner')) {
            $users = Database::connection()->query('SELECT id,name,email,status FROM users ORDER BY name,id')->fetchAll();
            foreach ($users as $user) {
                if ((int)$user['id'] === (int)$actor['id']) continue;
                $options[(int)$user['id']] = [
                    'label' => $user['name'].' ('.$user['email'].')'.($user['status'] === 'inactive' ? ' - Inactive' : ''),
                    'icon' => 'user',
                ];
            }
        }
        return $options;
    }

    private function creationOwner(array $data, array $actor): ?int
    {
        if (!Authorization::allows($actor, 'categories.create')) {
            throw new FieldValidationException('owner_id', 'You do not have permission to create categories.');
        }
        $value = $data['owner_id'] ?? (string)$actor['id'];
        if (!is_string($value) && !is_int($value)) {
            throw new FieldValidationException('owner_id', 'Choose a valid category owner.');
        }
        if ($value === 'global') {
            if (!Authorization::allows($actor, 'categories.manage_global')) {
                throw new FieldValidationException('owner_id', 'You do not have permission to create global categories.');
            }
            return null;
        }
        $owner = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($owner === false) throw new FieldValidationException('owner_id', 'Choose a valid category owner.');
        if ($owner !== (int)$actor['id'] && !Authorization::allows($actor, 'categories.assign_owner')) {
            throw new FieldValidationException('owner_id', 'You do not have permission to create categories for other users.');
        }
        $statement = Database::connection()->prepare('SELECT id FROM users WHERE id=?');
        $statement->execute([$owner]);
        if (!$statement->fetchColumn()) throw new FieldValidationException('owner_id', 'This user no longer exists. Choose another owner.');
        return $owner;
    }
    public function create(array $d, array $actor): int
    {
        $owner = $this->creationOwner($d, $actor);
        $s = Database::connection()->prepare('INSERT INTO categories(name,description,icon,color,status,owner_id,created_by) VALUES(?,?,?,?,?,?,?)');
        $s->execute([trim($d['name']), $d['description'] ?? null, $d['icon'], $d['color'], $d['status'], $owner, (int)$actor['id']]);
        return (int)Database::connection()->lastInsertId();
    }

    public function update(int $id, array $d, array $actor): void
    {
        $record = $this->find($id);
        if (!$record || !Authorization::categoryManageable($actor, $record)) throw new \RuntimeException('Category is outside your allowed scope.');
        // Ownership and scope are immutable; submitted owner/scope fields are never used.
        $s = Database::connection()->prepare('UPDATE categories SET name=?,description=?,icon=?,color=?,status=?,updated_at=NOW() WHERE id=?');
        $s->execute([trim($d['name']), $d['description'] ?? null, $d['icon'], $d['color'], $d['status'], $id]);
    }

    public function delete(int $id, array $actor): void
    {
        $record = $this->find($id);
        if (!$record || !Authorization::categoryManageable($actor, $record)) throw new \RuntimeException('Category is outside your allowed scope.');
        Database::connection()->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
    }

    public function customize(int $categoryId, int $userId, array $d): void
    {
        if (!$this->findForUser($categoryId, $userId)) throw new \RuntimeException('Category is outside your allowed scope.');
        $s = Database::connection()->prepare('INSERT INTO user_category_settings(user_id,category_id,badge,icon,color,created_at,updated_at)
            VALUES(?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE badge=VALUES(badge),icon=VALUES(icon),color=VALUES(color),updated_at=NOW()');
        $s->execute([$userId, $categoryId, trim($d['badge'] ?? '') ?: null, $d['icon'], $d['color']]);
    }
}
