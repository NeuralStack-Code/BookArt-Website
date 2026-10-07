<?php
/** Entidad Catalogo: los productos del catálogo (tabla catalogo). Procedures: webService/sql/sp/sp_catalogo.sql. */
class CatalogoBusiness extends Entity
{
    public ?int    $IdProducto  = null;
    public string  $Nombre      = '';
    public string  $Descripcion = '';
    public float   $Precio      = 0.0;
    public ?string $Imagen      = null;   // ruta pública; al editar, null = conservar la que ya tiene

    public function list(): array
    {
        $this->db->command('sp_catalogo_listar');
        return $this->db->execute();
    }

    /** Un producto; sin filas = no existe. */
    public function get(): array
    {
        $this->db->command('sp_catalogo_obtener');
        $this->db->addParameter('IdProducto', $this->IdProducto);
        return $this->db->execute();
    }

    /** Regresa el Id nuevo; 'dup' si ya hay un producto con ese nombre. */
    public function insert(): array
    {
        $this->db->command('sp_catalogo_crear');
        $this->db->addParameter('Nombre',      $this->Nombre);
        $this->db->addParameter('Descripcion', $this->Descripcion);
        $this->db->addParameter('Precio',      $this->Precio);
        $this->db->addParameter('Imagen',      $this->Imagen);
        return $this->db->execute();
    }

    /** 'notfound' si no existe; 'dup' si otro producto ya tiene ese nombre. */
    public function update(): array
    {
        $this->db->command('sp_catalogo_editar');
        $this->db->addParameter('IdProducto',  $this->IdProducto);
        $this->db->addParameter('Nombre',      $this->Nombre);
        $this->db->addParameter('Descripcion', $this->Descripcion);
        $this->db->addParameter('Precio',      $this->Precio);
        $this->db->addParameter('Imagen',      $this->Imagen);
        return $this->db->execute();
    }

    /** Regresa las filas afectadas; 'en_uso' si algún pedido tiene el producto. */
    public function delete(): array
    {
        $this->db->command('sp_catalogo_eliminar');
        $this->db->addParameter('IdProducto', $this->IdProducto);
        return $this->db->execute();
    }
}
