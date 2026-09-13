<?php
declare(strict_types=1);
use App\Core\Session;
function e(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function url(string $path=''):string{$base=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'')),'/'); if($base==='/'||$base==='.')$base=''; return $base.'/'.ltrim($path,'/');}
function asset(string $path):string{
    $relative = ltrim($path, '/');
    $file = dirname(__DIR__,2).'/public/assets/'.$relative;
    return url('assets/'.$relative).(is_file($file) ? '?v='.filemtime($file) : '');
}
function old(string $key,mixed $default=''):mixed{return $_POST[$key]??$default;}
function csrf_token():string{if(!Session::get('_csrf'))Session::put('_csrf',bin2hex(random_bytes(32)));return Session::get('_csrf');}
function csrf_field():string{return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">';}
function verify_csrf():void{if(!hash_equals((string)Session::get('_csrf',''),(string)($_POST['_csrf']??''))){http_response_code(419);\App\Core\View::render('errors/419');exit;}}
function flash(string $key):mixed{return Session::consumeFlash($key);}
function auth_user():?array{return Session::get('user');}
function can(string $permission):bool{return in_array($permission,Session::get('permissions',[]),true);}
function has_role(string $role):bool{return (auth_user()['role_name']??'')===$role;}
function is_adminish():bool{return can('finance.view_all');}
function money(float|int|string $amount):string{ $cfg=require dirname(__DIR__,2).'/config/app.php'; $symbols=['INR'=>'INR ','USD'=>'$','EUR'=>'EUR ','GBP'=>'GBP ']; return ($symbols[$cfg['currency']]??$cfg['currency'].' ').number_format((float)$amount,2);}
function selected(mixed $a,mixed $b):string{return (string)$a===(string)$b?'selected':'';}
function checked(mixed $v):string{return $v?'checked':'';}

function is_super_admin():bool{return \App\Services\Authorization::superAdmin(auth_user() ?? []);}
function landing_path():string {
    foreach (['dashboard','expenses','categories','accounts','budgets','reports','users','roles','settings'] as $module) {
        if(can($module.'.view')) return '/'.$module;
    }
    return '/dashboard';
}
/** Format display dates using the saved preference; form values stay ISO. */
function display_date(?string $value, bool $withTime = false): string
{
    if ($value === null || $value === '') return '—';
    static $format = null;
    if ($format === null) {
        $saved = \App\Core\Database::connection()->query("SELECT setting_value FROM settings WHERE setting_key='date_format'")->fetchColumn();
        $format = is_string($saved) && trim($saved) !== '' ? $saved : 'Y-m-d';
    }
    try {
        $date = new \DateTimeImmutable($value);
        return $date->format($format . ($withTime ? ' H:i:s' : ''));
    } catch (\Exception $exception) {
        return '—';
    }
}