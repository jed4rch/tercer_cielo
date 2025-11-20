<?php
$pdo = null;
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tercer_cielo');

// === URL DEL SITIO (IMPORTANTE) ===
define('SITIO_URL', 'http://localhost/tercer_cielo/public');

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