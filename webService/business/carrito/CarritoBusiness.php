<?php
/**
 * Entidad Carrito: los pedidos de una cuenta que aún no se realizan (estatus 'carrito').
 * Procedures: webService/sql/sp/sp_carrito.sql.
 */
class CarritoBusiness extends Entity
{
    public int $IdCuenta   = 0;
    public int $IdProducto = 0;
    public int $IdPedido   = 0;

    public function list(): array
    {
        $this->db->command('sp_carrito_listar');
        $this->db->addParameter('IdCuenta', $this->IdCuenta);
        return $this->db->execute();
    }

    /** Una fila con Total. */
    public function count(): array
    {
        $this->db->command('sp_carrito_contar');
        $this->db->addParameter('IdCuenta', $this->IdCuenta);
        return $this->db->execute();
    }

    /** Agrega un producto del catálogo. 'notfound' si no existe; 'dup' si ya está en el carrito. */
    public function add(): array
    {
        $this->db->command('sp_carrito_agregar');
        $this->db->addParameter('IdCuenta',   $this->IdCuenta);
        $this->db->addParameter('IdProducto', $this->IdProducto);
        return $this->db->execute();
    }

    /** Quita un artículo (y su diseño, si era personalizada). Regresa Afectadas y la Portada que tenía. */
    public function remove(): array
    {
        $this->db->command('sp_carrito_quitar');
        $this->db->addParameter('IdCuenta', $this->IdCuenta);
        $this->db->addParameter('IdPedido', $this->IdPedido);
        return $this->db->execute();
    }

    /** Realiza el pedido: el carrito pasa a 'pendiente'. Regresa cuántos artículos eran. */
    public function confirm(): array
    {
        $this->db->command('sp_carrito_confirmar');
        $this->db->addParameter('IdCuenta', $this->IdCuenta);
        return $this->db->execute();
    }
}
