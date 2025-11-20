<?php
require_once '../../includes/init.php';
require_once '../../includes/func_banners.php';

// Solo admins
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Verificar ID
if (!isset($_GET['id'])) {
    header('Location: banners.php?error=' . urlencode('ID de banner no especificado'));
    exit;
}

$banner_id = intval($_GET['id']);

// Verificar que el banner existe
$banner = obtenerBannerPorId($banner_id);
if (!$banner) {
    header('Location: banners.php?error=' . urlencode('Banner no encontrado'));
    exit;
}

// Eliminar el banner
$resultado = eliminarBanner($banner_id);

if ($resultado) {
    header('Location: banners.php?success=eliminado');
} else {
    header('Location: banners.php?error=' . urlencode('Error al eliminar el banner'));
}
exit;
