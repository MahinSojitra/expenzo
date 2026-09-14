<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Core\Response;
use App\Core\Database;
use App\Core\Session;
use App\Middleware\PermissionMiddleware;

final class SettingsController
{
    private function settings(): array
    {
        return Database::connection()->query('SELECT setting_key,setting_value FROM settings')->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function index(Request $r): void
    {
        PermissionMiddleware::require('settings.view');
        View::render('settings/index', ['settings' => $this->settings()]);
    }

    public function save(Request $r): void
    {
        PermissionMiddleware::require('settings.edit');
        verify_csrf();
        $size = filter_var($r->input('pagination_size'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
        if ($size === false) {
            http_response_code(422);
            View::render('settings/index', [
                'settings' => $this->settings(),
                'errors' => ['pagination_size' => 'Enter a whole number between 1 and 100.'],
            ]);
            return;
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare('INSERT INTO settings(setting_key,setting_value,updated_at) VALUES(?,?,NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=NOW()');
            foreach (['app_name','currency','date_format','timezone','pagination_size','budget_warning_thresholds'] as $key) {
                if ($r->input($key) !== null) $statement->execute([$key, $key === 'pagination_size' ? $size : $r->input($key)]);
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
        Session::flash('success', 'Settings saved.');
        Response::redirect('/settings');
    }
}
