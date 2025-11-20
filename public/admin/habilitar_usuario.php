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

// Habilitar usuario
$stmt = $pdo->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?");
$stmt->execute([$id]);

header('Location: usuarios.php?success=habilitado');
exit;
?>