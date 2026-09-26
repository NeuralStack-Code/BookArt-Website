<?php
// API: los errores se registran pero NUNCA se imprimen (romperían el JSON).
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/core/autoload.php';   // clases (classmap+fallback) + auth.php
require_once __DIR__ . '/middleware/cors.php';
require_once __DIR__ . '/core/config.php';

header('Content-Type: application/json; charset=utf-8');

// ── Errores globales: cualquier fatal sale como JSON limpio ────────────────────
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('BookArt fatal: ' . $e['message'] . ' en ' . $e['file'] . ':' . $e['line']);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
    }
});

$uri   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base  = rtrim(BASE_URL, '/');
$route = trim(preg_replace('#^' . preg_quote($base, '#') . '/?api/?#', '', $uri), '/');
$route = strtok($route, '?');

// Recurso => [Controlador, conexión: principal | ninguna]  (controllers PLANOS, sin subcarpeta)
$controllers = [
    'contacto' => ['ContactoController', 'ninguna'],
    'auth'     => ['AuthController',     'principal'],
    'catalogo' => ['CatalogoController', 'principal'],
    'carrito'  => ['CarritoController',  'principal'],
    'pedidos'  => ['PedidosController',  'principal'],
];

try {
    if (isset($controllers[$route])) {
        [$clase, $tipoDb] = $controllers[$route];
        $db = conexionPara($tipoDb);
        despacharControlador(new $clase($db), 'index');   // cada controller despacha por método/acción internamente
    } else {
        response(404, false, "Ruta '$route' no encontrada.");
    }
} catch (\Throwable $e) {
    error_log('BookArt API error: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    response(500, false, 'Error interno del servidor.');
}

/** Abre la conexión que declara el recurso (o null si no necesita BD). */
function conexionPara(string $tipo): ?mysqli
{
    if ($tipo === 'principal') {
        require __DIR__ . '/core/conexionBDD.php';   // $conexion
        return $conexion;
    }
    return null;
}

/** Despacha la acción → método público del controlador (vía reflexión). */
function despacharControlador(object $ctrl, string $accion): void
{
    if ($accion === '') $accion = 'index';
    if (!method_exists($ctrl, $accion)) {
        response(404, false, "Acción no encontrada.");
    }
    $m = new ReflectionMethod($ctrl, $accion);
    if (!$m->isPublic() || $m->isStatic() || $m->isConstructor()) {
        response(404, false, "Acción no disponible.");
    }
    $m->invoke($ctrl);
}

function response(int $code, bool $success, string $message, array $extra = []): void {
    http_response_code($code);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}
