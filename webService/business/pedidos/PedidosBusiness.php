<?php
/**
 * Pedidos (usuario y admin). Aquí vive el SQL.
 */
class PedidosBusiness
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /* ---------------- Usuario ---------------- */

    public function misPedidos(int $uid): array
    {
        $sql = "SELECT p.idPedido, p.fecha, p.hora, p.estatus, p.mensaje, tp.descripcion AS tipo,
                    CASE WHEN tp.id_tipoPedido = 1 THEN c.nombre ELSE 'Libreta Personalizada' END AS producto,
                    CASE WHEN tp.id_tipoPedido = 1 THEN c.precio ELSE COALESCE(per.precio, 0) END AS precio,
                    CASE WHEN tp.id_tipoPedido = 1 THEN c.img    ELSE per.portada             END AS imagen
                FROM pedidos p
                INNER JOIN tipoPedido tp   ON p.idTipoPedido    = tp.id_tipoPedido
                LEFT  JOIN catalogo c      ON p.idCatalogo      = c.id_producto
                LEFT  JOIN personalizada per ON p.idPersonalizada = per.id_personalizada
                WHERE p.IdCuenta = ? AND p.estatus != 'carrito'
                ORDER BY p.fecha DESC, p.hora DESC";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $uid);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        $out = [];
        while ($r = mysqli_fetch_assoc($res)) $out[] = $r;
        return $out;
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

    /** Checkout del carrito → pendiente. @return int filas afectadas. */
    public function checkout(int $uid): int
    {
        $stmt = mysqli_prepare($this->db,
            "UPDATE pedidos SET estatus = 'pendiente', fecha = CURDATE(), hora = CURTIME() WHERE IdCuenta = ? AND estatus = 'carrito'");
        mysqli_stmt_bind_param($stmt, 'i', $uid);
        mysqli_stmt_execute($stmt);
        $filas = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        return $filas;
    }

    /** Pedido del usuario cancelable (estatus pendiente/visto), o null. */
    public function pedidoCancelable(int $id, int $uid): ?array
    {
        $stmt = mysqli_prepare($this->db,
            "SELECT idPedido, estatus, idPersonalizada FROM pedidos WHERE idPedido = ? AND idCuenta = ? AND estatus IN ('pendiente','visto')");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $uid);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function cancelar(int $id): bool
    {
        $stmt = mysqli_prepare($this->db, "UPDATE pedidos SET estatus = 'cancelado' WHERE idPedido = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    /** Pedido personalizada del usuario (estatus), o null. */
    public function pedidoEditable(int $idPedido, int $uid): ?array
    {
        $stmt = mysqli_prepare($this->db, "SELECT estatus FROM pedidos WHERE idPedido = ? AND IdCuenta = ? AND idTipoPedido = 2");
        mysqli_stmt_bind_param($stmt, 'ii', $idPedido, $uid);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function portadaDePersonalizada(int $idPer): ?string
    {
        $stmt = mysqli_prepare($this->db, "SELECT portada FROM personalizada WHERE id_personalizada = ?");
        mysqli_stmt_bind_param($stmt, 'i', $idPer);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ? ($row['portada'] ?? null) : null;
    }

    /** Actualiza la personalizada (con portada nueva si $portada !== null). */
    public function editarPersonalizada(int $idPer, string $color, string $desc, string $tam, string $opcion, string $tipoPapel, ?string $portada): bool
    {
        if ($portada !== null) {
            $stmt = mysqli_prepare($this->db,
                "UPDATE personalizada SET color=?, descripcion=?, portada=?, tam=?, tipo_encuadernacion=?, tipo_papel=? WHERE id_personalizada=?");
            mysqli_stmt_bind_param($stmt, 'ssssssi', $color, $desc, $portada, $tam, $opcion, $tipoPapel, $idPer);
        } else {
            $stmt = mysqli_prepare($this->db,
                "UPDATE personalizada SET color=?, descripcion=?, tam=?, tipo_encuadernacion=?, tipo_papel=? WHERE id_personalizada=?");
            mysqli_stmt_bind_param($stmt, 'sssssi', $color, $desc, $tam, $opcion, $tipoPapel, $idPer);
        }
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    /* ---------------- Admin ---------------- */

    public function adminListar(): array
    {
        $sql = "SELECT p.idPedido AS id, p.fecha, p.hora, p.estatus, tp.id_tipoPedido, tp.descripcion AS tipo,
                    CASE WHEN tp.id_tipoPedido = 1 THEN c.nombre ELSE 'Libreta Personalizada' END AS producto_nombre,
                    CASE WHEN tp.id_tipoPedido = 1 THEN c.precio ELSE COALESCE(per.precio, 0) END AS producto_precio,
                    u.nombre AS cliente_nombre, u.paterno, u.materno,
                    cu.correo AS cliente_correo, cu.usuario AS cliente_usuario
                FROM pedidos p
                INNER JOIN tipoPedido tp    ON p.idTipoPedido    = tp.id_tipoPedido
                LEFT  JOIN catalogo c       ON p.idCatalogo      = c.id_producto
                LEFT  JOIN personalizada per ON p.idPersonalizada = per.id_personalizada
                INNER JOIN cuenta cu        ON p.idCuenta        = cu.id_cuenta
                INNER JOIN usuario u        ON cu.usuario_id     = u.id_usuario
                WHERE p.estatus != 'carrito'
                ORDER BY p.fecha DESC, p.hora DESC";
        $res = mysqli_query($this->db, $sql);
        $out = [];
        while ($r = mysqli_fetch_assoc($res)) $out[] = $r;
        return $out;
    }

    public function adminDetalle(int $id): ?array
    {
        $sql = "SELECT p.*, tp.descripcion AS tipo_descripcion, tp.id_tipoPedido,
                    CASE WHEN tp.id_tipoPedido = 1 THEN c.nombre      ELSE 'Libreta Personalizada' END AS producto_nombre,
                    CASE WHEN tp.id_tipoPedido = 1 THEN c.precio      ELSE COALESCE(per.precio, 0) END AS producto_precio,
                    CASE WHEN tp.id_tipoPedido = 1 THEN c.descripcion ELSE per.descripcion         END AS producto_descripcion,
                    CASE WHEN tp.id_tipoPedido = 1 THEN c.img         ELSE per.portada             END AS producto_img,
                    per.color, per.tam, per.tipo_encuadernacion, per.tipo_papel,
                    u.nombre AS cliente_nombre, u.paterno, u.materno, u.tel AS cliente_tel,
                    cu.correo AS cliente_correo, cu.usuario AS cliente_usuario
                FROM pedidos p
                INNER JOIN tipoPedido tp    ON p.idTipoPedido    = tp.id_tipoPedido
                LEFT  JOIN catalogo c       ON p.idCatalogo      = c.id_producto
                LEFT  JOIN personalizada per ON p.idPersonalizada = per.id_personalizada
                INNER JOIN cuenta cu        ON p.idCuenta        = cu.id_cuenta
                INNER JOIN usuario u        ON cu.usuario_id     = u.id_usuario
                WHERE p.idPedido = ?";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function adminEstatus(int $id, string $estatus, string $mensaje): bool
    {
        $stmt = mysqli_prepare($this->db, "UPDATE pedidos SET estatus = ?, mensaje = ? WHERE idPedido = ?");
        mysqli_stmt_bind_param($stmt, 'ssi', $estatus, $mensaje, $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function pedidoParaPrecio(int $idPedido): ?array
    {
        $stmt = mysqli_prepare($this->db, "SELECT idPersonalizada, idTipoPedido FROM pedidos WHERE idPedido = ?");
        mysqli_stmt_bind_param($stmt, 'i', $idPedido);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function asignarPrecio(int $idPer, float $precio): bool
    {
        $stmt = mysqli_prepare($this->db, "UPDATE personalizada SET precio = ? WHERE id_personalizada = ?");
        mysqli_stmt_bind_param($stmt, 'di', $precio, $idPer);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function estadisticas(): array
    {
        $activos = mysqli_fetch_assoc(mysqli_query($this->db,
            "SELECT COUNT(*) AS total FROM pedidos WHERE estatus NOT IN ('carrito','entregado','declinado')"))['total'] ?? 0;

        $clientes = mysqli_fetch_assoc(mysqli_query($this->db,
            "SELECT COUNT(DISTINCT id_cuenta) AS total FROM cuenta WHERE permiso_id = 2"))['total'] ?? 0;

        $ventas = mysqli_fetch_assoc(mysqli_query($this->db,
            "SELECT (
                SELECT COALESCE(SUM(c.precio),0) FROM pedidos p
                INNER JOIN catalogo c ON p.idCatalogo = c.id_producto
                WHERE p.idTipoPedido = 1 AND p.estatus = 'entregado'
                AND MONTH(p.fecha) = MONTH(CURDATE()) AND YEAR(p.fecha) = YEAR(CURDATE())
             ) + (
                SELECT COALESCE(SUM(per.precio),0) FROM pedidos p
                INNER JOIN personalizada per ON p.idPersonalizada = per.id_personalizada
                WHERE p.idTipoPedido = 2 AND p.estatus = 'entregado' AND per.precio > 0
                AND MONTH(p.fecha) = MONTH(CURDATE()) AND YEAR(p.fecha) = YEAR(CURDATE())
             ) AS total"))['total'] ?? 0;

        return [
            'pedidos_activos' => (int) $activos,
            'total_clientes'  => (int) $clientes,
            'ventas_mes'      => (float) $ventas,
        ];
    }
}
