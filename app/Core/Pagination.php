<?php
declare(strict_types=1);

namespace App\Core;

final class Pagination
{
    /**
     * A null page returns the complete result for selectors, analytics and exports.
     * SQL and ordering come only from repository code; filter values stay bound.
     */
    public static function query(string $sql, array $params = [], ?int $page = null): array
    {
        $pdo = Database::connection();
        if ($page === null) {
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            return $statement->fetchAll();
        }

        $per = pagination_size();
        $count = $pdo->prepare('SELECT COUNT(*) FROM ('.$sql.') pagination_rows');
        $count->execute($params);
        $total = (int)$count->fetchColumn();
        $page = max(1, min($page, max(1, (int)ceil($total / $per))));
        $offset = ($page - 1) * $per;
        $statement = $pdo->prepare($sql.' LIMIT '.$per.' OFFSET '.$offset);
        $statement->execute($params);

        return ['rows' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'per' => $per];
    }
}
