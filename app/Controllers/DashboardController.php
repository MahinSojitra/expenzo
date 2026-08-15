<?php
declare(strict_types=1);
namespace App\Controllers; use App\Core\Request; use App\Core\View; use App\Core\Session; use App\Middleware\AuthMiddleware; use App\Services\DashboardService;
final class DashboardController { public function index(Request $r):void{AuthMiddleware::require();$stats=(new DashboardService())->stats((int)Session::get('user_id'));View::render('dashboard/index',compact('stats'));}}
