<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use PDO;

final class TransactionService
{
    public function post(array $data, array $actor): int
    {
        $type = $data['type'] ?? '';
        if (!in_array($type, ['income', 'transfer', 'adjustment'], true)) throw new FieldValidationException('type', 'Choose a valid transaction type.');
        $this->authorize($actor, $type === 'adjustment' ? 'transactions.adjust' : 'transactions.create');
        $amount = Amount::cents($data['amount'] ?? null);
        $account = $this->id($data['account_id'] ?? null, 'account_id');
        $destination = $type === 'transfer' ? $this->id($data['destination_account_id'] ?? null, 'destination_account_id') : null;
        if ($destination === $account) throw new FieldValidationException('destination_account_id', 'Choose a different destination account.');
        $direction = $data['direction'] ?? 'increase';
        if ($type === 'adjustment' && !in_array($direction, ['increase', 'decrease'], true)) throw new FieldValidationException('direction', 'Choose increase or decrease.');
        $date = is_string($data['transaction_date'] ?? null) ? $data['transaction_date'] : '';
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date || $date < '1000-01-01' || $date > date('Y-m-d')) throw new FieldValidationException('transaction_date', 'Enter a valid date that is not in the future.');
        $description = $this->text($data['description'] ?? null, 'description', 255);
        $token = $data['submission_token'] ?? '';
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) throw new FieldValidationException('description', 'Reload the form and try again.');
        $uid = (int)$actor['id'];
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            if (Authorization::allows($actor, 'transactions.create_all')) {
                $owner = $pdo->prepare('SELECT user_id FROM accounts WHERE id=?');
                $owner->execute([$account]);
                $uid = (int)$owner->fetchColumn();
            }
            $accounts = $this->lockAccounts($pdo, array_filter([$account, $destination]), $uid, true);
            $existing = $pdo->prepare('SELECT id FROM account_transactions WHERE user_id=? AND submission_token=?');
            $existing->execute([$uid, $token]);
            if ($id = $existing->fetchColumn()) { $pdo->commit(); return (int)$id; }
            $delta = ($type === 'transfer' || ($type === 'adjustment' && $direction === 'decrease')) ? -$amount : $amount;
            $deltas = [$account => $delta];
            if ($destination) $deltas[$destination] = $amount;
            $this->balances($pdo, $accounts, $deltas);
            $row = ['user_id'=>$uid, 'type'=>$type, 'account_id'=>$account, 'destination_account_id'=>$destination, 'amount'=>Amount::decimal($amount), 'account_delta'=>Amount::decimal($delta), 'transaction_date'=>$date, 'description'=>$description, 'submission_token'=>$token];
            $statement = $pdo->prepare('INSERT INTO account_transactions(user_id,type,account_id,destination_account_id,amount,account_delta,transaction_date,description,submission_token) VALUES(?,?,?,?,?,?,?,?,?)');
            $statement->execute(array_values($row));
            $id = (int)$pdo->lastInsertId();
            AuditService::record((int)$actor['id'], 'create', 'account_transactions', $id, null, $row);
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) { $pdo->rollBack(); throw $e; }
    }

    public function reverse(int $id, mixed $reason, array $actor): void
    {
        $this->authorize($actor, 'transactions.reverse');
        $reason = $this->text($reason, 'reason', 255);
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $s = $pdo->prepare('SELECT * FROM account_transactions WHERE id=? FOR UPDATE');
            $s->execute([$id]);
            $row = $s->fetch();
            if (!$row || ((int)$row['user_id'] !== (int)$actor['id'] && !Authorization::allows($actor, 'transactions.reverse_all'))) throw new FieldValidationException('reason', 'Transaction not found or not owned by you.');
            if ($row['type'] === 'adjustment') $this->authorize($actor, 'transactions.adjust');
            if ($row['reversed_at'] !== null) throw new FieldValidationException('reason', 'This transaction has already been reversed.');
            $accounts = $this->lockAccounts($pdo, array_filter([$row['account_id'], $row['destination_account_id']]), (int)$row['user_id'], false);
            $deltas = [(int)$row['account_id'] => -Amount::storedCents($row['account_delta'])];
            if ($row['destination_account_id']) $deltas[(int)$row['destination_account_id']] = -Amount::cents($row['amount']);
            $this->balances($pdo, $accounts, $deltas);
            $pdo->prepare('UPDATE account_transactions SET reversed_at=NOW(),reversal_reason=? WHERE id=?')->execute([$reason, $id]);
            AuditService::record((int)$actor['id'], 'reverse', 'account_transactions', $id, $row, ['reversal_reason'=>$reason]);
            $pdo->commit();
        } catch (\Throwable $e) { $pdo->rollBack(); throw $e; }
    }

    private function authorize(array $actor, string $permission): void
    {
        if (!Authorization::allows($actor, $permission)) throw new FieldValidationException('type', 'You do not have permission for this action.');
    }

    private function id(mixed $value, string $field): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($id === false) throw new FieldValidationException($field, 'Choose an account.');
        return $id;
    }

    private function text(mixed $value, string $field, int $max): string
    {
        if (!is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > $max) throw new FieldValidationException($field, "Enter between 1 and $max characters.");
        return trim($value);
    }

    private function lockAccounts(PDO $pdo, array $ids, int $uid, bool $active): array
    {
        $ids = array_unique($ids);
        sort($ids, SORT_NUMERIC);
        $accounts = [];
        $s = $pdo->prepare('SELECT * FROM accounts WHERE id=? AND user_id=? FOR UPDATE');
        foreach ($ids as $id) {
            $s->execute([$id, $uid]);
            $row = $s->fetch();
            if (!$row || ($active && $row['status'] !== 'active')) throw new FieldValidationException('account_id', 'Choose active accounts belonging to the same permitted owner.');
            $accounts[(int)$id] = $row;
        }
        return $accounts;
    }

    private function balances(PDO $pdo, array $accounts, array $deltas): void
    {
        $s = $pdo->prepare('UPDATE accounts SET current_balance=?,updated_at=NOW() WHERE id=? AND user_id=?');
        foreach ($deltas as $id => $delta) {
            $next = Amount::storedCents($accounts[$id]['current_balance']) + $delta;
            if ($delta < 0 && $next < 0) throw new FieldValidationException('amount', 'Insufficient balance in '.$accounts[$id]['name'].'.');
            if ($next > Amount::MAX_CENTS || $next < -Amount::MAX_CENTS) throw new FieldValidationException('amount', 'The account balance limit would be exceeded.');
            $s->execute([Amount::decimal($next), $id, $accounts[$id]['user_id']]);
        }
    }
}