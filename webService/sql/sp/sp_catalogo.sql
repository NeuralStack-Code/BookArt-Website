-- ============================================================
-- BookArt · Procedures del módulo Catálogo (webService/controllers/CatalogoController.php)
-- Base: u165852803_bookart · Idempotente (DROP + CREATE)
-- Antes: sp_sistema.sql
--
-- Las columnas salen con el nombre de la propiedad del model Producto.
-- ============================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_catalogo_listar $$
CREATE PROCEDURE sp_catalogo_listar()
SQL SECURITY INVOKER
BEGIN
    SELECT id_producto AS IdProducto, nombre AS Nombre, descripcion AS Descripcion, precio AS Precio, img AS Imagen
      FROM catalogo
     ORDER BY id_producto DESC;
END $$

-- Un producto. Sin filas = no existe.
DROP PROCEDURE IF EXISTS sp_catalogo_obtener $$
CREATE PROCEDURE sp_catalogo_obtener(IN pIdProducto INT)
SQL SECURITY INVOKER
BEGIN
    SELECT id_producto AS IdProducto, nombre AS Nombre, descripcion AS Descripcion, precio AS Precio, img AS Imagen
      FROM catalogo
     WHERE id_producto = pIdProducto;
END $$

DROP PROCEDURE IF EXISTS sp_catalogo_crear $$
CREATE PROCEDURE sp_catalogo_crear(
    IN pNombre      VARCHAR(35),
    IN pDescripcion VARCHAR(500),
    IN pPrecio      FLOAT,
    IN pImagen      VARCHAR(255)
)
SQL SECURITY INVOKER
BEGIN
    DECLARE EXIT HANDLER FOR 1062
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dup:Ya existe un producto con ese nombre.';

    INSERT INTO catalogo (nombre, descripcion, precio, img)
    VALUES (pNombre, pDescripcion, pPrecio, pImagen);

    SELECT LAST_INSERT_ID() AS Id;
END $$

-- pImagen NULL o '' = conservar la imagen que ya tiene.
DROP PROCEDURE IF EXISTS sp_catalogo_editar $$
CREATE PROCEDURE sp_catalogo_editar(
    IN pIdProducto  INT,
    IN pNombre      VARCHAR(35),
    IN pDescripcion VARCHAR(500),
    IN pPrecio      FLOAT,
    IN pImagen      VARCHAR(255)
)
SQL SECURITY INVOKER
BEGIN
    DECLARE EXIT HANDLER FOR 1062
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dup:Ya existe otro producto con ese nombre.';

    IF NOT EXISTS (SELECT 1 FROM catalogo WHERE id_producto = pIdProducto) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'notfound:Producto no encontrado.';
    END IF;

    UPDATE catalogo
       SET nombre = pNombre, descripcion = pDescripcion, precio = pPrecio,
           img = COALESCE(NULLIF(pImagen, ''), img)
     WHERE id_producto = pIdProducto;

    SELECT 1 AS Afectadas;
END $$

-- 'en_uso' si algún pedido tiene el producto (pedidos.idCatalogo).
DROP PROCEDURE IF EXISTS sp_catalogo_eliminar $$
CREATE PROCEDURE sp_catalogo_eliminar(IN pIdProducto INT)
SQL SECURITY INVOKER
BEGIN
    DECLARE EXIT HANDLER FOR 1451
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'en_uso:No se puede eliminar: hay pedidos con este producto.';

    DELETE FROM catalogo WHERE id_producto = pIdProducto;

    SELECT ROW_COUNT() AS Afectadas;
END $$

DELIMITER ;
