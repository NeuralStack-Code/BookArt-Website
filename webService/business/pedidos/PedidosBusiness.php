<?php
/**
 * Entidad Pedidos: los pedidos ya realizados (lo que salió del carrito), para el cliente y para el administrador.
 * Procedures: webService/sql/sp/sp_pedidos.sql.
 */
class PedidosBusiness extends Entity
{
    public int     $IdCuenta = 0;
    public int     $IdPedido = 0;
    public string  $Estatus  = '';
    public ?string $Mensaje  = null;
    public float   $Precio   = 0.0;

    /** Los pedidos de la cuenta. */
    public function mine(): array
    {
        $this->db->command('sp_pedidos_mios');
        $this->db->addParameter('IdCuenta', $this->IdCuenta);
        return $this->db->execute();
    }

    /** El cliente cancela su pedido. 0 afectadas = no es suyo o ya no se puede cancelar. */
    public function cancel(): array
    {
        $this->db->command('sp_pedidos_cancelar');
        $this->db->addParameter('IdCuenta', $this->IdCuenta);
        $this->db->addParameter('IdPedido', $this->IdPedido);
        return $this->db->execute();
    }

    /** Todos los pedidos, con su cliente. */
    public function list(): array
    {
        $this->db->command('sp_pedidos_listar');
        return $this->db->execute();
    }

    /** Un pedido completo; sin filas = no existe. */
    public function get(): array
    {
        $this->db->command('sp_pedidos_detalle');
        $this->db->addParameter('IdPedido', $this->IdPedido);
        return $this->db->execute();
    }

    /** 'notfound' si el pedido no existe. */
    public function setStatus(): array
    {
        $this->db->command('sp_pedidos_estatus');
        $this->db->addParameter('IdPedido', $this->IdPedido);
        $this->db->addParameter('Estatus',  $this->Estatus);
        $this->db->addParameter('Mensaje',  $this->Mensaje);
        return $this->db->execute();
    }

    /** 'notfound' si no existe; 'invalido' si no es una libreta personalizada. */
    public function setPrice(): array
    {
        $this->db->command('sp_pedidos_precio');
        $this->db->addParameter('IdPedido', $this->IdPedido);
        $this->db->addParameter('Precio',   $this->Precio);
        return $this->db->execute();
    }

    /** Una fila con PedidosActivos, TotalClientes y VentasMes. */
    public function statistics(): array
    {
        $this->db->command('sp_pedidos_estadisticas');
        return $this->db->execute();
    }
}
