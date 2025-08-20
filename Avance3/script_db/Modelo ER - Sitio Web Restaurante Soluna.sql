CREATE DATABASE Restaurante_Soluna;
USE Restaurante_Soluna;

CREATE TABLE `Usuario` (
  `id_usuario` INT PRIMARY KEY AUTO_INCREMENT,
  `correo` VARCHAR(100) UNIQUE,
  `contrasena` VARCHAR(100),
  `activo` BOOLEAN,
  `fecha_registro` DATETIME,
  `rol` VARCHAR(50)
);

CREATE TABLE `Cliente` (
  `id_cliente` INT PRIMARY KEY AUTO_INCREMENT,
  `id_usuario` INT,
  `nombre` VARCHAR(100),
  `telefono` VARCHAR(20),
  `direccion` VARCHAR(250)
);

CREATE TABLE `Empleado` (
  `id_empleado` INT PRIMARY KEY AUTO_INCREMENT,
  `id_usuario` INT,
  `nombre` VARCHAR(100),
  `puesto` VARCHAR(50)
);

CREATE TABLE `Pedido` (
  `id_pedido` INT PRIMARY KEY AUTO_INCREMENT,
  `id_cliente` INT,
  `fecha_creacion` DATETIME,
  `nota_cliente` text,
  `id_estado` INT
);

CREATE TABLE `Detalle_pedido` (
  `id_detalle` INT PRIMARY KEY AUTO_INCREMENT,
  `id_carrito` INT,
  `id_producto` INT,
  `cantidad` INT,
  `precio_unitario` DECIMAL(10,2)
);

CREATE TABLE `Factura` (
  `id_factura` INT PRIMARY KEY AUTO_INCREMENT,
  `id_cliente` INT,
  `id_pedido` INT,
  `fecha` DATETIME,
  `total` DECIMAL(10,2),
  `id_metodo_pago` INT
);

CREATE TABLE `Detalle_Factura` (
  `id_detalle` INT PRIMARY KEY AUTO_INCREMENT,
  `id_factura` INT,
  `id_producto` INT,
  `cantidad` INT,
  `precio_unitario` DECIMAL(10,2),
  `nombre_producto` VARCHAR(100)
);

CREATE TABLE `Metodo_Pago` (
  `id_metodo_pago` INT PRIMARY KEY AUTO_INCREMENT,
  `nombre` VARCHAR(50)
);

CREATE TABLE `Reserva` (
  `id_reserva` INT PRIMARY KEY AUTO_INCREMENT,
  `id_cliente` INT,
  `id_mesa` INT,
  `fecha` DATETIME,
  `cantidad_personas` INT,
  `nota_cliente` TEXT,
  `id_estado` INT
);

CREATE TABLE `Mesa` (
  `id_mesa` INT PRIMARY KEY AUTO_INCREMENT,
  `numero_mesa` INT,
  `num_asientos` INT,
  `ubicacion` VARCHAR(100),
  `disponible` BOOLEAN
);

CREATE TABLE `Producto` (
  `id_producto` INT PRIMARY KEY AUTO_INCREMENT,
  `nombre` VARCHAR(100),
  `descripcion` TEXT,
  `precio` DECIMAL(10,2),
  `disponible` BOOLEAN,
  `imagen` VARCHAR(255),
  `id_categoria` INT
);

CREATE TABLE `Categoria_Producto` (
  `id_categoria` INT PRIMARY KEY AUTO_INCREMENT,
  `nombre` VARCHAR(50)
);

CREATE TABLE `Inventario` (
  `id_inventario` INT PRIMARY KEY AUTO_INCREMENT,
  `id_producto` INT,
  `stock_actual` INT,
  `stock_minimo` INT
);

CREATE TABLE `Promocion` (
  `id_promocion` INT PRIMARY KEY AUTO_INCREMENT,
  `nombre` VARCHAR(100),
  `descripcion` TEXT,
  `descuento` DECIMAL(5,2),
  `fecha_inicio` DATE,
  `fecha_fin` DATE,
  `id_producto` INT
);

