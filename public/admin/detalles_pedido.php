<?php
require_once '../../includes/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /tercer_cielo/public/login.php');
    exit;
}

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /tercer_cielo/public/index.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    exit('<div class="alert alert-warning">ID inválido</div>');
}

$pdo = getPdo();

// === VERIFICAR PEDIDO ===
$sql = "SELECT p.*, u.nombre as usuario, u.email, u.telefono 
        FROM pedidos p 
        LEFT JOIN usuarios u ON p.usuario_id = u.id 
        WHERE p.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    exit('<div class="alert alert-warning">Pedido no encontrado</div>');
}

// === DETALLES ===
$stmt = $pdo->prepare("
    SELECT pd.*, p.nombre as producto_nombre 
    FROM pedido_detalles pd 
    LEFT JOIN productos p ON pd.producto_id = p.id 
    WHERE pd.pedido_id = ?
");
$stmt->execute([$id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === HISTORIAL ===
$stmt = $pdo->prepare("SELECT * FROM historial_pedidos WHERE pedido_id = ? ORDER BY fecha_cambio DESC");
$stmt->execute([$id]);
$historial = $stmt->fetchAll();

$pageTitle = 'Detalles del Pedido - ' . htmlspecialchars($pedido['codigo']);
include 'layout_header.php';
?>

<style>
    .timeline {
        position: relative;
        padding: 20px 0;
    }

    .timeline-item {
        padding: 15px;
        border-left: 3px solid #dee2e6;
        padding-left: 20px;
        position: relative;
        margin-bottom: 15px;
        background-color: #f8f9fa;
        border-radius: 0 4px 4px 0;
    }

    .timeline-item:before {
        content: '';
        width: 12px;
        height: 12px;
        background: #fff;
        border: 3px solid #0d6efd;
        border-radius: 50%;
        position: absolute;
        left: -7px;
        top: 20px;
    }

    .timeline-item:hover {
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
</style>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>
            <i class="bi bi-box-seam me-2"></i>
            Pedido <?= htmlspecialchars($pedido['codigo']) ?>
        </h3>
        <a href="pedidos.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    <div class="row">
            <!-- Información del Cliente -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person-circle"></i> Información del Cliente
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="text-muted small">Nombre:</label>
                                <p class="mb-1"><?= htmlspecialchars($pedido['usuario']) ?></p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Email:</label>
                                <p class="mb-1"><?= htmlspecialchars($pedido['email']) ?></p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Teléfono:</label>
                                <p class="mb-1"><?= htmlspecialchars($pedido['telefono']) ?></p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Fecha del Pedido:</label>
                                <p class="mb-1"><?= date('d/m/Y H:i', strtotime($pedido['creado_en'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalles de Pago -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-credit-card"></i> Detalles de Pago
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td>Total productos:</td>
                                    <td class="text-end">S/ <?= number_format($pedido['total'] - $pedido['precio_envio'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Envío:</td>
                                    <td class="text-end">S/ <?= number_format($pedido['precio_envio'], 2) ?></td>
                                </tr>
                                <tr class="table-active fw-bold">
                                    <td>Total Final:</td>
                                    <td class="text-end">S/ <?= number_format($pedido['total'], 2) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="mt-3">
                            <label class="text-muted small">Método de pago:</label>
                            <p class="mb-2"><?= ucfirst($pedido['metodo_pago'] ?: 'No especificado') ?></p>

                            <label class="text-muted small">Estado del pedido:</label>
                            <div>
                                <select class="form-select form-select-sm w-auto" onchange="cambiarEstado(<?= $pedido['id'] ?>, this.value)">
                                    <option value="pendiente" <?= $pedido['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="aprobado" <?= $pedido['estado'] == 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                                    <option value="rechazado" <?= $pedido['estado'] == 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                                    <?php if ($pedido['tipo_envio'] !== 'tienda'): ?>
                                    <option value="enviado" <?= $pedido['estado'] == 'enviado' ? 'selected' : '' ?>>Enviado</option>
                                    <?php endif; ?>
                                    <option value="entregado" <?= $pedido['estado'] == 'entregado' ? 'selected' : '' ?>>Entregado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de Envío y Estado -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-truck"></i> Información de Envío
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small">Tipo de entrega:</label>
                            <p class="mb-2">
                                <?php 
                                if ($pedido['tipo_envio'] === 'domicilio') {
                                    $agencia_nombre = $pedido['agencia_envio'] === 'olva' ? 'Olva Courier' : 'Shalom';
                                    echo "<i class='bi bi-truck'></i> Envío a domicilio ($agencia_nombre)";
                                } elseif ($pedido['tipo_envio'] === 'agencia') {
                                    $agencia_nombre = $pedido['agencia_envio'] === 'olva' ? 'Olva Courier' : 'Shalom';
                                    echo "<i class='bi bi-box-seam'></i> Recojo en agencia $agencia_nombre";
                                } else {
                                    echo "<i class='bi bi-shop'></i> Recojo en tienda";
                                }
                                ?>
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Dirección:</label>
                            <p class="mb-2"><?= htmlspecialchars($pedido['direccion_envio']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Historial de Estados -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clock-history"></i> Historial de Estados
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($historial)): ?>
                            <p class="text-muted text-center py-3">
                                <i class="bi bi-info-circle"></i> No hay historial de cambios.
                            </p>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach ($historial as $h): ?>
                                    <?php if (!empty($h['estado_nuevo'])): ?>
                                        <div class="timeline-item">
                                            <div class="mb-2">
                                                <strong class="text-dark">
                                                    <?= date('d/m/Y H:i', strtotime($h['fecha_cambio'])) ?>
                                                </strong>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($h['estado_anterior'])): ?>
                                                    <span class="badge bg-<?=
                                                                            match ($h['estado_anterior']) {
                                                                                'rechazado' => 'danger',
                                                                                'pendiente', 'pendiente_pago' => 'warning',
                                                                                'enviado' => 'info',
                                                                                'entregado' => 'primary',
                                                                                'aprobado' => 'success',
                                                                                default => 'secondary'
                                                                            }
                                                                            ?>"><?= ucfirst($h['estado_anterior'] === 'pendiente_pago' ? 'pendiente' : $h['estado_anterior']) ?></span>
                                                    <i class="bi bi-arrow-right mx-2"></i>
                                                <?php endif; ?>
                                                <span class="badge bg-<?=
                                                                        match ($h['estado_nuevo']) {
                                                                            'rechazado' => 'danger',
                                                                            'pendiente', 'pendiente_pago' => 'warning',
                                                                            'enviado' => 'info',
                                                                            'entregado' => 'primary',
                                                                            'aprobado' => 'success',
                                                                            default => 'secondary'
                                                                        }
                                                                        ?>"><?= ucfirst($h['estado_nuevo'] === 'pendiente_pago' ? 'pendiente' : $h['estado_nuevo']) ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalles de Productos -->
        <div class="card mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-cart3"></i> Detalle de Productos
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-end">Precio Unitario</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['nombre']) ?></td>
                                    <td class="text-end">S/ <?= number_format($d['precio'], 2) ?></td>
                                    <td class="text-center"><?= $d['cantidad'] ?></td>
                                    <td class="text-end">S/ <?= number_format($d['precio'] * $d['cantidad'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-light">
                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end"><strong>S/ <?= number_format($pedido['total'] - $pedido['precio_envio'], 2) ?></strong></td>
                            </tr>
                            <tr class="table-light">
                                <td colspan="3" class="text-end"><strong>Envío:</strong></td>
                                <td class="text-end"><strong>S/ <?= number_format($pedido['precio_envio'], 2) ?></strong></td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="3" class="text-end"><strong>Total Final:</strong></td>
                                <td class="text-end"><strong>S/ <?= number_format($pedido['total'], 2) ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Comprobante -->
        <?php if ($pedido['comprobante']): ?>
            <div class="card mt-4">
                <div class="card-header bg-warning">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark-image"></i> Comprobante de Pago
                    </h5>
                </div>
                <div class="card-body text-center">
                    <img src="../<?= $pedido['comprobante'] ?>" class="img-fluid mb-3" style="max-height: 400px;" alt="Comprobante de pago">
                    <div class="btn-group">
                        <a href="../<?= $pedido['comprobante'] ?>" class="btn btn-primary" target="_blank">
                            <i class="bi bi-eye-fill"></i> Ver Original
                        </a>
                        <a href="../<?= $pedido['comprobante'] ?>" class="btn btn-secondary" download>
                            <i class="bi bi-download"></i> Descargar
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Overlay de carga -->
    <div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center;">
        <div style="text-align: center; color: white;">
            <div class="spinner-border" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p style="margin-top: 20px; font-size: 18px;" id="loadingText">Procesando...</p>
        </div>
    </div>

    <script>
        function mostrarCarga(texto) {
            document.getElementById('loadingText').textContent = texto;
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function ocultarCarga() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        function cambiarEstado(pedidoId, estado) {
            let mensaje = '¿Estás seguro de cambiar el estado a ' + estado + '?';
            let mensajeCarga = 'Procesando...';
            let titulo = 'Confirmar cambio de estado';
            let colorBtn = '#0d6efd';
            let textoBtn = 'Confirmar';
            
            if (estado === 'aprobado') {
                titulo = '✅ Aprobar pedido';
                mensaje = '¿Confirmas la aprobación del pedido? Se enviará una notificación por correo al cliente con la boleta de venta.';
                mensajeCarga = 'Aprobando pedido y generando boleta...';
                colorBtn = '#198754';
                textoBtn = 'Aprobar';
            } else if (estado === 'rechazado') {
                titulo = '❌ Rechazar pedido';
                mensaje = '¿Estás seguro de rechazar este pedido? El cliente será notificado por correo electrónico.';
                mensajeCarga = 'Rechazando pedido y notificando al cliente...';
                colorBtn = '#dc3545';
                textoBtn = 'Rechazar';
            } else if (estado === 'enviado') {
                titulo = '📦 Marcar como enviado';
                mensaje = '¿El pedido ha sido enviado? Se notificará al cliente con los detalles del envío.';
                mensajeCarga = 'Actualizando estado y enviando notificación...';
                colorBtn = '#0dcaf0';
                textoBtn = 'Marcar enviado';
            } else if (estado === 'entregado') {
                titulo = '✅ Marcar como entregado';
                mensaje = '¿El pedido ha sido entregado al cliente? Se enviará una notificación de confirmación.';
                mensajeCarga = 'Marcando como entregado y notificando...';
                colorBtn = '#198754';
                textoBtn = 'Marcar entregado';
            }

            mostrarConfirmacion(titulo, mensaje, textoBtn, colorBtn, () => {
                mostrarCarga(mensajeCarga);

                fetch('actualizar_estado.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `pedido_id=${pedidoId}&estado=${estado}`
                    })
                    .then(response => response.json().then(data => ({
                        ok: response.ok,
                        status: response.status,
                        data: data
                    })))
                    .then(result => {
                        ocultarCarga();
                        if (result.ok && result.data.success) {
                            let mensajeExito = '';
                            let iconoExito = '';
                            if (estado === 'aprobado') {
                                iconoExito = 'success';
                                mensajeExito = 'Pedido aprobado exitosamente. Se ha generado la boleta y enviado el correo al cliente.';
                            } else if (estado === 'rechazado') {
                                iconoExito = 'error';
                                mensajeExito = 'Pedido rechazado. Se ha notificado al cliente por correo.';
                            } else if (estado === 'enviado') {
                                iconoExito = 'info';
                                mensajeExito = 'Pedido marcado como enviado. El cliente ha sido notificado.';
                            } else if (estado === 'entregado') {
                                iconoExito = 'success';
                                mensajeExito = 'Pedido marcado como entregado. El cliente ha sido notificado.';
                            } else {
                                iconoExito = 'success';
                                mensajeExito = 'Estado actualizado correctamente.';
                            }
                            mostrarAlerta('✅ Éxito', mensajeExito, iconoExito);
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            console.error('Error completo:', result.data);
                            mostrarAlerta('⚠️ Error', result.data.message || 'Error al actualizar el estado. Por favor, intenta nuevamente.', 'error');
                        }
                    })
                    .catch(error => {
                        ocultarCarga();
                        console.error('Error completo:', error);
                        mostrarAlerta('⚠️ Error de conexión', 'No se pudo procesar la solicitud. Verifica tu conexión a internet.', 'error');
                    });
            });
        }

        // === MODAL DE CONFIRMACIÓN ===
        function mostrarConfirmacion(titulo, mensaje, textoBtn, colorBtn, onConfirm) {
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                animation: fadeIn 0.2s;
            `;
            
            const modal = document.createElement('div');
            modal.style.cssText = `
                background: white;
                border-radius: 12px;
                padding: 30px;
                max-width: 450px;
                width: 90%;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                animation: slideIn 0.3s;
            `;
            
            modal.innerHTML = `
                <style>
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes slideIn {
                        from { transform: translateY(-20px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                </style>
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="width: 70px; height: 70px; background: #e3f2fd; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                        <i class="bi bi-question-circle" style="font-size: 35px; color: #1976d2;"></i>
                    </div>
                    <h5 style="margin: 0 0 15px 0; color: #333; font-weight: 600; font-size: 20px;">${titulo}</h5>
                    <p style="margin: 0; color: #666; font-size: 15px; line-height: 1.5;">${mensaje}</p>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button id="btnCancelar" style="flex: 1; padding: 12px 20px; border: 2px solid #dee2e6; background: white; color: #6c757d; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 15px;">
                        Cancelar
                    </button>
                    <button id="btnConfirmar" style="flex: 1; padding: 12px 20px; border: none; background: ${colorBtn}; color: white; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 15px;">
                        ${textoBtn}
                    </button>
                </div>
            `;
            
            overlay.appendChild(modal);
            document.body.appendChild(overlay);
            
            const btnCancelar = modal.querySelector('#btnCancelar');
            const btnConfirmar = modal.querySelector('#btnConfirmar');
            
            btnCancelar.onmouseover = () => btnCancelar.style.background = '#f8f9fa';
            btnCancelar.onmouseout = () => btnCancelar.style.background = 'white';
            btnConfirmar.onmouseover = () => btnConfirmar.style.transform = 'scale(1.05)';
            btnConfirmar.onmouseout = () => btnConfirmar.style.transform = 'scale(1)';
            
            btnCancelar.onclick = () => overlay.remove();
            btnConfirmar.onclick = () => {
                overlay.remove();
                onConfirm();
            };
            overlay.onclick = (e) => {
                if (e.target === overlay) overlay.remove();
            };
        }

        // === MODAL DE ALERTA ===
        function mostrarAlerta(titulo, mensaje, tipo) {
            const colores = {
                success: { bg: '#d4edda', border: '#28a745', icon: 'check-circle', iconColor: '#28a745' },
                error: { bg: '#f8d7da', border: '#dc3545', icon: 'x-circle', iconColor: '#dc3545' },
                info: { bg: '#d1ecf1', border: '#17a2b8', icon: 'info-circle', iconColor: '#17a2b8' }
            };
            
            const color = colores[tipo] || colores.info;
            
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.2s;
            `;
            
            const modal = document.createElement('div');
            modal.style.cssText = `
                background: white;
                border-radius: 12px;
                padding: 30px;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                animation: slideIn 0.3s;
            `;
            
            modal.innerHTML = `
                <div style="text-align: center;">
                    <div style="width: 70px; height: 70px; background: ${color.bg}; border: 3px solid ${color.border}; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                        <i class="bi bi-${color.icon}" style="font-size: 35px; color: ${color.iconColor};"></i>
                    </div>
                    <h5 style="margin: 0 0 15px 0; color: #333; font-weight: 600; font-size: 20px;">${titulo}</h5>
                    <p style="margin: 0 0 25px 0; color: #666; font-size: 15px; line-height: 1.5;">${mensaje}</p>
                    <button id="btnCerrar" style="padding: 12px 40px; border: none; background: ${color.border}; color: white; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 15px;">
                        Aceptar
                    </button>
                </div>
            `;
            
            overlay.appendChild(modal);
            document.body.appendChild(overlay);
            
            const btnCerrar = modal.querySelector('#btnCerrar');
            btnCerrar.onmouseover = () => btnCerrar.style.transform = 'scale(1.05)';
            btnCerrar.onmouseout = () => btnCerrar.style.transform = 'scale(1)';
            btnCerrar.onclick = () => overlay.remove();
            overlay.onclick = (e) => {
                if (e.target === overlay) overlay.remove();
            };
        }
    </script>
</div>

<?php include 'layout_footer.php'; ?>