<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Core\Session; use App\Core\Response;
final class AuthMiddleware { public static function require():void {if(!Session::get('user_id')) Response::redirect('/login');} }
