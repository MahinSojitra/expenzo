<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class AuditLogRepository
{
    public function paginate(array $filters, int $page = 1, int $per = 15): array
    {
        $pdo = Database::connection();
        $where = ['1=1'];
        $params = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(a.entity LIKE ? OR a.action LIKE ? OR CAST(a.entity_id AS CHAR) LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR a.ip_address LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term, $term, $term, $term);
        }

        foreach (['action', 'entity', 'user_id'] as $key) {
            if (($filters[$key] ?? '') !== '') {
                $where[] = 'a.' . $key . ' = ?';
                $params[] = $filters[$key];
            }
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'a.created_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'a.created_at <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }

        $whereSql = implode(' AND ', $where);
        $count = $pdo->prepare('SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id WHERE ' . $whereSql);
        $count->execute($params);

        $total = (int) $count->fetchColumn();
        $per = max(1, min(100, $per));
        $page = max(1, min($page, max(1, (int)ceil($total / $per))));
        $offset = ($page - 1) * $per;

        $sql = 'SELECT a.*,u.name user_name,u.email user_email
            FROM audit_logs a
            LEFT JOIN users u ON u.id=a.user_id
            WHERE ' . $whereSql . '
            ORDER BY a.created_at DESC,a.id DESC
            LIMIT ' . (int) $per . ' OFFSET ' . (int) $offset;
        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return [
            'rows' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per' => $per,
        ];
    }

    public function filterOptions(): array
    {
        $pdo = Database::connection();

        return [
            'actions' => $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(),
            'entities' => $pdo->query('SELECT DISTINCT entity FROM audit_logs ORDER BY entity')->fetchAll(),
            'users' => $pdo->query('SELECT DISTINCT u.id,u.name,u.email FROM audit_logs a JOIN users u ON u.id=a.user_id ORDER BY u.name,u.email')->fetchAll(),
        ];
    }
}
