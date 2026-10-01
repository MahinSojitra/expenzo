<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Pagination;
use App\Services\Authorization;

final class TransactionRepository
{
    public function findVisible(int $id, array $actor): ?array
    {
        $sql = "SELECT t.*, a.name account_name,a.type account_type,d.name destination_name,d.type destination_type,u.name user_name
            FROM account_transactions t JOIN accounts a ON a.id=t.account_id
            LEFT JOIN accounts d ON d.id=t.destination_account_id JOIN users u ON u.id=t.user_id WHERE t.id=?";
        $params = [$id];
        if (!Authorization::allows($actor, 'finance.view_all')) { $sql .= ' AND t.user_id=?'; $params[]=$actor['id']; }
        $s = \App\Core\Database::connection()->prepare($sql);
        $s->execute($params);
        $row = $s->fetch();
        if (!$row) return null;
        $s = \App\Core\Database::connection()->prepare("SELECT l.action,l.created_at,u.name actor_name FROM audit_logs l LEFT JOIN users u ON u.id=l.user_id WHERE l.entity='account_transactions' AND l.entity_id=? ORDER BY l.id");
        $s->execute([$id]);
        $row['activity'] = $s->fetchAll();
        return $row;
    }

    public function postingAccounts(array $actor): array
    {
        $sql = "SELECT a.*,a.current_balance balance,u.name user_name FROM accounts a JOIN users u ON u.id=a.user_id WHERE a.status='active'";
        $params = [];
        if (!Authorization::allows($actor,'transactions.create_all')) { $sql .= ' AND a.user_id=?'; $params[]=$actor['id']; }
        return Pagination::query($sql.' ORDER BY u.name,a.name,a.id', $params);
    }

    public function history(array $actor, int $accountId, int $page, array $filters = []): array
    {
        $sql = "SELECT t.*,a.name account_name,a.type account_type,d.name destination_name,d.type destination_type,u.name user_name FROM (
            SELECT id,user_id,type,account_id,destination_account_id,amount,account_delta,transaction_date,description,
                IF(reversed_at IS NULL,'posted','reversed') status,reversal_reason,created_at,'transaction' source
            FROM account_transactions
            UNION ALL
            SELECT id,user_id,'expense',account_id,NULL,amount,-amount,expense_date,description,status,NULL,created_at,'expense'
            FROM expenses
        ) t JOIN accounts a ON a.id=t.account_id LEFT JOIN accounts d ON d.id=t.destination_account_id JOIN users u ON u.id=t.user_id WHERE 1=1";
        $params = [];
        if (!Authorization::allows($actor, 'finance.view_all')) { $sql .= ' AND t.user_id=?'; $params[] = $actor['id']; }
        if ($accountId > 0) { $sql .= ' AND (t.account_id=? OR t.destination_account_id=?)'; $params[]=$accountId; $params[]=$accountId; }
        if (($filters['q'] ?? '') !== '') {
            $sql .= " AND (t.description LIKE ? ESCAPE '!' OR t.reversal_reason LIKE ? ESCAPE '!')";
            $search = '%'.strtr($filters['q'], ['!'=>'!!', '%'=>'!%', '_'=>'!_']).'%';
            $params[] = $search;
            $params[] = $search;
        }
        foreach (['type', 'status'] as $key) {
            if (($filters[$key] ?? '') !== '') { $sql .= ' AND t.'.$key.'=?'; $params[] = $filters[$key]; }
        }
        if (($filters['from'] ?? '') !== '') { $sql .= ' AND t.transaction_date>=?'; $params[] = $filters['from']; }
        if (($filters['to'] ?? '') !== '') { $sql .= ' AND t.transaction_date<=?'; $params[] = $filters['to']; }
        return Pagination::query($sql.' ORDER BY t.transaction_date DESC,t.created_at DESC,t.source,t.id DESC', $params, $page);
    }
}