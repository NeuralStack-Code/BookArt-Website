<?php
/** Model Producto del catálogo. Lo carga Producto::fromTable() / fromRow() desde sp_catalogo_listar y sp_catalogo_obtener. */
class Producto extends Model
{
    public ?int   $IdProducto  = null;
    public string $Nombre      = '';
    public string $Descripcion = '';
    public float  $Precio      = 0.0;
    public string $Imagen      = '';     // ruta pública de la imagen
}
