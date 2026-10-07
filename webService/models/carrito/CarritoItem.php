<?php
/** Model de un artículo del carrito. Lo carga CarritoItem::fromTable() desde sp_carrito_listar. */
class CarritoItem extends Model
{
    public ?int    $IdPedido     = null;
    public int     $IdTipoPedido = 1;      // 1 = catálogo · 2 = libreta personalizada
    public string  $Nombre       = '';
    public float   $Precio       = 0.0;
    public ?string $Imagen       = null;   // ruta pública; una personalizada puede no traer portada

    public function isCatalog(): bool
    {
        return $this->IdTipoPedido === 1;
    }

    /** Imagen a mostrar: la suya o la predeterminada. */
    public function image(): string
    {
        return $this->Imagen ?: '/webService/wwwroot/catalogo/imgNoEncontrada.png';
    }

    /** Una libreta personalizada no tiene precio hasta que el administrador la cotiza. */
    public function priceLabel(): string
    {
        return $this->isCatalog() ? money($this->Precio) : 'A cotizar';
    }
}
