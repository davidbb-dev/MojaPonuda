<?php
declare(strict_types=1);

//------------------
// DEVELOPMENT ONLY - Development error reporting that will be disabled in production.
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
// DEVELOPMENT ONLY
//------------------

session_start();

// Automatically load application classes based on their namespace.
spl_autoload_register(function($class){
    $path = __DIR__.'/../'.str_replace('\\','/',$class).'.php';
    require $path;
});

// Load configuration and global helper functions.
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../config/auction_config.php';
require_once __DIR__.'/../App/Support/helpers.php';


use App\Router\Router;

// Create the router and dispatch the current HTTP request.
$router = new Router();
$router->routing();