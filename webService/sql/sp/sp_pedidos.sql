-- ============================================================
-- BookArt · Procedures del módulo Pedidos (webService/controllers/PedidosController.php)
-- Base: u165852803_bookart · Idempotente (DROP + CREATE)
-- Antes: sp_sistema.sql
--
-- Un pedido es una fila de pedidos que ya salió del carrito (estatus distinto de 'carrito').
-- Tipo 1 = producto del catálogo · 2 = libreta personalizada (su precio lo asigna el administrador).
-- Las columnas salen con el nombre de la propiedad de los models Pedido y PedidoDetalle.
-- ============================================================

DELIMITER $$

-- Los pedidos de una cuenta (pantalla "Mis pedidos").
DROP PROCEDURE IF EXISTS sp_pedidos_mios $$
CREATE PROCEDURE sp_pedidos_mios(IN pIdCuenta INT)
SQL SECURITY INVOKER
BEGIN
    SELECT p.idPedido AS IdPedido, p.fecha AS Fecha, p.hora AS Hora, p.estatus AS Estatus, p.mensaje AS Mensaje,
           p.idTipoPedido AS IdTipoPedido,
           CASE WHEN p.idTipoPedido = 1 THEN c.nombre      ELSE 'Libreta Personalizada' END AS Nombre,
           CASE WHEN p.idTipoPedido = 1 THEN c.precio      ELSE COALESCE(per.precio, 0) END AS Precio,
           CASE WHEN p.idTipoPedido = 1 THEN c.img         ELSE per.portada             END AS Imagen,
           CASE WHEN p.idTipoPedido = 1 THEN c.descripcion ELSE per.descripcion         END AS Descripcion
      FROM pedidos p
      LEFT JOIN catalogo c        ON p.idCatalogo      = c.id_producto
      LEFT JOIN personalizada per ON p.idPersonalizada = per.id_personalizada
     WHERE p.idCuenta = pIdCuenta AND p.estatus <> 'carrito'
     ORDER BY p.fecha DESC, p.hora DESC;
END $$

-- El cliente cancela su pedido mientras esté pendiente o visto. 0 afectadas = no es suyo o ya no se puede.
DROP PROCEDURE IF EXISTS sp_pedidos_cancelar $$
CREATE PROCEDURE sp_pedidos_cancelar(IN pIdCuenta INT, IN pIdPedido INT)
SQL SECURITY INVOKER
BEGIN
    UPDATE pedidos
       SET estatus = 'cancelado'
     WHERE idPedido = pIdPedido AND idCuenta = pIdCuenta AND estatus IN ('pendiente', 'visto');

    SELECT ROW_COUNT() AS Afectadas;
END $$

-- Todos los pedidos, con su cliente (panel del administrador).
DROP PROCEDURE IF EXISTS sp_pedidos_listar $$
CREATE PROCEDURE sp_pedidos_listar()
SQL SECURITY INVOKER
BEGIN
    SELECT p.idPedido AS IdPedido, p.fecha AS Fecha, p.hora AS Hora, p.estatus AS Estatus, p.idTipoPedido AS IdTipoPedido,
           CASE WHEN p.idTipoPedido = 1 THEN c.nombre ELSE 'Libreta Personalizada' END AS Nombre,
           CASE WHEN p.idTipoPedido = 1 THEN c.precio ELSE COALESCE(per.precio, 0) END AS Precio,
           CONCAT_WS(' ', u.nombre, u.paterno, NULLIF(u.materno, '')) AS ClienteNombre,
           cu.correo AS ClienteCorreo
      FROM pedidos p
      LEFT JOIN catalogo c        ON p.idCatalogo      = c.id_producto
      LEFT JOIN personalizada per ON p.idPersonalizada = per.id_personalizada
     INNER JOIN cuenta cu         ON p.idCuenta        = cu.id_cuenta
     INNER JOIN usuario u         ON cu.usuario_id     = u.id_usuario
     WHERE p.estatus <> 'carrito'
     ORDER BY p.fecha DESC, p.hora DESC;
END $$

