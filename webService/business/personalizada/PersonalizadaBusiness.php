<?php
/**
 * Entidad Personalizada: el diseño de una libreta a la medida y su pedido (tablas personalizada + pedidos).
 * Siempre va con la cuenta: nadie lee ni cambia el diseño de otra. Procedures: webService/sql/sp/sp_personalizada.sql.
 */
class PersonalizadaBusiness extends Entity
{
    public int     $IdCuenta           = 0;
    public int     $IdPedido           = 0;
    public string  $TipoEncuadernacion = '';
    public string  $Tamano             = '';
    public string  $TipoPapel          = '';
    public string  $Color              = '';
    public string  $Descripcion        = '';
    public ?string $Portada            = null;   // ruta pública; al editar, null = conservar la que ya tiene

    /** Guarda el diseño y lo deja en el carrito. Regresa el Id del pedido. */
    public function insert(): array
    {
        $this->db->command('sp_personalizada_crear');
        $this->db->addParameter('IdCuenta',           $this->IdCuenta);
        $this->db->addParameter('TipoEncuadernacion', $this->TipoEncuadernacion);
        $this->db->addParameter('Tamano',             $this->Tamano);
        $this->db->addParameter('TipoPapel',          $this->TipoPapel);
        $this->db->addParameter('Color',              $this->Color);
        $this->db->addParameter('Descripcion',        $this->Descripcion);
        $this->db->addParameter('Portada',            $this->Portada);
        return $this->db->execute();
    }

    /** El diseño de un pedido de la cuenta; sin filas = no existe o no es suyo. */
    public function get(): array
    {
        $this->db->command('sp_personalizada_obtener');
        $this->db->addParameter('IdCuenta', $this->IdCuenta);
        $this->db->addParameter('IdPedido', $this->IdPedido);
        return $this->db->execute();
    }

    /** 'notfound' si no es su pedido; 'en_uso' si ya no está pendiente ni visto. */
    public function update(): array
    {
        $this->db->command('sp_personalizada_editar');
        $this->db->addParameter('IdCuenta',           $this->IdCuenta);
        $this->db->addParameter('IdPedido',           $this->IdPedido);
        $this->db->addParameter('TipoEncuadernacion', $this->TipoEncuadernacion);
        $this->db->addParameter('Tamano',             $this->Tamano);
        $this->db->addParameter('TipoPapel',          $this->TipoPapel);
        $this->db->addParameter('Color',              $this->Color);
        $this->db->addParameter('Descripcion',        $this->Descripcion);
        $this->db->addParameter('Portada',            $this->Portada);
        return $this->db->execute();
    }
}
