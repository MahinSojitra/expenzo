<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class DashboardService
{
    public function stats(array $actor): array
    {
        $pdo = Database::connection();
        $admin = Authorization::allows($actor, 'finance.view_all');

        if ($admin) {
            $totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $activeUsers = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE status="active"')->fetchColumn();
            $total = (float) $pdo->query('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE status="posted"')->fetchColumn();
            $month = (float) $pdo->query('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE status="posted" AND DATE_FORMAT(expense_date,"%Y-%m")=DATE_FORMAT(CURDATE(),"%Y-%m")')->fetchColumn();
            $categories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
            $accounts = (int) $pdo->query('SELECT COUNT(*) FROM accounts')->fetchColumn();
            $budgetAlerts = $this->budgetAlerts(null);

            return compact('totalUsers', 'activeUsers', 'total', 'month', 'categories', 'accounts') + [
                'admin' => true,
                'byCategory' => [],
                'monthly' => [],
                'budgetAlerts' => $budgetAlerts,
            ];
        }

        $userId = (int) $actor['id'];
        $q = function (string $sql, array $params = []) use ($pdo) {
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            return $statement->fetchColumn();
        };

        $total = (float) $q('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=? AND status="posted"', [$userId]);
        $month = (float) $q('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=? AND status="posted" AND DATE_FORMAT(expense_date,"%Y-%m")=DATE_FORMAT(CURDATE(),"%Y-%m")', [$userId]);
        $today = (float) $q('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=? AND status="posted" AND expense_date=CURDATE()', [$userId]);
        $budget = (float) $q('SELECT COALESCE(SUM(budget_amount),0) FROM budgets WHERE user_id=? AND status="active" AND start_date<=CURDATE() AND end_date>=CURDATE()', [$userId]);
        $balance = (float) $q('SELECT COALESCE(SUM(current_balance),0) FROM accounts WHERE user_id=? AND status="active"', [$userId]);

        $byCat = $pdo->prepare('SELECT c.name label,COALESCE(SUM(e.amount),0) value FROM expenses e JOIN categories c ON c.id=e.category_id WHERE e.user_id=? AND e.status="posted" GROUP BY c.id ORDER BY value DESC LIMIT 8');
        $byCat->execute([$userId]);

        $monthly = $pdo->prepare('SELECT DATE_FORMAT(expense_date,"%b") label,ROUND(SUM(amount),2) value FROM expenses WHERE user_id=? AND status="posted" AND expense_date>=DATE_SUB(CURDATE(),INTERVAL 11 MONTH) GROUP BY YEAR(expense_date),MONTH(expense_date) ORDER BY YEAR(expense_date),MONTH(expense_date)');
        $monthly->execute([$userId]);

        $budgetAlerts = $this->budgetAlerts($userId);

        return compact('total', 'month', 'today', 'budget', 'balance') + [
            'admin' => false,
            'remaining' => $budget - $month,
            'byCategory' => $byCat->fetchAll(),
            'monthly' => $monthly->fetchAll(),
            'budgetAlerts' => $budgetAlerts,
        ];
    }

    private function budgetAlerts(?int $userId): array
    {
        $sql = 'SELECT b.id,b.user_id,b.category_id,b.start_date,b.end_date,b.budget_amount,b.warning_threshold,u.name user_name,c.name category_name,
            (SELECT COALESCE(SUM(e.amount),0)
             FROM expenses e
             WHERE e.user_id=b.user_id
               AND e.expense_date BETWEEN b.start_date AND b.end_date
               AND e.status="posted"
               AND (b.category_id IS NULL OR e.category_id=b.category_id)) spent
            FROM budgets b
            JOIN users u ON u.id=b.user_id
            LEFT JOIN categories c ON c.id=b.category_id
            WHERE b.status="active" AND b.start_date<=CURDATE() AND b.end_date>=CURDATE()';

        $params = [];
        if ($userId !== null) {
            $sql .= ' AND b.user_id=?';
            $params[] = $userId;
        }

        $sql .= '
            ORDER BY b.end_date ASC';

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        $alerts = [];
        foreach ($statement->fetchAll() as $budget) {
            $amount = (float) $budget['budget_amount'];
            if ($amount <= 0) {
                continue;
            }

            $spent = (float) $budget['spent'];
            $percent = ($spent / $amount) * 100;
            $threshold = (float) ($budget['warning_threshold'] ?? 80);

            if ($percent < $threshold) {
                continue;
            }

            $alerts[] = [
                'id' => (int) $budget['id'],
                'user' => $budget['user_name'] ?? '',
                'category' => $budget['category_name'] ?: 'All categories',
                'spent' => $spent,
                'amount' => $amount,
                'remaining' => $amount - $spent,
                'percent' => $percent,
                'threshold' => $threshold,
                'limitReached' => $percent >= 100,
            ];
        }

        return $alerts;
    }
}
