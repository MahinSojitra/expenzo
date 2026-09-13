<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Middleware\PermissionMiddleware;
use App\Repositories\AccountRepository;
use App\Services\CrudValidation;

final class AccountController
{
    use CrudForm;

    public function index(Request $r): void
    {
        PermissionMiddleware::require('accounts.view');
        $rows = (new AccountRepository())->allVisible(auth_user());
        View::render('accounts/index', compact('rows'));
    }

    public function create(Request $r): void
    {
        PermissionMiddleware::require('accounts.create');
        $this->form();
    }

    public function edit(Request $r, string $id): void
    {
        PermissionMiddleware::require('accounts.edit');
        $record = $this->missing((new AccountRepository())->findForUser((int)$id, (int)Session::get('user_id')));
        $this->form($record);
    }

    private function form(array $record = [], array $errors = []): void
    {
        if ($errors) http_response_code(422);

        View::render('accounts/' . (isset($record['id']) ? 'edit' : 'create'), compact('record', 'errors'));
    }

    private function validate(array $data, bool $editing): array
    {
        $errors = CrudValidation::validate('accounts', $data, $editing);

        return $errors;
    }

    public function store(Request $r): void
    {
        PermissionMiddleware::require('accounts.create');
        verify_csrf();
        $data = $r->all();
        $errors = $this->validate($data, false);
        if (!$errors) {
            try {
                (new AccountRepository())->create($data, (int)Session::get('user_id'));
                $this->saved('accounts', 'Account created successfully.');
            } catch (\PDOException $e) {
                $errors = $this->persistenceError($e, 'name');
            }
        }
        $this->form([], $errors);
    }

    public function update(Request $r, string $id): void
    {
        PermissionMiddleware::require('accounts.edit');
        verify_csrf();
        $repo = new AccountRepository();
        $uid = (int)Session::get('user_id');
        $record = $this->missing($repo->findForUser((int)$id, $uid));
        $data = $r->all();
        $errors = $this->validate($data, true);
        if (!$errors) {
            try {
                $repo->update((int)$id, $data, $uid);
                $this->saved('accounts', 'Account updated successfully.');
            } catch (\PDOException $e) {
                $errors = $this->persistenceError($e, 'name');
            }
        }
        $this->form($record, $errors);
    }

    public function delete(Request $r, string $id): void
    {
        PermissionMiddleware::require('accounts.delete');
        verify_csrf();
        $repo = new AccountRepository();
        $uid = (int)Session::get('user_id');
        $this->missing($repo->findForUser((int)$id, $uid));
        $this->removeRecord('accounts', fn() => $repo->delete((int)$id, $uid), 'Account deleted successfully.');
    }
}