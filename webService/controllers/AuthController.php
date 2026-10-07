<?php
require_once dirname(__DIR__, 2) . '/apiService/core/mail.php';   // enviarCorreo() → api central

/**
 * Cuentas de BookArt: inicio de sesión, registro y recuperar contraseña (permiso: 1 = admin, 2 = usuario).
 * Una pantalla y su formulario comparten la URL: GET pinta la pantalla, POST hace la acción.
 *   /auth/entrar       → pantalla de sesión (entrar, crear cuenta, olvidé mi contraseña) · POST inicia sesión
 *   /auth/registrar    → crea la cuenta
 *   /auth/recuperar    → manda el enlace para restablecer la contraseña
 *   /auth/restablecer  → pantalla del enlace (?Token=) · POST guarda la contraseña nueva
 *   /auth/salir        → cierra la sesión
 * Las URLs anteriores (/inicio-sesion, /nueva-contrasena) redirigen aquí (index.php raíz).
 */
class AuthController extends Controller
{
    /** Vigencia del enlace para restablecer la contraseña (minutos). */
    private const RESET_MINUTES = 60;
    /** Tiempo que debe pasar para mandarle otro enlace a la misma cuenta (minutos). */
    private const RESET_WAIT_MINUTES = 2;

    /** Acciones que también son pantalla (GET la pinta, POST hace la acción). */
    private const SCREENS = ['entrar', 'restablecer'];

    /** Aquí todavía no hay sesión que exigir: las pantallas aceptan GET y todo lo demás es POST. */
    public function authorize(string $action): void
    {
        if (in_array($action, self::SCREENS, true) && $this->method === 'GET') return;
        $this->requirePost();
    }

