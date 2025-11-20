<?php
require_once '../includes/init.php';
$pdo = getPdo();

$dept = $_GET['departamento'] ?? '';
$prov = $_GET['provincia'] ?? '';
$tipo = $_GET['tipo'] ?? 'domicilio'; // domicilio, agencia
$agencia = $_GET['agencia'] ?? 'olva'; // olva, shalom

// Determinar qué campo de precio usar
$campo_precio = '';
if ($tipo === 'domicilio' && $agencia === 'olva') {
    $campo_precio = 'precio_domicilio_olva';
} elseif ($tipo === 'agencia' && $agencia === 'olva') {
    $campo_precio = 'precio_agencia_olva';
} elseif ($tipo === 'domicilio' && $agencia === 'shalom') {
    $campo_precio = 'precio_domicilio_shalom';
} elseif ($tipo === 'agencia' && $agencia === 'shalom') {
    $campo_precio = 'precio_agencia_shalom';
}

// Consultar distritos con precio > 0 (disponibles)
$stmt = $pdo->prepare("SELECT id, distrito, $campo_precio AS precio FROM envios WHERE departamento = ? AND provincia = ? AND $campo_precio > 0 AND habilitado = 1 ORDER BY distrito");
$stmt->execute([$dept, $prov]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>