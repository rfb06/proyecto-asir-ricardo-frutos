-- =============================================================================
-- tecnorural.sql Esquema completo de la base de datos TecnoRural
-- =============================================================================

CREATE DATABASE IF NOT EXISTS tecnorural CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tecnorural;

-- ---------------------------------------------------------------------------
-- Tabla: tienda
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tienda (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 direccion VARCHAR(200),
 municipio VARCHAR(100),
 provincia VARCHAR(50) DEFAULT 'Cceres',
 telefono VARCHAR(20),
 email VARCHAR(100),
 horario VARCHAR(150),
 activa TINYINT(1) DEFAULT 1,
 fecha_apertura DATE,
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO tienda (nombre, direccion, municipio, provincia, telefono, email, horario, fecha_apertura) VALUES
('TecnoRural Plasencia', 'Av. de Espaa, 12', 'Plasencia', 'Cceres', '927 100 001', 'plasencia@tecnorural.es', 'L-V 09:00-20:00 / S 10:00-14:00', '2021-03-15'),
('TecnoRural Cceres', 'C/ Gil Cordero, 5', 'Cceres', 'Cceres', '927 100 002', 'caceres@tecnorural.es', 'L-V 09:00-20:00 / S 10:00-14:00', '2022-01-10'),
('TecnoRural Navalmoral', 'C/ Constitucin, 34', 'Navalmoral de la Mata', 'Cceres', '927 100 003', 'navalmoral@tecnorural.es', 'L-V 09:30-19:30', '2022-09-01'),
('TecnoRural Mrida', 'Av. de la Libertad, 7', 'Mrida', 'Badajoz', '924 100 004', 'merida@tecnorural.es', 'L-V 09:00-20:00 / S 10:00-14:00', '2023-04-20'),
('TecnoRural Badajoz', 'C/ Menacho, 22', 'Badajoz', 'Badajoz', '924 100 005', 'badajoz@tecnorural.es', 'L-V 09:00-20:30 / S 10:00-14:00', '2023-11-05');

-- ---------------------------------------------------------------------------
-- Tabla: categoria
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categoria (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(80) NOT NULL,
 icono VARCHAR(10) DEFAULT ''
) ENGINE=InnoDB;

INSERT INTO categoria (nombre, icono) VALUES
('Porttiles', ''),
('Sobremesa', ''),
('Componentes', ''),
('Perifricos', ''),
('Redes', ''),
('Almacenamiento', ''),
('Mviles', ''),
('Impresoras', '');

-- ---------------------------------------------------------------------------
-- Tabla: inventario
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventario (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(150) NOT NULL,
 referencia VARCHAR(50) UNIQUE,
 id_categoria INT,
 id_tienda INT,
 stock INT DEFAULT 0,
 precio_compra DECIMAL(10,2),
 precio_venta DECIMAL(10,2),
 descripcion TEXT,
 proveedor VARCHAR(100),
 stock_minimo INT DEFAULT 3,
 activo TINYINT(1) DEFAULT 1,
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (id_categoria) REFERENCES categoria(id),
 FOREIGN KEY (id_tienda) REFERENCES tienda(id)
) ENGINE=InnoDB;

INSERT INTO inventario (nombre, referencia, id_categoria, id_tienda, stock, precio_compra, precio_venta, descripcion, proveedor, stock_minimo) VALUES
('Porttil Lenovo IdeaPad 3', 'LEN-IP3-15', 1, 1, 8, 450.00, 599.00, 'Intel i5, 16GB RAM, 512GB SSD', 'Lenovo Iberia', 3),
('Porttil ASUS VivoBook 15', 'ASU-VB15', 1, 2, 5, 380.00, 519.00, 'Ryzen 5, 8GB RAM, 256GB SSD', 'ASUS Spain', 3),
('PC Sobremesa HP Prodesk 400', 'HP-PD400', 2, 1, 3, 520.00, 699.00, 'Intel i5, 16GB, 512GB SSD', 'HP Espaa', 2),
('Ratn Logitech MX Master 3', 'LOG-MX3', 4, 3, 20, 55.00, 89.00, 'Inalmbrico, ergonmico', 'Logitech', 5),
('Teclado Mecnico Redragon', 'RED-K552', 4, 1, 12, 35.00, 59.00, 'Switch rojo, retroiluminado', 'Redragon', 5),
('Router TP-Link AX1800', 'TPL-AX1800', 5, 2, 7, 42.00, 69.00, 'WiFi 6, doble banda', 'TP-Link', 3),
('SSD Samsung 870 EVO 1TB', 'SAM-870-1T', 6, 1, 15, 65.00, 99.00, 'SATA III, 560MB/s lectura', 'Samsung', 5),
('Monitor LG 24" IPS', 'LG-24IPS', 4, 4, 4, 120.00, 189.00, '1080p, 75Hz, FreeSync', 'LG Espaa', 2),
('Impresora HP LaserJet Pro', 'HP-LJP', 8, 5, 2, 180.00, 249.00, 'Monocromo, dplex automtico', 'HP Espaa', 2),
('Smartphone Xiaomi Redmi 13', 'XIA-R13', 7, 3, 9, 145.00, 219.00, '6.79", 6GB RAM, 128GB', 'Xiaomi Spain', 4),
('RAM Corsair 16GB DDR4', 'COR-16DDR4', 3, 2, 18, 32.00, 55.00, '3200MHz, CL16', 'Corsair', 5),
('Switch TP-Link 8 puertos', 'TPL-SW8', 5, 1, 6, 18.00, 29.00, 'Gigabit no gestionable', 'TP-Link', 3);

-- ---------------------------------------------------------------------------
-- Tabla: tecnico
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tecnico (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 email VARCHAR(100) UNIQUE,
 telefono VARCHAR(20),
 especialidad VARCHAR(100),
 activo TINYINT(1) DEFAULT 1,
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO tecnico (nombre, email, telefono, especialidad) VALUES
('Carlos Moreno', 'carlos@tecnorural.es', '600 111 001', 'Hardware y redes'),
('Luca Fernndez', 'lucia@tecnorural.es', '600 111 002', 'Software y sistemas'),
('Javier Blanco', 'javier@tecnorural.es', '600 111 003', 'Soporte a cliente'),
('Ana Gutirrez', 'ana@tecnorural.es', '600 111 004', 'Redes y ciberseguridad');

-- ---------------------------------------------------------------------------
-- Tabla: incidencia
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS incidencia (
 id INT AUTO_INCREMENT PRIMARY KEY,
 titulo VARCHAR(200) NOT NULL,
 descripcion TEXT NOT NULL,
 nombre_cliente VARCHAR(100) NOT NULL,
 email_cliente VARCHAR(100),
 telefono_cliente VARCHAR(20),
 id_tienda INT,
 estado ENUM('nueva','asignada','en_proceso','resuelta','cerrada') DEFAULT 'nueva',
 prioridad ENUM('baja','media','alta','critica') DEFAULT 'media',
 id_tecnico INT DEFAULT NULL,
 notas_supervisor TEXT,
 notas_tecnico TEXT,
 fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
 fecha_asignacion DATETIME DEFAULT NULL,
 fecha_resolucion DATETIME DEFAULT NULL,
 FOREIGN KEY (id_tienda) REFERENCES tienda(id),
 FOREIGN KEY (id_tecnico) REFERENCES tecnico(id)
) ENGINE=InnoDB;

INSERT INTO incidencia (titulo, descripcion, nombre_cliente, email_cliente, telefono_cliente, id_tienda, estado, prioridad, id_tecnico, notas_supervisor, fecha_asignacion) VALUES
('PC no enciende tras apagn', 'Despus de un corte de luz el ordenador no arranca. Solo parpadea el led de encendido.', 'Manuel Garca', 'mgarcia@email.com', '600 001 001', 1, 'asignada', 'alta', 1, 'Revisar fuente de alimentacin primero', NOW()),
('WiFi muy lento en la oficina', 'La velocidad de internet ha cado drsticamente desde ayer por la maana.', 'Empresa Agro SL', 'contacto@agro.es', '924 200 100', 2, 'en_proceso', 'media', 4, 'Comprobar configuracin del router y canal WiFi', NOW()),
('Pantalla con rayas verticales', 'El monitor muestra lneas de colores verticales en el lado derecho.', 'Rosa Prez', 'rperez@gmail.com', '657 002 002', 3, 'nueva', 'baja', NULL, NULL, NULL),
('Virus en el equipo', 'El antivirus detecta amenazas y el equipo va muy lento. Hay ventanas emergentes constantes.', 'Pedro Snchez', 'psanchez@hotmail.com', '666 003 003', 1, 'asignada', 'critica', 2, 'URGENTE: posible ransomware. Aislar equipo de la red inmediatamente.', NOW()),
('Impresora no imprime en color', 'La impresora solo imprime en blanco y negro aunque los cartuchos de color estn llenos.', 'Farmacia Central', 'farmacia@central.es', '927 400 200', 4, 'nueva', 'baja', NULL, NULL, NULL);

-- ---------------------------------------------------------------------------
-- Usuario aplicacin web
-- ---------------------------------------------------------------------------
GRANT ALL PRIVILEGES ON tecnorural.* TO 'webuser'@'192.168.20.11' IDENTIFIED BY 'WebPass2024!';
GRANT ALL PRIVILEGES ON tecnorural.* TO 'webuser'@'192.168.20.12' IDENTIFIED BY 'WebPass2024!';
FLUSH PRIVILEGES;
