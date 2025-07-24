<?php
require_once __DIR__ . '../../../config/database.php';

$cliente_id = $_GET['cliente_id'] ?? null;
if (!$cliente_id) {
    die('Cliente no especificado.');
}

// Obtener cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch();
if (!$cliente) {
    die('Cliente no encontrado.');
}

// Toast desde redirección
$toast = '';
if (isset($_GET['success'])) {
    $toast = "<script>window.toastMsg = {type: 'success', message: 'MOVIMIENTO GUARDADO CORRECTAMENTE'};</script>";
}

// Guardar nuevo movimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = null;

    // Calcular saldo actual
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN tipo = 'cargo' THEN monto ELSE -monto END) AS saldo 
        FROM movimientos 
        WHERE cliente_id = ?
    ");
    $stmt->execute([$cliente_id]);
    $saldo = $stmt->fetchColumn();

    // Validaciones
    if (empty($_POST['descripcion']) || empty($_POST['tipo']) || empty($_POST['monto'])) {
        $error = 'Todos los campos son requeridos';
    } elseif ($_POST['tipo'] === 'abono' && $_POST['monto'] > $saldo) {
        $error = 'EL MONTO DE ABONO NO PUEDE SER MAYOR AL SALDO ACTUAL';
    }

    if ($error) {
        $toast = "<script>window.toastMsg = {type: 'error', message: '" . addslashes($error) . "'};</script>";
    } else {
        $sql = "INSERT INTO movimientos (cliente_id, descripcion, tipo, monto)
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $cliente_id,
            $_POST['descripcion'],
            $_POST['tipo'],
            $_POST['monto']
        ]);

        // Redirige para evitar doble envío
        header("Location: movimientos.php?cliente_id=$cliente_id&success=1");
        exit;
    }
}

// Calcular saldo actualizado (si no hubo POST, o después del redireccionamiento)
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN tipo = 'cargo' THEN monto ELSE -monto END) AS saldo 
    FROM movimientos 
    WHERE cliente_id = ?
");
$stmt->execute([$cliente_id]);
$saldo = $stmt->fetchColumn();

// Filtros
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

$sql = "SELECT * FROM movimientos WHERE " . implode(' AND ', $condiciones) . " ORDER BY fecha DESC, id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimientos = $stmt->fetchAll();

$title = "Movimientos";
$titlepage =$cliente['id']. " - "  . $cliente['nombre'] . " - ". $cliente["direccion"] ." | Saldo: C$ " . number_format($saldo, 2);
require '../../templates/header.php';
if (!empty($toast)) {
    echo $toast;
    $toast = ''; // 🧹 limpiar para que no se muestre otra vez
}
?>

<h1><?= $titlepage ?></h1>

