<?php
require_once '../../includes/init.php';
require_once '../../includes/func_imagenes.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /tercer_cielo/public/login.php');
    exit;
}

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /tercer_cielo/public/index.php');
    exit;
}

$pdo = getPdo();

// === ACCIONES: INHABILITAR / HABILITAR ===
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'inhabilitar') {
        // Inhabilitar la categoría
        $stmt = $pdo->prepare("UPDATE categorias SET habilitado = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        // Inhabilitar todos los productos de esta categoría
        $stmt = $pdo->prepare("UPDATE productos SET habilitado = 0 WHERE id_categoria = ?");
        $stmt->execute([$id]);
        
        // Inhabilitar todos los banners enlazados a esta categoría
        $stmt = $pdo->prepare("UPDATE banners SET habilitado = 0 WHERE tipo_enlace = 'categoria' AND enlace_id = ?");
        $stmt->execute([$id]);
        
        // Inhabilitar banners enlazados a productos de esta categoría
        $stmt = $pdo->prepare("UPDATE banners SET habilitado = 0 WHERE tipo_enlace = 'producto' AND enlace_id IN (SELECT id FROM productos WHERE id_categoria = ?)");
        $stmt->execute([$id]);
        
        header('Location: categorias.php?success=inhabilitado');
        exit;
    } elseif ($_GET['action'] === 'habilitar') {
        // Habilitar la categoría
        $stmt = $pdo->prepare("UPDATE categorias SET habilitado = 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        // Habilitar todos los productos de esta categoría
        $stmt = $pdo->prepare("UPDATE productos SET habilitado = 1 WHERE id_categoria = ?");
        $stmt->execute([$id]);
        
        // Habilitar todos los banners enlazados a esta categoría
        $stmt = $pdo->prepare("UPDATE banners SET habilitado = 1 WHERE tipo_enlace = 'categoria' AND enlace_id = ?");
        $stmt->execute([$id]);
        
        // Habilitar banners enlazados a productos de esta categoría
        $stmt = $pdo->prepare("UPDATE banners SET habilitado = 1 WHERE tipo_enlace = 'producto' AND enlace_id IN (SELECT id FROM productos WHERE id_categoria = ?)");
        $stmt->execute([$id]);
        
        header('Location: categorias.php?success=habilitado');
        exit;
    }
}

// === AGREGAR CATEGORÍA ===
if (isset($_POST['action']) && $_POST['action'] === 'add' && isset($_POST['nombre'])) {
    $nombre = trim($_POST['nombre']);
    $imagen = null;
    
    // Manejo de la imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $resultado = guardarImagen($_FILES['imagen'], 'categorias', 500, 500);
        if ($resultado['success']) {
            $imagen = $resultado['path'];
        } else {
            $error = $resultado['message'];
        }
    }

    if (!isset($error)) {
        $stmt = $pdo->prepare("INSERT INTO categorias (nombre, imagen) VALUES (?, ?)");
        if ($stmt->execute([$nombre, $imagen])) {
            $success = "Categoría agregada.";
        } else {
            $error = "Error al agregar.";
        }
    }
}

// === EDITAR CATEGORÍA ===
if (isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $nombre = trim($_POST['nombre']);
    
    // Obtener imagen actual
    $stmt = $pdo->prepare("SELECT imagen FROM categorias WHERE id = ?");
    $stmt->execute([$id]);
    $categoriaActual = $stmt->fetch();
    $imagen = $categoriaActual['imagen'];
    
    // Manejo de la imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $resultado = guardarImagen($_FILES['imagen'], 'categorias', 500, 500);
        if ($resultado['success']) {
            // Eliminar imagen anterior si existe
            if ($imagen) {
                eliminarImagen($imagen);
            }
            $imagen = $resultado['path'];
        } else {
            $error = $resultado['message'];
        }
    }

    if (!isset($error)) {
        $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, imagen = ? WHERE id = ?");
        if ($stmt->execute([$nombre, $imagen, $id])) {
            $success = "Categoría actualizada.";
        } else {
            $error = "Error al actualizar.";
        }
    }
}

