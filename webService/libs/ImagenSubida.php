<?php
/**
 * Imágenes que sube la gente (productos del catálogo, portadas de libretas). Cada tipo es una clase que
 * solo dice dónde se guardan, cuánto pueden pesar y qué formatos acepta; lo demás vive aquí una sola vez.
 * Se guardan en /wwwroot/<carpeta> (fuera del repo) y la base guarda su ruta pública.
 * El formato se decide por el contenido del archivo, nunca por su nombre.
 */
abstract class ImagenSubida
{
    /** Carpeta pública, con diagonal al inicio y al final. */
    protected const PUBLIC_FOLDER = '';
    protected const PREFIX        = '';
    protected const MAX_MB        = 3;
    /** Formatos aceptados: tipo de imagen => extensión con la que se guarda. */
    protected const EXTENSIONS    = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];
    protected const FORMATS_LABEL = 'JPG o PNG';

    /** ¿El formulario trae un archivo? */
    public static function sent(?array $file): bool
    {
        return $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /** Por qué no se puede aceptar el archivo, o null si está bien. */
    public static function problem(array $file): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_OK;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || ($file['size'] ?? 0) > static::MAX_MB * 1024 * 1024) {
            return 'La imagen no debe superar ' . static::MAX_MB . 'MB.';
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            return 'No se pudo recibir la imagen.';
        }
        return self::extension($file['tmp_name']) === null
            ? 'Formato de imagen no válido (usa ' . static::FORMATS_LABEL . ').'
            : null;
    }

    /** Guarda el archivo y regresa su ruta pública, o null si no se pudo. */
    public static function store(array $file): ?string
    {
        $extension = self::extension($file['tmp_name']);
        if ($extension === null) return null;

        $folder = self::root() . static::PUBLIC_FOLDER;
        if (!is_dir($folder)) mkdir($folder, 0755, true);

        $name = uniqid(static::PREFIX) . '.' . $extension;
        return move_uploaded_file($file['tmp_name'], $folder . $name) ? static::PUBLIC_FOLDER . $name : null;
    }

    /** Borra una imagen de esta carpeta. Lo que esté fuera de ella no se toca. */
    public static function delete(?string $path): void
    {
        if ($path === null || !str_starts_with($path, static::PUBLIC_FOLDER)) return;
        $file = self::root() . static::PUBLIC_FOLDER . basename($path);
        if (is_file($file)) unlink($file);
    }

    private static function extension(string $tmpFile): ?string
    {
        $info = @getimagesize($tmpFile);
        return $info ? (static::EXTENSIONS[$info[2]] ?? null) : null;
    }

    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }
}
