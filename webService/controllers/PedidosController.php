<?php

/**
 * Recurso: pedidos.  Ruta: /api/pedidos?action=... (despacha por acción + método).
 * Acciones admin: admin-listar, admin-detalle, admin-estatus, admin-precio, estadisticas.
 * Usuario: mis pedidos (GET), checkout (POST), editar, cancel/DELETE.
 */
class PedidosController
{
    private PedidosBusiness $pedidos;

    public function __construct(mysqli $conexion)
    {
        requireAuth();
        $this->pedidos = new PedidosBusiness($conexion);
    }

    private function idCuenta(): int
    {
        if (!empty($_SESSION['id_cuenta'])) return (int) $_SESSION['id_cuenta'];
        response(401, false, 'Sesión no válida. Inicia sesión nuevamente.');
    }

    public function index(): void
    {
        $action = trim($_GET['action'] ?? '');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $esAdmin = in_array($action, ['admin-listar', 'admin-detalle', 'admin-estatus', 'admin-precio', 'estadisticas']);
        if ($esAdmin) requireAdmin(); else requireUser();

        match (true) {
            $action === 'admin-listar'  => $this->adminListar(),
            $action === 'admin-detalle' => $this->adminDetalle(),
            $action === 'admin-estatus' => $this->adminEstatus($method),
            $action === 'admin-precio'  => $this->adminPrecio($method),
            $action === 'estadisticas'  => $this->estadisticas(),
            $action === 'editar'        => $this->editarPedido($method),
            $action === 'cancel'        => $this->cancelarPedido(),
            $method === 'GET'           => $this->misPedidos(),
            $method === 'POST'          => $this->checkout(),
            $method === 'DELETE'        => $this->cancelarPedido(),
            default                     => response(405, false, 'Acción no válida.'),
        };
    }

    /* ---------------- Usuario ---------------- */

    private function misPedidos(): void
    {
        response(200, true, 'OK', ['pedidos' => $this->pedidos->misPedidos($this->idCuenta())]);
    }

    private function checkout(): void
    {
        $uid = $this->idCuenta();
        if ($this->pedidos->contarCarrito($uid) == 0) response(422, false, 'No hay productos en el carrito.');

        $filas = $this->pedidos->checkout($uid);
        if ($filas > 0) response(200, true, 'Pedido realizado con éxito.', ['pedidos_procesados' => $filas]);
        response(500, false, 'No se pudo procesar el pedido.');
    }

    private function cancelarPedido(): void
    {
        $uid = $this->idCuenta();
        $id  = filter_var($_POST['id'] ?? $_GET['id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) response(422, false, 'ID de pedido inválido.');

        $pedido = $this->pedidos->pedidoCancelable($id, $uid);
        if (!$pedido) response(404, false, 'Pedido no encontrado o no se puede cancelar.');

        if ($this->pedidos->cancelar($id)) response(200, true, 'Pedido cancelado correctamente.');
        response(500, false, 'Error al cancelar el pedido.');
    }

    private function editarPedido(string $method): void
    {
        if ($method !== 'POST') response(405, false, 'Método no permitido.');

        $uid             = $this->idCuenta();
        $idPedido        = isset($_POST['idPedido'])        ? intval($_POST['idPedido'])        : 0;
        $idPersonalizada = isset($_POST['idPersonalizada']) ? intval($_POST['idPersonalizada']) : 0;
        $opcion      = trim($_POST['opcFinal']    ?? '');
        $tamanio     = trim($_POST['tamaño']      ?? '');
        $tipoPapel   = trim($_POST['tipoPapel']   ?? '');
        $color       = trim($_POST['color']       ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($idPedido <= 0 || $idPersonalizada <= 0) response(422, false, 'IDs de pedido no válidos.');
        if ($opcion === '' || $tamanio === '' || $tipoPapel === '' || $color === '' || $descripcion === '') {
            response(422, false, 'Todos los campos son obligatorios.');
        }

        $pedido = $this->pedidos->pedidoEditable($idPedido, $uid);
        if (!$pedido) response(404, false, 'Pedido no encontrado.');
        if (!in_array(strtolower($pedido['estatus']), ['pendiente', 'visto'])) response(409, false, 'Este pedido ya no puede editarse.');

        $portadaUrl = null;
        if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
            $r = $this->procesarPortada($_FILES['portada']);
            if (!$r['success']) response(422, false, $r['message']);
            $portadaUrl = $r['url'];
            // Borrar portada anterior
            $old = $this->pedidos->portadaDePersonalizada($idPersonalizada);
            if (!empty($old)) {
                $fs = dirname(__DIR__, 2) . $old;
                if (file_exists($fs)) unlink($fs);
            }
        }

        if ($this->pedidos->editarPersonalizada($idPersonalizada, $color, $descripcion, $tamanio, $opcion, $tipoPapel, $portadaUrl)) {
            response(200, true, 'Pedido actualizado correctamente.', ['redirect' => '/mis-pedidos']);
        }
        response(500, false, 'Error al actualizar el pedido.');
    }

    /* ---------------- Admin ---------------- */

    private function adminListar(): void
    {
        response(200, true, 'OK', ['pedidos' => $this->pedidos->adminListar()]);
    }

    private function adminDetalle(): void
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) response(422, false, 'ID no válido.');
        $pedido = $this->pedidos->adminDetalle($id);
        if (!$pedido) response(404, false, 'Pedido no encontrado.');
        response(200, true, 'OK', ['pedido' => $pedido]);
    }

    private function adminEstatus(string $method): void
    {
        if ($method !== 'PUT') response(405, false, 'Método no permitido.');
        parse_str(file_get_contents('php://input'), $input);
        $id      = isset($input['pedidoId']) ? intval($input['pedidoId']) : 0;
        $estatus = trim($input['estatus'] ?? '');
        $mensaje = trim($input['mensaje'] ?? '');

        $validos = ['pendiente', 'visto', 'aprobado', 'declinado', 'proceso', 'terminado', 'entregado'];
        if (!in_array($estatus, $validos)) response(422, false, 'Estatus no válido.');

        if ($this->pedidos->adminEstatus($id, $estatus, $mensaje)) response(200, true, "Estatus actualizado a: $estatus.");
        response(500, false, 'Error al actualizar estatus.');
    }

    private function adminPrecio(string $method): void
    {
        if ($method !== 'PUT') response(405, false, 'Método no permitido.');
        parse_str(file_get_contents('php://input'), $input);
        $idPedido = isset($input['pedidoId']) ? intval($input['pedidoId']) : 0;
        $precio   = $input['precio'] ?? '';

        if (!is_numeric($precio) || $precio < 0) response(422, false, 'Precio no válido.');

        $row = $this->pedidos->pedidoParaPrecio($idPedido);
        if (!$row)                     response(404, false, 'Pedido no encontrado.');
        if ($row['idTipoPedido'] != 2) response(422, false, 'Solo se puede asignar precio a libretas personalizadas.');

        $precioFloat = floatval($precio);
        if ($this->pedidos->asignarPrecio((int) $row['idPersonalizada'], $precioFloat)) response(200, true, 'Precio asignado: $' . number_format($precioFloat, 2));
        response(500, false, 'Error al asignar precio.');
    }

    private function estadisticas(): void
    {
        response(200, true, 'OK', $this->pedidos->estadisticas());
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
