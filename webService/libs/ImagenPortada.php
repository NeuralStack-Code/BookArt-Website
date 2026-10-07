<?php
/** Portadas que suben los clientes para su libreta personalizada (/wwwroot/portadas). */
class ImagenPortada extends ImagenSubida
{
    protected const PUBLIC_FOLDER = '/wwwroot/portadas/';
    protected const PREFIX        = 'portada_';
    protected const MAX_MB        = 5;
    protected const EXTENSIONS    = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    protected const FORMATS_LABEL = 'JPG, PNG o WEBP';
}
