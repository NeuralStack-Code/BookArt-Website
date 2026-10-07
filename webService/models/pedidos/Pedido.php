<?php
/**
 * Model Pedido: un pedido ya realizado. Lo cargan sp_pedidos_mios ("Mis pedidos") y sp_pedidos_listar (panel);
 * cada uno trae las columnas que usa y las demás quedan con su valor por defecto.
 */
class Pedido extends Model
{
    /** Estatus → [ícono, texto para el cliente]. */
    private const STATUS = [
        'pendiente' => ['⏳', 'Pendiente de revisión'],
        'visto'     => ['👀', 'Visto por el administrador'],
        'aprobado'  => ['✅', 'Aprobado'],
        'declinado' => ['❌', 'Declinado'],
        'proceso'   => ['🔨', 'En proceso de elaboración'],
        'terminado' => ['🎉', 'Terminado - Listo para entrega'],
        'entregado' => ['📦', 'Entregado'],
        'cancelado' => ['🚫', 'Cancelado'],
    ];

    /** Estatus que puede poner el administrador. */
    public const ADMIN_STATUSES = ['pendiente', 'visto', 'aprobado', 'declinado', 'proceso', 'terminado', 'entregado'];

    public ?int    $IdPedido      = null;
    public string  $Fecha         = '';
    public string  $Hora          = '';
    public string  $Estatus       = '';
    public ?string $Mensaje       = null;   // lo que el administrador le escribe al cliente
    public int     $IdTipoPedido  = 1;      // 1 = catálogo · 2 = libreta personalizada
    public string  $Nombre        = '';
    public float   $Precio        = 0.0;
    public ?string $Imagen        = null;
    public ?string $Descripcion   = null;
    public string  $ClienteNombre = '';
    public string  $ClienteCorreo = '';

    public function isCatalog(): bool
    {
        return $this->IdTipoPedido === 1;
    }

    /** El estatus en minúsculas (así se compara y así se llama su clase CSS). */
    public function status(): string
    {
        return strtolower($this->Estatus);
    }

    /** "⏳ Pendiente de revisión" */
    public function statusLabel(): string
    {
        return implode(' ', self::STATUS[$this->status()] ?? ['❓', 'Estado desconocido']);
    }

    /** El cliente puede cambiar o cancelar su pedido mientras nadie lo haya aprobado. */
    public function isOpen(): bool
    {
        return in_array($this->status(), ['pendiente', 'visto'], true);
    }

    /** #00012 */
    public function number(): string
    {
        return '#' . str_pad((string) $this->IdPedido, 5, '0', STR_PAD_LEFT);
    }

    public function image(): string
    {
        return $this->Imagen ?: '/webService/wwwroot/catalogo/imgNoEncontrada.png';
    }

    /** Una libreta personalizada no tiene precio hasta que el administrador la cotiza. */
    public function priceLabel(): string
    {
        return $this->isCatalog() || $this->Precio > 0 ? money($this->Precio) : 'A cotizar';
    }

    /** El mensaje del administrador se muestra cuando ya revisó el pedido. */
    public function showsMessage(): bool
    {
        return !empty($this->Mensaje)
            && in_array($this->status(), ['aprobado', 'proceso', 'terminado', 'visto', 'declinado', 'entregado'], true);
    }
}
