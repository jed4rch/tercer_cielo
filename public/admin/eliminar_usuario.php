<?php
require_once '../../includes/init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: usuarios.php');
    exit;
}

$pdo = getPdo();

// Verificar que no sea el último administrador
$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'");
$stmt->execute();
$totalAdmins = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if ($usuario['rol'] === 'admin' && $totalAdmins <= 1) {
    header('Location: usuarios.php?error=ultimo_admin');
    exit;
}

// No permitir que un admin se elimine a sí mismo
if ($id === $_SESSION['user_id']) {
    header('Location: usuarios.php?error=autoeliminar');
    exit;
}

// Verificar si el usuario admin tiene una sesión activa
if ($usuario['rol'] === 'admin' && !empty($usuario['session_id'])) {
    header('Location: usuarios.php?error=sesion_activa');
    exit;
}

// Verificar si el usuario tiene pedidos asociados
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE usuario_id = ?");
$stmt->execute([$id]);
$totalPedidos = $stmt->fetchColumn();

// Si es cliente y tiene pedidos, inactivarlo en lugar de eliminar
if ($usuario['rol'] === 'cliente' && $totalPedidos > 0) {
    $stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: usuarios.php?success=inactivado');
    exit;
}

// Si es administrador o no tiene pedidos, eliminar (solo si no es el último admin)
if ($usuario['rol'] === 'admin' && $totalAdmins <= 1) {
    header('Location: usuarios.php?error=ultimo_admin');
    exit;
}

$stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->execute([$id]);

header('Location: usuarios.php?success=eliminado');
exit;
?>