<?php
require_once __DIR__ . '../../../config/database.php';

$cliente_id = $_POST['cliente_id'] ?? null;
if (!$cliente_id) {
    die("Cliente no especificado.");
}

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch();
if (!$cliente) {
    die("Cliente no encontrado.");
}

$stmt = $pdo->prepare("SELECT * FROM movimientos WHERE cliente_id = ? ORDER BY fecha ASC, id ASC");
$stmt->execute([$cliente_id]);
$movimientos = $stmt->fetchAll();

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=movimientos_{$cliente['nombre']}.csv");

$output = fopen("php://output", "w");
fputcsv($output, ['Fecha', 'Descripción', 'Tipo', 'Monto']);

foreach ($movimientos as $m) {
    fputcsv($output, [$m['fecha'], $m['descripcion'], $m['tipo'], $m['monto']]);
}
fclose($output);
exit;
