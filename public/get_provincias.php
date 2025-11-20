<?php
require_once '../includes/init.php';
$pdo = getPdo();
$dept = $_GET['departamento'] ?? '';
$stmt = $pdo->prepare("SELECT DISTINCT provincia FROM envios WHERE departamento = ? AND habilitado = 1 ORDER BY provincia");
$stmt->execute([$dept]);
echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
?>