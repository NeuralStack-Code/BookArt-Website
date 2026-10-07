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

    // Usuario
    'productos'          => __DIR__ . '/webService/views/user/Productos.php',

    // Admin
    'administrador'      => __DIR__ . '/webService/views/admin/Administrador.php',
];

// Pantallas que ya son /<controller>/<método>. Las URLs anteriores redirigen (301) conservando el query string.
$moved = [
    'inicio-sesion'    => '/auth/entrar',
    'nueva-contrasena' => '/auth/restablecer',
    'mis-pedidos'      => '/pedidos',
];
if (isset($moved[$route])) {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . BASE_URL . $moved[$route] . ($query !== '' ? '?' . $query : ''), true, 301);
    exit;
}

// Estas dos cambiaron de URL y de nombre de parámetro:
//   /extension-catalogo?id=  →  /catalogo/detalle?IdProducto=
//   /editar-pedido?id=       →  /personalizada/editar?IdPedido=
if ($route === 'extension-catalogo') {
    header('Location: ' . BASE_URL . '/catalogo/detalle?IdProducto=' . (int) ($_GET['id'] ?? 0), true, 301);
    exit;
}
if ($route === 'editar-pedido') {
    header('Location: ' . BASE_URL . '/personalizada/editar?IdPedido=' . (int) ($_GET['id'] ?? 0), true, 301);
    exit;
}

// Admin bloqueado en su panel hasta cerrar sesión: de las páginas sueltas solo ve la suya.
// (Las pantallas que ya son de un controller lo mandan a su panel ellas mismas; sus métodos sí los usa el panel.)
if (isset($_SESSION['permiso']) && (int)$_SESSION['permiso'] === 1) {
    if (array_key_exists($route, $routes) && $route !== 'administrador') {
        header('Location: ' . BASE_URL . '/administrador');
        exit;
    }
}

// 1) Páginas sueltas de $routes.  2) /<controller>/<método> (controllers que heredan de Controller;
//    lo despacha el router de apiService).  3) 404.
if (array_key_exists($route, $routes)) {
    require $routes[$route];
} elseif (($class = routeController(explode('/', $route)[0])) !== null
          && method_exists($class, explode('/', $route, 2)[1] ?? 'index')) {
    require __DIR__ . '/apiService/index.php';
} else {
    http_response_code(404);
    require __DIR__ . '/webService/views/404.php';
}