<div class="movimientos-flex">
    <div class="historial">
        <!-- <form method="GET" style="display: flex; align-items: center; gap: 0.5rem;">
            <input type="hidden" name="cliente_id" value="<= $cliente_id ?>">
            <input type="date" name="f" value="<= $_GET['f'] ?? '' ?>" placeholder="Fecha">
            <input type="text" name="q" value="<= $_GET['q'] ?? '' ?>" placeholder="Descripción">
            <button type="submit">🔍 Filtrar</button>
        </form> -->
        <div style="display: flex;gap: 1rem;" >
            <!-- <a href="movimientos.php?cliente_id=<= $cliente_id ?>" class="button">🔄 Limpiar</a> -->
            <form method="POST" action="export_excel.php"style="align-content: center;">
                <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
                <button type="submit" class="button">📥 Exportar a Excel</button>
            </form>
            <h3>Historial</h3>
        </div>

        
        <table>
            <tr>
                <th>Fecha</th>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Monto</th>
            </tr>
            <?php foreach ($movimientos as $m): ?>
                <tr>
                    <td>
                        <?= date('d/m/Y H:i:s', strtotime($m['fecha'])); ?>
                        <button type="button" class="edit-fecha-btn" data-id="<?= $m['id'] ?>" data-fecha="<?= $m['fecha'] ?>">✏️</button>
                    </td>
                    <td><?= htmlspecialchars($m['descripcion']) ?></td>
                    <td><?= $m['tipo'] === 'abono' ? 'Abono' : 'Cargo' ?></td>
                    <td><?= number_format($m['monto'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="formulario" style="justify-content: center;">
        <h3>Nuevo Movimiento</h3>
        <form method="POST" style="display: flex; flex-direction: column; gap: 1rem;">

            <label>Descripción:
                <input name="descripcion" required>
            </label>
            <div style="display: inline-flex; gap: 1rem;">

                <label>Tipo:
                    <select name="tipo">
                        <option value="cargo">Cargo (Compra)</option>
                        <option value="abono">Abono (Pago)</option>
                    </select>
                </label>
                <label>Monto:
                    <input type="number" step="0.01" name="monto" required>
                </label>
            </div>
            <button type="submit">Guardar Movimiento</button>
        </form>
    </div>
</div>

<p><a href="../clientes/clientes.php">← Volver a Clientes</a></p>

<!-- Modal para editar fecha -->
<div id="modal-fecha" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:#0008; z-index:99999; align-items:center; justify-content:center;">
    <div style="background:#fff; color:#000; padding:2rem; border-radius:8px; min-width:300px; max-width:90vw;">
        <h3>Editar Fecha de Movimiento</h3>
        <form id="form-fecha" method="POST" style="display:flex; flex-direction:column; gap:1rem;">
            <input type="hidden" name="edit_fecha_id" id="edit_fecha_id">
            <label>Fecha:
                <input type="date" name="edit_fecha" id="edit_fecha" required>
            </label>
            <label>Descripción:
                <input type="text" name="edit_descripcion" id="edit_descripcion" required>
            </label>
            <label>Tipo:
                <select name="edit_tipo" id="edit_tipo" required>
                    <option value="cargo">Cargo (Compra)</option>
                    <option value="abono">Abono (Pago)</option>
                </select>
            </label>
            <label>Monto:
                <input type="number" step="0.01" name="edit_monto" id="edit_monto" required>
            </label>
            <div style="display:flex; gap:1rem;">
                <button type="submit">Guardar</button>
                <button type="button" onclick="document.getElementById('modal-fecha').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.edit-fecha-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('edit_fecha_id').value = btn.dataset.id;
        document.getElementById('edit_fecha').value = btn.dataset.fecha;
        // Buscar el movimiento en JS para rellenar los demás campos
        var mov = movimientos.find(function(m) { return m.id == btn.dataset.id; });
        if (mov) {
            document.getElementById('edit_descripcion').value = mov.descripcion;
            document.getElementById('edit_tipo').value = mov.tipo;
            document.getElementById('edit_monto').value = mov.monto;
        }
        document.getElementById('modal-fecha').style.display = 'flex';
    });
});

// Pasar los movimientos a JS
var movimientos = <?php echo json_encode(array_map(function($m) {
    return [
        'id' => $m['id'],
        'fecha' => $m['fecha'],
        'descripcion' => $m['descripcion'],
        'tipo' => $m['tipo'],
        'monto' => $m['monto']
    ];
}, $movimientos)); ?>;

// Ya no se requiere validación de usuario/contraseña
</script>

<?php
// Procesar edición de fecha
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['edit_fecha_id'], $_POST['edit_fecha'], $_POST['edit_descripcion'], $_POST['edit_tipo'], $_POST['edit_monto'])
) {
    $id = $_POST['edit_fecha_id'];
    $nueva_fecha = $_POST['edit_fecha'];
    $nueva_desc = $_POST['edit_descripcion'];
    $nueva_tipo = $_POST['edit_tipo'];
    $nueva_monto = $_POST['edit_monto'];
    $stmt = $pdo->prepare("UPDATE movimientos SET fecha = ?, descripcion = ?, tipo = ?, monto = ? WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$nueva_fecha, $nueva_desc, $nueva_tipo, $nueva_monto, $id, $cliente_id]);
    echo "<script>window.toastMsg = {type: 'success', message: 'Movimiento actualizado'};</script>";
    // Redirige para evitar doble envío
    echo "<script>setTimeout(function(){ location.href='movimientos.php?cliente_id=$cliente_id'; }, 1000);</script>";
}
?>

<?php require '../../templates/footer.php'; ?>
