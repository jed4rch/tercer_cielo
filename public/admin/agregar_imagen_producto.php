<?php
require_once '../../includes/init.php';
require_once '../../includes/func_imagenes.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$producto_id = (int)($_POST['producto_id'] ?? 0);
$orden = (int)($_POST['orden'] ?? 0);

if ($producto_id <= 0) {
    die(json_encode(['success' => false, 'error' => 'ID de producto inválido']));
}

if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
    die(json_encode(['success' => false, 'error' => 'No se ha seleccionado ninguna imagen']));
}

$pdo = getPdo();

try {
    // Guardar la imagen
    $resultado = guardarImagen($_FILES['imagen'], 'productos', 800, 800);
    
    if (!$resultado['success']) {
        die(json_encode(['success' => false, 'error' => $resultado['message']]));
    }
    
    // Insertar en la base de datos
    $stmt = $pdo->prepare("INSERT INTO producto_imagenes (producto_id, url_imagen, orden) VALUES (?, ?, ?)");
    $stmt->execute([$producto_id, $resultado['path'], $orden]);
    
    $id = $pdo->lastInsertId();
    
    die(json_encode([
        'success' => true,
        'id' => $id,
        'url' => $resultado['path']
    ]));
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'error' => 'Error al guardar la imagen en la base de datos']));
}
?>