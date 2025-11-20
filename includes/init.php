<?php
// includes/init.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === RUTA CORRECTA A TU ARCHIVO ===
require_once dirname(__DIR__) . '/config/conexion.php';
require_once 'func_carrito.php';
require_once 'func_correo.php';

// Verificar si el usuario cliente tiene su cuenta activa
if (isset($_SESSION['user_id']) && isset($_SESSION['rol']) && $_SESSION['rol'] === 'cliente') {
    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT activo FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch();
    
    // Si la cuenta está inactiva, cerrar sesión y redirigir
    if ($usuario && $usuario['activo'] == 0) {
        session_destroy();
        header('Location: /tercer_cielo/public/login.php?error=cuenta_inactiva');
        exit;
    }
}