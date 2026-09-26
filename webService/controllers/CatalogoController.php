<?php

/**
 * Recurso: catalogo.  Ruta: /api/catalogo (despacha por método: GET/POST/PUT/DELETE).
 * Listar es público; agregar/editar/eliminar son de admin.
 */
class CatalogoController
{
    private CatalogoBusiness $cat;

    public function __construct(mysqli $conexion)
    {
        $this->cat = new CatalogoBusiness($conexion);
    }

    public function index(): void
    {
        match ($_SERVER['REQUEST_METHOD'] ?? 'GET') {
            'GET'    => $this->listar(),
            'POST'   => $this->agregar(),
            'PUT'    => $this->editar(),
            'DELETE' => $this->eliminar(),
            default  => response(405, false, 'Método no permitido.'),
        };
    }

    private function listar(): void
    {
        $id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
        if ($id) {
            $producto = $this->cat->obtener($id);
            if (!$producto) response(404, false, 'Producto no encontrado.');
            response(200, true, 'OK', ['producto' => $producto]);
        }
        response(200, true, 'OK', ['productos' => $this->cat->listarTodos()]);
    }

    private function agregar(): void
    {
        requireAdmin();
        $nombre      = trim($_POST['nombre']      ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio      = trim($_POST['precio']      ?? '');

        if ($nombre === '' || $descripcion === '' || $precio === '') response(422, false, 'Nombre, descripción y precio son obligatorios.');
        if (!is_numeric($precio) || $precio < 0)                     response(422, false, 'El precio no es válido.');
        if ($this->cat->nombreExiste($nombre))                       response(409, false, 'Ya existe un producto con ese nombre.');

        $imagen = '/wwwroot/catalogo/imgNoEncontrada.png';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $r = $this->procesarImagen($_FILES['imagen']);
            if (!$r['success']) response(422, false, $r['message']);
            $imagen = $r['ruta'];
        }

        if ($this->cat->crear($nombre, $descripcion, (float) $precio, $imagen)) response(201, true, 'Producto agregado exitosamente.');
        response(500, false, 'Error al agregar el producto.');
    }

    private function editar(): void
    {
        requireAdmin();
        $_PUT = [];
        parse_str(file_get_contents('php://input'), $_PUT); // PHP no parsea PUT con FormData

        $id          = isset($_PUT['id_producto']) ? intval($_PUT['id_producto']) : 0;
        $nombre      = trim($_PUT['nombre']      ?? '');
        $descripcion = trim($_PUT['descripcion'] ?? '');
        $precio      = trim($_PUT['precio']      ?? '');

        if ($id <= 0 || $nombre === '' || $descripcion === '' || $precio === '') response(422, false, 'ID, nombre, descripción y precio son obligatorios.');
        if (!is_numeric($precio) || $precio < 0)                                 response(422, false, 'El precio no es válido.');
        if ($this->cat->nombreExiste($nombre, $id))                              response(409, false, 'Ya existe otro producto con ese nombre.');

        if ($this->cat->editar($id, $nombre, $descripcion, (float) $precio)) response(200, true, 'Producto actualizado exitosamente.');
        response(500, false, 'Error al actualizar el producto.');
    }

    private function eliminar(): void
    {
        requireAdmin();
        parse_str(file_get_contents('php://input'), $_DELETE);
        $id = isset($_DELETE['id']) ? intval($_DELETE['id']) : 0;
        if ($id <= 0) response(422, false, 'ID no válido.');

        $row = $this->cat->obtenerImg($id);
        if (!$row) response(404, false, 'Producto no encontrado.');

        if ($this->cat->eliminar($id)) {
            $img = $row['img'];
            if ($img !== '/wwwroot/catalogo/imgNoEncontrada.png') {
                $fs = dirname(__DIR__, 2) . $img;
                if (file_exists($fs)) unlink($fs);
            }
            response(200, true, 'Producto eliminado exitosamente.');
        }
        response(500, false, 'Error al eliminar el producto.');
    }

    private function procesarImagen(array $file): array
    {
        if ($file['size'] > 3 * 1024 * 1024)
            return ['success' => false, 'message' => 'La imagen no debe superar 3MB.'];

        $permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $permitidos))
            return ['success' => false, 'message' => 'Formato de imagen no válido.'];

        $ext     = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nombre  = uniqid('producto_') . '.' . $ext;
        $carpeta = dirname(__DIR__, 2) . '/wwwroot/catalogo/';

        if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $carpeta . $nombre))
            return ['success' => false, 'message' => 'Error al guardar la imagen.'];

        return ['success' => true, 'ruta' => '/wwwroot/catalogo/' . $nombre];
    }
}
