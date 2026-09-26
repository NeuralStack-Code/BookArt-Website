<?php
/**
 * Productos del catálogo (tabla catalogo). Aquí vive el SQL.
 */
class CatalogoBusiness
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function obtener(int $id): ?array
    {
        $stmt = mysqli_prepare($this->db, "SELECT * FROM catalogo WHERE id_producto = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function listarTodos(): array
    {
        $res = mysqli_query($this->db, "SELECT * FROM catalogo ORDER BY id_producto DESC");
        $out = [];
        while ($r = mysqli_fetch_assoc($res)) $out[] = $r;
        return $out;
    }

    public function nombreExiste(string $nombre, int $excepto = 0): bool
    {
        if ($excepto > 0) {
            $stmt = mysqli_prepare($this->db, "SELECT 1 FROM catalogo WHERE nombre = ? AND id_producto != ?");
            mysqli_stmt_bind_param($stmt, 'si', $nombre, $excepto);
        } else {
            $stmt = mysqli_prepare($this->db, "SELECT 1 FROM catalogo WHERE nombre = ?");
            mysqli_stmt_bind_param($stmt, 's', $nombre);
        }
        mysqli_stmt_execute($stmt);
        $hay = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
        mysqli_stmt_close($stmt);
        return $hay;
    }

    public function crear(string $nombre, string $descripcion, float $precio, string $img): bool
    {
        $stmt = mysqli_prepare($this->db, "INSERT INTO catalogo (nombre, descripcion, precio, img) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssds', $nombre, $descripcion, $precio, $img);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function editar(int $id, string $nombre, string $descripcion, float $precio): bool
    {
        $stmt = mysqli_prepare($this->db, "UPDATE catalogo SET nombre = ?, descripcion = ?, precio = ? WHERE id_producto = ?");
        mysqli_stmt_bind_param($stmt, 'ssdi', $nombre, $descripcion, $precio, $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    /** Regresa la fila {img} del producto (o null si no existe). */
    public function obtenerImg(int $id): ?array
    {
        $stmt = mysqli_prepare($this->db, "SELECT img FROM catalogo WHERE id_producto = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function eliminar(int $id): bool
    {
        $stmt = mysqli_prepare($this->db, "DELETE FROM catalogo WHERE id_producto = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
}
