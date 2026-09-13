<?php
declare(strict_types=1);
namespace App\Core;
final class View {
 public static function render(string $view,array $data=[],string $layout='app'):void { extract($data); if (str_starts_with($view, 'errors/')) $layout = 'error'; $viewFile=dirname(__DIR__,2).'/resources/views/'.$view.'.php'; if(!is_file($viewFile)) throw new \RuntimeException('View not found: '.$view); ob_start(); require $viewFile; $content=ob_get_clean(); require dirname(__DIR__,2).'/resources/views/layouts/'.$layout.'.php'; }
}
