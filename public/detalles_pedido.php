<?php
require_once '../includes/init.php';

// === REDIRECCIÓN SI ES ADMIN ===
if (isset($_SESSION['user_id']) && $_SESSION['rol'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pedido_id = $_GET['id'] ?? 0;
if (!is_numeric($pedido_id)) exit('ID inválido');

$pdo = getPdo();
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?");
$stmt->execute([$pedido_id, $_SESSION['user_id']]);
$pedido = $stmt->fetch();

if (!$pedido) exit('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Pedido no encontrado</div>');

// === DETALLES ===
$stmt = $pdo->prepare("SELECT * FROM pedido_detalles WHERE pedido_id = ?");
$stmt->execute([$pedido_id]);
$detalles = $stmt->fetchAll();

// === HISTORIAL ===
$stmt = $pdo->prepare("SELECT * FROM historial_pedidos WHERE pedido_id = ? ORDER BY fecha_cambio DESC");
$stmt->execute([$pedido_id]);
$historial = $stmt->fetchAll();
?>

<style>
    .info-card {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .info-card h6 {
        color: #007bff;
        font-weight: 700;
        margin-bottom: 1.2rem;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .detail-item {
        display: flex;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        background: white;
        border-radius: 10px;
        transition: all 0.2s ease;
    }
    
    .detail-item:hover {
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        transform: translateX(3px);
    }
    
    .detail-item strong {
        color: #495057;
        min-width: 150px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .detail-item strong i {
        color: #007bff;
        width: 20px;
    }
    
    .timeline-modern {
        position: relative;
        padding: 1rem;
    }
    
    .timeline-modern::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 3px;
        background: linear-gradient(180deg, #007bff 0%, #0056b3 100%);
        border-radius: 10px;
    }
    
    .timeline-event {
        position: relative;
        padding-left: 3rem;
        margin-bottom: 2rem;
    }
    
    .timeline-event::before {
        content: '';
        position: absolute;
        left: 7px;
        top: 5px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border: 4px solid white;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
    }
    
    .timeline-event .event-time {
        color: #6c757d;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    
    .timeline-event .event-content {
        background: white;
        padding: 1rem;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .badge-transition {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .table-modern {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .table-modern thead {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
    }
    
    .table-modern thead th {
        border: none;
        padding: 1rem;
        font-weight: 600;
    }
    
    .table-modern tbody tr {
        transition: background 0.2s ease;
    }
    
    .table-modern tbody tr:hover {
        background: rgba(0, 123, 255, 0.05);
    }
    
    .table-modern tbody td {
        padding: 1rem;
        vertical-align: middle;
    }
    
    .table-modern tfoot {
        background: #f8f9fa;
        font-weight: 600;
    }
    
    .table-modern tfoot td {
        padding: 1rem;
        border-top: 2px solid #dee2e6;
    }
    
    .product-name {
        font-weight: 600;
        color: #495057;
    }
    
    .price-highlight {
        color: #007bff;
        font-weight: 700;
        font-size: 1.1rem;
    }
</style>

<div class="row">
    <div class="col-lg-6">
        <div class="info-card">
            <h6><i class="bi bi-info-circle-fill"></i>Información del Pedido</h6>
            
            <div class="detail-item">
                <strong><i class="bi bi-receipt"></i>Código:</strong>
                <span><?= htmlspecialchars($pedido['codigo']) ?></span>
            </div>
            
            <div class="detail-item">
                <strong><i class="bi bi-calendar-check"></i>Fecha:</strong>
                <span><?= date('d/m/Y H:i', strtotime($pedido['creado_en'])) ?></span>
            </div>
            
            <div class="detail-item">
                <strong><i class="bi bi-currency-dollar"></i>Total:</strong>
                <span class="price-highlight">S/ <?= number_format($pedido['total'], 2) ?></span>
            </div>
            
            <div class="detail-item">
                <strong><i class="bi bi-credit-card"></i>Método de pago:</strong>
                <span><?= ucfirst($pedido['metodo_pago']) ?></span>
            </div>
            
            <div class="detail-item">
                <strong><i class="bi bi-truck"></i>Método de entrega:</strong>
                <span><?= ucfirst($pedido['metodo_envio']) ?></span>
            </div>
            
            <div class="detail-item">
                <strong><i class="bi bi-flag-fill"></i>Estado actual:</strong>
                <span class="badge bg-<?php 
                    echo match($pedido['estado']) {
                        'rechazado' => 'danger',
                        'pendiente', 'pendiente_pago' => 'warning',
                        'enviado' => 'info',
                        'entregado' => 'primary',
                        'aprobado' => 'success',
                        default => 'secondary'
                    };
                ?>">
                    <i class="bi bi-<?php 
                        echo match($pedido['estado']) {
                            'rechazado' => 'x-circle-fill',
                            'pendiente', 'pendiente_pago' => 'clock-history',
                            'enviado' => 'truck',
                            'entregado' => 'check2-circle',
                            default => 'check-circle-fill'
                        };
                    ?>"></i>
                    <?= ucfirst($pedido['estado'] === 'pendiente_pago' ? 'pendiente' : $pedido['estado']) ?>
                </span>
            </div>
            
            <div class="detail-item">
                <strong><i class="bi bi-box-seam"></i>Tipo de Entrega:</strong>
                <span>
                <?php 
                if ($pedido['tipo_envio'] === 'domicilio') {
                    $agencia_nombre = $pedido['agencia_envio'] === 'olva' ? 'Olva Courier' : 'Shalom';
                    echo "Envío a domicilio ($agencia_nombre)";
                } elseif ($pedido['tipo_envio'] === 'agencia') {
                    $agencia_nombre = $pedido['agencia_envio'] === 'olva' ? 'Olva Courier' : 'Shalom';
                    echo "Recojo en agencia $agencia_nombre";
                } else {
                    echo "Recojo en tienda";
                }
                ?>
                </span>
            </div>
            
            <div class="detail-item">
                <strong><i class="bi bi-geo-alt-fill"></i>Dirección:</strong>
                <span><?= htmlspecialchars($pedido['direccion_envio']) ?></span>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="info-card">
            <h6><i class="bi bi-clock-history"></i>Historial de Estados</h6>
            <div class="timeline-modern">
                <?php 
                $hasHistory = false;
                foreach ($historial as $h): 
                    // Normalizar estados
                    $estado_anterior = $h['estado_anterior'] === 'pendiente_pago' ? 'pendiente' : $h['estado_anterior'];
                    $estado_nuevo = $h['estado_nuevo'];
                    
                    // Solo mostrar si hay un cambio válido de estado
                    if ($estado_anterior && $estado_nuevo && $estado_anterior !== $estado_nuevo):
                        $hasHistory = true;
                ?>
                <div class="timeline-event">
                    <div class="event-time">
                        <i class="bi bi-clock"></i>
                        <?= date('d/m/Y H:i', strtotime($h['fecha_cambio'])) ?>
                    </div>
                    <div class="event-content">
                        <span class="badge-transition bg-<?php 
                                echo match($estado_anterior) {
                                'rechazado' => 'danger',
                                'pendiente', 'pendiente_pago' => 'warning',
                                'enviado' => 'info',
                                'entregado' => 'primary',
                                'aprobado' => 'success',
                                default => 'secondary'
                            };
                        ?>">
                            <i class="bi bi-<?php 
                                echo match($estado_anterior) {
                                    'rechazado' => 'x-circle-fill',
                                    'pendiente', 'pendiente_pago' => 'clock-history',
                                    'enviado' => 'truck',
                                    'entregado' => 'check2-circle',
                                    default => 'check-circle-fill'
                                };
                            ?>"></i>
                            <?= ucfirst($estado_anterior === 'pendiente_pago' ? 'pendiente' : $estado_anterior) ?>
                        </span>
                        <i class="bi bi-arrow-right mx-2"></i>
                        <span class="badge-transition bg-<?php 
                            echo match($estado_nuevo) {
                                'rechazado' => 'danger',
                                'pendiente' => 'warning',
                                'enviado' => 'info',
                                'entregado' => 'primary',
                                'aprobado' => 'success',
                                default => 'secondary'
                            };
                        ?>">
                            <i class="bi bi-check-circle"></i> 
                            <?= ucfirst($estado_nuevo) ?>
                        </span>
                    </div>
                </div>
                <?php 
                    endif; 
                endforeach; 
                
                if (!$hasHistory): 
                ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-2">No hay historial de cambios disponible.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="info-card mt-4">
    <h6><i class="bi bi-cart-check-fill"></i>Productos del Pedido</h6>
    <div class="table-responsive">
        <table class="table table-modern mb-0">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="text-center">Precio Unit.</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                foreach ($detalles as $d): 
                    $subtotal += $d['precio'] * $d['cantidad'];
                ?>
                    <tr>
                        <td class="product-name"><?= htmlspecialchars($d['nombre']) ?></td>
                        <td class="text-center">S/ <?= number_format($d['precio'], 2) ?></td>
                        <td class="text-center"><span class="badge bg-secondary"><?= $d['cantidad'] ?></span></td>
                        <td class="text-end">S/ <?= number_format($d['precio'] * $d['cantidad'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end">Subtotal:</td>
                    <td class="text-end">S/ <?= number_format($subtotal, 2) ?></td>
                </tr>
                <?php if ($pedido['metodo_envio'] === 'envio' && $pedido['precio_envio'] > 0): ?>
                <tr>
                    <td colspan="3" class="text-end">Precio de envío:</td>
                    <td class="text-end">S/ <?= number_format($pedido['precio_envio'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
                    <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                    <td class="text-end price-highlight">S/ <?= number_format($pedido['total'], 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>