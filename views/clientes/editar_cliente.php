<?php
require_once __DIR__ . '../../../config/database.php';

$cliente_id = $_GET['cliente_id'] ?? null;
if (!$cliente_id) {
    die("Cliente no especificado.");
}

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch();
if (!$cliente) {
    die("Cliente no encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE clientes SET nombre = ?, telefono = ?, direccion = ?, cedula = ?, frecuencia_pago = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['nombre'], $_POST['telefono'], $_POST['direccion'],
        $_POST['cedula'], $_POST['frecuencia_pago'], $cliente_id
    ]);
    echo "<script>toastr('success', 'Cliente actualizado');</script>";
}

$title = "Editar Cliente";
require '../../templates/header.php';
?>

<h2>Editar Cliente</h2>
<form method="POST">
    <label>Nombre:<input name="nombre" value="<?= htmlspecialchars($cliente['nombre']) ?>" required></label>
    <label>Teléfono:<input name="telefono" value="<?= htmlspecialchars($cliente['telefono']) ?>" required></label>
    <label>Dirección:<input name="direccion" value="<?= htmlspecialchars($cliente['direccion']) ?>"></label>
    <label>Cédula:<input name="cedula" value="<?= htmlspecialchars($cliente['cedula']) ?>"></label>
    <label>Frecuencia:
        <select name="frecuencia_pago">
            <option value="semanal" <?= $cliente['frecuencia_pago'] === 'semanal' ? 'selected' : '' ?>>Semanal</option>
            <option value="quincenal" <?= $cliente['frecuencia_pago'] === 'quincenal' ? 'selected' : '' ?>>Quincenal</option>
            <option value="mensual" <?= $cliente['frecuencia_pago'] === 'mensual' ? 'selected' : '' ?>>Mensual</option>
        </select>
    </label>
    <button type="submit">Actualizar</button>
</form>
<p><a href="clientes.php">← Volver a Clientes</a></p>

<?php require '../../templates/footer.php'; ?>
