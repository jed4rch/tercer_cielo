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

// Verificar que no sea el usuario actual
if ($id === $_SESSION['user_id']) {
    header('Location: usuarios.php?error=automodificar');
    exit;
}

// Obtener información del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: usuarios.php');
    exit;
}

// Verificar si el usuario admin tiene una sesión activa
if ($usuario['rol'] === 'admin' && !empty($usuario['session_id'])) {
    header('Location: usuarios.php?error=sesion_activa_inhabilitar');
    exit;
}

// Inhabilitar usuario
$stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
$stmt->execute([$id]);

header('Location: usuarios.php?success=inhabilitado');
exit;
?>