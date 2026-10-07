-- ============================================================
-- BookArt · Procedures del módulo Personalizada (webService/controllers/PersonalizadaController.php)
-- Base: u165852803_bookart · Idempotente (DROP + CREATE)
-- Antes: sp_sistema.sql
--
-- Una libreta personalizada es un diseño (tabla personalizada) + su pedido (tabla pedidos, tipo 2).
-- Siempre se identifica por el pedido Y la cuenta: nadie toca el diseño de otra cuenta.
-- Las columnas salen con el nombre de la propiedad del model Personalizada.
-- ============================================================

DELIMITER $$

-- Guarda el diseño y lo deja en el carrito de la cuenta, todo o nada. Regresa el id del pedido.
DROP PROCEDURE IF EXISTS sp_personalizada_crear $$
CREATE PROCEDURE sp_personalizada_crear(
    IN pIdCuenta           INT,
    IN pTipoEncuadernacion VARCHAR(25),
    IN pTamano             VARCHAR(25),
    IN pTipoPapel          VARCHAR(10),
    IN pColor              VARCHAR(15),
    IN pDescripcion        VARCHAR(150),
    IN pPortada            VARCHAR(255)
)
SQL SECURITY INVOKER
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    INSERT INTO personalizada (tipo_encuadernacion, tam, tipo_papel, color, descripcion, portada)
    VALUES (pTipoEncuadernacion, pTamano, pTipoPapel, pColor, pDescripcion, COALESCE(pPortada, ''));

    INSERT INTO pedidos (fecha, hora, estatus, idPersonalizada, idTipoPedido, idCuenta)
    VALUES (CURDATE(), CURTIME(), 'carrito', LAST_INSERT_ID(), 2, pIdCuenta);

    COMMIT;

    SELECT LAST_INSERT_ID() AS Id;
END $$

-- El diseño de un pedido de la cuenta. Sin filas = no existe, no es suyo o no es una libreta personalizada.
DROP PROCEDURE IF EXISTS sp_personalizada_obtener $$
CREATE PROCEDURE sp_personalizada_obtener(IN pIdCuenta INT, IN pIdPedido INT)
SQL SECURITY INVOKER
BEGIN
    SELECT p.idPedido AS IdPedido, p.estatus AS Estatus,
           per.tipo_encuadernacion AS TipoEncuadernacion, per.tam AS Tamano, per.tipo_papel AS TipoPapel,
           per.color AS Color, per.descripcion AS Descripcion, per.portada AS Portada
      FROM pedidos p
     INNER JOIN personalizada per ON p.idPersonalizada = per.id_personalizada
     WHERE p.idPedido = pIdPedido AND p.idCuenta = pIdCuenta AND p.idTipoPedido = 2;
END $$

-- Cambia el diseño de un pedido de la cuenta. pPortada NULL o '' = conservar la que ya tiene.
-- 'notfound' si no es su pedido; 'en_uso' si ya no está pendiente ni visto.
DROP PROCEDURE IF EXISTS sp_personalizada_editar $$
CREATE PROCEDURE sp_personalizada_editar(
    IN pIdCuenta           INT,
    IN pIdPedido           INT,
    IN pTipoEncuadernacion VARCHAR(25),
    IN pTamano             VARCHAR(25),
    IN pTipoPapel          VARCHAR(10),
    IN pColor              VARCHAR(15),
    IN pDescripcion        VARCHAR(150),
    IN pPortada            VARCHAR(255)
)
SQL SECURITY INVOKER
BEGIN
    DECLARE vIdPersonalizada INT DEFAULT NULL;
    DECLARE vEstatus         VARCHAR(60) DEFAULT NULL;

    SELECT idPersonalizada, estatus INTO vIdPersonalizada, vEstatus
      FROM pedidos
     WHERE idPedido = pIdPedido AND idCuenta = pIdCuenta AND idTipoPedido = 2;

    IF vIdPersonalizada IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'notfound:Pedido no encontrado.';
    END IF;
    IF LOWER(vEstatus) NOT IN ('pendiente', 'visto') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'en_uso:Este pedido ya no puede editarse.';
    END IF;

    UPDATE personalizada
       SET tipo_encuadernacion = pTipoEncuadernacion, tam = pTamano, tipo_papel = pTipoPapel,
           color = pColor, descripcion = pDescripcion,
           portada = COALESCE(NULLIF(pPortada, ''), portada)
     WHERE id_personalizada = vIdPersonalizada;

    SELECT 1 AS Afectadas;
END $$

DELIMITER ;
