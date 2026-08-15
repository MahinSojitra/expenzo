<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/Core/Autoloader.php';
require dirname(__DIR__).'/app/Helpers/helpers.php';
use App\Core\Kernel;use App\Core\Request;use App\Core\Router;
Kernel::boot();
$router=new Router();(require dirname(__DIR__).'/routes/web.php')($router);$router->dispatch(new Request());