// === ELIMINAR CATEGORÍA ===
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    // Obtener imagen para eliminarla del servidor
    $stmt = $pdo->prepare("SELECT imagen FROM categorias WHERE id = ?");
    $stmt->execute([$id]);
    $categoria = $stmt->fetch();
    
    $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
    if ($stmt->execute([$id])) {
        // Eliminar la imagen del servidor
        if ($categoria && $categoria['imagen']) {
            eliminarImagen($categoria['imagen']);
        }
        $success = "Categoría eliminada.";
    } else {
        $error = "Error al eliminar.";
    }
}

// === OBTENER CATEGORÍAS ===
$search = $_GET['search'] ?? '';
$orderby = $_GET['orderby'] ?? '';
$where = '';
$params = [];

if ($search) {
    $where = "WHERE id = ? OR nombre LIKE ?";
    
    // Verificar si la búsqueda es numérica (para ID)
    if (is_numeric($search)) {
        $params = [$search, "%$search%"];
    } else {
        $params = [0, "%$search%"];
    }
}

// Determinar ordenamiento
$order = 'nombre ASC';
switch($orderby) {
    case 'nombre_asc': $order = 'nombre ASC'; break;
    case 'nombre_desc': $order = 'nombre DESC'; break;
    case 'id_asc': $order = 'id ASC'; break;
    case 'id_desc': $order = 'id DESC'; break;
}

$sql = "SELECT * FROM categorias $where ORDER BY $order";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categorias = $stmt->fetchAll();

// Verificar si cada categoría tiene productos asociados
foreach ($categorias as &$categoria) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE id_categoria = ?");
    $stmt->execute([$categoria['id']]);
    $categoria['tiene_productos'] = $stmt->fetchColumn() > 0;
}
unset($categoria); // Romper referencia

$pageTitle = 'Gestión de Categorías';
include 'layout_header.php';
?>

