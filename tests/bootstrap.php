<?php
require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir func_admin.php primero para que su versión de actualizar_estado_pedido 
// sea la que se use (tiene más funcionalidad con envío de correo)
require_once __DIR__ . '/../includes/func_admin.php';

// No requerir conexion.php directamente aquí; getPdo() maneja la conexión
?>