<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ReportService
{
    public function expenses(array $filters, array $actor): array
    {
        [$where, $params] = $this->conditions($filters, $actor);
        $sql = 'SELECT e.expense_date,e.amount,c.name category,ucs.badge,
                COALESCE(ucs.icon,c.icon) category_icon,COALESCE(ucs.color,c.color) category_color,
                a.name account,a.type account_type,e.description,u.name user_name,u.email user_email
            FROM expenses e
            LEFT JOIN categories c ON c.id=e.category_id
            LEFT JOIN accounts a ON a.id=e.account_id
            LEFT JOIN users u ON u.id=e.user_id
            LEFT JOIN user_category_settings ucs ON ucs.user_id=e.user_id AND ucs.category_id=e.category_id
            WHERE '.implode(' AND ', $where).'
            ORDER BY e.expense_date DESC,e.id DESC';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function analytics(array $filters, array $actor): array
    {
        $rows = $this->expenses($filters, $actor);
        $total = array_sum(array_map(static fn($row) => (float)$row['amount'], $rows));
        $count = count($rows);
        $highest = $count ? max(array_map(static fn($row) => (float)$row['amount'], $rows)) : 0.0;
        return [
            'summary' => [
                'total' => $total,
                'count' => $count,
                'average' => $count ? $total / $count : 0.0,
                'highest' => $highest,
            ],
            'monthly' => $this->breakdown($filters, $actor, 'DATE_FORMAT(e.expense_date,"%b %Y")', 'YEAR(e.expense_date),MONTH(e.expense_date)', 12),
            'byCategory' => $this->breakdown($filters, $actor, 'COALESCE(c.name,"Uncategorized")', 'value DESC', 8),
            'byAccount' => $this->breakdown($filters, $actor, 'COALESCE(a.name,"No account")', 'value DESC', 8),
            'byUser' => Authorization::allows($actor, 'finance.view_all') ? $this->breakdown($filters, $actor, 'COALESCE(u.name,"Deleted user")', 'value DESC', 8) : [],
            'insights' => $this->insights($rows, $total),
        ];
    }

    public function insightRows(array $filters, array $actor): array
    {
        $analytics = $this->analytics($filters, $actor);
        $rows = [
            ['Metric', 'Value'],
            ['Total spent', number_format($analytics['summary']['total'], 2, '.', '')],
            ['Expense count', (string)$analytics['summary']['count']],
            ['Average expense', number_format($analytics['summary']['average'], 2, '.', '')],
            ['Highest expense', number_format($analytics['summary']['highest'], 2, '.', '')],
        ];
        foreach ($analytics['insights'] as $insight) $rows[] = [$insight['label'], $insight['value']];
        return $rows;
    }

    private function breakdown(array $filters, array $actor, string $labelExpression, string $order, int $limit): array
    {
        [$where, $params] = $this->conditions($filters, $actor);
        $sql = 'SELECT '.$labelExpression.' label,ROUND(COALESCE(SUM(e.amount),0),2) value,COUNT(*) count
            FROM expenses e
            LEFT JOIN categories c ON c.id=e.category_id
            LEFT JOIN accounts a ON a.id=e.account_id
            LEFT JOIN users u ON u.id=e.user_id
            WHERE '.implode(' AND ', $where).'
            GROUP BY label
            ORDER BY '.$order.'
            LIMIT '.(int)$limit;
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    private function conditions(array $filters, array $actor): array
    {
        $where = ['e.status="posted"'];
        $params = [];
        if (!Authorization::allows($actor, 'finance.view_all')) {
            $where[] = 'e.user_id=?';
            $params[] = (int)$actor['id'];
        } elseif (($filters['user_id'] ?? '') !== '') {
            $where[] = 'e.user_id=?';
            $params[] = (int)$filters['user_id'];
        }
        foreach (['category_id', 'account_id'] as $key) {
            if (($filters[$key] ?? '') !== '') {
                $where[] = 'e.'.$key.'=?';
                $params[] = $filters[$key];
            }
        }
        if (!empty($filters['q'])) {
            $where[] = '(e.description LIKE ? OR e.notes LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';
            $params[] = '%'.$filters['q'].'%';
            $params[] = '%'.$filters['q'].'%';
            $params[] = '%'.$filters['q'].'%';
            $params[] = '%'.$filters['q'].'%';
        }
        if (!empty($filters['from'])) {
            $where[] = 'e.expense_date>=?';
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'e.expense_date<=?';
            $params[] = $filters['to'];
        }
        return [$where, $params];
    }

    private function insights(array $rows, float $total): array
    {
        if (!$rows) return [];
        $byCategory = [];
        $byAccount = [];
        foreach ($rows as $row) {
            $category = $row['category'] ?? 'Uncategorized';
            $account = $row['account'] ?? 'No account';
            $byCategory[$category] = ($byCategory[$category] ?? 0) + (float)$row['amount'];
            $byAccount[$account] = ($byAccount[$account] ?? 0) + (float)$row['amount'];
        }
        arsort($byCategory);
        arsort($byAccount);
        $topCategory = (string)array_key_first($byCategory);
        $topAccount = (string)array_key_first($byAccount);
        return [
            ['label' => 'Top category', 'value' => $topCategory.' ('.number_format(($byCategory[$topCategory] / max($total, 0.01)) * 100, 1).'%)'],
            ['label' => 'Top account', 'value' => $topAccount.' ('.number_format(($byAccount[$topAccount] / max($total, 0.01)) * 100, 1).'%)'],
        ];
    }
}
