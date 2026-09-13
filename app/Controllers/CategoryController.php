<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Middleware\PermissionMiddleware;
use App\Repositories\CategoryRepository;
use App\Services\CrudValidation;

final class CategoryController
{
    use CrudForm;

    private function manage(string $permission): void
    {
        PermissionMiddleware::require($permission);
        if (!is_adminish()) $this->forbidden();
    }

    public function index(Request $r): void
    {
        PermissionMiddleware::require('categories.view');
        $repo = new CategoryRepository();
        $rows = is_adminish() ? $repo->all() : $repo->allForUser((int)Session::get('user_id'));
        View::render('categories/index', compact('rows'));
    }

    public function create(Request $r): void
    {
        $this->manage('categories.create');
        $this->form();
    }

    public function edit(Request $r, string $id): void
    {
        $this->manage('categories.edit');
        $this->form($this->missing((new CategoryRepository())->find((int)$id)));
    }

    private function form(array $record = [], array $errors = []): void
    {
        if ($errors) http_response_code(422);
        View::render('categories/' . (isset($record['id']) ? 'edit' : 'create'), compact('record', 'errors'));
    }

    public function store(Request $r): void
    {
        $this->manage('categories.create');
        verify_csrf();
        $data = $r->all();
        $errors = CrudValidation::validate('categories', $data);
        if (!$errors) {
            try {
                (new CategoryRepository())->create($data, (int)Session::get('user_id'));
                $this->saved('categories', 'Category created successfully.');
            } catch (\PDOException $e) {
                $errors = $this->persistenceError($e, 'name');
            }
        }
        $this->form([], $errors);
    }

    public function update(Request $r, string $id): void
    {
        $this->manage('categories.edit');
        verify_csrf();
        $repo = new CategoryRepository();
        $record = $this->missing($repo->find((int)$id));
        $data = $r->all();
        $errors = CrudValidation::validate('categories', $data);
        if (!$errors) {
            try {
                $repo->update((int)$id, $data);
                $this->saved('categories', 'Category updated successfully.');
            } catch (\PDOException $e) {
                $errors = $this->persistenceError($e, 'name');
            }
        }
        $this->form($record, $errors);
    }

    private function appearanceRecord(int $id): array
    {
        foreach ((new CategoryRepository())->allForUser((int)Session::get('user_id')) as $record) {
            if ((int)$record['id'] === $id) return $record;
        }
        return $this->missing(null);
    }

    public function appearance(Request $r, string $id): void
    {
        PermissionMiddleware::require('categories.customize');
        $record = $this->appearanceRecord((int)$id);
        View::render('categories/appearance', ['record' => $record, 'errors' => []]);
    }

    public function saveAppearance(Request $r, string $id): void
    {
        PermissionMiddleware::require('categories.customize');
        verify_csrf();
        $record = $this->appearanceRecord((int)$id);
        $errors = CrudValidation::validate('appearance', $r->all());
        if ($errors) {
            http_response_code(422);
            View::render('categories/appearance', compact('record', 'errors'));
            return;
        }
        (new CategoryRepository())->customize((int)$id, (int)Session::get('user_id'), $r->all());
        $this->saved('categories', 'Category appearance updated successfully.');
    }

    public function delete(Request $r, string $id): void
    {
        $this->manage('categories.delete');
        verify_csrf();
        $repo = new CategoryRepository();
        $this->missing($repo->find((int)$id));
        $this->removeRecord('categories', fn() => $repo->delete((int)$id), 'Category deleted successfully.');
    }
}