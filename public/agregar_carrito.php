<?php
require_once '../includes/init.php';
require_once '../includes/func_carrito.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Debes iniciar sesión']);
    exit;
}

$id = (int)($_POST['producto_id'] ?? 0);
$cant = max(1, (int)($_POST['cantidad'] ?? 1));

if ($id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'Producto inválido']);
    exit;
}

// Usar directamente la función agregar_al_carrito que ya maneja la lógica de duplicación
$resultado = agregar_al_carrito($id, $cant);

echo json_encode($resultado);
exit;