<?php
require_once __DIR__ . '../../../config/database.php';
/*
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
    fecha DATE NOT NULL,
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
*/

// Llamamos al procedimiento para guardar el cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("CALL guardar_cliente(?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['nombre'], $_POST['telefono'], $_POST['direccion'],
        $_POST['cedula'], $_POST['frecuencia_pago']
    ]);
    echo "<script>toastr.success('Cliente guardado');</script>";
}

$title = 'Nuevo Cliente';
require '../../templates/header.php';
?>
<h2>Registrar Nuevo Cliente</h2>
<form method="POST">
    <label>Nombre:<input name="nombre" required></label><br>
    <label>Teléfono:<input name="telefono" required></label><br>
    <label>Dirección:<input name="direccion"></label><br>
    <label>Cédula:<input name="cedula"></label><br>
    <label>Frecuencia:
        <select name="frecuencia_pago">
            <option value="semanal">Semanal</option>
            <option value="quincenal">Quincenal</option>
            <option value="mensual">Mensual</option>
        </select>
    </label><br>
    <button type="submit">Guardar</button>
</form>
<?php require '../../templates/footer.php'; ?>
