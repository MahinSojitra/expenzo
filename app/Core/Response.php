<?php
declare(strict_types=1);
namespace App\Core;
final class Response {
 public static function redirect(string $url):never{if(str_starts_with($url,'/'))$url=\url($url);header('Location: '.$url); exit;}
 public static function json(array $data,int $status=200):never{http_response_code($status);header('Content-Type: application/json');echo json_encode($data);exit;}
 public static function status(int $code):void{http_response_code($code);}
}
