-- =============================================================================
-- auth.sql Tablas de autenticacin para TecnoRural
-- Se aade a tecnorural.sql o se ejecuta por separado
-- =============================================================================

USE tecnorural;

-- ---------------------------------------------------------------------------
-- Tabla: usuario (clientes registrados)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuario (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 apellidos VARCHAR(150),
 email VARCHAR(150) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL, -- bcrypt hash
 telefono VARCHAR(20),
 id_tienda INT DEFAULT NULL, -- tienda preferida
 activo TINYINT(1) DEFAULT 1,
 fecha_reg DATETIME DEFAULT CURRENT_TIMESTAMP,
 ultimo_acc DATETIME DEFAULT NULL,
 FOREIGN KEY (id_tienda) REFERENCES tienda(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Tabla: supervisor (acceso privilegiado, solo Ricardo)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS supervisor (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 activo TINYINT(1) DEFAULT 1,
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Supervisor Ricardo contrasea: Ricardo2024!
-- Hash generado con password_hash('Ricardo2024!', PASSWORD_BCRYPT)
INSERT INTO supervisor (nombre, email, password) VALUES
('Ricardo', 'ricardo@tecnorural.es', '$2y$12$QKH0b0VD4D5HZCBQlbkCEeJgY7IJ5NnF3Hd9A/s5.CkOE7vEhIFOS');

-- ---------------------------------------------------------------------------
-- Aadir columna id_usuario a incidencia para vincular con usuario registrado
-- ---------------------------------------------------------------------------
ALTER TABLE incidencia
 ADD COLUMN IF NOT EXISTS id_usuario INT DEFAULT NULL AFTER id_tienda,
 ADD CONSTRAINT fk_inc_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id);
