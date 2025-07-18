create database cuentasporcobrar;
use cuentasporcobrar;
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    direccion VARCHAR(255),
    cedula VARCHAR(20),
    frecuencia_pago ENUM('semanal', 'quincenal', 'mensual') NOT NULL
);

-- Tabla movimientos
CREATE TABLE movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    fecha DATEtime default CURRENT_TIMESTAMP,
    descripcion VARCHAR(255),
    tipo ENUM('cargo', 'abono') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

-- guardar cliente
DELIMITER $$
CREATE PROCEDURE guardar_cliente (
    IN p_nombre VARCHAR(100),
    IN p_telefono VARCHAR(20),
    IN p_direccion VARCHAR(255),
    IN p_cedula VARCHAR(20),
    IN p_frecuencia_pago ENUM('semanal', 'quincenal', 'mensual')
)
BEGIN
    INSERT INTO clientes (nombre, telefono, direccion, cedula, frecuencia_pago)
    VALUES (p_nombre, p_telefono, p_direccion, p_cedula, p_frecuencia_pago);
END $$

DELIMITER ;
-- registrar movimiento
DELIMITER $$

CREATE PROCEDURE registrar_movimiento (
    IN p_cliente_id INT,
    IN p_fecha DATE,
    IN p_descripcion VARCHAR(255),
    IN p_tipo ENUM('cargo', 'abono'),
    IN p_monto DECIMAL(10,2)
)
BEGIN
    INSERT INTO movimientos (cliente_id, fecha, descripcion, tipo, monto)
    VALUES (p_cliente_id, p_fecha, p_descripcion, p_tipo, p_monto);
END $$
DELIMITER $$

CREATE PROCEDURE clientes_atrasados_por_fecha(IN fecha_base DATE)
BEGIN
    SELECT 
        c.id AS cliente_id,
        c.nombre,
        c.telefono,
        c.frecuencia_pago,
        MAX(CASE WHEN m.tipo = 'abono' THEN m.fecha END) AS ultima_fecha_abono,
        DATEDIFF(fecha_base, MAX(CASE WHEN m.tipo = 'abono' THEN m.fecha END)) AS dias_desde_ultimo_abono,
        SUM(CASE WHEN m.tipo = 'cargo' THEN m.monto ELSE -m.monto END) AS saldo
    FROM clientes c
    JOIN movimientos m ON c.id = m.cliente_id
    GROUP BY c.id, c.nombre, c.telefono, c.frecuencia_pago
    HAVING 
        saldo > 0 AND (
            (frecuencia_pago = 'semanal' AND dias_desde_ultimo_abono >= 14) OR
            (frecuencia_pago = 'quincenal' AND dias_desde_ultimo_abono >= 30) OR
            (frecuencia_pago = 'mensual' AND dias_desde_ultimo_abono >= 45)
        );
END $$

DELIMITER ;

DELIMITER ;
CREATE VIEW vista_clientes_atrasados AS
SELECT 
    c.id AS cliente_id,
    c.nombre,
    c.telefono,
    c.frecuencia_pago,
    MAX(CASE WHEN m.tipo = 'abono' THEN m.fecha END) AS ultima_fecha_abono,
    DATEDIFF(CURDATE(), MAX(CASE WHEN m.tipo = 'abono' THEN m.fecha END)) AS dias_desde_ultimo_abono,
    SUM(CASE WHEN m.tipo = 'cargo' THEN m.monto ELSE -m.monto END) AS saldo
FROM clientes c
JOIN movimientos m ON c.id = m.cliente_id
GROUP BY c.id, c.nombre, c.telefono, c.frecuencia_pago
HAVING 
    saldo > 0 AND (
        (frecuencia_pago = 'semanal' AND dias_desde_ultimo_abono >= 14) OR
        (frecuencia_pago = 'quincenal' AND dias_desde_ultimo_abono >= 30) OR
        (frecuencia_pago = 'mensual' AND dias_desde_ultimo_abono >= 45)
    );
