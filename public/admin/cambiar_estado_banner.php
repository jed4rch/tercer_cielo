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

if (!isset($datos['banner_id']) || !isset($datos['habilitado'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$banner_id = intval($datos['banner_id']);
$habilitado = intval($datos['habilitado']);

// Cambiar estado
$resultado = cambiarEstadoBanner($banner_id, $habilitado);

// Verificar si es un error con mensaje
if (is_array($resultado)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $resultado['error'], 'nombre' => $resultado['nombre']]);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => $resultado]);
