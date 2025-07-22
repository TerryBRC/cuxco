<?php
require_once __DIR__ . '../../../config/database.php';
// Llamamos al procedimiento para guardar el cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("CALL guardar_cliente(?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['nombre'], $_POST['telefono'], $_POST['direccion'],
        $_POST['cedula'], $_POST['frecuencia_pago']
    ]);
    $toast = "<script>window.toastMsg = {type: 'success', message: 'CLIENTE GUARDADO CORRECTAMENTE'};</script>";
}

$title = 'Nuevo Cliente';
require '../../templates/header.php';
?>
<h2>Registrar Nuevo Cliente</h2>
<form method="POST">
    <label>Nombre:<input name="nombre" required></label><br>
    <label>Teléfono:<input class="tel" name="telefono"></label><br>
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
