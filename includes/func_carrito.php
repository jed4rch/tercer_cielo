<?php
// includes/func_carrito.php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/func_productos.php';

function agregar_al_carrito($producto_id, $cantidad = 1)
{
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    $producto_id = (int)$producto_id;
    $cantidad = max(1, (int)$cantidad);

    if ($producto_id <= 0) {
        return ['success' => false, 'mensaje' => 'Producto inválido'];
    }

    // Obtener producto
    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT id, nombre, precio, stock, imagen FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        return ['success' => false, 'mensaje' => 'Producto no encontrado'];
    }

    if ($producto['stock'] <= 0) {
        return ['success' => false, 'mensaje' => 'Sin stock'];
    }

    // Calcular disponible
    $en_carrito = isset($_SESSION['carrito'][$producto_id]) ? $_SESSION['carrito'][$producto_id]['cantidad'] : 0;
    $disponible = $producto['stock'] - $en_carrito;

    if ($cantidad > $disponible) {
        return [
            'success' => false,
            'mensaje' => "Solo hay $disponible unidad" . ($disponible !== 1 ? 'es' : '') . " disponibles"
        ];
    }

    // Agregar o actualizar
    if (isset($_SESSION['carrito'][$producto_id])) {
        $_SESSION['carrito'][$producto_id]['cantidad'] += $cantidad;
    } else {
        $_SESSION['carrito'][$producto_id] = [
            'id' => $producto['id'],
            'nombre' => $producto['nombre'],
            'imagen' => $producto['imagen'],
            'precio' => $producto['precio'],
            'cantidad' => $cantidad
        ];
    }

    // Calcular totales
    $nuevo_en_carrito = $_SESSION['carrito'][$producto_id]['cantidad'];
    $nuevo_disponible = $producto['stock'] - $nuevo_en_carrito;
    $items = contar_items_carrito();
    $total = get_total_carrito();

    return [
        'success' => true,
        'mensaje' => 'Producto agregado al carrito',
        'nuevoDisponible' => $nuevo_disponible,
        'stockTotal' => $producto['stock'],
        'enCarrito' => $nuevo_en_carrito,
        'items' => $items,
        'total' => number_format($total, 2)
    ];
}

function get_carrito()
{
    if (!isset($_SESSION)) {
        session_start();
    }
    if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }
    return $_SESSION['carrito'];
}
function contar_items_carrito()
{
    $carrito = get_carrito();
    return array_sum(array_column($carrito, 'cantidad'));
}

function get_total_carrito()
{
    $carrito = get_carrito();
    $total = 0;
    foreach ($carrito as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
    return $total;
}
function actualizar_cantidad_carrito($producto_id, $nueva_cantidad)
{
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    if (!isset($_SESSION['carrito'][$producto_id])) {
        return ['success' => false, 'mensaje' => 'No está en el carrito'];
    }

    if ($nueva_cantidad <= 0) {
        eliminar_del_carrito($producto_id);
        return [
            'success' => true,
            'items' => contar_items_carrito(),
            'total' => get_total_carrito()
        ];
    }

    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT stock FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $stock = $stmt->fetchColumn();

    if ($nueva_cantidad > $stock) {
        return ['success' => false, 'mensaje' => 'Stock insuficiente'];
    }

    $_SESSION['carrito'][$producto_id]['cantidad'] = $nueva_cantidad;

    return [
        'success' => true,
        'items' => contar_items_carrito(),
        'total' => get_total_carrito()
    ];
}

function eliminar_del_carrito($producto_id)
{
    unset($_SESSION['carrito'][$producto_id]);
}
