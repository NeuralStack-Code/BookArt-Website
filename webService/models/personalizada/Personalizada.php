<?php
/**
 * Model Personalizada: el diseño de una libreta a la medida. Lo carga Personalizada::fromForm() desde el
 * formulario de diseño y Personalizada::fromRow() desde sp_personalizada_obtener.
 */
class Personalizada extends Model
{
    /** Opciones del formulario (lo que no esté aquí no se guarda). */
    public const BINDINGS = ['encuadernacionClasica' => 'Encuadernación Clásica', 'diseñoPiel' => 'Diseño en Piel', 'diseñoEngargolado' => 'Diseño Engargolado'];
    public const SIZES    = ['Chica 11cm x 12cm' => 'Chica (11cm x 12cm)', 'Mediana 14cm x 23cm' => 'Mediana (14cm x 23cm)', 'Grande 18cm x 23cm' => 'Grande (18cm x 23cm)'];
    public const PAPERS   = ['Ahuesado' => 'Ahuesado', 'Capuchino' => 'Capuchino (Reciclado)', 'Blanco' => 'Blanco'];

    public ?int    $IdPedido           = null;   // con él es un cambio; sin él, un diseño nuevo
    public string  $Estatus            = '';
    public string  $TipoEncuadernacion = '';
    public string  $Tamano             = '';
    public string  $TipoPapel          = '';
    public string  $Color              = '';
    public string  $Descripcion        = '';
    public ?string $Portada            = null;   // ruta pública de la imagen que subió

    /** El cliente puede cambiar su diseño mientras nadie lo haya aprobado. */
    public function isOpen(): bool
    {
        return in_array(strtolower($this->Estatus), ['pendiente', 'visto'], true);
    }

    /** #00012 */
    public function number(): string
    {
        return '#' . str_pad((string) $this->IdPedido, 5, '0', STR_PAD_LEFT);
    }
}
