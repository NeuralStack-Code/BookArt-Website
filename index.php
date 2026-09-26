<?php
session_start();
require_once __DIR__ . '/apiService/core/autoload.php';
require_once __DIR__ . '/apiService/core/config.php';
require_once __DIR__ . '/apiService/core/checkLicense.php';
checkLicense();

$uri   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Rutas de API → apiService/index.php (el .htaccess lo maneja en producción; aquí lo cubrimos para el dev server)
if (preg_match('#^/?api(/|$)#', $uri)) {
    require __DIR__ . '/apiService/index.php';
    exit;
}

// Quitar el prefijo de BASE_URL para que las rutas funcionen en local y producción
$base  = rtrim(BASE_URL, '');
if ($base !== '' && strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}

$route = trim($uri, '/');
$route = strtok($route, '?');
if ($route === false) $route = '';

$routes = [
    ''                   => __DIR__ . '/webService/views/home.php',
    'index.php'          => __DIR__ . '/webService/views/home.php',
    'contacto'           => __DIR__ . '/webService/views/user/Contacto.php',

    // Usuario
    'catalogo'           => __DIR__ . '/webService/views/user/Catalogo.php',
    'productos'          => __DIR__ . '/webService/views/user/Productos.php',
    'carrito'            => __DIR__ . '/webService/views/user/Carrito.php',
    'inicio-sesion'      => __DIR__ . '/webService/views/user/Inicio_sesion.php',
    'nueva-contrasena'   => __DIR__ . '/webService/views/user/NuevaContrasena.php',
    'mis-pedidos'        => __DIR__ . '/webService/views/user/MisPedidos.php',
    'personalizada'      => __DIR__ . '/webService/views/user/Personalizada.php',
    'extension-catalogo' => __DIR__ . '/webService/views/user/Extension_Catalogo.php',
    'editar-pedido'      => __DIR__ . '/webService/views/user/EditarPedido.php',

    // Admin
    'administrador'      => __DIR__ . '/webService/views/admin/Administrador.php',
];

// Admin bloqueado en su panel hasta cerrar sesión
if (isset($_SESSION['permiso']) && (int)$_SESSION['permiso'] === 1) {
    if ($route !== 'administrador') {
        header('Location: ' . BASE_URL . '/administrador');
        exit;
    }
}

if (array_key_exists($route, $routes)) {
    require $routes[$route];
} else {
    http_response_code(404);
    require __DIR__ . '/webService/views/404.php';
}
