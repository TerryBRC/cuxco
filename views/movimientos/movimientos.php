<?php
require_once __DIR__ . '../../../config/database.php';

$cliente_id = $_GET['cliente_id'] ?? null;

// Validar cliente
if (!$cliente_id) {
    die('Cliente no especificado.');
}

// Obtener datos del cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch();
if (!$cliente) {
    die('Cliente no encontrado.');
}

// Guardar nuevo movimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "INSERT INTO movimientos (cliente_id, fecha, descripcion, tipo, monto)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $cliente_id,
        $_POST['fecha'],
        $_POST['descripcion'],
        $_POST['tipo'],
        $_POST['monto']
    ]);
    echo "<script>toastr('success', 'Movimiento guardado correctamente');</script>";
}

$filtro_fecha = $_GET['f'] ?? null;
$filtro_desc = $_GET['q'] ?? null;

$condiciones = ["cliente_id = ?"];
$params = [$cliente_id];

if ($filtro_fecha) {
    $condiciones[] = "fecha = ?";
    $params[] = $filtro_fecha;
}
if ($filtro_desc) {
    $condiciones[] = "descripcion LIKE ?";
    $params[] = "%$filtro_desc%";
}

$sql = "SELECT * FROM movimientos WHERE " . implode(' AND ', $condiciones) . " ORDER BY fecha ASC, id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$movimientos = $stmt->fetchAll();

// Calcular saldo
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN tipo = 'cargo' THEN monto ELSE -monto END) AS saldo 
    FROM movimientos 
    WHERE cliente_id = ?
");
$stmt->execute([$cliente_id]);
$saldo = $stmt->fetchColumn();

$title = "Movimientos de " . $cliente['nombre'];
require '../../templates/header.php';
?>

<h2><?= htmlspecialchars($title) ?></h2>
<p><strong>Saldo actual:</strong> <?= number_format($saldo, 2) ?> </p>

<form method="POST" action="export_excel.php" style="margin-top: 1rem;">
    <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
    <button type="submit">📥 Exportar a Excel</button>
</form>

<div class="movimientos-flex">
    <div class="formulario">
        <h3>Nuevo Movimiento</h3>
        <form method="POST">
            <label>Fecha:
                <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
            </label>
            <label>Descripción:
                <input name="descripcion" required>
            </label>
            <label>Tipo:
                <select name="tipo">
                    <option value="cargo">Cargo (Compra)</option>
                    <option value="abono">Abono (Pago)</option>
                </select>
            </label>
            <label>Monto:
                <input type="number" step="0.01" name="monto" required>
            </label>
            <button type="submit">Guardar Movimiento</button>
        </form>
    </div>

    <div class="historial">
        <form method="GET" style="margin-bottom: 1rem;">
    <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
    <input type="date" name="f" value="<?= $_GET['f'] ?? '' ?>" placeholder="Fecha">
    <input type="text" name="q" value="<?= $_GET['q'] ?? '' ?>" placeholder="Descripción">
    <button type="submit">🔍 Filtrar</button>
    <a href="movimientos.php?cliente_id=<?= $cliente_id ?>" class="button">🔄 Limpiar</a>
</form>

        <h3>Historial</h3>
        <table>
            <tr>
                <th>Fecha</th>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Monto</th>
            </tr>
            <?php foreach ($movimientos as $m): ?>
            <tr>
                <td><?= $m['fecha'] ?></td>
                <td><?= htmlspecialchars($m['descripcion']) ?></td>
                <td><?= $m['tipo'] === 'abono' ? 'Abono' : 'Cargo' ?></td>
                <td><?= number_format($m['monto'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>


<p><a href="../clientes/clientes.php">← Volver a Clientes</a></p>

<?php require '../../templates/footer.php'; ?>