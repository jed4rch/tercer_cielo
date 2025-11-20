<?php
require_once '../includes/init.php';
require_once '../includes/func_carrito.php';

header('Content-Type: application/json; charset=utf-8');
ob_clean();

$carrito = get_carrito();
$items_count = contar_items_carrito();
$total = get_total_carrito();

ob_start();

if (empty($carrito)) {
    echo '<p class="text-center text-muted p-3">Tu carrito está vacío</p>';
    $items_data = []; // vacío
} else {
    $items_data = [];
    foreach ($carrito as $id => $item) {
        $stmt = getPdo()->prepare("SELECT stock, imagen FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $producto = $stmt->fetch();
        $stock_total = $producto['stock'] ?: 0;
        
        // Actualizar imagen del item si existe en la BD
        if ($producto && $producto['imagen']) {
            $item['imagen'] = $producto['imagen'];
        }

        $items_data[$id] = [
            'id' => $id,
            'cantidad' => $item['cantidad'],
            'stock_total' => $stock_total
        ];

        $img_src = !empty($item['imagen'])
            ? $item['imagen']
            : '../assets/img/default-product.jpg';

        echo '
        <div class="carrito-item d-flex align-items-center mb-3" data-id="' . $id . '">
            <a href="producto.php?id=' . $id . '" class="me-3">
                <img src="' . htmlspecialchars($img_src) . '" 
                     alt="' . htmlspecialchars($item['nombre']) . '"
                     class="rounded"
                     style="width:50px;height:50px;object-fit:contain;background:#f8f9fa;"
                     onerror="this.src=\'../assets/img/default-product.jpg\';">
            </a>
            <div class="flex-grow-1">
                <h6 class="mb-1">
                    <a href="producto.php?id=' . $id . '" class="text-decoration-none text-dark">
                        ' . htmlspecialchars($item['nombre']) . '
                    </a>
                </h6>
                <small style="color: #007bff; font-weight: 600;">S/ ' . number_format($item['precio'], 2) . '</small>
            </div>
            <div class="d-flex align-items-center">
                <button class="btn btn-sm" onclick="cambiarCant(' . $id . ', -1)" style="border: 2px solid #007bff; color: #007bff; background: white; transition: all 0.2s ease;" onmouseover="this.style.background=\'#007bff\'; this.style.color=\'white\';" onmouseout="this.style.background=\'white\'; this.style.color=\'#007bff\';">-</button>
                <input type="text"
                    class="form-control form-control-sm mx-1 text-center cantidad-input"
                    value="' . $item['cantidad'] . '"
                    readonly
                    style="width:50px; border: 2px solid #e9ecef;">
                <button class="btn btn-sm" onclick="cambiarCant(' . $id . ', 1)" style="border: 2px solid #007bff; color: #007bff; background: white; transition: all 0.2s ease;" onmouseover="this.style.background=\'#007bff\'; this.style.color=\'white\';" onmouseout="this.style.background=\'white\'; this.style.color=\'#007bff\';">+</button>
                <button class="btn btn-sm ms-2" onclick="eliminar(' . $id . ')" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; transition: all 0.2s ease;" onmouseover="this.style.transform=\'scale(1.05)\'; this.style.boxShadow=\'0 2px 8px rgba(220, 53, 69, 0.3)\';" onmouseout="this.style.transform=\'scale(1)\'; this.style.boxShadow=\'none\';">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <hr class="my-2">';
    }

    echo '
    <div class="carrito-footer mt-3 p-3" style="background: linear-gradient(135deg, rgba(0, 123, 255, 0.05) 0%, rgba(0, 86, 179, 0.05) 100%); border-radius: 8px;">
        <div class="d-flex justify-content-between mb-3">
            <strong style="color: #495057;">Total:</strong>
            <strong style="color: #007bff; font-size: 1.25rem;">S/ ' . number_format($total, 2) . '</strong>
        </div>';

    if (isset($_SESSION['user_id'])) {
        echo $items_count > 0
            ? '<a href="checkout.php" class="btn w-100" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; font-weight: 600; border: none; padding: 0.75rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3); transition: all 0.3s ease; text-decoration: none; display: inline-block;" onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 6px 15px rgba(0, 123, 255, 0.4)\';" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 10px rgba(0, 123, 255, 0.3)\';">
                <i class="bi bi-lock"></i> Ir a Pagar
            </a>'
            : '<button class="btn w-100" disabled style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); color: white; font-weight: 600; border: none; padding: 0.75rem; border-radius: 8px; opacity: 0.65;">
                <i class="bi bi-lock"></i> Ir a Pagar
            </button>';
    } else {
        echo '<a href="login.php" class="btn w-100" style="color: #007bff; border: 2px solid #007bff; font-weight: 600; padding: 0.75rem; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: inline-block; background: white;" onmouseover="this.style.background=\'linear-gradient(135deg, #007bff 0%, #0056b3 100%)\'; this.style.color=\'white\'; this.style.borderColor=\'transparent\'; this.style.transform=\'translateY(-2px)\';" onmouseout="this.style.background=\'white\'; this.style.color=\'#007bff\'; this.style.borderColor=\'#007bff\'; this.style.transform=\'translateY(0)\';">Inicia sesión para pagar</a>';
    }

    echo '</div>';
}

$html = ob_get_clean();

echo json_encode([
    'html' => $html,
    'items_count' => $items_count,
    'total' => number_format($total, 2),
    'items' => $items_data  // ← SIEMPRE ARRAY, aunque vacío
]);

exit;