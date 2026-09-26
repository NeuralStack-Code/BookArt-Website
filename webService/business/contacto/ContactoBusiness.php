<?php
require_once dirname(__DIR__, 3) . '/apiService/core/mail.php'; // enviarCorreo() → API central

/**
 * Envío del formulario de contacto por la API central (correo centralizado).
 * NO usa SMTP local ni MAIL_* del producto. Lanza \Throwable si falla (el
 * ContactoController lo atrapa y responde 500).
 */
class ContactoBusiness
{
    public function enviar(string $nombre, string $email, string $tel, string $mensaje): void
    {
        $nombreSafe   = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $emailSafe    = htmlspecialchars($email,  ENT_QUOTES, 'UTF-8');
        $telefonoSafe = htmlspecialchars($tel,    ENT_QUOTES, 'UTF-8');
        $mensajeSafe  = nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'));

        $body = "
<html>
<head><meta charset='UTF-8'></head>
<body style='font-family:sans-serif; font-size:15px; color:#333;'>
    <h2 style='color:#5a3e28;'>Nuevo mensaje de contacto — BookArt</h2>
    <p><strong>Nombre:</strong> {$nombreSafe}</p>
    <p><strong>Email:</strong> {$emailSafe}</p>
    <p><strong>Teléfono:</strong> {$telefonoSafe}</p>
    <p><strong>Mensaje:</strong><br>{$mensajeSafe}</p>
</body>
</html>
";
        // Destino de los mensajes (no es credencial SMTP): configurable por el producto.
        $destino = $_ENV['MAIL_TO'] ?? ($_ENV['MAIL_FROM'] ?? '');
        $altBody = "Nombre: {$nombre}\nEmail: {$email}\nTeléfono: {$tel}\nMensaje: {$mensaje}";

        $err = enviarCorreo($destino, 'Nuevo mensaje de contacto — BookArt', $body, [
            'replyTo'     => $email,
            'replyToName' => $nombre,
            'altBody'     => $altBody,
        ]);
        if ($err !== '') {
            throw new \RuntimeException($err);
        }
    }
}
