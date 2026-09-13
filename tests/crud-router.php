<?php
$public = realpath(dirname(__DIR__).'/public');
$path = realpath($public . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($path && str_starts_with($path, $public . DIRECTORY_SEPARATOR) && is_file($path)) return false;
require $public.'/index.php';