-- datos de prueba
INSERT INTO clientes (nombre, telefono, direccion, cedula, frecuencia_pago) VALUES
('Juan Pérez', '8888-1111', 'Calle Principal 123, Managua', '001-010180-0001A', 'mensual'),
('María García', '7777-2222', 'Avenida Central 456, León', '002-020290-0002B', 'quincenal'),
('Carlos López', '6666-3333', 'Barrio El Sol 789, Granada', '003-030375-0003C', 'semanal'),
('Ana Martínez', '5555-4444', 'Residencial Las Flores 101, Masaya', '004-040488-0004D', 'mensual'),
('Pedro Sánchez', '4444-5555', 'Km 10 Carretera Sur, Diriamba', '005-050592-0005E', 'quincenal'),
('Laura Rodríguez', '3333-6666', 'Colonia Moderna 202, Estelí', '006-060685-0006F', 'semanal');
-- Movimientos para Juan Pérez (ID 1 - Mensual)
INSERT INTO movimientos (cliente_id, fecha, descripcion, tipo, monto) VALUES
(1, '2025-04-01', 'Compra de mercadería A', 'cargo', 500.00),
(1, '2025-05-01', 'Compra de mercadería B', 'cargo', 300.00),
(1, '2025-05-15', 'Abono factura abril', 'abono', 500.00),
(1, '2025-06-01', 'Compra de mercadería C', 'cargo', 400.00),
(1, '2025-06-15', 'Abono factura mayo', 'abono', 300.00),
(1, '2025-07-01', 'Compra de mercadería D', 'cargo', 250.00); -- Saldo pendiente: 250 (debería estar atrasado si no abona antes del 15/07)

-- Movimientos para María García (ID 2 - Quincenal)
INSERT INTO movimientos (cliente_id, fecha, descripcion, tipo, monto) VALUES
(2, '2025-05-10', 'Servicio de consultoría', 'cargo', 800.00),
(2, '2025-05-25', 'Abono servicio', 'abono', 400.00),
(2, '2025-06-10', 'Servicio adicional', 'cargo', 200.00),
(2, '2025-06-25', 'Abono parcial', 'abono', 300.00); -- Saldo pendiente: 300 (800-400+200-300 = 300). Último abono 25/06. Atrasado si no abona antes del 25/07.

-- Movimientos para Carlos López (ID 3 - Semanal)
INSERT INTO movimientos (cliente_id, fecha, descripcion, tipo, monto) VALUES
(3, '2025-06-20', 'Reparación equipo', 'cargo', 150.00),
(3, '2025-06-27', 'Abono reparación', 'abono', 150.00),
(3, '2025-07-05', 'Materiales de oficina', 'cargo', 80.00); -- Saldo pendiente: 80. Último abono 27/06. Atrasado si no abona antes del 11/07 (14 días).

-- Movimientos para Ana Martínez (ID 4 - Mensual) - Al día
INSERT INTO movimientos (cliente_id, fecha, descripcion, tipo, monto) VALUES
(4, '2025-05-15', 'Diseño web', 'cargo', 1200.00),
(4, '2025-06-10', 'Abono inicial diseño', 'abono', 600.00),
(4, '2025-07-10', 'Abono final diseño', 'abono', 600.00); -- Saldo: 0. Al día.

-- Movimientos para Pedro Sánchez (ID 5 - Quincenal) - Saldo pendiente, pero quizás no atrasado aún
INSERT INTO movimientos (cliente_id, fecha, descripcion, tipo, monto) VALUES
(5, '2025-06-20', 'Suministro de oficina', 'cargo', 300.00),
(5, '2025-07-05', 'Abono suministro', 'abono', 100.00); -- Saldo pendiente: 200. Último abono 05/07. No atrasado si no abona antes del 05/08.

-- Movimientos para Laura Rodríguez (ID 6 - Semanal) - Atrasada
INSERT INTO movimientos (cliente_id, fecha, descripcion, tipo, monto) VALUES
(6, '2025-06-01', 'Venta de productos', 'cargo', 200.00),
(6, '2025-06-08', 'Abono parcial', 'abono', 50.00),
(6, '2025-06-15', 'Venta de productos (parte 2)', 'cargo', 100.00); -- Saldo: 250. Último abono 08/06. Definitivamente atrasada.