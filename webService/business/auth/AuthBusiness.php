<?php
/**
 * Autenticación y cuentas de BookArt. Contraseñas con hash sha512 (no crypto).
 * Aquí vive el SQL contra las tablas cuenta y usuario.
 */
class AuthBusiness
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /** Busca la cuenta por usuario/correo + hash; regresa la fila o null. */
    public function autenticar(string $usuario, string $hash): ?array
    {
        $sql = "SELECT id_cuenta, usuario, correo, permiso_id
                FROM cuenta
                WHERE (correo = ? OR usuario = ?) AND contrasenia = ?
                ORDER BY (correo = ?) DESC
                LIMIT 1";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, 'ssss', $usuario, $usuario, $hash, $usuario);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function correoExiste(string $correo): bool
    {
        $stmt = mysqli_prepare($this->db, "SELECT 1 FROM cuenta WHERE correo = ?");
        mysqli_stmt_bind_param($stmt, 's', $correo);
        mysqli_stmt_execute($stmt);
        $hay = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
        mysqli_stmt_close($stmt);
        return $hay;
    }

    public function usuarioExiste(string $usuario): bool
    {
        $stmt = mysqli_prepare($this->db, "SELECT 1 FROM cuenta WHERE usuario = ?");
        mysqli_stmt_bind_param($stmt, 's', $usuario);
        mysqli_stmt_execute($stmt);
        $hay = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
        mysqli_stmt_close($stmt);
        return $hay;
    }

    /** Inserta el usuario (datos personales); regresa su id. */
    public function crearUsuario(array $d): int
    {
        $stmt = mysqli_prepare($this->db,
            "INSERT INTO usuario (nombre, paterno, materno, tel, dia, mes, anio)
             VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssssss',
            $d['nombre'], $d['paterno'], $d['materno'], $d['tel'], $d['Dia'], $d['Mes'], $d['anio']);
        mysqli_stmt_execute($stmt);
        $id = mysqli_insert_id($this->db);
        mysqli_stmt_close($stmt);
        return $id;
    }

    public function crearCuenta(string $usuario, string $correo, string $hash, int $permiso, int $idUsuario): bool
    {
        $stmt = mysqli_prepare($this->db,
            "INSERT INTO cuenta (usuario, correo, contrasenia, permiso_id, usuario_id)
             VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssii', $usuario, $correo, $hash, $permiso, $idUsuario);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function eliminarUsuario(int $idUsuario): void
    {
        $stmt = mysqli_prepare($this->db, "DELETE FROM usuario WHERE id_usuario = ?");
        mysqli_stmt_bind_param($stmt, 'i', $idUsuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    /* ── Recuperación de contraseña (tabla reset_contrasena) ── */

    public function idCuentaPorCorreo(string $correo): ?int
    {
        $stmt = mysqli_prepare($this->db, "SELECT id_cuenta FROM cuenta WHERE correo = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $correo);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ? (int) $row['id_cuenta'] : null;
    }

    /** ¿Ya se pidió un enlace para esta cuenta en los últimos minutos? (evita spam de correos). */
    public function resetReciente(int $idCuenta, int $minutos): bool
    {
        $stmt = mysqli_prepare($this->db,
            "SELECT 1 FROM reset_contrasena
             WHERE cuenta_id = ? AND usado = 0 AND creado > NOW() - INTERVAL ? MINUTE LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ii', $idCuenta, $minutos);
        mysqli_stmt_execute($stmt);
        $hay = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
        mysqli_stmt_close($stmt);
        return $hay;
    }

    /** Invalida los enlaces anteriores de la cuenta y guarda el nuevo (solo el hash). Vigencia en la hora de la BD. */
    public function crearReset(int $idCuenta, string $tokenHash, int $minutosVigencia): bool
    {
        $del = mysqli_prepare($this->db, "DELETE FROM reset_contrasena WHERE cuenta_id = ?");
        mysqli_stmt_bind_param($del, 'i', $idCuenta);
        mysqli_stmt_execute($del);
        mysqli_stmt_close($del);

        $stmt = mysqli_prepare($this->db,
            "INSERT INTO reset_contrasena (cuenta_id, token_hash, expira)
             VALUES (?, ?, NOW() + INTERVAL ? MINUTE)");
        mysqli_stmt_bind_param($stmt, 'isi', $idCuenta, $tokenHash, $minutosVigencia);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    /**
     * Consume el token de forma atómica: lo marca usado solo si está vigente y sin usar.
     * Regresa el id de la cuenta, o null si el enlace es inválido, ya expiró o ya se usó.
     */
    public function consumirReset(string $tokenHash): ?int
    {
        $upd = mysqli_prepare($this->db,
            "UPDATE reset_contrasena SET usado = 1
             WHERE token_hash = ? AND usado = 0 AND expira > NOW()");
        mysqli_stmt_bind_param($upd, 's', $tokenHash);
        mysqli_stmt_execute($upd);
        $consumido = mysqli_stmt_affected_rows($upd) === 1;
        mysqli_stmt_close($upd);
        if (!$consumido) return null;

        $stmt = mysqli_prepare($this->db, "SELECT cuenta_id FROM reset_contrasena WHERE token_hash = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $tokenHash);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ? (int) $row['cuenta_id'] : null;
    }

    public function actualizarPassword(int $idCuenta, string $hash): bool
    {
        $stmt = mysqli_prepare($this->db, "UPDATE cuenta SET contrasenia = ? WHERE id_cuenta = ?");
        mysqli_stmt_bind_param($stmt, 'si', $hash, $idCuenta);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
}
