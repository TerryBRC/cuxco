<?php
require_once __DIR__ . '../../../config/database.php';

$busqueda = $_GET['q'] ?? '';
$pagina = isset($_GET['p']) ? max((int)$_GET['p'], 1) : 1;
$por_pagina = 10;
$inicio = ($pagina - 1) * $por_pagina;

// Conteo total
$condicion = '';
$params = [];

if ($busqueda) {
    $condicion = "WHERE nombre LIKE ? OR telefono LIKE ?";
    $params = ["%$busqueda%", "%$busqueda%"];
}

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM clientes $condicion");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();
$paginas = ceil($total / $por_pagina);

// Clientes actuales
$sql = "SELECT * FROM clientes $condicion ORDER BY nombre ASC LIMIT $inicio, $por_pagina";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

$title = 'Clientes';
$ventana = 'Lista de Clientes';
require '../../templates/header.php';
?>
<form method="GET" style="margin-bottom: 1rem;">
    <div style="display: flex; gap: 1rem;">
        <input type="text" name="q" placeholder="Buscar por nombre o teléfono" value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit">🔍 Buscar</button>
    </div>
    
</form>    

<table>
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Teléfono</th>
        <th>Frecuencia</th>
        <th>Acciones</th>
    </tr>
    <?php foreach($clientes as $c): ?>
    <tr>
        <td><?= $c['id']; ?></td>
        <td><?= htmlspecialchars($c['nombre']). " - " . htmlspecialchars($c['direccion']); ?></td>
        <td><?= htmlspecialchars($c['telefono']); ?></td>
        <td><?= htmlspecialchars($c['frecuencia_pago']); ?></td>
        <td>
            <a href="../movimientos/movimientos.php?cliente_id=<?= $c['id']; ?>">📄 Movimientos</a> |
            <a href="editar_cliente.php?cliente_id=<?= $c['id']; ?>">✏️ Editar</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<div class="pagination">
    <?php if ($pagina > 1): ?>
        <a href="?q=<?= urlencode($busqueda) ?>&p=1">« Primera</a>
        <a href="?q=<?= urlencode($busqueda) ?>&p=<?= $pagina - 1 ?>">‹ Anterior</a>
    <?php endif; ?>

    <?php
    // Mostrar máximo 5 páginas alrededor de la actual
    $rango = 2;
    $inicio_pag = max(1, $pagina - $rango);
    $fin_pag = min($paginas, $pagina + $rango);

    for ($i = $inicio_pag; $i <= $fin_pag; $i++): ?>
        <?php if ($i == $pagina): ?>
            <span class="active"><?= $i ?></span>
        <?php else: ?>
            <a href="?q=<?= urlencode($busqueda) ?>&p=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($pagina < $paginas): ?>
        <a href="?q=<?= urlencode($busqueda) ?>&p=<?= $pagina + 1 ?>">Siguiente ›</a>
        <a href="?q=<?= urlencode($busqueda) ?>&p=<?= $paginas ?>">Última »</a>
    <?php endif; ?>
</div>


<?php require '../../templates/footer.php'; ?>