-- Un pedido completo: producto o diseño, y los datos de contacto del cliente. Sin filas = no existe.
DROP PROCEDURE IF EXISTS sp_pedidos_detalle $$
CREATE PROCEDURE sp_pedidos_detalle(IN pIdPedido INT)
SQL SECURITY INVOKER
BEGIN
    SELECT p.idPedido AS IdPedido, p.fecha AS Fecha, p.hora AS Hora, p.estatus AS Estatus, p.mensaje AS Mensaje,
           p.idTipoPedido AS IdTipoPedido,
           CASE WHEN p.idTipoPedido = 1 THEN c.nombre      ELSE 'Libreta Personalizada' END AS Nombre,
           CASE WHEN p.idTipoPedido = 1 THEN c.precio      ELSE COALESCE(per.precio, 0) END AS Precio,
           CASE WHEN p.idTipoPedido = 1 THEN c.img         ELSE per.portada             END AS Imagen,
           CASE WHEN p.idTipoPedido = 1 THEN c.descripcion ELSE per.descripcion         END AS Descripcion,
           per.color AS Color, per.tam AS Tamano, per.tipo_encuadernacion AS TipoEncuadernacion, per.tipo_papel AS TipoPapel,
           CONCAT_WS(' ', u.nombre, u.paterno, NULLIF(u.materno, '')) AS ClienteNombre,
           cu.correo AS ClienteCorreo, u.tel AS ClienteTelefono, cu.usuario AS ClienteUsuario
      FROM pedidos p
      LEFT JOIN catalogo c        ON p.idCatalogo      = c.id_producto
      LEFT JOIN personalizada per ON p.idPersonalizada = per.id_personalizada
     INNER JOIN cuenta cu         ON p.idCuenta        = cu.id_cuenta
     INNER JOIN usuario u         ON cu.usuario_id     = u.id_usuario
     WHERE p.idPedido = pIdPedido AND p.estatus <> 'carrito';
END $$

-- El administrador cambia el estatus y deja un mensaje para el cliente. 'notfound' si el pedido no existe.
DROP PROCEDURE IF EXISTS sp_pedidos_estatus $$
CREATE PROCEDURE sp_pedidos_estatus(IN pIdPedido INT, IN pEstatus VARCHAR(60), IN pMensaje VARCHAR(250))
SQL SECURITY INVOKER
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pedidos WHERE idPedido = pIdPedido AND estatus <> 'carrito') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'notfound:Pedido no encontrado.';
    END IF;

    UPDATE pedidos SET estatus = pEstatus, mensaje = COALESCE(pMensaje, '') WHERE idPedido = pIdPedido;

    SELECT 1 AS Afectadas;
END $$

-- El administrador cotiza una libreta personalizada. 'notfound' si no existe; 'invalido' si es del catálogo.
DROP PROCEDURE IF EXISTS sp_pedidos_precio $$
CREATE PROCEDURE sp_pedidos_precio(IN pIdPedido INT, IN pPrecio DECIMAL(10,2))
SQL SECURITY INVOKER
BEGIN
    DECLARE vIdPedido        INT DEFAULT NULL;
    DECLARE vIdPersonalizada INT DEFAULT NULL;
    DECLARE vIdTipoPedido    INT DEFAULT NULL;

    SELECT idPedido, idPersonalizada, idTipoPedido INTO vIdPedido, vIdPersonalizada, vIdTipoPedido
      FROM pedidos WHERE idPedido = pIdPedido;

    IF vIdPedido IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'notfound:Pedido no encontrado.';
    END IF;
    IF vIdTipoPedido <> 2 OR vIdPersonalizada IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'invalido:Solo se puede asignar precio a libretas personalizadas.';
    END IF;

    UPDATE personalizada SET precio = pPrecio WHERE id_personalizada = vIdPersonalizada;

    SELECT 1 AS Afectadas;
END $$

-- Números del panel: pedidos activos, clientes y ventas entregadas del mes.
DROP PROCEDURE IF EXISTS sp_pedidos_estadisticas $$
CREATE PROCEDURE sp_pedidos_estadisticas()
SQL SECURITY INVOKER
BEGIN
    SELECT
        (SELECT COUNT(*) FROM pedidos WHERE estatus NOT IN ('carrito', 'entregado', 'declinado')) AS PedidosActivos,
        (SELECT COUNT(*) FROM cuenta WHERE permiso_id = 2) AS TotalClientes,
        (SELECT COALESCE(SUM(c.precio), 0)
           FROM pedidos p
          INNER JOIN catalogo c ON p.idCatalogo = c.id_producto
          WHERE p.idTipoPedido = 1 AND p.estatus = 'entregado'
            AND MONTH(p.fecha) = MONTH(CURDATE()) AND YEAR(p.fecha) = YEAR(CURDATE()))
      + (SELECT COALESCE(SUM(per.precio), 0)
           FROM pedidos p
          INNER JOIN personalizada per ON p.idPersonalizada = per.id_personalizada
          WHERE p.idTipoPedido = 2 AND p.estatus = 'entregado' AND per.precio > 0
            AND MONTH(p.fecha) = MONTH(CURDATE()) AND YEAR(p.fecha) = YEAR(CURDATE())) AS VentasMes;
END $$

DELIMITER ;
