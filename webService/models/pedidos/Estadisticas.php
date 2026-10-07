<?php
/** Model de los números del panel del administrador. Lo carga sp_pedidos_estadisticas. */
class Estadisticas extends Model
{
    public int   $PedidosActivos = 0;
    public int   $TotalClientes  = 0;
    public float $VentasMes      = 0.0;   // lo entregado en el mes
}
