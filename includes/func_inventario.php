<?php
// includes/func_inventario.php
require_once __DIR__ . '/../config/conexion.php';

/**
 * Registra movimiento de inventario y actualiza stock.
 * 
 * @param int $id_producto ID del producto
 * @param string $tipo Tipo de movimiento: 'entrada' o 'salida'
 * @param int $cantidad Cantidad del movimiento
 * @param PDO|null $pdo_externo Conexión PDO externa (si ya hay una transacción activa)
 * @return bool True si se registró correctamente, false en caso contrario
 */
function registrar_movimiento($id_producto, $tipo, $cantidad, $pdo_externo = null) {
    $pdo = $pdo_externo ?? getPdo();
    if (!$pdo || $cantidad <= 0) return false;
    
    // Solo iniciar transacción si no se proporcionó una conexión externa
    $transaccion_propia = ($pdo_externo === null);
    
    try {
        if ($transaccion_propia) {
            $pdo->beginTransaction();
        }
        
        $stmt = $pdo->prepare("INSERT INTO movimientos_inventario (id_producto, tipo, cantidad) VALUES (?, ?, ?)");
        $stmt->execute([$id_producto, $tipo, $cantidad]);

        $op = $tipo === 'entrada' ? '+' : '-';
        $stmt = $pdo->prepare("UPDATE productos SET stock = stock $op ? WHERE id = ?");
        $stmt->execute([$cantidad, $id_producto]);
        
        if ($transaccion_propia) {
            $pdo->commit();
        }
        return true;
    } catch (Exception $e) {
        if ($transaccion_propia) {
            $pdo->rollBack();
        }
        return false;
    }
}

