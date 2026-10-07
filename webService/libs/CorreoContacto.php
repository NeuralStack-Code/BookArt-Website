<?php
/** Correo que le llega a BookArt cuando alguien escribe en el formulario de contacto (lo manda ContactoController::enviar por la api central). */
class CorreoContacto
{
    public const SUBJECT = 'Nuevo mensaje de contacto — BookArt';

    public static function body(string $nombre, string $correo, string $telefono, string $mensaje): string
    {
        return '<html><head><meta charset="UTF-8"></head>'
             . '<body style="font-family:sans-serif; font-size:15px; color:#333;">'
             . '<h2 style="color:#5a3e28;">Nuevo mensaje de contacto — BookArt</h2>'
             . '<p><strong>Nombre:</strong> ' . e($nombre) . '</p>'
             . '<p><strong>Email:</strong> ' . e($correo) . '</p>'
             . '<p><strong>Teléfono:</strong> ' . e($telefono) . '</p>'
             . '<p><strong>Mensaje:</strong><br>' . nl2br(e($mensaje)) . '</p>'
             . '</body></html>';
    }

    /** El mismo mensaje sin formato, para los lectores de correo que no muestran HTML. */
    public static function text(string $nombre, string $correo, string $telefono, string $mensaje): string
    {
        return "Nombre: {$nombre}\nEmail: {$correo}\nTeléfono: {$telefono}\nMensaje: {$mensaje}";
    }
}
