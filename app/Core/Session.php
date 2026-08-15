<?php
declare(strict_types=1);
namespace App\Core;
final class Session {
 public static function start():void{if(session_status()===PHP_SESSION_ACTIVE)return; $c=require dirname(__DIR__,2).'/config/app.php'; ini_set('session.use_strict_mode','1'); ini_set('session.cookie_httponly','1'); ini_set('session.cookie_samesite','Lax'); if(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')ini_set('session.cookie_secure','1'); session_name($c['session_name']); session_set_cookie_params(['lifetime'=>$c['session_lifetime'],'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']); session_start(); if(isset($_SESSION['last_activity']) && time()-$_SESSION['last_activity']>$c['session_lifetime']) self::destroy(); $_SESSION['last_activity']=time(); }
 public static function get(string $k,mixed $d=null):mixed{return $_SESSION[$k]??$d;}
 public static function put(string $k,mixed $v):void{$_SESSION[$k]=$v;}
 public static function forget(string $k):void{unset($_SESSION[$k]);}
 public static function flash(string $k,mixed $v):void{$_SESSION['_flash'][$k]=$v;}
 public static function consumeFlash(string $k,mixed $d=null):mixed{$v=$_SESSION['_flash'][$k]??$d;unset($_SESSION['_flash'][$k]);return $v;}
 public static function regenerate():void{session_regenerate_id(true);}
 public static function destroy():never{$_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',['expires'=>time()-42000,'path'=>$p['path'],'domain'=>$p['domain'],'secure'=>$p['secure'],'httponly'=>$p['httponly'],'samesite'=>$p['samesite']??'Lax']);}session_destroy();Response::redirect('/login');}
}
