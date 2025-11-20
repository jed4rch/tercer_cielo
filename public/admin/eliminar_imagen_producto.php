<?php
require_once '../../includes/init.php';
require_once '../../includes/func_imagenes.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$imagen_id = (int)($_POST['imagen_id'] ?? 0);

if ($imagen_id <= 0) {
    die(json_encode(['success' => false, 'error' => 'ID inválido']));
}

$pdo = getPdo();

try {
    // Obtener la ruta de la imagen antes de eliminarla
    $stmt = $pdo->prepare("SELECT url_imagen FROM producto_imagenes WHERE id = ?");
    $stmt->execute([$imagen_id]);
    $imagen = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$imagen) {
        die(json_encode(['success' => false, 'error' => 'Imagen no encontrada']));
    }
    
    // Eliminar de la base de datos
    $stmt = $pdo->prepare("DELETE FROM producto_imagenes WHERE id = ?");
    $stmt->execute([$imagen_id]);
    
    // Eliminar el archivo físico del servidor
    eliminarImagen($imagen['url_imagen']);
    
    die(json_encode(['success' => true]));
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'error' => 'Error al eliminar la imagen']));
}
?>