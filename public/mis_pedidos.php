<?php
require_once '../includes/init.php';
require_once '../includes/func_pedidos.php';

// === REDIRECCIÓN SI ES ADMIN ===
if (isset($_SESSION['user_id']) && $_SESSION['rol'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$pdo = getPdo();

// === PEDIDOS DEL USUARIO (CORREGIDO: CASE + COALESCE + precio_envio) ===
$stmt = $pdo->prepare("
    SELECT 
           p.*,
           COALESCE(p.estado, 'pendiente') as estado,
           COALESCE(
               CASE UPPER(p.metodo_pago)
                   WHEN 'YAPE' THEN 'Yape'
                   WHEN 'PLIN' THEN 'Plin'
                   ELSE p.metodo_pago
               END, 'No especificado'
           ) AS metodo_nombre
    FROM pedidos p
    WHERE p.usuario_id = ?
    ORDER BY p.creado_en DESC
");
$stmt->execute([$user_id]);
$pedidos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis Pedidos - Tercer Cielo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --purple-color: #6f42c1;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .page-header h2 {
            font-weight: 700;
            margin: 0;
            font-size: 2.5rem;
        }

        .page-header .subtitle {
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        .pedido-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .pedido-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .pedido-card .card-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            padding: 1.25rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pedido-card .card-header .order-code {
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pedido-card .card-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background 0.2s ease;
        }

        .info-row:hover {
            background: #f8f9fa;
        }

        .info-row i {
            width: 30px;
            color: #007bff;
            font-size: 1.1rem;
        }

        .info-row .label {
            font-weight: 600;
            color: #495057;
            min-width: 140px;
        }

        .info-row .value {
            color: #6c757d;
        }

        .badge-status {
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pendiente,
        .badge-pendiente_pago {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: #212529;
        }

        .badge-rechazado {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .badge-aprobado {
            background: linear-gradient(135deg, #28a745 0%, #5cb85c 100%);
            color: white;
        }

        .badge-enviado {
            background: linear-gradient(135deg, #17a2b8 0%, #5bc0de 100%);
            color: white;
        }

        .badge-entregado {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .btn-ver-detalles {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white !important;
            border: none;
            border-radius: 25px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
            text-decoration: none;
        }

        .btn-ver-detalles:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
            color: white !important;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .empty-state i {
            font-size: 5rem;
            color: #007bff;
            margin-bottom: 1.5rem;
        }

        .empty-state h4 {
            font-weight: 700;
            color: #495057;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #6c757d;
            margin-bottom: 2rem;
        }

        .btn-explorar {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            border-radius: 25px;
            padding: 0.8rem 2.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-explorar:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
            color: white;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, #007bff 0%, #0056b3 100%);
            border-radius: 10px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .timeline-dot {
            position: absolute;
            left: -0.6rem;
            top: 0.5rem;
            width: 1.2rem;
            height: 1.2rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .modal-content {
            border-radius: 20px;
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 700;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 123, 255, 0.05);
        }

        @media (max-width: 768px) {
            .page-header h2 {
                font-size: 2rem;
            }
            
            .info-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .info-row .label {
                min-width: auto;
            }
        }
    </style>
</head>

<?php
$titulo = 'Mis Pedidos - Tercer Cielo';
include 'cabecera_unificada.php';
?>

    <div class="page-header">
        <div class="container">
            <h2><i class="bi bi-bag-check-fill me-3"></i>Mis Pedidos</h2>
            <p class="subtitle mb-0">Gestiona y revisa el estado de tus compras</p>
        </div>
    </div>

    <div class="container mb-5">
        <?php if (empty($pedidos)): ?>
            <div class="empty-state">
                <i class="bi bi-cart-x"></i>
                <h4>No tienes pedidos aún</h4>
                <p>Tu historial de compras aparecerá aquí una vez realices tu primera orden.</p>
                <a href="catalogo.php" class="btn btn-explorar">
                    <i class="bi bi-shop me-2"></i>Explorar Productos
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($pedidos as $p): ?>
                    <div class="col-md-6 col-xl-4 d-flex">
                        <div class="card pedido-card">
                            <div class="card-header">
                                <div class="order-code">
                                    <i class="bi bi-receipt"></i>
                                    <span><?= htmlspecialchars($p['codigo']) ?></span>
                                </div>
                                <span class="badge-status badge-<?= $p['estado'] ?>">
                                    <i class="bi bi-<?= 
                                        $p['estado'] == 'rechazado' ? 'x-circle-fill' :
                                        ($p['estado'] == 'entregado' ? 'check2-circle' :
                                        ($p['estado'] == 'enviado' ? 'truck' :
                                        (in_array($p['estado'], ['pendiente', 'pendiente_pago']) ? 'clock-history' : 'check-circle-fill')))
                                    ?>"></i>
                                    <?= $p['estado'] == 'pendiente_pago' ? 'Pendiente' : ucfirst($p['estado']) ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="info-row">
                                    <i class="bi bi-calendar-event"></i>
                                    <span class="label">Fecha:</span>
                                    <span class="value"><?= date('d/m/Y H:i', strtotime($p['creado_en'])) ?></span>
                                </div>

                                <div class="info-row">
                                    <i class="bi bi-currency-dollar"></i>
                                    <span class="label">Total:</span>
                                    <span class="value" style="font-size: 1.2rem; font-weight: 700; color: #007bff;">S/ <?= number_format($p['total'], 2) ?></span>
                                </div>

                                <div class="info-row">
                                    <i class="bi bi-credit-card"></i>
                                    <span class="label">Pago:</span>
                                    <span class="value"><?= htmlspecialchars($p['metodo_nombre']) ?></span>
                                </div>

                                <div class="info-row">
                                    <i class="bi bi-<?= $p['metodo_envio'] === 'envio' ? 'truck' : 'shop' ?>"></i>
                                    <span class="label">Entrega:</span>
                                    <span class="value"><?php 
                                        if ($p['metodo_envio'] === 'envio') {
                                            if ($p['tipo_envio'] === 'domicilio') {
                                                echo 'Envío a domicilio';
                                            } elseif ($p['tipo_envio'] === 'agencia') {
                                                echo 'Recojo en agencia';
                                            } else {
                                                echo 'Envío a domicilio';
                                            }
                                        } else {
                                            echo 'Recojo en tienda';
                                        }
                                    ?></span>
                                </div>

                                <?php if ($p['metodo_envio'] === 'envio' && isset($p['precio_envio']) && $p['precio_envio'] > 0): ?>
                                <div class="info-row">
                                    <i class="bi bi-truck"></i>
                                    <span class="label">Costo envío:</span>
                                    <span class="value">S/ <?= number_format($p['precio_envio'], 2) ?></span>
                                </div>
                                <?php endif; ?>

                                <div class="info-row">
                                    <i class="bi bi-geo-alt"></i>
                                    <span class="label">Dirección:</span>
                                    <span class="value"><?= htmlspecialchars(strlen($p['direccion_envio']) > 40 ? substr($p['direccion_envio'], 0, 40) . '...' : $p['direccion_envio']) ?></span>
                                </div>

                                <div style="margin-top: auto;">
                                    <button class="btn btn-ver-detalles" onclick="verDetalles(<?= $p['id'] ?>)">
                                        <i class="bi bi-eye me-2"></i>Ver Detalles Completos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL DETALLES -->
    <div class="modal fade" id="modalDetalles" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-text me-2"></i>Detalles del Pedido</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="modal-content">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="text-muted mt-3">Cargando detalles del pedido...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalles(pedidoId) {
            fetch(`detalles_pedido.php?id=${pedidoId}`)
                .then(r => r.text())
                .then(html => {
                    document.getElementById('modal-content').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('modalDetalles')).show();
                });
        }
    </script>
</body>

</html>
