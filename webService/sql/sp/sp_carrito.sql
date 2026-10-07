-- ============================================================
-- BookArt · Procedures del módulo Carrito (webService/controllers/CarritoController.php)
-- Base: u165852803_bookart · Idempotente (DROP + CREATE)
-- Antes: sp_sistema.sql
--
-- El carrito son los pedidos de la cuenta con estatus 'carrito' (tipo 1 = catálogo, 2 = personalizada).
-- Las columnas salen con el nombre de la propiedad del model CarritoItem.
-- ============================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_carrito_listar $$
CREATE PROCEDURE sp_carrito_listar(IN pIdCuenta INT)
SQL SECURITY INVOKER
BEGIN
    SELECT p.idPedido AS IdPedido, p.idTipoPedido AS IdTipoPedido,
           CASE WHEN p.idTipoPedido = 1 THEN c.nombre ELSE 'Libreta Personalizada' END AS Nombre,
           CASE WHEN p.idTipoPedido = 1 THEN c.precio ELSE COALESCE(per.precio, 0) END AS Precio,
           CASE WHEN p.idTipoPedido = 1 THEN c.img    ELSE per.portada             END AS Imagen
      FROM pedidos p
      LEFT JOIN catalogo c        ON p.idCatalogo      = c.id_producto
      LEFT JOIN personalizada per ON p.idPersonalizada = per.id_personalizada
     WHERE p.idCuenta = pIdCuenta AND p.estatus = 'carrito'
     ORDER BY p.fecha DESC, p.hora DESC;
END $$

DROP PROCEDURE IF EXISTS sp_carrito_contar $$
CREATE PROCEDURE sp_carrito_contar(IN pIdCuenta INT)
SQL SECURITY INVOKER
BEGIN
    SELECT COUNT(*) AS Total FROM pedidos WHERE idCuenta = pIdCuenta AND estatus = 'carrito';
END $$

-- Agrega un producto del catálogo. 'notfound' si el producto no existe; 'dup' si ya está en el carrito.
DROP PROCEDURE IF EXISTS sp_carrito_agregar $$
CREATE PROCEDURE sp_carrito_agregar(IN pIdCuenta INT, IN pIdProducto INT)
SQL SECURITY INVOKER
BEGIN
    IF NOT EXISTS (SELECT 1 FROM catalogo WHERE id_producto = pIdProducto) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'notfound:Producto no encontrado.';
    END IF;
    IF EXISTS (SELECT 1 FROM pedidos
                WHERE idCuenta = pIdCuenta AND idCatalogo = pIdProducto AND idTipoPedido = 1 AND estatus = 'carrito') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dup:Este producto ya está en tu carrito.';
    END IF;

    INSERT INTO pedidos (fecha, hora, estatus, idCatalogo, idTipoPedido, idCuenta)
    VALUES (CURDATE(), CURTIME(), 'carrito', pIdProducto, 1, pIdCuenta);

    SELECT LAST_INSERT_ID() AS Id;
END $$

-- Quita un artículo del carrito; si era una libreta personalizada, borra también su diseño.
-- Regresa la portada que tenía (para que el controller borre el archivo). 'notfound' si no está en el carrito.
DROP PROCEDURE IF EXISTS sp_carrito_quitar $$
CREATE PROCEDURE sp_carrito_quitar(IN pIdCuenta INT, IN pIdPedido INT)
SQL SECURITY INVOKER
BEGIN
    DECLARE vIdPedido        INT DEFAULT NULL;
    DECLARE vIdPersonalizada INT DEFAULT NULL;
    DECLARE vIdTipoPedido    INT DEFAULT NULL;
    DECLARE vPortada         VARCHAR(255) DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT idPedido, idPersonalizada, idTipoPedido INTO vIdPedido, vIdPersonalizada, vIdTipoPedido
      FROM pedidos
     WHERE idPedido = pIdPedido AND idCuenta = pIdCuenta AND estatus = 'carrito'
       FOR UPDATE;

    IF vIdPedido IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'notfound:Pedido no encontrado.';
    END IF;

    DELETE FROM pedidos WHERE idPedido = vIdPedido;

    IF vIdTipoPedido = 2 AND vIdPersonalizada IS NOT NULL THEN
        SELECT portada INTO vPortada FROM personalizada WHERE id_personalizada = vIdPersonalizada;
        DELETE FROM personalizada WHERE id_personalizada = vIdPersonalizada;
    END IF;

    COMMIT;

    SELECT 1 AS Afectadas, vPortada AS Portada;
END $$

-- Realiza el pedido: todo el carrito pasa a 'pendiente' con la fecha y hora de este momento.
DROP PROCEDURE IF EXISTS sp_carrito_confirmar $$
CREATE PROCEDURE sp_carrito_confirmar(IN pIdCuenta INT)
SQL SECURITY INVOKER
BEGIN
    UPDATE pedidos
       SET estatus = 'pendiente', fecha = CURDATE(), hora = CURTIME()
     WHERE idCuenta = pIdCuenta AND estatus = 'carrito';

    SELECT ROW_COUNT() AS Afectadas;
END $$

DELIMITER ;
