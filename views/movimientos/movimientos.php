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
// Calcular saldo
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN tipo = 'cargo' THEN monto ELSE -monto END) AS saldo 
    FROM movimientos 
    WHERE cliente_id = ?
");
$stmt->execute([$cliente_id]);
$saldo = $stmt->fetchColumn();
// Guardar nuevo movimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$toast = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = null;
    // Validar campos requeridos
    if (empty($_POST['descripcion']) || empty($_POST['tipo']) || empty($_POST['monto'])) {
        $error = 'Todos los campos son requeridos';
    }
    // Validar que el monto de abono no sea mayor al saldo
    elseif ($_POST['tipo'] === 'abono' && $_POST['monto'] > $saldo) {
        $error = 'EL MONTO DE ABONO NO PUEDE SER MAYOR AL SALDO ACTUAL';
    }
    if ($error) {
        $toast = "<script>window.toastMsg = {type: 'error', message: '" . addslashes($error) . "'};</script>";
    } else {
        // Insertar movimiento
        $sql = "INSERT INTO movimientos (cliente_id, descripcion, tipo, monto)
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $cliente_id,
            $_POST['descripcion'],
            $_POST['tipo'],
            $_POST['monto']
        ]);
        $toast = "<script>window.toastMsg = {type: 'success', message: 'MOVIMIENTO GUARDADO CORRECTAMENTE'};</script>";
    }
}
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



$title = "Movimientos de " . $cliente['nombre'];
require '../../templates/header.php';
?>

<h1><?= htmlspecialchars($title) ?></h1>
<h2><strong>Saldo actual:</strong> C$ <?= number_format($saldo, 2) ?> </h2>

<div class="movimientos-flex">
    <div class="historial">
        
            <form method="GET" style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
                <input type="date" name="f" value="<?= $_GET['f'] ?? '' ?>" placeholder="Fecha">
                <input type="text" name="q" value="<?= $_GET['q'] ?? '' ?>" placeholder="Descripción">
                <button type="submit">🔍 Filtrar</button>
            </form>
            <div style="display: flex;align-items: flex-start;gap: 1rem;align-content: center;justify-content: center;">
            <a href="movimientos.php?cliente_id=<?= $cliente_id ?>" class="button"> 🔄 Limpiar</a>
            <form method="POST" action="export_excel.php">
                <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
                <button type="submit" class="a.button">📥 Exportar a Excel</button></div>
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
    <div class="formulario">
        <h3>Nuevo Movimiento</h3>
        <form method="POST">
            <!-- <label>Fecha:
                <input type="date" name="fecha" value="<= date('Y-m-d') ?>" required>
            </label>
-->
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


</div>


<p><a href="../clientes/clientes.php">← Volver a Clientes</a></p>

<?php require '../../templates/footer.php'; ?>