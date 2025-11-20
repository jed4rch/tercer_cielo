<?php
require_once '../../includes/init.php';
require_once '../../includes/func_imagenes.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: productos.php');
    exit;
}

$pdo = getPdo();

// Obtener todas las imágenes del producto para eliminarlas del servidor
$stmt = $pdo->prepare("SELECT imagen FROM productos WHERE id = ?");
$stmt->execute([$id]);
$producto = $stmt->fetch();

// Obtener imágenes adicionales
$stmt = $pdo->prepare("SELECT url_imagen FROM producto_imagenes WHERE producto_id = ?");
$stmt->execute([$id]);
$imagenes_adicionales = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Eliminar producto de la base de datos
$stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
$stmt->execute([$id]);

// Eliminar imagen principal del servidor
if ($producto && $producto['imagen']) {
    eliminarImagen($producto['imagen']);
}

// Eliminar imágenes adicionales del servidor
foreach ($imagenes_adicionales as $imagen) {
    eliminarImagen($imagen);
}

header('Location: productos.php?success=eliminado');
exit;
?>