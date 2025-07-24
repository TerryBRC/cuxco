<?php
require_once __DIR__ . '../../../config/database.php';
// esta es la vista de clientes atrasados
// se conecta a la base de datos y obtiene los clientes con pagos atrasados
$title = 'Clientes Atrasados';

// conteo total de clientes atrasados
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
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM vista_clientes_atrasados $condicion");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();
$paginas = ceil($total / $por_pagina);


// llamamos a la vista de clientes atrasados
$stmt = $pdo->query("SELECT * FROM vista_clientes_atrasados");
$clientes_atrasados = $stmt->fetchAll();
if (!$clientes_atrasados) {
    die('No hay clientes atrasados.');
}

require '../../templates/header.php';
?>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Teléfono</th>
            <th>Frecuencia de Pago</th>
            <th>Último Abono</th>
            <th>Días desde Último Abono</th>
            <th>Saldo</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($clientes_atrasados as $cliente): ?>
            <tr>
                <td><?= htmlspecialchars($cliente['nombre']) ?></td>
                <td><?= htmlspecialchars($cliente['telefono']) ?></td>
                <td><?= htmlspecialchars($cliente['frecuencia_pago']) ?></td>
                <td><?= $cliente['ultima_fecha_abono'] ? htmlspecialchars($cliente['ultima_fecha_abono']) : 'N/A' ?></td>
                <td><?= $cliente['dias_desde_ultimo_abono'] ?></td>
                <td><?= number_format($cliente['saldo'], 2) ?></td>
                <td><a href="../movimientos/movimientos.php?cliente_id=<?= $cliente['cliente_id'] ?>">Ver Movimientos</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
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
<p><a href="clientes.php">← Volver a Clientes</a></p>
<?php require '../../templates/footer.php'; ?>