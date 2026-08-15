<?php
declare(strict_types=1);
namespace App\Core;
final class Router { private array $routes=[];
 public function get(string $path,string $handler):void{$this->add('GET',$path,$handler);}
 public function post(string $path,string $handler):void{$this->add('POST',$path,$handler);}
 private function add(string $method,string $path,string $handler):void{$this->routes[] = compact('method','path','handler');}
 public function dispatch(Request $request):void{foreach($this->routes as $r){if($r['method']!==$request->method())continue;$pattern=preg_replace('#\\{([a-zA-Z_][a-zA-Z0-9_]*)\\}#','(?P<$1>[^/]+)',str_replace('/','\/',$r['path']));if(preg_match('#^'.$pattern.'$#',$request->path(),$m)){[$class,$method]=explode('@',$r['handler']);$controller=new $class();$params=[];foreach($m as $k=>$v)if(!is_int($k))$params[]=$v; $controller->$method($request,...$params);return;}} Response::status(404); View::render('errors/404',[], 'app');}
}
