<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Middleware\PermissionMiddleware;
use App\Repositories\AccountRepository;
use App\Repositories\TransactionRepository;
use App\Services\FieldValidationException;
use App\Services\TransactionService;

final class TransactionController
{
    use CrudForm;

    public function index(Request $r): void
    {
        PermissionMiddleware::require('transactions.view');
        $accountId = max(0, (int)$r->query('account_id', 0));
        $filters = [];
        $errors = [];
        foreach (['q', 'type', 'status', 'from', 'to'] as $key) {
            $value = $r->query($key, '');
            $filters[$key] = is_string($value) ? trim($value) : '';
            if (!is_string($value)) $errors[$key] = 'Enter a valid filter value.';
        }
        if (mb_strlen($filters['q']) > 255) $errors['q'] = 'Use 255 characters or fewer.';
        foreach (['type'=>['income','transfer','adjustment','expense'], 'status'=>['posted','reversed','draft','void']] as $key=>$allowed) {
            if ($filters[$key] !== '' && !in_array($filters[$key], $allowed, true)) $errors[$key] = 'Choose a valid '.$key.'.';
        }
        foreach (['from', 'to'] as $key) {
            if ($filters[$key] === '') continue;
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $filters[$key]);
            if (!$date || $date->format('Y-m-d') !== $filters[$key] || $filters[$key] < '1000-01-01') $errors[$key] = 'Enter a valid date.';
        }
        if (!$errors && $filters['from'] !== '' && $filters['to'] !== '' && $filters['from'] > $filters['to']) $errors['to'] = 'End date must be on or after start date.';
        if ($errors) {
            http_response_code(422);
            $data = ['rows'=>[], 'total'=>0, 'page'=>1, 'per'=>pagination_size()];
        } else {
            $data = (new TransactionRepository())->history(auth_user(), $accountId, (int)$r->query('page', 1), $filters);
        }
        $accounts = (new AccountRepository())->allVisible(auth_user());
        View::render('transactions/index', compact('data', 'accounts', 'accountId', 'filters', 'errors'));
    }

    public function create(Request $r): void
    {
        $type = $r->query('type', 'income');
        PermissionMiddleware::require($type === 'adjustment' ? 'transactions.adjust' : 'transactions.create');
        $this->form(['type'=>$type, 'account_id'=>(int)$r->query('account_id', 0)]);
    }

    private function form(array $record, array $errors = []): void
    {
        if ($errors) http_response_code(422);
        $accounts = (new TransactionRepository())->postingAccounts(auth_user());
        $submissionToken = bin2hex(random_bytes(32));
        View::render('transactions/form', compact('record', 'errors', 'accounts', 'submissionToken'));
    }

    public function store(Request $r): void
    {
        PermissionMiddleware::require($r->input('type') === 'adjustment' ? 'transactions.adjust' : 'transactions.create');
        verify_csrf();
        try {
            (new TransactionService())->post($_POST, auth_user());
            $this->saved(can('transactions.view') ? 'transactions' : ltrim(landing_path(), '/'), 'Transaction recorded successfully.');
        } catch (FieldValidationException $e) { $this->form([], [$e->field=>$e->getMessage()]); }
    }

    public function show(Request $r, string $id): void
    {
        PermissionMiddleware::require('transactions.view');
        $this->details((int)$id);
    }

    private function details(int $id, array $errors = []): void
    {
        $transaction = $this->missing((new TransactionRepository())->findVisible($id, auth_user()));
        $mayReverse = can('transactions.reverse')
            && ((int)$transaction['user_id'] === (int)auth_user()['id'] || can('transactions.reverse_all'))
            && ($transaction['type'] !== 'adjustment' || can('transactions.adjust'))
            && $transaction['reversed_at'] === null;
        if ($errors) http_response_code(422);
        View::render('transactions/show', compact('transaction','mayReverse','errors'));
    }

    public function reverse(Request $r, string $id): void
    {
        PermissionMiddleware::require('transactions.reverse');
        PermissionMiddleware::require('transactions.view');
        verify_csrf();
        $transaction = $this->missing((new TransactionRepository())->findVisible((int)$id, auth_user()));
        if (((int)$transaction['user_id'] !== (int)auth_user()['id'] && !can('transactions.reverse_all'))
            || ($transaction['type'] === 'adjustment' && !can('transactions.adjust'))) $this->forbidden();
        try {
            (new TransactionService())->reverse((int)$id, $r->input('reason'), auth_user());
            $this->saved('transactions/'.$id, 'Transaction reversed. The original record is preserved.');
        } catch (FieldValidationException $e) {
            $this->details((int)$id, ['reason'=>$e->getMessage()]);
        }
    }
}