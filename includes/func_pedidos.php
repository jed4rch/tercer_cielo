<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/func_carrito.php';
require_once __DIR__ . '/func_productos.php';

/**
 * Obtiene pedidos de usuario, filtrado por estado opcional.
 * @param int $id_usuario ID usuario.
 * @param string|null $estado Estado (pendiente, enviado, entregado).
 * @return array Lista de pedidos.
 */
function get_pedidos_by_usuario($id_usuario, $estado = null)
{
    $pdo = getPdo();
    if (!$pdo) return [];
    $sql = "SELECT * FROM pedidos WHERE id_usuario = ?";
    $params = [$id_usuario];
    if ($estado) {
        $sql .= " AND estado = ?";
        $params[] = $estado;
    }
    $sql .= " ORDER BY fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Actualiza estado de un pedido.
 * @param string $id_pedido ID pedido.
 * @param string $estado Nuevo estado.
 * @return bool True si update exitoso.
 */
function actualizar_estado_pedido($id_pedido, $estado)
{
    $pdo = getPdo();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    if ($stmt->execute([$estado, $id_pedido])) {
        // Notificación (email simple)
        $stmt = $pdo->prepare("SELECT u.email FROM usuarios u JOIN pedidos p ON u.id = p.id_usuario WHERE p.id = ?");
        $stmt->execute([$id_pedido]);
        $email = $stmt->fetchColumn();
        if ($email) mail($email, "Estado cambiado a $estado", "Tu pedido ID $id_pedido ha cambiado.");
        return true;
    }
    return $stmt->execute([$estado, $id_pedido]);
}
