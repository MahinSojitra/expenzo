<?php
declare(strict_types=1);
namespace App\Repositories; use App\Core\Database;
final class BudgetRepository {
 public function allForUser(int $userId):array{$sql='SELECT b.*,c.name category_name,(SELECT COALESCE(SUM(e.amount),0) FROM expenses e WHERE e.user_id=b.user_id AND e.expense_date BETWEEN b.start_date AND b.end_date AND e.status="posted" AND (b.category_id IS NULL OR e.category_id=b.category_id)) spent FROM budgets b LEFT JOIN categories c ON c.id=b.category_id WHERE b.user_id=? ORDER BY b.start_date DESC';$s=Database::connection()->prepare($sql);$s->execute([$userId]);return $s->fetchAll();}
 public function allVisible(array $actor):array{if(in_array($actor['role_name']??'', ['Super Admin','Admin'], true)){return Database::connection()->query('SELECT b.*,u.name user_name,c.name category_name,(SELECT COALESCE(SUM(e.amount),0) FROM expenses e WHERE e.user_id=b.user_id AND e.expense_date BETWEEN b.start_date AND b.end_date AND e.status="posted" AND (b.category_id IS NULL OR e.category_id=b.category_id)) spent FROM budgets b JOIN users u ON u.id=b.user_id LEFT JOIN categories c ON c.id=b.category_id ORDER BY b.start_date DESC')->fetchAll();}return $this->allForUser((int)$actor['id']);}
 public function create(array $d,int $userId):int{$s=Database::connection()->prepare('INSERT INTO budgets(user_id,category_id,start_date,end_date,budget_amount,warning_threshold,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,NOW(),NOW())');$s->execute([$userId,($d['category_id']??'')!==''?(int)$d['category_id']:null,$d['start_date'],$d['end_date'],round((float)$d['budget_amount'],2),round((float)($d['warning_threshold']??80),2),$d['status']??'active']);return (int)Database::connection()->lastInsertId();}
 public function delete(int $id,int $userId):void{$s=Database::connection()->prepare('DELETE FROM budgets WHERE id=? AND user_id=?');$s->execute([$id,$userId]);if($s->rowCount()<1)throw new \RuntimeException('Budget not found or not owned by you.');}

    public function findForUser(int $id, int $userId): ?array
    {
        $s = Database::connection()->prepare('SELECT * FROM budgets WHERE id=? AND user_id=?');
        $s->execute([$id, $userId]);
        return $s->fetch() ?: null;
    }

    public function update(int $id, array $d, int $userId): void
    {
        $s = Database::connection()->prepare('UPDATE budgets SET category_id=?,start_date=?,end_date=?,budget_amount=?,warning_threshold=?,status=?,updated_at=NOW() WHERE id=? AND user_id=?');
        $s->execute([
            ($d['category_id'] ?? '') !== '' ? (int)$d['category_id'] : null,
            $d['start_date'], $d['end_date'], round((float)$d['budget_amount'], 2),
            round((float)$d['warning_threshold'], 2), $d['status'], $id, $userId,
        ]);
    }

}
