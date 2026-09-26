<?php
/**
 * Carrito de compras (pedidos con estatus 'carrito') + libretas personalizadas.
 * Aquí vive el SQL.
 */
class CarritoBusiness
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function contarCarrito(int $uid): int
    {
        $stmt = mysqli_prepare($this->db, "SELECT COUNT(*) AS total FROM pedidos WHERE IdCuenta = ? AND estatus = 'carrito'");
        mysqli_stmt_bind_param($stmt, 'i', $uid);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return (int) $row['total'];
    }

    public function verCarrito(int $uid): array
    {
        $sql = "SELECT p.idPedido, p.idTipoPedido,
                    CASE WHEN p.idTipoPedido = 1 THEN c.nombre ELSE 'Libreta Personalizada' END AS nombre,
                    CASE WHEN p.idTipoPedido = 1 THEN c.precio ELSE COALESCE(per.precio, 0) END AS precio,
                    CASE WHEN p.idTipoPedido = 1 THEN c.img    ELSE per.portada             END AS imagen
                FROM pedidos p
                LEFT JOIN catalogo     c   ON p.idCatalogo      = c.id_producto
                LEFT JOIN personalizada per ON p.idPersonalizada = per.id_personalizada
                WHERE p.IdCuenta = ? AND p.estatus = 'carrito'";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $uid);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        $items = [];
        while ($r = mysqli_fetch_assoc($res)) $items[] = $r;
        return $items;
    }

    /** Inserta la libreta personalizada; regresa su id (0 = falló). */
    public function crearPersonalizada(string $color, string $descripcion, string $portada, string $tam, string $opcion, string $tipoPapel): int
    {
        $stmt = mysqli_prepare($this->db,
            "INSERT INTO personalizada (color, descripcion, portada, tam, tipo_encuadernacion, tipo_papel) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssssss', $color, $descripcion, $portada, $tam, $opcion, $tipoPapel);
        mysqli_stmt_execute($stmt);
        $id = mysqli_insert_id($this->db);
        mysqli_stmt_close($stmt);
        return $id;
    }

    public function crearPedidoCarrito(int $idPersonalizada, int $uid): bool
    {
        $fecha = date('Y-m-d'); $hora = date('H:i:s');
        $stmt = mysqli_prepare($this->db,
            "INSERT INTO pedidos (fecha, hora, estatus, idPersonalizada, idTipoPedido, IdCuenta) VALUES (?, ?, 'carrito', ?, 2, ?)");
        mysqli_stmt_bind_param($stmt, 'ssii', $fecha, $hora, $idPersonalizada, $uid);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function eliminarPersonalizada(int $id): void
    {
        $stmt = mysqli_prepare($this->db, "DELETE FROM personalizada WHERE id_personalizada = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    /** Pasa el carrito a 'pendiente' (checkout). */
    public function checkout(int $uid): bool
    {
        $stmt = mysqli_prepare($this->db, "UPDATE pedidos SET estatus = 'pendiente' WHERE IdCuenta = ? AND estatus = 'carrito'");
        mysqli_stmt_bind_param($stmt, 'i', $uid);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function yaEnCarrito(int $uid, int $id, string $columna, int $idTipo): bool
    {
        $sql = "SELECT 1 FROM pedidos WHERE IdCuenta = ? AND $columna = ? AND idTipoPedido = ? AND estatus = 'carrito'";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, 'iii', $uid, $id, $idTipo);
        mysqli_stmt_execute($stmt);
        $hay = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
        mysqli_stmt_close($stmt);
        return $hay;
    }

    public function agregar(int $uid, int $id, string $columna, int $idTipo): bool
    {
        $fecha = date('Y-m-d'); $hora = date('H:i:s');
        $sql = "INSERT INTO pedidos (fecha, hora, estatus, $columna, idTipoPedido, IdCuenta) VALUES (?, ?, 'carrito', ?, ?, ?)";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, 'ssiii', $fecha, $hora, $id, $idTipo, $uid);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    /** Pedido del carrito del usuario (o null). */
    public function pedidoCarrito(int $idPedido, int $uid): ?array
    {
        $stmt = mysqli_prepare($this->db,
            "SELECT idPersonalizada, idTipoPedido FROM pedidos WHERE idPedido = ? AND IdCuenta = ? AND estatus = 'carrito'");
        mysqli_stmt_bind_param($stmt, 'ii', $idPedido, $uid);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function eliminarPedidoCarrito(int $idPedido, int $uid): void
    {
        $stmt = mysqli_prepare($this->db, "DELETE FROM pedidos WHERE idPedido = ? AND IdCuenta = ? AND estatus = 'carrito'");
        mysqli_stmt_bind_param($stmt, 'ii', $idPedido, $uid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    public function portadaDe(int $idPersonalizada): ?string
    {
        $stmt = mysqli_prepare($this->db, "SELECT portada FROM personalizada WHERE id_personalizada = ?");
        mysqli_stmt_bind_param($stmt, 'i', $idPersonalizada);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ? ($row['portada'] ?? null) : null;
    }
}
