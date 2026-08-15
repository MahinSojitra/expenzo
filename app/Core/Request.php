<?php
declare(strict_types=1);
namespace App\Core;
final class Request {
 public function method():string{return strtoupper($_SERVER['REQUEST_METHOD']??'GET');}
 public function path():string { $path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH) ?: '/'; $base=rtrim(dirname($_SERVER['SCRIPT_NAME']??''),'/'); if($base && $base!=='/' && str_starts_with($path,$base)) $path=substr($path,strlen($base)); return '/'.ltrim($path,'/'); }
 public function input(string $key,mixed $default=null):mixed{return $_POST[$key]??$default;}
 public function query(string $key,mixed $default=null):mixed{return $_GET[$key]??$default;}
 public function all():array{return array_merge($_GET,$_POST);}
 public function file(string $key):?array{return $_FILES[$key]??null;}
 public function isPost():bool{return $this->method()==='POST';}
 public function wantsJson():bool{return str_contains($_SERVER['HTTP_ACCEPT']??'','application/json') || $this->query('format')==='json';}
}
