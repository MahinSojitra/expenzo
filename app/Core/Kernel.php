<?php
declare(strict_types=1);
namespace App\Core;
use App\Middleware\AuthMiddleware; use App\Middleware\PermissionMiddleware;
final class Kernel { public static function boot():void{
  require dirname(__DIR__,2).'/config/constants.php'; Env::load(dirname(__DIR__,2).'/.env'); $cfg=require dirname(__DIR__,2).'/config/app.php'; date_default_timezone_set($cfg['timezone']); Session::start();
  set_exception_handler(function(\Throwable $e)use($cfg){error_log((string)$e); if($cfg['debug']){http_response_code(500);echo '<pre>'.htmlspecialchars((string)$e).'</pre>';}else{http_response_code(500); View::render('errors/500',[],'app');}});
 }
}
