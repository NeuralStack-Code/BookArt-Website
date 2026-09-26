-- ============================================================
-- BookArt · Recuperación de contraseña con token
-- Base: u165852803_bookart
--
-- Guarda solo el HASH (sha256) del token: si alguien lee esta tabla
-- no obtiene enlaces válidos. El token en claro viaja únicamente
-- en el correo. Cada token vale 1 hora y se usa una sola vez.
--
-- Idempotente: se puede correr varias veces.
-- ============================================================

CREATE TABLE IF NOT EXISTS reset_contrasena (
    id_reset    INT(11)     NOT NULL AUTO_INCREMENT,
    cuenta_id   INT(11)     NOT NULL,
    token_hash  CHAR(64)    NOT NULL,
    expira      DATETIME    NOT NULL,
    usado       TINYINT(1)  NOT NULL DEFAULT 0,
    creado      DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_reset),
    UNIQUE KEY uq_reset_token (token_hash),
    KEY idx_reset_cuenta (cuenta_id),
    CONSTRAINT fk_reset_cuenta FOREIGN KEY (cuenta_id)
        REFERENCES cuenta (id_cuenta) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
