<?php
require_once '../includes/init.php';
require_once '../includes/func_carrito.php';

header('Content-Type: application/json');
ob_clean();

$id = (int)($_POST['producto_id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

eliminar_del_carrito($id);

$carrito = get_carrito();
$items_count = contar_items_carrito();
$total = get_total_carrito();

echo json_encode([
    'success' => true,
    'items' => $items_count,
    'total' => number_format($total, 2)
]);
exit;