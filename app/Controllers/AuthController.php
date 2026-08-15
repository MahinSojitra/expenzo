<?php
declare(strict_types=1);
namespace App\Controllers; use App\Core\Request; use App\Core\Response; use App\Core\View; use App\Core\Session; use App\Services\AuthService;
final class AuthController { public function showLogin(Request $r):void{if(Session::get('user_id'))Response::redirect('/dashboard');View::render('auth/login',[],'auth');} public function login(Request $r):void{verify_csrf();$ok=(new AuthService())->login(trim((string)$r->input('email')), (string)$r->input('password'),(bool)$r->input('remember'));if(!$ok){Session::flash('error','Invalid email or password.');Response::redirect('/login');}Session::flash('success','Welcome back!');Response::redirect('/dashboard');} public function logout(Request $r):void{(new AuthService())->logout();Response::redirect('/login');}}
