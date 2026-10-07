<?php
require_once dirname(__DIR__, 2) . '/apiService/core/mail.php';   // enviarCorreo() → api central

/**
 * Contacto: cualquiera puede escribirle a BookArt, sin cuenta.
 *   /contacto/index   → pantalla de contacto
 *   /contacto/enviar  → manda el mensaje por correo a BookArt
 * No guarda nada en la base: el mensaje sale por la api central (correo centralizado, sin SMTP local).
 */
class ContactoController extends Controller
{
    /** No usa la base. */
    public const CONNECTION = 'ninguna';

    protected array $publicActions = ['index', 'enviar'];

    public function index(): void
    {
        if ($this->isAdmin()) $this->redirect('/administrador');     // el administrador trabaja en su panel
        $this->view('user/Contacto');
    }

    public function enviar(string $Nombre = '', string $Correo = '', string $Telefono = '', string $Mensaje = ''): void
    {
        $this->ensure($Nombre !== '' && $Correo !== '' && $Mensaje !== '', 'Nombre, email y mensaje son obligatorios.');
        $this->ensure(filter_var($Correo, FILTER_VALIDATE_EMAIL) !== false, 'El email no es válido.');
        $this->ensure((bool) preg_match('/^.{1,100}$/us', $Nombre) && (bool) preg_match('/^.{0,30}$/us', $Telefono),
                      'El nombre admite máximo 100 caracteres y el teléfono 30.');
        $this->ensure((bool) preg_match('/^.{1,3000}$/us', $Mensaje), 'El mensaje admite máximo 3000 caracteres.');

        // A quién le llega (no es una credencial): lo define el proyecto en su .env.
        $to    = $_ENV['MAIL_TO'] ?? ($_ENV['MAIL_FROM'] ?? '');
        $error = $to === '' ? 'Falta MAIL_TO en el .env.' : enviarCorreo(
            $to, CorreoContacto::SUBJECT, CorreoContacto::body($Nombre, $Correo, $Telefono, $Mensaje), [
                'replyTo'     => $Correo,                // al responder el correo, le llega a quien escribió
                'replyToName' => $Nombre,
                'altBody'     => CorreoContacto::text($Nombre, $Correo, $Telefono, $Mensaje),
            ]);

        if ($error === '') {
            $this->success('¡Mensaje enviado! Te contactaremos pronto.');
        } else {
            error_log('[BookArt contacto] ' . $error);
            response(500, false, 'No pudimos enviar tu mensaje. Intenta de nuevo más tarde.');
        }
    }
}
