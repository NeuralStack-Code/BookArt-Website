-- ============================================================
-- BookArt · Procedures del módulo Auth (webService/controllers/AuthController.php)
-- Base: u165852803_bookart · Idempotente (DROP + CREATE)
-- Antes: sp_sistema.sql y ../2026-09-26_reset_contrasena.sql (la tabla de los enlaces).
--
-- La contraseña llega ya en hash (sha512): la base nunca recibe la contraseña.
--
-- OJO intercalaciones: las tablas son utf8mb4_unicode_ci y la conexión llega como
-- utf8mb4_uca1400_ai_ci. Al comparar un parámetro de texto contra una columna se fija
-- con COLLATE o falla con "Illegal mix of collations" (ver apiService/core/procedure.php).
-- ============================================================

DELIMITER $$

-- Login: entra con el correo o con el nombre de usuario. Sin filas = credenciales incorrectas.
-- Si el dato coincide con el correo de una cuenta y con el usuario de otra, gana el correo.
DROP PROCEDURE IF EXISTS sp_cuenta_autenticar $$
CREATE PROCEDURE sp_cuenta_autenticar(IN pUsuario VARCHAR(50), IN pContrasena VARCHAR(150))
SQL SECURITY INVOKER
BEGIN
    SELECT c.id_cuenta AS IdCuenta, c.usuario AS Usuario, c.correo AS Correo,
           c.permiso_id AS IdPermiso, c.usuario_id AS IdUsuario
      FROM cuenta c
     WHERE (c.correo = pUsuario COLLATE utf8mb4_unicode_ci OR c.usuario = pUsuario COLLATE utf8mb4_unicode_ci)
       AND c.contrasenia = pContrasena COLLATE utf8mb4_unicode_ci
     ORDER BY (c.correo = pUsuario COLLATE utf8mb4_unicode_ci) DESC
     LIMIT 1;
END $$

-- La cuenta de un correo (sin validar contraseña). Sin filas = no existe.
DROP PROCEDURE IF EXISTS sp_cuenta_por_correo $$
CREATE PROCEDURE sp_cuenta_por_correo(IN pCorreo VARCHAR(50))
SQL SECURITY INVOKER
BEGIN
    SELECT c.id_cuenta AS IdCuenta, c.usuario AS Usuario, c.correo AS Correo,
           c.permiso_id AS IdPermiso, c.usuario_id AS IdUsuario
      FROM cuenta c
     WHERE c.correo = pCorreo COLLATE utf8mb4_unicode_ci
     LIMIT 1;
END $$

-- Alta de usuario (datos personales) + cuenta, en una transacción. Regresa el id de la cuenta.
DROP PROCEDURE IF EXISTS sp_cuenta_crear $$
CREATE PROCEDURE sp_cuenta_crear(
    IN pNombre     VARCHAR(15),
    IN pPaterno    VARCHAR(15),
    IN pMaterno    VARCHAR(15),
    IN pTelefono   VARCHAR(10),
    IN pDia        INT,
    IN pMes        INT,
    IN pAnio       INT,
    IN pUsuario    VARCHAR(15),
    IN pCorreo     VARCHAR(50),
    IN pContrasena VARCHAR(150),
    IN pIdPermiso  INT
)
SQL SECURITY INVOKER
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    IF EXISTS (SELECT 1 FROM cuenta WHERE correo = pCorreo COLLATE utf8mb4_unicode_ci) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dup:Este correo ya está registrado.';
    END IF;
    IF EXISTS (SELECT 1 FROM cuenta WHERE usuario = pUsuario COLLATE utf8mb4_unicode_ci) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dup:Este nombre de usuario ya está registrado.';
    END IF;

    INSERT INTO usuario (nombre, paterno, materno, tel, dia, mes, anio)
    VALUES (pNombre, pPaterno, pMaterno, pTelefono, pDia, pMes, pAnio);

    INSERT INTO cuenta (usuario, correo, contrasenia, permiso_id, usuario_id)
    VALUES (pUsuario, pCorreo, pContrasena, pIdPermiso, LAST_INSERT_ID());

    COMMIT;

    SELECT LAST_INSERT_ID() AS Id;
END $$

-- ¿La cuenta ya pidió un enlace hace poco y sigue sin usarlo? (evita llenar de correos una bandeja)
DROP PROCEDURE IF EXISTS sp_reset_reciente $$
CREATE PROCEDURE sp_reset_reciente(IN pIdCuenta INT, IN pMinutos INT)
SQL SECURITY INVOKER
BEGIN
    SELECT id_reset AS IdReset
      FROM reset_contrasena
     WHERE cuenta_id = pIdCuenta AND usado = 0 AND creado > NOW() - INTERVAL pMinutos MINUTE
     LIMIT 1;
END $$

-- Guarda el enlace nuevo (solo el hash del token) y borra los anteriores de la cuenta.
-- La vigencia se calcula con la hora de la base.
DROP PROCEDURE IF EXISTS sp_reset_crear $$
CREATE PROCEDURE sp_reset_crear(IN pIdCuenta INT, IN pTokenHash CHAR(64), IN pMinutos INT)
SQL SECURITY INVOKER
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    DELETE FROM reset_contrasena WHERE cuenta_id = pIdCuenta;

    INSERT INTO reset_contrasena (cuenta_id, token_hash, expira)
    VALUES (pIdCuenta, pTokenHash, NOW() + INTERVAL pMinutos MINUTE);

    COMMIT;

    SELECT LAST_INSERT_ID() AS Id;
END $$

-- Usa el enlace: lo marca como usado y cambia la contraseña, todo o nada.
-- 'invalido' si el enlace no existe, ya se usó o venció.
DROP PROCEDURE IF EXISTS sp_reset_usar $$
CREATE PROCEDURE sp_reset_usar(IN pTokenHash CHAR(64), IN pContrasena VARCHAR(150))
SQL SECURITY INVOKER
BEGIN
    DECLARE vIdReset  INT DEFAULT NULL;
    DECLARE vIdCuenta INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT id_reset, cuenta_id INTO vIdReset, vIdCuenta
      FROM reset_contrasena
     WHERE token_hash = pTokenHash COLLATE utf8mb4_unicode_ci AND usado = 0 AND expira > NOW()
     LIMIT 1
       FOR UPDATE;

    IF vIdReset IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'invalido:El enlace no es válido, ya se usó o venció. Pide uno nuevo.';
    END IF;

    UPDATE reset_contrasena SET usado = 1 WHERE id_reset = vIdReset;
    UPDATE cuenta SET contrasenia = pContrasena WHERE id_cuenta = vIdCuenta;

    COMMIT;

    SELECT 1 AS Afectadas;
END $$

DELIMITER ;
