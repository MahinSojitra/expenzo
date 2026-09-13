<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Response;
use App\Core\Session;
use App\Core\View;

trait CrudForm
{
    private function missing(?array $record): array
    {
        if (!$record) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }
        return $record;
    }

    private function forbidden(): never
    {
        http_response_code(403);
        View::render('errors/403');
        exit;
    }

    private function saved(string $module, string $message): never
    {
        Session::flash('success', $message);
        Response::redirect('/' . $module);
    }

    private function persistenceError(\PDOException $e, string $field): array
    {
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return [$field => 'This value is already in use. Please choose another.'];
        }
        throw $e;
    }

    private function removeRecord(string $module, callable $remove, string $message): never
    {
        try {
            $remove();
            Session::flash('success', $message);
        } catch (\PDOException $e) {
            if (($e->errorInfo[1] ?? 0) !== 1451) {
                throw $e;
            }
            Session::flash('error', 'This record is in use and cannot be deleted. You can deactivate it instead.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect('/' . $module);
    }
}