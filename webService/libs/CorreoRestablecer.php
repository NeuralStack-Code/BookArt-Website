<?php
/** Correo con el enlace para restablecer la contraseña (lo manda AuthController::recuperar por la api central). */
class CorreoRestablecer
{
    public const SUBJECT = 'Restablece tu contraseña · BookArt';

    public static function body(string $link): string
    {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
             . '<body style="font-family:Arial,sans-serif;background:#FFF9F0;padding:2rem;">'
             . '<div style="max-width:520px;margin:0 auto;background:#fff;border:3px solid #4A3830;padding:2rem;">'
             . '<h2 style="color:#4A3830;margin-top:0;">Restablece tu contraseña</h2>'
             . '<p style="color:#4A3830;line-height:1.6;">Recibimos una solicitud para cambiar la contraseña de tu cuenta en BookArt Encuadernaciones.</p>'
             . '<p style="margin:1.5rem 0;"><a href="' . htmlspecialchars($link) . '" style="background:#1E9332;color:#fff;padding:.8rem 1.6rem;text-decoration:none;font-weight:700;border:3px solid #4A3830;display:inline-block;">Elegir nueva contraseña</a></p>'
             . '<p style="color:#7A6A60;font-size:.85rem;">El enlace vence en 1 hora y solo funciona una vez. Si no pediste este cambio, ignora este correo: tu contraseña sigue igual.</p>'
             . '</div></body></html>';
    }
}