<div class="content-card">
    <h3 class="mb-4"><i class="bi bi-tags-fill me-2"></i>Categorías de Productos</h3>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                if ($_GET['success'] == 'inhabilitado') echo '<i class="bi bi-check-circle me-2"></i>Categoría inhabilitada correctamente. No se mostrará a los clientes.';
                if ($_GET['success'] == 'habilitado') echo '<i class="bi bi-check-circle me-2"></i>Categoría habilitada correctamente. Ahora es visible para los clientes.';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Barra de búsqueda -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Buscar por ID o nombre..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                    <?php if ($search): ?>
                        <a href="categorias.php" class="btn btn-outline-secondary ms-2">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <select class="form-select d-inline-block w-auto me-2" onchange="window.location.href='?orderby=' + this.value + '<?= $search ? '&search=' . urlencode($search) : '' ?>'">
                    <option value="">Ordenar por...</option>
                    <option value="nombre_asc" <?= ($_GET['orderby'] ?? '') == 'nombre_asc' ? 'selected' : '' ?>>Nombre (A-Z)</option>
                    <option value="nombre_desc" <?= ($_GET['orderby'] ?? '') == 'nombre_desc' ? 'selected' : '' ?>>Nombre (Z-A)</option>
                    <option value="id_asc" <?= ($_GET['orderby'] ?? '') == 'id_asc' ? 'selected' : '' ?>>ID (Menor a Mayor)</option>
                    <option value="id_desc" <?= ($_GET['orderby'] ?? '') == 'id_desc' ? 'selected' : '' ?>>ID (Mayor a Menor)</option>
                </select>
                <!-- BOTÓN AGREGAR -->
                <a href="#modalAgregar" class="btn btn-primary" data-bs-toggle="modal"><i class="bi bi-plus-circle me-1"></i>Agregar Categoría</a>
            </div>
        </div>

        <!-- TABLA -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Imagen</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $c): ?>
                        <tr style="<?= $c['habilitado'] == 0 ? 'opacity: 0.5; background-color: #f8f9fa;' : '' ?>">
                            <td><?= $c['id'] ?></td>
                            <td>
                                <?= htmlspecialchars($c['nombre']) ?>
                                <?php if ($c['habilitado'] == 0): ?>
                                    <span class="badge bg-secondary ms-1">Inhabilitado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['imagen']): ?>
                                    <img src="<?= htmlspecialchars($c['imagen']) ?>" alt="Imagen categoría" style="max-width: 80px; max-height: 80px; border-radius: 4px;">
                                <?php else: ?>
                                    Sin imagen
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['habilitado'] == 1): ?>
                                    <span class="badge bg-success"><i class="bi bi-eye"></i> Visible</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="bi bi-eye-slash"></i> Oculto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editarCategoria(<?= $c['id'] ?>)" title="Editar">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                                
                                <?php if ($c['habilitado'] == 1): ?>
                                    <!-- Botón Inhabilitar -->
                                    <button class="btn btn-sm btn-warning btn-inhabilitar-categoria" 
                                       data-id="<?= $c['id'] ?>"
                                       data-nombre="<?= htmlspecialchars($c['nombre']) ?>"
                                       title="Inhabilitar (ocultar del público)">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                <?php else: ?>
                                    <!-- Botón Habilitar -->
                                    <button class="btn btn-sm btn-success btn-habilitar-categoria" 
                                       data-id="<?= $c['id'] ?>"
                                       data-nombre="<?= htmlspecialchars($c['nombre']) ?>"
                                       title="Habilitar (mostrar al público)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (!$c['tiene_productos']): ?>
                                    <!-- Solo mostrar eliminar si NO tiene productos -->
                                    <button class="btn btn-sm btn-danger btn-eliminar-categoria-final" 
                                       data-id="<?= $c['id'] ?>"
                                       data-nombre="<?= htmlspecialchars($c['nombre']) ?>"
                                       title="Eliminar permanentemente">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <!-- Tooltip explicativo -->
                                    <button class="btn btn-sm btn-secondary" 
                                       disabled
                                       title="No se puede eliminar: tiene productos asociados"
                                       data-bs-toggle="tooltip">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL AGREGAR -->
    <div class="modal fade" id="modalAgregar">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="formAgregar">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imagen</label>
                            <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" id="imagenAgregar">
                            <small class="form-text text-muted">Formatos aceptados: JPG, PNG, GIF, WEBP. Máximo 5MB.</small>
                            <div id="vistaPrevia" class="mt-3" style="display: none;">
                                <img id="imagenPrevia" src="" alt="Vista previa" style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Agregar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div class="modal fade" id="modalEditar">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditar" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" id="edit_nombre" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imagen Actual</label>
                            <div id="imagenActualContainer"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva Imagen (opcional)</label>
                            <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" id="imagenEditar">
                            <small class="form-text text-muted">Deja en blanco para mantener la imagen actual. Formatos aceptados: JPG, PNG, GIF, WEBP. Máximo 5MB.</small>
                            <div id="vistaPreviaEditar" class="mt-3" style="display: none;">
                                <img id="imagenPreviaEditar" src="" alt="Vista previa" style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let categorias = <?= json_encode($categorias) ?>;

        // Vista previa imagen al agregar
        document.getElementById('imagenAgregar')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagenPrevia').src = e.target.result;
                    document.getElementById('vistaPrevia').style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                document.getElementById('vistaPrevia').style.display = 'none';
            }
        });

        // Vista previa imagen al editar
        document.getElementById('imagenEditar')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagenPreviaEditar').src = e.target.result;
                    document.getElementById('vistaPreviaEditar').style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                document.getElementById('vistaPreviaEditar').style.display = 'none';
            }
        });

        function editarCategoria(id) {
            const cat = categorias.find(c => c.id == id);
            if (cat) {
                document.getElementById('edit_id').value = cat.id;
                document.getElementById('edit_nombre').value = cat.nombre;
                
                // Mostrar imagen actual
                const container = document.getElementById('imagenActualContainer');
                if (cat.imagen) {
                    container.innerHTML = `<img src="${cat.imagen}" alt="Imagen actual" style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">`;
                } else {
                    container.innerHTML = '<small class="text-muted">Sin imagen</small>';
                }
                
                // Limpiar vista previa
                document.getElementById('vistaPreviaEditar').style.display = 'none';
                document.getElementById('imagenEditar').value = '';
                
                new bootstrap.Modal(document.getElementById('modalEditar')).show();
            }

            document.getElementById('formEditar').onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch('categorias.php', { method: 'POST', body: formData })
                    .then(r => r.text())
                    .then(() => location.reload());
            };
        }

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

            // Botones de INHABILITAR categoría
            document.querySelectorAll('.btn-inhabilitar-categoria').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const nombre = this.getAttribute('data-nombre');
                    showConfirmModal(
                        `<strong>¿Inhabilitar la categoría "${nombre}"?</strong><br><br>` +
                        `<div style="text-align: left; display: inline-block;">` +
                        `Al inhabilitar esta categoría:<br>` +
                        `• La categoría NO aparecerá en el catálogo público<br>` +
                        `• <strong>Todos los productos</strong> de esta categoría se inhabilitarán automáticamente<br>` +
                        `• <strong>Todos los banners</strong> enlazados se inhabilitarán automáticamente<br>` +
                        `• Se mantiene en la base de datos<br>` +
                        `• Puedes habilitarla nuevamente cuando quieras<br><br>` +
                        `<strong style="color: #856404;">⚠️ Todo se desactivará en cascada inmediatamente.</strong>` +
                        `</div>`,
                        'bi bi-tags-fill',
                        '#ffc107',
                        () => window.location.href = `categorias.php?action=inhabilitar&id=${id}`
                    );
                });
            });

            // Botones de HABILITAR categoría
            document.querySelectorAll('.btn-habilitar-categoria').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const nombre = this.getAttribute('data-nombre');
                    showConfirmModal(
                        `<strong>¿Habilitar la categoría "${nombre}"?</strong><br><br>` +
                        `<div style="text-align: left; display: inline-block;">` +
                        `Al habilitar esta categoría:<br>` +
                        `• La categoría aparecerá en el catálogo público<br>` +
                        `• <strong>Todos los productos</strong> de esta categoría se habilitarán automáticamente<br>` +
                        `• <strong>Todos los banners</strong> enlazados se habilitarán automáticamente<br>` +
                        `• Estará disponible en filtros<br><br>` +
                        `<strong style="color: #28a745;">✓ Todo se activará en cascada inmediatamente.</strong>` +
                        `</div>`,
                        'bi bi-tags-fill',
                        '#28a745',
                        () => window.location.href = `categorias.php?action=habilitar&id=${id}`
                    );
                });
            });

            // Botones de ELIMINAR categoría (solo si no tiene productos)
            document.querySelectorAll('.btn-eliminar-categoria-final').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const nombre = this.getAttribute('data-nombre');
                    showConfirmModal(
                        `<strong>¿Está seguro de ELIMINAR PERMANENTEMENTE la categoría "${nombre}"?</strong><br><br>` +
                        `<div style="text-align: left; display: inline-block;">` +
                        `Esta acción eliminará:<br>` +
                        `• La categoría de la base de datos<br>` +
                        `• Su imagen del servidor<br>` +
                        `• No se puede recuperar después<br><br>` +
                        `<strong style="color: #dc3545;">⚠️ ESTA ACCIÓN NO SE PUEDE DESHACER.</strong><br><br>` +
                        `<span style="color: #856404;">💡 Consejo: Si solo quieres ocultarla temporalmente, usa "Inhabilitar" en lugar de eliminar.</span>` +
                        `</div>`,
                        'bi bi-trash-fill',
                        '#dc3545',
                        () => {
                            const formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('id', id);
                            fetch('categorias.php', { method: 'POST', body: formData })
                                .then(() => location.reload());
                        }
                    );
                });
            });

            window.eliminarCategoria = function(id) {
                const cat = categorias.find(c => c.id == id);
                const nombre = cat ? cat.nombre : 'esta categoría';
                
                showConfirmModal(
                    `<strong>¿Está seguro de eliminar la categoría "${nombre}"?</strong><br><br>` +
                    `<div style="text-align: left; display: inline-block;">` +
                    `Esta acción eliminará:<br>` +
                    `• La categoría de la base de datos<br>` +
                    `• Su imagen del servidor<br>` +
                    `• La referencia en todos los productos asociados<br><br>` +
                    `<strong style="color: #dc3545;">⚠️ Esta acción NO se puede deshacer.</strong>` +
                    `</div>`,
                    'bi bi-tags-fill',
                    '#dc3545',
                    () => {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('id', id);
                        fetch('categorias.php', { method: 'POST', body: formData })
                            .then(() => location.reload());
                    }
                );
            };

            document.getElementById('modalAgregar').addEventListener('shown.bs.modal', function() {
                // Limpiar vista previa al abrir modal
                document.getElementById('vistaPrevia').style.display = 'none';
                document.getElementById('imagenAgregar').value = '';
                
                document.getElementById('formAgregar').onsubmit = function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    fetch('categorias.php', { method: 'POST', body: formData })
                        .then(r => r.text())
                        .then(() => location.reload());
                };
            });

            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
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
</div>

<?php include 'layout_footer.php'; ?>