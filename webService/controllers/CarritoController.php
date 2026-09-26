<?php

/**
 * Recurso: carrito.  Ruta: /api/carrito (despacha por método + acción).
 * Solo usuarios (no admin). index() enruta internamente.
 */
class CarritoController
{
    private CarritoBusiness $carrito;
    private int $uid;

    public function __construct(mysqli $conexion)
    {
        requireUser();
        $this->carrito = new CarritoBusiness($conexion);
        $this->uid     = $this->idCuenta();
    }

    private function idCuenta(): int
    {
        if (!empty($_SESSION['id_cuenta'])) return (int) $_SESSION['id_cuenta'];
        response(401, false, 'Sesión no válida. Inicia sesión nuevamente.');
    }

    public function index(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $action = trim($_GET['action'] ?? '');
        match (true) {
            $method === 'GET'                               => $this->verCarrito(),
            $method === 'POST' && $action === 'checkout'    => $this->checkout(),
            $method === 'POST' && isset($_POST['opcFinal']) => $this->crearPersonalizada(),
            $method === 'POST'                              => $this->agregarAlCarrito(),
            $method === 'DELETE'                            => $this->eliminarDelCarrito(),
            default                                         => response(405, false, 'Método no permitido.'),
        };
    }

    private function verCarrito(): void
    {
        if (trim($_GET['action'] ?? '') === 'count') {
            response(200, true, 'OK', ['count' => $this->carrito->contarCarrito($this->uid)]);
        }
        response(200, true, 'OK', ['items' => $this->carrito->verCarrito($this->uid)]);
    }

    private function crearPersonalizada(): void
    {
        $opcion      = trim($_POST['opcFinal']    ?? '');
        $tamanio     = trim($_POST['tamaño']      ?? '');
        $tipoPapel   = trim($_POST['tipoPapel']   ?? '');
        $color       = trim($_POST['color']       ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($opcion === '' || $tamanio === '' || $tipoPapel === '' || $color === '' || $descripcion === '') {
            response(422, false, 'Todos los campos de la libreta son obligatorios.');
        }

        $portadaUrl = '';
        if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
            $r = $this->procesarPortada($_FILES['portada']);
            if (!$r['success']) response(422, false, $r['message']);
            $portadaUrl = $r['url'];
        }

        $idPer = $this->carrito->crearPersonalizada($color, $descripcion, $portadaUrl, $tamanio, $opcion, $tipoPapel);
        if ($idPer <= 0) response(500, false, 'Error al guardar el diseño.');

        if (!$this->carrito->crearPedidoCarrito($idPer, $this->uid)) {
            $this->carrito->eliminarPersonalizada($idPer);
            response(500, false, 'Error al agregar al carrito.');
        }
        response(201, true, '¡Diseño creado y agregado al carrito!', ['redirect' => '/carrito']);
    }

    private function checkout(): void
    {
        if ($this->carrito->contarCarrito($this->uid) === 0) response(422, false, 'Tu carrito está vacío.');

        if ($this->carrito->checkout($this->uid)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => '¡Pedido realizado! Nos pondremos en contacto contigo pronto.'];
            response(200, true, '¡Pedido realizado exitosamente!');
        }
        response(500, false, 'Error al procesar el pedido.');
    }

    private function agregarAlCarrito(): void
    {
        $tipo = trim($_POST['tipo'] ?? '');
        $id   = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

        if (!$id || $id <= 0) response(422, false, 'ID de producto inválido.');
        if (!in_array($tipo, ['catalogo', 'personalizada'])) response(422, false, 'Tipo de producto no válido.');

        $idTipo  = $tipo === 'catalogo' ? 1 : 2;
        $columna = $tipo === 'catalogo' ? 'idCatalogo' : 'idPersonalizada';

        if ($this->carrito->yaEnCarrito($this->uid, $id, $columna, $idTipo)) response(409, false, 'Este producto ya está en tu carrito.');
        if ($this->carrito->agregar($this->uid, $id, $columna, $idTipo)) response(201, true, 'Producto agregado al carrito.');
        response(500, false, 'Error al agregar al carrito.');
    }

    private function eliminarDelCarrito(): void
    {
        $id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : false;
        if (!$id || $id <= 0) response(422, false, 'ID de pedido inválido.');

        $row = $this->carrito->pedidoCarrito($id, $this->uid);
        if (!$row) response(404, false, 'Pedido no encontrado.');

        $this->carrito->eliminarPedidoCarrito($id, $this->uid);

        if ($row['idTipoPedido'] == 2 && $row['idPersonalizada']) {
            $idPer   = (int) $row['idPersonalizada'];
            $portada = $this->carrito->portadaDe($idPer);
            $this->carrito->eliminarPersonalizada($idPer);
            if (!empty($portada)) {
                $fs = dirname(__DIR__, 2) . $portada;
                if (file_exists($fs)) unlink($fs);
            }
        }
        response(200, true, 'Producto eliminado del carrito.');
    }

    private function procesarPortada(array $file): array
    {
        if ($file['size'] > 5 * 1024 * 1024)
            return ['success' => false, 'message' => 'La portada no debe superar 5MB.'];

        $permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $permitidos))
            return ['success' => false, 'message' => 'Formato de imagen no válido (jpg, png, webp).'];

        $ext     = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nombre  = uniqid('portada_') . '.' . $ext;
        $carpeta = dirname(__DIR__, 2) . '/wwwroot/portadas/';

        if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $carpeta . $nombre))
            return ['success' => false, 'message' => 'Error al guardar la portada.'];

        return ['success' => true, 'url' => '/wwwroot/portadas/' . $nombre];
    }
}