    public function entrar(string $Usuario = '', string $Contrasena = ''): void
    {
        if ($this->method === 'GET') {
            if ($this->user() !== null) $this->redirect($this->isAdmin() ? '/administrador' : '/');
            $this->view('user/Inicio_sesion');
            return;
        }

        $this->ensure($Usuario !== '' && $Contrasena !== '', 'Usuario y contraseña son obligatorios.');

        $this->api->command('Auth', 'Cuenta', 'Authenticate');
        $this->api->addParameter('Usuario',    'S', $Usuario);
        $this->api->addParameter('Contrasena', 'S', hash('sha512', $Contrasena));
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->ensure($result->row() !== null, 'Usuario o contraseña incorrectos.', 401);
            $cuenta = Cuenta::fromRow($result->row());

            session_regenerate_id(true);
            $_SESSION['usuario']   = $cuenta->Usuario ?: $cuenta->Correo;
            $_SESSION['correo']    = $cuenta->Correo;
            $_SESSION['permiso']   = $cuenta->IdPermiso;
            $_SESSION['id_cuenta'] = $cuenta->IdCuenta;

            $this->success('Sesión iniciada.', ['redirect' => $cuenta->homePath(), 'permiso' => $cuenta->IdPermiso]);
        } else {
            $this->failure($result);
        }
    }

    public function registrar(Cuenta $cuenta, string $Contrasena = '', string $ConfirmaContrasena = ''): void
    {
        // El apellido materno nunca es obligatorio.
        $required = ['Nombre' => 'el nombre', 'Paterno' => 'el apellido paterno', 'Telefono' => 'el teléfono',
                     'Usuario' => 'el nombre de usuario', 'Correo' => 'el correo'];
        foreach ($required as $field => $label) {
            $this->ensure($cuenta->{$field} !== '', "Falta $label.");
        }
        // Los tamaños son los de las columnas (usuario y cuenta): lo que no cabe se rechaza aquí, no se recorta en la base.
        $fits = fn(?string $text, int $max): bool => (bool) preg_match('/^.{0,' . $max . '}$/us', (string) $text);
        $this->ensure($fits($cuenta->Nombre, 15) && $fits($cuenta->Paterno, 15) && $fits($cuenta->Materno, 15),
                      'El nombre y los apellidos admiten máximo 15 caracteres cada uno.');
        $this->ensure($fits($cuenta->Usuario, 15), 'El nombre de usuario admite máximo 15 caracteres.');
        $this->ensure((bool) preg_match('/^\d{10}$/', $cuenta->Telefono), 'El teléfono debe tener 10 dígitos.');
        $this->ensure(filter_var($cuenta->Correo, FILTER_VALIDATE_EMAIL) !== false && $fits($cuenta->Correo, 50),
                      'El correo no es válido.');
        $this->ensure($cuenta->Anio >= 1930 && $cuenta->Anio <= (int) date('Y') && checkdate($cuenta->Mes, $cuenta->Dia, $cuenta->Anio),
                      'La fecha de nacimiento no es válida.');
        $this->ensure($Contrasena === $ConfirmaContrasena, 'Las contraseñas no coinciden.');
        $this->ensure($this->isStrongPassword($Contrasena), 'La contraseña debe tener al menos 8 caracteres, con letras y números.');

        $this->api->command('Auth', 'Cuenta', 'Insert');
        $this->api->addParameter('Nombre',     'S', $cuenta->Nombre);
        $this->api->addParameter('Paterno',    'S', $cuenta->Paterno);
        $this->api->addParameter('Materno',    'S', $cuenta->Materno);
        $this->api->addParameter('Telefono',   'S', $cuenta->Telefono);
        $this->api->addParameter('Dia',        'I', $cuenta->Dia);
        $this->api->addParameter('Mes',        'I', $cuenta->Mes);
        $this->api->addParameter('Anio',       'I', $cuenta->Anio);
        $this->api->addParameter('Usuario',    'S', $cuenta->Usuario);
        $this->api->addParameter('Correo',     'S', $cuenta->Correo);
        $this->api->addParameter('Contrasena', 'S', hash('sha512', $Contrasena));
        $this->api->addParameter('IdPermiso',  'I', 2);
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->success('¡Cuenta creada exitosamente!', [], 201);
        } else {
            $this->failure($result);
        }
    }

    /** Paso 1: pide el correo y, si existe, manda un enlace con token de un solo uso. */
    public function recuperar(string $Correo = ''): void
    {
        $this->ensure(filter_var($Correo, FILTER_VALIDATE_EMAIL) !== false, 'El correo no es válido.');

        // Misma respuesta exista o no el correo, y aunque se limite el reenvío (evita enumerar cuentas).
        $generic = 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.';

        $this->api->command('Auth', 'Cuenta', 'ByEmail');
        $this->api->addParameter('Correo', 'S', $Correo);
        $result = $this->api->execute();
        if ($result->status()) $this->failure($result);
        if ($result->row() === null) $this->success($generic);
        $cuenta = Cuenta::fromRow($result->row());

        $this->api->command('Auth', 'ResetContrasena', 'Recent');
        $this->api->addParameter('IdCuenta', 'I', $cuenta->IdCuenta);
        $this->api->addParameter('Minutos',  'I', self::RESET_WAIT_MINUTES);
        $result = $this->api->execute();
        if ($result->status()) $this->failure($result);
        if ($result->row() !== null) $this->success($generic);

        $token = bin2hex(random_bytes(32));
        $this->api->command('Auth', 'ResetContrasena', 'Insert');
        $this->api->addParameter('IdCuenta',  'I', $cuenta->IdCuenta);
        $this->api->addParameter('TokenHash', 'S', hash('sha256', $token));
        $this->api->addParameter('Minutos',   'I', self::RESET_MINUTES);
        $result = $this->api->execute();
        if ($result->status()) response(500, false, 'No se pudo generar el enlace. Intenta de nuevo.');

        // Dominio fijo (APP_URL o producción), nunca el Host de la petición: evita que el enlace apunte a otro sitio.
        $origin = rtrim($_ENV['APP_URL'] ?? 'https://bookartencuadernaciones.com', '/');
        $link   = $origin . BASE_URL . '/auth/restablecer?Token=' . $token;

        $error = enviarCorreo($cuenta->Correo, CorreoRestablecer::SUBJECT, CorreoRestablecer::body($link));
        if ($error !== '') error_log('[BookArt recuperar] ' . $error);

        $this->success($generic);
    }

    /** Paso 2: con el token del correo, guarda la contraseña nueva. Sin token válido no cambia nada. */
    public function restablecer(string $Token = '', string $Contrasena = '', string $ConfirmaContrasena = ''): void
    {
        $validToken = (bool) preg_match('/^[a-f0-9]{64}$/', $Token);

        if ($this->method === 'GET') {
            $this->view('user/NuevaContrasena', ['token' => $Token, 'validToken' => $validToken]);
            return;
        }

        $this->ensure($validToken, 'El enlace no es válido. Pide uno nuevo.', 400);
        $this->ensure($Contrasena === $ConfirmaContrasena, 'Las contraseñas no coinciden.');
        $this->ensure($this->isStrongPassword($Contrasena), 'La contraseña debe tener al menos 8 caracteres, con letras y números.');

        $this->api->command('Auth', 'ResetContrasena', 'Redeem');
        $this->api->addParameter('TokenHash',  'S', hash('sha256', $Token));
        $this->api->addParameter('Contrasena', 'S', hash('sha512', $Contrasena));
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->success('Tu contraseña se actualizó. Ya puedes iniciar sesión.', ['redirect' => '/auth/entrar']);
        } else {
            $this->failure($result);
        }
    }

    public function salir(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->success('Sesión cerrada.', ['redirect' => '/auth/entrar']);
    }

    /** Al menos 8 caracteres, con letras y números. */
    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
    }
}
