<?php
$pdo = null;
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tercer_cielo');

// === URL DEL SITIO (IMPORTANTE) ===
// Detectar automáticamente el puerto del servidor
$puerto = $_SERVER['SERVER_PORT'] ?? 80;
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Si es puerto 3000 (desarrollo), usar Apache en puerto 80 para los assets
define('SITIO_URL', 'http://localhost/tercer_cielo/public');
define('ASSETS_URL', 'http://localhost/tercer_cielo/public');

/**
 * Convierte una ruta de imagen de la BD a una URL absoluta que funcione desde cualquier puerto
 * @param string|null $ruta Ruta almacenada en la BD (ej: /tercer_cielo/public/uploads/...)
 * @return string URL completa de la imagen
 */
function getImageUrl($ruta) {
    if (empty($ruta)) {
        return ASSETS_URL . '/assets/img/default-product.png';
    }
    
    // Si ya es una URL completa, devolverla tal cual
    if (strpos($ruta, 'http://') === 0 || strpos($ruta, 'https://') === 0) {
        return $ruta;
    }
    
    // Si la ruta comienza con /tercer_cielo/public/, convertirla a URL absoluta
    if (strpos($ruta, '/tercer_cielo/public/') === 0) {
        return 'http://localhost' . $ruta;
    }
    
    // Si es una ruta relativa (uploads/...), agregarle la URL base
    if (strpos($ruta, 'uploads/') === 0) {
        return ASSETS_URL . '/' . $ruta;
    }
    
    // Para rutas que comienzan con /, agregarlas al host
    if (strpos($ruta, '/') === 0) {
        return 'http://localhost' . $ruta;
    }
    
    return $ruta;
}

function getPdo() {
    global $pdo;
    if ($pdo === null) {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
        }
    }
    return $pdo;
}
?>