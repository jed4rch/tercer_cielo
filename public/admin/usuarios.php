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

$pdo = getPdo();

// Procesar búsqueda
$search = $_GET['search'] ?? '';
$orderby = $_GET['orderby'] ?? '';
$where = '';
$params = [];

if ($search) {
    $where = "WHERE id = ? OR nombre LIKE ? OR email LIKE ? OR telefono LIKE ? OR rol LIKE ?";
    
    // Verificar si la búsqueda es numérica (para ID)
    if (is_numeric($search)) {
        $params = [$search, "%$search%", "%$search%", "%$search%", "%$search%"];
    } else {
        $params = [0, "%$search%", "%$search%", "%$search%", "%$search%"];
    }
}

// Determinar ordenamiento
$order = 'created_at DESC';
switch($orderby) {
    case 'nombre_asc': $order = 'nombre ASC'; break;
    case 'nombre_desc': $order = 'nombre DESC'; break;
    case 'email_asc': $order = 'email ASC'; break;
    case 'email_desc': $order = 'email DESC'; break;
    case 'rol_asc': $order = 'rol ASC'; break;
    case 'rol_desc': $order = 'rol DESC'; break;
    case 'fecha_asc': $order = 'created_at ASC'; break;
    case 'fecha_desc': $order = 'created_at DESC'; break;
}

$sql = "SELECT * FROM usuarios $where ORDER BY $order";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

$pageTitle = 'Gestión de Usuarios';
include 'layout_header.php';
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-people-fill me-2"></i>Usuarios Registrados</h3>
        <a href="agregar_admin.php" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-2"></i>Agregar Administrador
        </a>
    </div>

        <!-- Barra de búsqueda -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Buscar por ID, nombre, email, teléfono o rol..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                    <?php if ($search): ?>
                        <a href="usuarios.php" class="btn btn-outline-secondary ms-2">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <select class="form-select d-inline-block w-auto" onchange="window.location.href='?orderby=' + this.value + '<?= $search ? '&search=' . urlencode($search) : '' ?>'">
                    <option value="">Ordenar por...</option>
                    <option value="nombre_asc" <?= ($_GET['orderby'] ?? '') == 'nombre_asc' ? 'selected' : '' ?>>Nombre (A-Z)</option>
                    <option value="nombre_desc" <?= ($_GET['orderby'] ?? '') == 'nombre_desc' ? 'selected' : '' ?>>Nombre (Z-A)</option>
                    <option value="email_asc" <?= ($_GET['orderby'] ?? '') == 'email_asc' ? 'selected' : '' ?>>Email (A-Z)</option>
                    <option value="email_desc" <?= ($_GET['orderby'] ?? '') == 'email_desc' ? 'selected' : '' ?>>Email (Z-A)</option>
                    <option value="rol_asc" <?= ($_GET['orderby'] ?? '') == 'rol_asc' ? 'selected' : '' ?>>Rol (A-Z)</option>
                    <option value="rol_desc" <?= ($_GET['orderby'] ?? '') == 'rol_desc' ? 'selected' : '' ?>>Rol (Z-A)</option>
                    <option value="fecha_asc" <?= ($_GET['orderby'] ?? '') == 'fecha_asc' ? 'selected' : '' ?>>Fecha (Antigua a Reciente)</option>
                    <option value="fecha_desc" <?= ($_GET['orderby'] ?? '') == 'fecha_desc' ? 'selected' : '' ?>>Fecha (Reciente a Antigua)</option>
                </select>
            </div>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php if ($_GET['error'] === 'ultimo_admin'): ?>
                    <i class="bi bi-exclamation-triangle-fill"></i> No se puede eliminar el último administrador del sistema
                <?php elseif ($_GET['error'] === 'autoeliminar'): ?>
                    <i class="bi bi-exclamation-triangle-fill"></i> No puedes eliminarte a ti mismo
                <?php elseif ($_GET['error'] === 'automodificar'): ?>
                    <i class="bi bi-exclamation-triangle-fill"></i> No puedes modificar tu propio estado de cuenta
                <?php elseif ($_GET['error'] === 'sesion_activa'): ?>
                    <i class="bi bi-exclamation-triangle-fill"></i> No se puede eliminar un usuario administrador con sesión activa. Debe cerrar sesión primero.
                <?php elseif ($_GET['error'] === 'sesion_activa_inhabilitar'): ?>
                    <i class="bi bi-exclamation-triangle-fill"></i> No se puede inhabilitar un usuario administrador con sesión activa. Debe cerrar sesión primero.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <?php if ($_GET['success'] === 'eliminado'): ?>
                    Usuario eliminado exitosamente
                <?php elseif ($_GET['success'] === 'editado'): ?>
                    Usuario actualizado exitosamente
                <?php elseif ($_GET['success'] === 'inactivado'): ?>
                    Usuario inactivado exitosamente (tenía pedidos asociados)
                <?php elseif ($_GET['success'] === 'inhabilitado'): ?>
                    Usuario inhabilitado exitosamente
                <?php elseif ($_GET['success'] === 'habilitado'): ?>
                    Usuario habilitado exitosamente
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['nombre']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['telefono']) ?></td>
                            <td><span class="badge bg-<?= $u['rol'] == 'admin' ? 'danger' : 'secondary' ?>"><?= ucfirst($u['rol']) ?></span></td>
                            <td>
                                <span class="badge bg-<?= ($u['activo'] ?? 1) ? 'success' : 'warning' ?>">
                                    <?= ($u['activo'] ?? 1) ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php 
                                $tiene_sesion_activa = ($u['rol'] === 'admin' && !empty($u['session_id']));
                                $es_usuario_actual = ($u['id'] === $_SESSION['user_id']);
                                ?>
                                
                                <?php if ($u['rol'] === 'admin'): ?>
                                    <!-- Botones para admin -->
                                    <!-- Botón Editar - siempre disponible -->
                                    <a href="editar_usuario.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    
                                    <?php if (($u['activo'] ?? 1) == 1): ?>
                                        <a href="#" 
                                           data-url="inhabilitar_usuario.php?id=<?= $u['id'] ?>" 
                                           class="btn btn-sm btn-warning btn-inhabilitar <?= ($tiene_sesion_activa || $es_usuario_actual) ? 'disabled' : '' ?>" 
                                           title="<?= $tiene_sesion_activa ? 'Sesión activa - No disponible' : ($es_usuario_actual ? 'No puedes modificar tu propia cuenta' : 'Inhabilitar') ?>">
                                            <i class="bi bi-x-circle"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="#" 
                                           data-url="habilitar_usuario.php?id=<?= $u['id'] ?>" 
                                           class="btn btn-sm btn-success btn-habilitar <?= $es_usuario_actual ? 'disabled' : '' ?>" 
                                           title="<?= $es_usuario_actual ? 'No puedes modificar tu propia cuenta' : 'Habilitar' ?>">
                                            <i class="bi bi-check-circle"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="#" 
                                       data-url="eliminar_usuario.php?id=<?= $u['id'] ?>" 
                                       class="btn btn-sm btn-danger btn-eliminar <?= ($tiene_sesion_activa || $es_usuario_actual) ? 'disabled' : '' ?>" 
                                       title="<?= $tiene_sesion_activa ? 'Sesión activa - No disponible' : ($es_usuario_actual ? 'No puedes eliminarte a ti mismo' : 'Eliminar') ?>">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <!-- Botones para cliente - solo habilitar/inhabilitar -->
                                    <?php if (($u['activo'] ?? 1) == 1): ?>
                                        <a href="#" 
                                           data-url="inhabilitar_usuario.php?id=<?= $u['id'] ?>" 
                                           class="btn btn-sm btn-warning btn-inhabilitar" 
                                           title="Inhabilitar">
                                            <i class="bi bi-x-circle"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="#" 
                                           data-url="habilitar_usuario.php?id=<?= $u['id'] ?>" 
                                           class="btn btn-sm btn-success btn-habilitar" 
                                           title="Habilitar">
                                            <i class="bi bi-check-circle"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
