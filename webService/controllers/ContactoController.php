<?php

/**
 * Recurso: contacto.  Ruta: /api/contacto (POST, sin action → index).
 */
class ContactoController
{
    private ContactoBusiness $contacto;

    public function __construct(?mysqli $conexion = null)
    {
        $this->contacto = new ContactoBusiness();
    }

    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            response(405, false, 'Método no permitido.');
        }

        $nombre  = trim($_POST['nombre']  ?? '');
        $email   = trim($_POST['email']   ?? '');
        $tel     = trim($_POST['tel']     ?? '');
        $mensaje = trim($_POST['mensaje'] ?? '');

        if ($nombre === '' || $email === '' || $mensaje === '') {
            response(422, false, 'Nombre, email y mensaje son obligatorios.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            response(422, false, 'El email no es válido.');
        }

        try {
            $this->contacto->enviar($nombre, $email, $tel, $mensaje);
            response(200, true, '¡Mensaje enviado! Te contactaremos pronto.');
        } catch (\Throwable $e) {
            error_log('BookArt contact error: ' . $e->getMessage());
            response(500, false, 'No pudimos enviar tu mensaje. Intenta de nuevo más tarde.');
        }
    }
}
