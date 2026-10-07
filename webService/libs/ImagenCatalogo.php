<?php
/** Imágenes de los productos del catálogo (/wwwroot/catalogo). */
class ImagenCatalogo extends ImagenSubida
{
    /** La que lleva un producto sin imagen propia (no se borra nunca). */
    public const DEFAULT = '/wwwroot/catalogo/imgNoEncontrada.png';

    protected const PUBLIC_FOLDER = '/wwwroot/catalogo/';
    protected const PREFIX        = 'producto_';
    protected const MAX_MB        = 3;
    protected const EXTENSIONS    = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif'];
    protected const FORMATS_LABEL = 'JPG, PNG o GIF';

    public static function delete(?string $path): void
    {
        if ($path !== self::DEFAULT) parent::delete($path);
    }
}
