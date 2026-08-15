<?php
declare(strict_types=1);
use App\Core\Router;
return static function(Router $r):void{
 $r->get('/','App\Controllers\DashboardController@index');
 $r->get('/login','App\Controllers\AuthController@showLogin');$r->post('/login','App\Controllers\AuthController@login');$r->get('/logout','App\Controllers\AuthController@logout');
 $r->get('/dashboard','App\Controllers\DashboardController@index');
 $r->get('/expenses','App\Controllers\ExpenseController@index');$r->get('/expenses/create','App\Controllers\ExpenseController@create');$r->post('/expenses','App\Controllers\ExpenseController@store');$r->get('/expenses/{id}','App\Controllers\ExpenseController@show');$r->get('/expenses/{id}/edit','App\Controllers\ExpenseController@edit');$r->post('/expenses/{id}','App\Controllers\ExpenseController@update');$r->post('/expenses/{id}/delete','App\Controllers\ExpenseController@delete');
 $r->get('/categories','App\Controllers\CategoryController@index');$r->post('/categories','App\Controllers\CategoryController@store');$r->post('/categories/{id}','App\Controllers\CategoryController@update');$r->post('/categories/{id}/delete','App\Controllers\CategoryController@delete');
 $r->get('/accounts','App\Controllers\AccountController@index');$r->post('/accounts','App\Controllers\AccountController@store');$r->post('/accounts/{id}','App\Controllers\AccountController@update');$r->post('/accounts/{id}/delete','App\Controllers\AccountController@delete');
 $r->get('/budgets','App\Controllers\BudgetController@index');$r->post('/budgets','App\Controllers\BudgetController@store');$r->post('/budgets/{id}/delete','App\Controllers\BudgetController@delete');
 $r->get('/reports','App\Controllers\ReportController@index');$r->get('/reports/csv','App\Controllers\ReportController@csv');
 $r->get('/users','App\Controllers\UserController@index');$r->post('/users','App\Controllers\UserController@store');$r->post('/users/{id}','App\Controllers\UserController@update');
 $r->get('/settings','App\Controllers\SettingsController@index');$r->post('/settings','App\Controllers\SettingsController@save');
};
