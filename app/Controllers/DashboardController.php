<?php
declare(strict_types=1);
namespace App\Controllers; use App\Core\Request; use App\Core\View; use App\Middleware\PermissionMiddleware; use App\Services\DashboardService;
final class DashboardController { public function index(Request $r):void{PermissionMiddleware::require('dashboard.view');$stats=(new DashboardService())->stats(auth_user());View::render('dashboard/index',compact('stats'));}}
