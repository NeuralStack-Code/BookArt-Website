<?php
require_once dirname(__DIR__, 2) . '/apiService/core/mail.php';   // enviarCorreo() → api central

/**
 * Recurso: auth.  Ruta: /api/auth?action=login|register|logout|forgot|reset
 * Cuentas de BookArt (permiso: 1 = admin, 2 = usuario).
 */
class AuthController
{
    private AuthBusiness $auth;
    private string       $method;

    public function __construct(mysqli $conexion)
    {
        $this->auth   = new AuthBusiness($conexion);
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /** Despacha por ?action (el router siempre entra por index). */
    public function index(): void
    {
        match (trim($_GET['action'] ?? '')) {
            'login'    => $this->login(),
            'register' => $this->register(),
            'logout'   => $this->logout(),
            'forgot'   => $this->forgot(),
            'reset'    => $this->reset(),
            default    => response(404, false, 'Acción no encontrada.'),
        };
    }

    public function login(): void
    {
        if ($this->method !== 'POST') response(405, false, 'Método no permitido.');

        $usuario = trim($_POST['sesionUsuario'] ?? '');
        $contra  = trim($_POST['sesionContra']  ?? '');
        if ($usuario === '' || $contra === '') response(422, false, 'Usuario y contraseña son obligatorios.');

        $row = $this->auth->autenticar($usuario, hash('sha512', $contra));
        if (!$row) response(401, false, 'Usuario o contraseña incorrectos.');

        $_SESSION['usuario']   = $row['usuario'] ?: $row['correo'];
        $_SESSION['correo']    = $row['correo'];
        $_SESSION['permiso']   = $row['permiso_id'];
        $_SESSION['id_cuenta'] = $row['id_cuenta'];

        $redirect = $row['permiso_id'] == 1 ? '/administrador' : '/catalogo';
        response(200, true, 'Sesión iniciada.', ['redirect' => $redirect, 'permiso' => (int) $row['permiso_id']]);
    }

    public function register(): void
    {
        if ($this->method !== 'POST') response(405, false, 'Método no permitido.');

        $campos = ['nombre', 'paterno', 'materno', 'tel', 'Dia', 'Mes', 'anio', 'usuario', 'correo', 'contrasena'];
        $data = [];
        foreach ($campos as $campo) {
            $val = trim($_POST[$campo] ?? '');
            if ($val === '') response(422, false, "El campo '$campo' es obligatorio.");
            $data[$campo] = $val;
        }
        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) response(422, false, 'El correo no es válido.');

        if ($this->auth->correoExiste($data['correo']))   response(409, false, 'Este correo ya está registrado.');
        if ($this->auth->usuarioExiste($data['usuario'])) response(409, false, 'Este nombre de usuario ya está registrado.');

        $idUsuario = $this->auth->crearUsuario($data);
        $hash      = hash('sha512', $data['contrasena']);
        if (!$this->auth->crearCuenta($data['usuario'], $data['correo'], $hash, 2, $idUsuario)) {
            $this->auth->eliminarUsuario($idUsuario); // rollback
            response(500, false, 'Error al crear la cuenta.');
        }
        response(201, true, '¡Cuenta creada exitosamente!');
    }

    public function logout(): void
    {
        if ($this->method !== 'POST') response(405, false, 'Método no permitido.');
        $_SESSION = [];
        session_destroy();
        response(200, true, 'Sesión cerrada.', ['redirect' => '/inicio-sesion']);
    }

    /** Paso 1: pide el correo y, si existe, manda un enlace con token de un solo uso (1 hora). */
    public function forgot(): void
    {
        if ($this->method !== 'POST') response(405, false, 'Método no permitido.');

        $correo = trim($_POST['correoRecupera'] ?? '');
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) response(422, false, 'El correo no es válido.');

        // Misma respuesta exista o no el correo, y aunque se limite el reenvío (evita enumerar cuentas).
        $generico = 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.';

        $idCuenta = $this->auth->idCuentaPorCorreo($correo);
        if (!$idCuenta || $this->auth->resetReciente($idCuenta, 2)) response(200, true, $generico);

        $token = bin2hex(random_bytes(32));
        if (!$this->auth->crearReset($idCuenta, hash('sha256', $token), 60)) {
            response(500, false, 'No se pudo generar el enlace. Intenta de nuevo.');
        }

        // Dominio fijo (APP_URL o producción), nunca el Host de la petición: evita que el enlace apunte a otro sitio.
        $origen = rtrim($_ENV['APP_URL'] ?? 'https://bookartencuadernaciones.com', '/');
        $link   = $origen . BASE_URL . '/nueva-contrasena?token=' . $token;

        $cuerpo = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
                . '<body style="font-family:Arial,sans-serif;background:#FFF9F0;padding:2rem;">'
                . '<div style="max-width:520px;margin:0 auto;background:#fff;border:3px solid #4A3830;padding:2rem;">'
                . '<h2 style="color:#4A3830;margin-top:0;">Restablece tu contraseña</h2>'
                . '<p style="color:#4A3830;line-height:1.6;">Recibimos una solicitud para cambiar la contraseña de tu cuenta en BookArt Encuadernaciones.</p>'
                . '<p style="margin:1.5rem 0;"><a href="' . htmlspecialchars($link) . '" style="background:#1E9332;color:#fff;padding:.8rem 1.6rem;text-decoration:none;font-weight:700;border:3px solid #4A3830;display:inline-block;">Elegir nueva contraseña</a></p>'
                . '<p style="color:#7A6A60;font-size:.85rem;">El enlace vence en 1 hora y solo funciona una vez. Si no pediste este cambio, ignora este correo: tu contraseña sigue igual.</p>'
                . '</div></body></html>';

        $err = enviarCorreo($correo, 'Restablece tu contraseña · BookArt', $cuerpo);
        if ($err !== '') error_log('[BookArt forgot] ' . $err);

        response(200, true, $generico);
    }

    /** Paso 2: con el token del correo, guarda la nueva contraseña. Sin token válido no cambia nada. */
    public function reset(): void
    {
        if ($this->method !== 'POST') response(405, false, 'Método no permitido.');

        $token    = trim($_POST['token'] ?? '');
        $nueva    = trim($_POST['recuperaContra']  ?? '');   // login y registro también recortan
        $confirma = trim($_POST['recuperaContra2'] ?? '');

        if (!preg_match('/^[a-f0-9]{64}$/', $token)) response(400, false, 'El enlace no es válido. Pide uno nuevo.');
        if ($nueva !== $confirma)                    response(422, false, 'Las contraseñas no coinciden.');
        if (strlen($nueva) < 8 || !preg_match('/[A-Za-z]/', $nueva) || !preg_match('/[0-9]/', $nueva)) {
            response(422, false, 'La contraseña debe tener al menos 8 caracteres, con letras y números.');
        }

        $idCuenta = $this->auth->consumirReset(hash('sha256', $token));
        if (!$idCuenta) response(400, false, 'El enlace no es válido, ya se usó o venció. Pide uno nuevo.');

        if (!$this->auth->actualizarPassword($idCuenta, hash('sha512', $nueva))) {
            response(500, false, 'No se pudo actualizar la contraseña. Pide un enlace nuevo.');
        }
        response(200, true, 'Tu contraseña se actualizó. Ya puedes iniciar sesión.', ['redirect' => '/inicio-sesion']);
    }
}