CREATE TABLE Solicitudes (
    id_solicitud INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    tipo ENUM('Reserva','Consulta','Queja','Otro') NOT NULL,
    descripcion TEXT NOT NULL,
    estado ENUM('Pendiente','En Proceso','Resuelta') DEFAULT 'Pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

ALTER TABLE solicitudes
MODIFY estado ENUM('Pendiente','Hecho') NOT NULL DEFAULT 'Pendiente';

CREATE TABLE `Estado` (
  `id_estado` INT PRIMARY KEY AUTO_INCREMENT,
  `nombre` VARCHAR(50),
  `tipo_estado` VARCHAR(50)
);

ALTER TABLE `Cliente` ADD FOREIGN KEY (`id_usuario`) REFERENCES `Usuario` (`id_usuario`);

ALTER TABLE `Empleado` ADD FOREIGN KEY (`id_usuario`) REFERENCES `Usuario` (`id_usuario`);

ALTER TABLE `Pedido` ADD FOREIGN KEY (`id_cliente`) REFERENCES `Cliente` (`id_cliente`);

ALTER TABLE `Detalle_pedido` ADD FOREIGN KEY (`id_carrito`) REFERENCES `Pedido` (`id_pedido`);

ALTER TABLE `Detalle_pedido` ADD FOREIGN KEY (`id_producto`) REFERENCES `Producto` (`id_producto`);

ALTER TABLE `Factura` ADD FOREIGN KEY (`id_cliente`) REFERENCES `Cliente` (`id_cliente`);

ALTER TABLE `Factura` ADD FOREIGN KEY (`id_pedido`) REFERENCES `Pedido` (`id_pedido`);

ALTER TABLE `Factura` ADD FOREIGN KEY (`id_metodo_pago`) REFERENCES `Metodo_Pago` (`id_metodo_pago`);

ALTER TABLE `Pedido` ADD FOREIGN KEY (`id_estado`) REFERENCES `Estado` (`id_estado`);

ALTER TABLE `Detalle_Factura` ADD FOREIGN KEY (`id_factura`) REFERENCES `Factura` (`id_factura`);

ALTER TABLE `Detalle_Factura` ADD FOREIGN KEY (`id_producto`) REFERENCES `Producto` (`id_producto`);

ALTER TABLE `Reserva` ADD FOREIGN KEY (`id_cliente`) REFERENCES `Cliente` (`id_cliente`);

ALTER TABLE `Reserva` ADD FOREIGN KEY (`id_mesa`) REFERENCES `Mesa` (`id_mesa`);

ALTER TABLE `Reserva` ADD FOREIGN KEY (`id_estado`) REFERENCES `Estado` (`id_estado`);

ALTER TABLE `Producto` ADD FOREIGN KEY (`id_categoria`) REFERENCES `Categoria_Producto` (`id_categoria`);

ALTER TABLE `Inventario` ADD FOREIGN KEY (`id_producto`) REFERENCES `Producto` (`id_producto`);

ALTER TABLE `Promocion` ADD FOREIGN KEY (`id_producto`) REFERENCES `Producto` (`id_producto`);

-- Insertar Estados
INSERT INTO Estado (nombre, tipo_estado) 
Values ("Pagado", "Pedido");

INSERT INTO Estado (nombre, tipo_estado) 
Values ("Pendiente", "Pedido");

INSERT INTO Estado (nombre, tipo_estado) 
Values ("Entregado", "Pedido");

--Insertar Métodos de Pago
INSERT INTO Metodo_Pago (nombre) 
Values ("Tarjeta");

INSERT INTO Metodo_Pago (nombre) 
Values ("Efectivo");

INSERT INTO Estado (nombre, tipo_estado) 
VALUES ('Pendiente', 'Reserva');

INSERT INTO Estado (nombre, tipo_estado) 
VALUES ('Confirmada', 'Reserva');

INSERT INTO Estado (nombre, tipo_estado) 
VALUES ('Cancelada', 'Reserva');

INSERT INTO Estado (nombre, tipo_estado) 
VALUES ('Finalizada', 'Reserva');
