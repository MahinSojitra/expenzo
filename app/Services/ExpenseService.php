<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Repositories\ExpenseRepository;
use PDO;

final class ExpenseService
{
    public function create(array $data, array $actor): int
    {
        $data = $this->validated($data, $actor);
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $accounts = $this->lockAccounts($pdo, [(int)$data['account_id']], (int)$actor['id']);
            $this->assertRefs($pdo, $data, $accounts);
            $this->changeBalances($pdo, $accounts, $this->deltas(null, $data));
            $id = (new ExpenseRepository())->create($data);
            $this->audit($pdo, (int)$actor['id'], 'create', $id, null, $data);
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data, array $actor): void
    {
        $data = $this->validated($data, $actor);
        $uid = (int)$actor['id'];
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $old = $this->lockExpense($pdo, $id, $uid);
            $accounts = $this->lockAccounts($pdo, [(int)$old['account_id'], (int)$data['account_id']], $uid);
            $this->assertRefs($pdo, $data, $accounts);
            $this->changeBalances($pdo, $accounts, $this->deltas($old, $data));
            (new ExpenseRepository())->updateOwned($id, $data, $uid);
            $this->audit($pdo, $uid, 'update', $id, $old, $data);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $id, array $actor): void
    {
        $uid = (int)$actor['id'];
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $old = $this->lockExpense($pdo, $id, $uid);
            $accounts = $this->lockAccounts($pdo, [(int)$old['account_id']], $uid);
            $this->changeBalances($pdo, $accounts, $this->deltas($old, null));
            (new ExpenseRepository())->deleteOwned($id, $uid);
            $this->audit($pdo, $uid, 'delete', $id, $old, null);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function validated(array $data, array $actor): array
    {
        $data['user_id'] = (int)$actor['id'];
        $data['amount'] = Amount::decimal(Amount::cents($data['amount'] ?? null));
        $data['status'] = $data['status'] ?? 'posted';
        if (!in_array($data['status'], ['posted', 'draft', 'void'], true)) {
            throw new FieldValidationException('status', 'Choose a valid expense status.');
        }
        return $data;
    }

    private function lockExpense(PDO $pdo, int $id, int $uid): array
    {
        $statement = $pdo->prepare('SELECT * FROM expenses WHERE id=? AND user_id=? FOR UPDATE');
        $statement->execute([$id, $uid]);
        $expense = $statement->fetch();
        if (!$expense) throw new \RuntimeException('Expense not found or not owned by you.');
        return $expense;
    }

    private function lockAccounts(PDO $pdo, array $ids, int $uid): array
    {
        $ids = array_unique($ids);
        sort($ids, SORT_NUMERIC);
        $accounts = [];
        $statement = $pdo->prepare('SELECT * FROM accounts WHERE id=? AND user_id=? FOR UPDATE');
        foreach ($ids as $id) {
            $statement->execute([$id, $uid]);
            $row = $statement->fetch();
            if (!$row) throw new FieldValidationException('account_id', 'Choose an account owned by you.');
            $accounts[$id] = $row;
        }
        return $accounts;
    }

    private function assertRefs(PDO $pdo, array $data, array $accounts): void
    {
        if ($accounts[(int)$data['account_id']]['status'] !== 'active') {
            throw new FieldValidationException('account_id', 'Choose an active account owned by you.');
        }
        $statement = $pdo->prepare('SELECT id FROM categories WHERE id=? AND status="active" AND (owner_id IS NULL OR owner_id=?) FOR UPDATE');
        $statement->execute([(int)$data['category_id'], (int)$data['user_id']]);
        if (!$statement->fetchColumn()) throw new FieldValidationException('category_id', 'Choose an active category available to you.');
    }

    private function deltas(?array $old, ?array $new): array
    {
        $deltas = [];
        if ($old && $old['status'] === 'posted') {
            $deltas[(int)$old['account_id']] = Amount::cents($old['amount']);
        }
        if ($new && $new['status'] === 'posted') {
            $id = (int)$new['account_id'];
            $deltas[$id] = ($deltas[$id] ?? 0) - Amount::cents($new['amount']);
        }
        return $deltas;
    }

    private function changeBalances(PDO $pdo, array $accounts, array $deltas): void
    {
        $statement = $pdo->prepare('UPDATE accounts SET current_balance=?,updated_at=NOW() WHERE id=? AND user_id=?');
        foreach ($deltas as $id => $delta) {
            if ($delta === 0) continue;
            $account = $accounts[$id];
            $current = Amount::storedCents((string)$account['current_balance']);
            $next = $current + $delta;
            // Existing negative balances may be improved, but never reduced further.
            if ($delta < 0 && $next < 0) {
                throw new FieldValidationException('account_id', 'Insufficient balance in '.$account['name'].'. Available: '.money(Amount::decimal($current)).'. Choose another account or reduce the amount.');
            }
            if ($next > Amount::MAX_CENTS) {
                throw new FieldValidationException('account_id', 'This change would exceed the account balance limit.');
            }
            $statement->execute([Amount::decimal($next), $id, $account['user_id']]);
        }
    }

    private function audit(PDO $pdo, int $uid, string $action, int $id, ?array $old, ?array $new): void
    {
        $statement = $pdo->prepare('INSERT INTO audit_logs(user_id,action,entity,entity_id,old_values,new_values,ip_address,user_agent,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())');
        $statement->execute([$uid, $action, 'expenses', $id, $old ? json_encode($old) : null, $new ? json_encode($new) : null, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
    }
}
