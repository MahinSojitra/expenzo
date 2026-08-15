<?php
declare(strict_types=1);
use App\Core\Session;
function e(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function url(string $path=''):string{$base=rtrim(dirname($_SERVER['SCRIPT_NAME']??''),'/'); if($base==='/'||$base==='.')$base=''; return $base.'/'.ltrim($path,'/');}
function asset(string $path):string{return url('assets/'.ltrim($path,'/'));}
function old(string $key,mixed $default=''):mixed{return $_POST[$key]??$default;}
function csrf_token():string{if(!Session::get('_csrf'))Session::put('_csrf',bin2hex(random_bytes(32)));return Session::get('_csrf');}
function csrf_field():string{return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">';}
function verify_csrf():void{if(!hash_equals((string)Session::get('_csrf',''),(string)($_POST['_csrf']??''))){http_response_code(419);exit('Invalid CSRF token.');}}
function flash(string $key):mixed{return Session::consumeFlash($key);}
function auth_user():?array{return Session::get('user');}
function can(string $permission):bool{return in_array($permission,Session::get('permissions',[]),true);}
function money(float|int $amount):string{ $cfg=require dirname(__DIR__,2).'/config/app.php'; $symbols=['INR'=>'₹','USD'=>'$','EUR'=>'€','GBP'=>'£']; return ($symbols[$cfg['currency']]??$cfg['currency'].' ').number_format((float)$amount,2);}
function selected(mixed $a,mixed $b):string{return (string)$a===(string)$b?'selected':'';}
function checked(mixed $v):string{return $v?'checked':'';}
