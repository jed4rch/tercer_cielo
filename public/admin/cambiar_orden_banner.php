<?php
require_once '../../includes/init.php';
require_once '../../includes/func_banners.php';

// Solo admins
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$datos = json_decode(file_get_contents('php://input'), true);

if (!isset($datos['banner_id']) || !isset($datos['direccion'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$banner_id = intval($datos['banner_id']);
$direccion = $datos['direccion']; // 'up' o 'down'

// Obtener el banner actual
$pdo = getPdo();
$stmt = $pdo->prepare("SELECT id, orden FROM banners WHERE id = ?");
$stmt->execute([$banner_id]);
$banner_actual = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$banner_actual) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Banner no encontrado']);
    exit;
}

$orden_actual = $banner_actual['orden'];

// Determinar el nuevo orden
if ($direccion === 'up') {
    // Subir (disminuir orden)
    // Buscar el banner inmediatamente arriba
    $stmt = $pdo->prepare("SELECT id, orden FROM banners WHERE orden < ? ORDER BY orden DESC LIMIT 1");
    $stmt->execute([$orden_actual]);
    $banner_swap = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($banner_swap) {
        // Intercambiar órdenes
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE banners SET orden = ? WHERE id = ?");
            $stmt->execute([$banner_swap['orden'], $banner_id]);
            $stmt->execute([$orden_actual, $banner_swap['id']]);
            $pdo->commit();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al actualizar orden']);
            exit;
        }
    }
} elseif ($direccion === 'down') {
    // Bajar (aumentar orden)
    // Buscar el banner inmediatamente abajo
    $stmt = $pdo->prepare("SELECT id, orden FROM banners WHERE orden > ? ORDER BY orden ASC LIMIT 1");
    $stmt->execute([$orden_actual]);
    $banner_swap = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($banner_swap) {
        // Intercambiar órdenes
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE banners SET orden = ? WHERE id = ?");
            $stmt->execute([$banner_swap['orden'], $banner_id]);
            $stmt->execute([$orden_actual, $banner_swap['id']]);
            $pdo->commit();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al actualizar orden']);
            exit;
        }
    }
}

// Si llegamos aquí, no hay banner para intercambiar
header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'No se puede mover más en esa dirección']);
