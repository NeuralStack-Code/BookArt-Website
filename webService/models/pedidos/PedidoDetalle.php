<?php
/** Model de un pedido completo (panel del administrador): suma el diseño y el contacto del cliente. Lo carga sp_pedidos_detalle. */
class PedidoDetalle extends Pedido
{
    public ?string $Color              = null;
    public ?string $Tamano             = null;
    public ?string $TipoEncuadernacion = null;
    public ?string $TipoPapel          = null;
    public ?string $ClienteTelefono    = null;
    public ?string $ClienteUsuario     = null;
}
