<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../core/Router.php';
require_once '../resources/v1/UserResource.php';
require_once '../resources/v1/ProductoResource.php';
require_once '../resources/v1/LoginResource.php';

$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$basePath = $scriptName;

$router = new Router('v1', $basePath);
$userResource = new UserResource();
$productoResource = new ProductoResource();
$loginResource = new LoginResource();

// rutas de autenticación
$router->addRoute('POST', '/login', [$loginResource, 'login']);
$router->addRoute('POST', '/logout', [$loginResource, 'logout']);
$router->addRoute('POST', '/register', [$loginResource, 'register']);

// rutas de usuarios
$router->addRoute('GET', '/users', [$userResource, 'index']);
$router->addRoute('GET', '/users/{id}', [$userResource, 'show']);
$router->addRoute('POST', '/users', [$userResource, 'store']);
$router->addRoute('PUT', '/users/{id}', [$userResource, 'update']);
$router->addRoute('DELETE', '/users/{id}', [$userResource, 'destroy']);

// rutas de productos
$router->addRoute('GET', '/productos', [$productoResource, 'index']);
$router->addRoute('GET', '/productos/{id}', [$productoResource, 'show']);
$router->addRoute('POST', '/productos', [$productoResource, 'store']);
$router->addRoute('PUT', '/productos/{id}', [$productoResource, 'update']);
$router->addRoute('DELETE', '/productos/{id}', [$productoResource, 'destroy']);

$router->dispatch();
?>