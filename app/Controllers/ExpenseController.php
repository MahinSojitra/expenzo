<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Request;
use App\Core\View;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\PermissionMiddleware;
use App\Repositories\ExpenseRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\AccountRepository;
use App\Services\ExpenseService;
final class ExpenseController
{
    use CrudForm;
    private function common(array $extra = []): array
    {
        $uid = (int) Session::get('user_id');
        return ['categories' => (new CategoryRepository())->allForUser($uid, true), 'accounts' => (new AccountRepository())->allForUser($uid, true)] + $extra;
    }
    public function index(Request $r): void
    {
        PermissionMiddleware::require('expenses.view');
        $f = ['q' => (string) $r->query('q', ''), 'category_id' => (string) $r->query('category_id', ''), 'account_id' => (string) $r->query('account_id', ''), 'from' => (string) $r->query('from', ''), 'to' => (string) $r->query('to', ''), 'status' => (string) $r->query('status', '')];
        $data = (new ExpenseRepository())->paginate($f, auth_user(), max(1, (int) $r->query('page', 1)), pagination_size());
        View::render('expenses/index', $this->common(compact('data', 'f')));
    }
    public function create(Request $r): void
    {
        PermissionMiddleware::require('expenses.create');
        View::render('expenses/form', $this->common(['expense' => null, 'mode' => 'create']));
    }
    public function store(Request $r): void
    {
        PermissionMiddleware::require('expenses.create');
        verify_csrf();
        try {
            $d = $this->validate($r);
            $d['user_id'] = (int) Session::get('user_id');
            $d['receipt_path'] = $this->upload($r->file('receipt'));
            (new ExpenseService())->create($d, auth_user());
        } catch (\DomainException | \InvalidArgumentException | \RuntimeException $e) {
            http_response_code(422);
            View::render('expenses/form', $this->common(['expense' => null, 'mode' => 'create', 'error' => $e->getMessage()]));
            return;
        }
        Session::flash('success', 'Expense created successfully.');
        Response::redirect('/expenses');
    }
    public function show(Request $r, string $id): void
    {
        PermissionMiddleware::require('expenses.view');
        $expense = (new ExpenseRepository())->findVisible((int) $id, auth_user());
        if (!$expense) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        View::render('expenses/show', compact('expense'));
    }
    public function receipt(Request $r, string $id): void
    {
        PermissionMiddleware::require('expenses.view');
        $expense = (new ExpenseRepository())->findVisible((int) $id, auth_user());
        if (!$expense || empty($expense['receipt_path'])) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        $path = dirname(__DIR__, 2) . '/' . str_replace(['../', '..\\'], '', (string) $expense['receipt_path']);
        if (!is_file($path)) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
    public function edit(Request $r, string $id): void
    {
        PermissionMiddleware::require('expenses.edit');
        $expense = $this->missing((new ExpenseRepository())->findOwned((int) $id, (int) Session::get('user_id')));
        View::render('expenses/form', $this->common(compact('expense')) + ['mode' => 'edit']);
    }
    public function update(Request $r, string $id): void
    {
        PermissionMiddleware::require('expenses.edit');
        verify_csrf();
        $expense = $this->missing((new ExpenseRepository())->findOwned((int) $id, (int) Session::get('user_id')));
        try {
            $d = $this->validate($r);
            $d['user_id'] = (int) Session::get('user_id');
            $d['receipt_path'] = $this->upload($r->file('receipt'));
            (new ExpenseService())->update((int) $id, $d, auth_user());
        } catch (\DomainException | \InvalidArgumentException | \RuntimeException $e) {
            http_response_code(422);
            View::render('expenses/form', $this->common(['expense' => $expense, 'mode' => 'edit', 'error' => $e->getMessage()]));
            return;
        }
        Session::flash('success', 'Expense updated successfully.');
        Response::redirect('/expenses/' . $id);
    }
    public function delete(Request $r, string $id): void
    {
        PermissionMiddleware::require('expenses.delete');
        verify_csrf();
        $this->missing((new ExpenseRepository())->findOwned((int) $id, (int) Session::get('user_id')));
        (new ExpenseService())->delete((int) $id, auth_user());
        Session::flash('success', 'Expense deleted.');
        Response::redirect('/expenses');
    }
    private function validate(Request $r): array
    {
        $status = (string) $r->input('status', 'posted');
        if (!in_array($status, ['draft', 'posted', 'void'], true))
            $status = 'posted';
        $d = ['amount' => round((float) $r->input('amount'), 2), 'expense_date' => (string) $r->input('expense_date'), 'category_id' => (int) $r->input('category_id'), 'account_id' => (int) $r->input('account_id'), 'description' => trim((string) $r->input('description')), 'notes' => trim((string) $r->input('notes')), 'status' => $status];
        if ($d['amount'] <= 0 || $d['expense_date'] === '' || $d['category_id'] <= 0 || $d['account_id'] <= 0 || $d['description'] === '')
            throw new \InvalidArgumentException('Please provide all required expense fields.');
        return $d;
    }
    private function upload(?array $f): ?string
    {
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE)
            return null;
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5 * 1024 * 1024)
            throw new \RuntimeException('Invalid receipt upload.');
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!isset($allowed[$mime]))
            throw new \RuntimeException('Only JPG, PNG and PDF receipts are allowed.');
        $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $dir = dirname(__DIR__, 2) . '/storage/receipts';
        if (!is_dir($dir))
            mkdir($dir, 0750, true);
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name))
            throw new \RuntimeException('Unable to store receipt.');
        return 'storage/receipts/' . $name;
    }
}
