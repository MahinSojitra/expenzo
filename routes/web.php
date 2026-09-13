<?php
declare(strict_types=1);

use App\Core\Router;

return static function (Router $r): void {
    $r->get('/register', 'App\Controllers\AuthController@showRegister');
    $r->post('/register', 'App\Controllers\AuthController@register');
    $r->get('/', 'App\Controllers\DashboardController@index');
    $r->get('/login', 'App\Controllers\AuthController@showLogin');
    $r->post('/login', 'App\Controllers\AuthController@login');
    $r->get('/logout', 'App\Controllers\AuthController@logout');
    $r->get('/dashboard', 'App\Controllers\DashboardController@index');

    $r->get('/expenses', 'App\Controllers\ExpenseController@index');
    $r->get('/expenses/create', 'App\Controllers\ExpenseController@create');
    $r->post('/expenses', 'App\Controllers\ExpenseController@store');
    $r->get('/expenses/{id}', 'App\Controllers\ExpenseController@show');
    $r->get('/expenses/{id}/edit', 'App\Controllers\ExpenseController@edit');
    $r->post('/expenses/{id}', 'App\Controllers\ExpenseController@update');
    $r->post('/expenses/{id}/delete', 'App\Controllers\ExpenseController@delete');

    foreach (['categories' => 'Category', 'accounts' => 'Account', 'budgets' => 'Budget', 'users' => 'User', 'roles' => 'Role'] as $module => $controller) {
        $handler = 'App\\Controllers\\' . $controller . 'Controller@';
        $r->get('/' . $module, $handler . 'index');
        $r->get('/' . $module . '/create', $handler . 'create');
        $r->post('/' . $module, $handler . 'store');
        $r->get('/' . $module . '/{id}/edit', $handler . 'edit');
        $r->post('/' . $module . '/{id}/update', $handler . 'update');
        if ($module !== 'users') $r->post('/' . $module . '/{id}/delete', $handler . 'delete');
    }
    $r->get('/categories/{id}/appearance', 'App\Controllers\CategoryController@appearance');
    $r->post('/categories/{id}/appearance', 'App\Controllers\CategoryController@saveAppearance');

    $r->get('/reports', 'App\Controllers\ReportController@index');
    $r->get('/reports/csv', 'App\Controllers\ReportController@csv');
    $r->get('/settings', 'App\Controllers\SettingsController@index');
    $r->post('/settings', 'App\Controllers\SettingsController@save');
};