</div>

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

<script>
    // Sistema de confirmación personalizado
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    let pendingAction = null;

    function showConfirmModal(message, icon, iconColor, callback) {
        document.getElementById('confirmModalMessage').textContent = message;
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

    // Sobrescribir los onclick de los botones
    document.addEventListener('DOMContentLoaded', function() {
        // Botones de inhabilitar
        document.querySelectorAll('.btn-inhabilitar').forEach(btn => {
            if (!btn.classList.contains('disabled')) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('data-url');
                    showConfirmModal(
                        '¿Está seguro de inhabilitar esta cuenta?',
                        'bi bi-x-circle-fill',
                        '#ffc107',
                        () => window.location.href = url
                    );
                });
            }
        });

        // Botones de habilitar
        document.querySelectorAll('.btn-habilitar').forEach(btn => {
            if (!btn.classList.contains('disabled')) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('data-url');
                    showConfirmModal(
                        '¿Está seguro de habilitar esta cuenta?',
                        'bi bi-check-circle-fill',
                        '#28a745',
                        () => window.location.href = url
                    );
                });
            }
        });

        // Botones de eliminar
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            if (!btn.classList.contains('disabled')) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('data-url');
                    showConfirmModal(
                        '¿Está seguro de eliminar este usuario? Esta acción no se puede deshacer.',
                        'bi bi-trash-fill',
                        '#dc3545',
                        () => window.location.href = url
                    );
                });
            }
        });
    });
</script>