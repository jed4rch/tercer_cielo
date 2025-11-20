<?php
require_once '../includes/init.php';

// === REDIRECCIÓN SI ES ADMIN ===
if (isset($_SESSION['user_id']) && $_SESSION['rol'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

$codigo = $_GET['codigo'] ?? '';
if (!$codigo) {
    header('Location: index.php');
    exit;
}
?>
<?php include 'cabecera_unificada.php'; ?>
    <div class="container my-5">
        <div class="text-center py-5">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
            <h1 class="mt-3">¡Pedido Confirmado!</h1>
            <p class="lead">Tu pedido ha sido recibido con éxito.</p>
            <div class="alert alert-info d-inline-block">
                <strong>Código de seguimiento:</strong> <h3 class="d-inline"><?= htmlspecialchars($codigo) ?></h3>
            </div>
            <p>Te enviaremos un correo con los detalles.</p>
            <a href="mis_pedidos.php" class="btn btn-primary">Ver mis pedidos</a>
            <a href="index.php" class="btn btn-outline-secondary">Seguir comprando</a>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>