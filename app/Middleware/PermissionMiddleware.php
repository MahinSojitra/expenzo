<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Core\Session; use App\Core\Response; use App\Repositories\UserRepository;
final class PermissionMiddleware { public static function require(string $permission):void {AuthMiddleware::require(); $user=(new UserRepository())->findWithRole((int)Session::get('user_id')); if(!$user || !in_array($permission,$user['permissions']??[],true)) {http_response_code(403); \App\Core\View::render('errors/403',[],'app'); exit;}} }
