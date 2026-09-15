<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Middleware\PermissionMiddleware;
use App\Repositories\CategoryRepository;
use App\Services\Authorization;
use App\Services\CrudValidation;
use App\Services\FieldValidationException;

final class CategoryController
{
    use CrudForm;

    private function target(int $id): array
    {
        $record = $this->missing((new CategoryRepository())->find($id));
        if (!Authorization::categoryVisible(auth_user(), $record)) return $this->missing(null);
        if (!Authorization::categoryManageable(auth_user(), $record)) $this->forbidden();
        return $record;
    }

    public function index(Request $r): void
    {
        PermissionMiddleware::require('categories.view');
        $repo = new CategoryRepository();
        $filters = ['scope' => (string)$r->query('scope', ''), 'owner_id' => (string)$r->query('owner_id', ''), 'status' => (string)$r->query('status', '')];
        $page = (int)$r->query('page', 1);
        $data = can('categories.view_all') ? $repo->all($filters, $page) : $repo->allForUser((int)Session::get('user_id'), false, $page);
        $rows = $data['rows'];
        $owners = can('categories.view_all') ? $repo->owners() : [];
        View::render('categories/index', compact('rows', 'filters', 'owners', 'data'));
    }

    public function create(Request $r): void
    {
        PermissionMiddleware::require('categories.create');
        $this->form();
    }

    public function edit(Request $r, string $id): void
    {
        PermissionMiddleware::require('categories.edit');
        $this->form($this->target((int)$id));
    }

    private function form(array $record = [], array $errors = []): void
    {
        if ($errors) http_response_code(422);
        $ownerOptions = isset($record['id']) ? [] : (new CategoryRepository())->ownerOptions(auth_user());
        View::render('categories/'.(isset($record['id']) ? 'edit' : 'create'), compact('record', 'errors', 'ownerOptions'));
    }

    public function store(Request $r): void
    {
        PermissionMiddleware::require('categories.create');
        verify_csrf();
        $errors = CrudValidation::validate('categories', $r->all());
        if (!$errors) {
            try {
                (new CategoryRepository())->create($r->all(), auth_user());
                $this->saved('categories', 'Category created successfully.');
            } catch (FieldValidationException $e) {
                $errors[$e->field] = $e->getMessage();
            } catch (\PDOException $e) {
                $errors = $this->persistenceError($e, 'name');
            }
        }
        $this->form([], $errors);
    }

    public function update(Request $r, string $id): void
    {
        PermissionMiddleware::require('categories.edit');
        verify_csrf();
        $record = $this->target((int)$id);
        $errors = CrudValidation::validate('categories', $r->all());
        if (!$errors) {
            try {
                (new CategoryRepository())->update((int)$id, $r->all(), auth_user());
                $this->saved('categories', 'Category updated successfully.');
            } catch (FieldValidationException $e) {
                $errors[$e->field] = $e->getMessage();
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
        PermissionMiddleware::require('categories.delete');
        verify_csrf();
        $this->target((int)$id);
        $this->removeRecord('categories', fn() => (new CategoryRepository())->delete((int)$id, auth_user()), 'Category deleted successfully.');
    }
}
