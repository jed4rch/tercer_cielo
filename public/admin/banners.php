<?php
require_once '../../includes/init.php';
require_once '../../includes/func_banners.php';
require_once '../../includes/func_productos.php';

// Solo admins
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Obtener todos los banners
$banners = obtenerBanners(false);
$productos = obtenerProductosParaBanner();
$categorias = obtenerCategoriasParaBanner();

$pageTitle = 'Gestión de Banners';
include 'layout_header.php';
?>

<!-- Contenido específico de Banners -->
<style>
    .banner-preview {
        width: 100%;
        max-width: 200px;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 123, 255, 0.2);
    }

        .table-actions {
            white-space: nowrap;
        }

    .badge-orden {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1.1rem;
        min-width: 40px;
        display: inline-block;
        text-align: center;
    }

    .btn-orden-up:hover,
    .btn-orden-down:hover {
        transform: scale(1.1);
    }

    .btn-orden-up:disabled,
    .btn-orden-down:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
</style>

    <div class="container mt-4">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="bi bi-image me-2"></i>Gestión de Banners</h3>
                <a href="agregar_banner.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Agregar Banner
                </a>
            </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i>
                <?php
                switch ($_GET['success']) {
                    case 'agregado':
                        echo 'Banner agregado correctamente';
                        break;
                    case 'editado':
                        echo 'Banner actualizado correctamente';
                        break;
                    case 'eliminado':
                        echo 'Banner eliminado correctamente';
                        break;
                    case 'habilitado':
                        echo 'Banner habilitado correctamente';
                        break;
                    case 'deshabilitado':
                        echo 'Banner deshabilitado correctamente';
                        break;
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($banners)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-image" style="font-size: 4rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">No hay banners registrados</p>
                        <a href="agregar_banner.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Agregar Primer Banner
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Preview</th>
                                    <th>Enlace</th>
                                    <th style="width: 120px;">Orden</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($banners as $banner): ?>
                                    <tr>
                                        <td>
                                            <img src="../<?= htmlspecialchars($banner['imagen']) ?>" 
                                                 alt="Banner" 
                                                 class="banner-preview">
                                        </td>
                                        <td>
                                            <?php if ($banner['tipo_enlace'] === 'ninguno'): ?>
                                                <span class="badge bg-secondary">Sin enlace</span>
                                            <?php elseif ($banner['tipo_enlace'] === 'producto'): ?>
                                                <span class="badge bg-info">
                                                    <i class="bi bi-box"></i> Producto
                                                </span>
                                                <br><small><?= htmlspecialchars($banner['enlace_nombre'] ?? 'N/A') ?></small>
                                            <?php elseif ($banner['tipo_enlace'] === 'categoria'): ?>
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-tag"></i> Categoría
                                                </span>
                                                <br><small><?= htmlspecialchars($banner['enlace_nombre'] ?? 'N/A') ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge-orden"><?= $banner['orden'] ?></span>
                                                <div class="btn-group-vertical" style="height: 50px;">
                                                    <button class="btn btn-sm btn-outline-primary btn-orden-up" 
                                                            data-banner-id="<?= $banner['id'] ?>"
                                                            style="padding: 2px 8px; font-size: 0.7rem;"
                                                            title="Subir">
                                                        <i class="bi bi-chevron-up"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-primary btn-orden-down" 
                                                            data-banner-id="<?= $banner['id'] ?>"
                                                            style="padding: 2px 8px; font-size: 0.7rem;"
                                                            title="Bajar">
                                                        <i class="bi bi-chevron-down"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input toggle-estado" 
                                                       type="checkbox" 
                                                       data-banner-id="<?= $banner['id'] ?>"
                                                       <?= $banner['habilitado'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">
                                                    <?= $banner['habilitado'] ? 'Activo' : 'Inactivo' ?>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <small><?= date('d/m/Y', strtotime($banner['fecha_creacion'])) ?></small>
                                        </td>
                                        <td class="text-center table-actions">
                                            <a href="editar_banner.php?id=<?= $banner['id'] ?>" 
                                               class="btn btn-sm btn-warning" 
                                               title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger btn-eliminar" 
                                                    data-banner-id="<?= $banner['id'] ?>"
                                                    data-banner-titulo="<?= htmlspecialchars($banner['titulo'] ?? 'este banner') ?>"
                                                    title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div>
</div>
<!-- Fin Content Area -->
</div>
<!-- Fin Main Content -->

<script>
        // Toggle estado de banner
        document.querySelectorAll('.toggle-estado').forEach(toggle => {
            toggle.addEventListener('change', async function() {
                const bannerId = this.dataset.bannerId;
                const habilitado = this.checked ? 1 : 0;
                const label = this.nextElementSibling;

                try {
                    const response = await fetch('cambiar_estado_banner.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ banner_id: bannerId, habilitado: habilitado })
                    });

                    const data = await response.json();

                    if (data.success) {
                        label.textContent = habilitado ? 'Activo' : 'Inactivo';
                        
                        // Mostrar alerta temporal
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
                        alert.style.zIndex = '9999';
                        alert.innerHTML = '<i class="bi bi-check-circle"></i> Estado actualizado correctamente';
                        document.body.appendChild(alert);
                        
                        setTimeout(() => alert.remove(), 2000);
                    } else {
                        this.checked = !this.checked;
                        alert('Error al cambiar el estado: ' + (data.error || 'Error desconocido'));
                    }
                } catch (error) {
                    this.checked = !this.checked;
                    alert('Error de conexión al cambiar el estado');
                }
            });
        });

        // Cambiar orden de banner
        document.querySelectorAll('.btn-orden-up, .btn-orden-down').forEach(btn => {
            btn.addEventListener('click', async function() {
                const bannerId = this.dataset.bannerId;
                const direccion = this.classList.contains('btn-orden-up') ? 'up' : 'down';

                try {
                    const response = await fetch('cambiar_orden_banner.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ banner_id: bannerId, direccion: direccion })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Recargar la página para mostrar el nuevo orden
                        location.reload();
                    } else {
                        alert('Error al cambiar el orden: ' + (data.error || 'Error desconocido'));
                    }
                } catch (error) {
                    alert('Error de conexión al cambiar el orden');
                }
            });
        });

        // Sistema de confirmación personalizado
        document.addEventListener('DOMContentLoaded', function() {
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            let pendingAction = null;

            function showConfirmModal(message, icon, iconColor, callback) {
                document.getElementById('confirmModalMessage').innerHTML = message;
                document.getElementById('confirmModalIcon').innerHTML = `<i class="${icon}" style="color: ${iconColor};"></i>`;
                
                pendingAction = callback;
                confirmModal.show();
            }

            document.getElementById('confirmModalButton').addEventListener('click', function() {
                if (pendingAction) {
                    pendingAction();
                    pendingAction = null;
                }
                confirmModal.hide();
            });

            // Eliminar banner
            document.querySelectorAll('.btn-eliminar').forEach(btn => {
                btn.addEventListener('click', function() {
                    const bannerId = this.dataset.bannerId;
                    const bannerTitulo = this.dataset.bannerTitulo;

                    showConfirmModal(
                        `<strong>¿Está seguro de eliminar el banner "${bannerTitulo}"?</strong><br><br>` +
                        `<div style="text-align: left; display: inline-block;">` +
                        `Esta acción eliminará:<br>` +
                        `• El banner de la base de datos<br>` +
                        `• Su imagen del servidor<br>` +
                        `• Dejará de mostrarse en el sitio web<br><br>` +
                        `<strong style="color: #dc3545;">⚠️ Esta acción NO se puede deshacer.</strong>` +
                        `</div>`,
                        'bi bi-images',
                        '#dc3545',
                        () => {
                            window.location.href = `eliminar_banner.php?id=${bannerId}`;
                        }
                    );
                });
            });
        });
    </script>

<!-- Modal de confirmación personalizado -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0, 123, 255, 0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); border-radius: 15px 15px 0 0; border: none;">
                <h5 class="modal-title text-white" id="confirmModalTitle">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmar Acción
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div id="confirmModalIcon" class="mb-3" style="font-size: 3rem;"></div>
                <p id="confirmModalMessage" class="mb-0" style="font-size: 1.1rem; color: #495057;"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 10px;">
                    <i class="bi bi-x-lg me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary px-4" id="confirmModalButton" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); border: none; border-radius: 10px;">
                    <i class="bi bi-check-lg me-2"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>
