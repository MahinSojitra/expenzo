<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Middleware\PermissionMiddleware;
use App\Repositories\BudgetRepository;
use App\Repositories\CategoryRepository;
use App\Services\CrudValidation;

final class BudgetController
{
    use CrudForm;

    public function index(Request $r): void
    {
        PermissionMiddleware::require('budgets.view');
        $data = (new BudgetRepository())->allVisible(auth_user(), (int)$r->query('page', 1));
        $rows = $data['rows'];
        View::render('budgets/index', compact('rows', 'data'));
    }

    public function create(Request $r): void
    {
        PermissionMiddleware::require('budgets.create');
        $this->form();
    }

    public function edit(Request $r, string $id): void
    {
        PermissionMiddleware::require('budgets.edit');
        $record = $this->missing((new BudgetRepository())->findForUser((int)$id, (int)Session::get('user_id')));
        $this->form($record);
    }

    private function form(array $record = [], array $errors = []): void
    {
        if ($errors) http_response_code(422);
        $categories = (new CategoryRepository())->allForUser((int)Session::get('user_id'));
        View::render('budgets/' . (isset($record['id']) ? 'edit' : 'create'), compact('record', 'errors', 'categories'));
    }

    private function validate(array $data, bool $editing): array
    {
        $errors = CrudValidation::validate('budgets', $data, $editing);
        if (($data['category_id'] ?? '') !== '') {
            $category = (new CategoryRepository())->findForUser((int)$data['category_id'], (int)Session::get('user_id'));
            if (!$category || (string)$category['id'] !== (string)$data['category_id']) $errors['category_id'] = 'Choose a valid category.';
        }
        return $errors;
    }

    public function store(Request $r): void
    {
        PermissionMiddleware::require('budgets.create');
        verify_csrf();
        $data = $r->all();
        $errors = $this->validate($data, false);
        if (!$errors) {
            try {
                (new BudgetRepository())->create($data, (int)Session::get('user_id'));
                $this->saved('budgets', 'Budget created successfully.');
            } catch (\PDOException $e) {
                $errors = $this->persistenceError($e, 'name');
            }
        }
        $this->form([], $errors);
    }

    public function update(Request $r, string $id): void
    {
        PermissionMiddleware::require('budgets.edit');
        verify_csrf();
        $repo = new BudgetRepository();
        $uid = (int)Session::get('user_id');
        $record = $this->missing($repo->findForUser((int)$id, $uid));
        $data = $r->all();
        $errors = $this->validate($data, true);
        if (!$errors) {
            try {
                $repo->update((int)$id, $data, $uid);
                $this->saved('budgets', 'Budget updated successfully.');
            } catch (\PDOException $e) {
                $errors = $this->persistenceError($e, 'name');
            }
        }
        $this->form($record, $errors);
    }

    public function delete(Request $r, string $id): void
    {
        PermissionMiddleware::require('budgets.delete');
        verify_csrf();
        $repo = new BudgetRepository();
        $uid = (int)Session::get('user_id');
        $this->missing($repo->findForUser((int)$id, $uid));
        $this->removeRecord('budgets', fn() => $repo->delete((int)$id, $uid), 'Budget deleted successfully.');
    }
}
