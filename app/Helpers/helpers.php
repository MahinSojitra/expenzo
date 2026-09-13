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
function app_setting(string $key, mixed $default = null): mixed
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        try {
            $rows = \App\Core\Database::connection()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
            foreach ($rows as $row) $settings[$row['setting_key']] = $row['setting_value'];
        } catch (\Throwable $exception) {
            $settings = [];
        }
    }
    return $settings[$key] ?? $default;
}
function currency_code(): string
{
    $cfg = require dirname(__DIR__,2).'/config/app.php';
    return strtoupper(trim((string)app_setting('currency', $cfg['currency'] ?? 'INR')));
}
function money(float|int|string $amount):string{ $symbols=['INR'=>'₹','USD'=>'$','EUR'=>'€','GBP'=>'£']; $currency=currency_code(); return ($symbols[$currency]??$currency.' ').number_format((float)$amount,2);}
function currency_icon(): string
{
    return match (currency_code()) {
        'USD' => '<i data-feather="dollar-sign" aria-hidden="true"></i>',
        'EUR' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 10h12"/><path d="M4 14h10"/><path d="M19 5.5A7.5 7.5 0 1 0 19 18.5"/></svg>',
        'GBP' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 21h12"/><path d="M6 12h10"/><path d="M9 21c2-3 2-6 0-9a5 5 0 0 1 9-4"/></svg>',
        default => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12"/><path d="M6 8h12"/><path d="M6 13h4a5 5 0 0 0 0-10"/><path d="M6 13l8 8"/></svg>',
    };
}
function selected(mixed $a,mixed $b):string{return (string)$a===(string)$b?'selected':'';}
function checked(mixed $v):string{return $v?'checked':'';}
function account_type_icon(?string $type): string
{
    return match (strtolower(trim((string)$type))) {
        'cash' => 'dollar-sign',
        'bank' => 'briefcase',
        'card', 'credit card', 'debit card' => 'credit-card',
        'upi' => 'smartphone',
        'wallet' => 'pocket',
        default => 'credit-card',
    };
}
function account_type_label(?string $type, ?string $name = null): string
{
    $type = trim((string)$type);
    $label = trim((string)$name) !== '' ? trim((string)$name) : ($type !== '' ? $type : 'Account');
    return '<span class="inline-icon-text"><i data-feather="'.e(account_type_icon($type)).'" aria-hidden="true"></i>'.e($label).'</span>';
}
function category_label(?string $name, ?string $icon = null, ?string $color = null, ?string $badge = null): string
{
    $label = trim((string)$name);
    $displayIcon = trim((string)($icon ?: 'tag'));
    $displayColor = preg_match('/^#[a-fA-F0-9]{6}$/D', (string)$color) ? (string)$color : '#3b7ddd';
    $iconHtml = trim((string)$badge) !== ''
        ? '<span class="inline-category-badge-text">'.e($badge).'</span>'
        : '<i data-feather="'.e($displayIcon).'" aria-hidden="true"></i>';

    return '<span class="inline-icon-text"><span class="inline-category-icon" style="--category-color: '.e($displayColor).'">'.$iconHtml.'</span>'.e($label !== '' ? $label : 'All Categories').'</span>';
}

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
