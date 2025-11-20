<?php
require_once '../includes/init.php';
require_once '../includes/func_usuarios.php';

// Limpiar el session_id en la base de datos para usuarios admin
if (isset($_SESSION['user_id']) && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    $pdo = getPdo();
    $stmt = $pdo->prepare("UPDATE usuarios SET session_id = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

logout_usuario();
header('Location: login.php');
exit;
?>