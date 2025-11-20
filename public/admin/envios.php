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

// === ACCIONES: INHABILITAR / HABILITAR ===
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'inhabilitar') {
        $stmt = $pdo->prepare("UPDATE envios SET habilitado = 0 WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: envios.php?success=inhabilitado');
        exit;
    } elseif ($_GET['action'] === 'habilitar') {
        $stmt = $pdo->prepare("UPDATE envios SET habilitado = 1 WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: envios.php?success=habilitado');
        exit;
    }
}

// === AGREGAR ENVÍO ===
if (isset($_POST['action']) && $_POST['action'] === 'add' && isset($_POST['departamento'])) {
    $departamento = trim($_POST['departamento']);
    $provincia = trim($_POST['provincia']);
    $distrito = trim($_POST['distrito']);
    $precio_domicilio_olva = (float)($_POST['precio_domicilio_olva'] ?? 0);
    $precio_agencia_olva = (float)($_POST['precio_agencia_olva'] ?? 0);
    $precio_domicilio_shalom = (float)($_POST['precio_domicilio_shalom'] ?? 0);
    $precio_agencia_shalom = (float)($_POST['precio_agencia_shalom'] ?? 0);

    $stmt = $pdo->prepare("INSERT INTO envios (departamento, provincia, distrito, precio_domicilio_olva, precio_agencia_olva, precio_domicilio_shalom, precio_agencia_shalom) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$departamento, $provincia, $distrito, $precio_domicilio_olva, $precio_agencia_olva, $precio_domicilio_shalom, $precio_agencia_shalom])) {
        $success = "Envío agregado.";
    } else {
        $error = "Error al agregar.";
    }
}

// === EDITAR ENVÍO ===
if (isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $departamento = trim($_POST['departamento']);
    $provincia = trim($_POST['provincia']);
    $distrito = trim($_POST['distrito']);
    $precio_domicilio_olva = (float)($_POST['precio_domicilio_olva'] ?? 0);
    $precio_agencia_olva = (float)($_POST['precio_agencia_olva'] ?? 0);
    $precio_domicilio_shalom = (float)($_POST['precio_domicilio_shalom'] ?? 0);
    $precio_agencia_shalom = (float)($_POST['precio_agencia_shalom'] ?? 0);

    $stmt = $pdo->prepare("UPDATE envios SET departamento = ?, provincia = ?, distrito = ?, precio_domicilio_olva = ?, precio_agencia_olva = ?, precio_domicilio_shalom = ?, precio_agencia_shalom = ? WHERE id = ?");
    if ($stmt->execute([$departamento, $provincia, $distrito, $precio_domicilio_olva, $precio_agencia_olva, $precio_domicilio_shalom, $precio_agencia_shalom, $id])) {
        $success = "Envío actualizado.";
    } else {
        $error = "Error al actualizar.";
    }
}

// === ELIMINAR ENVÍO ===
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM envios WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Envío eliminado.";
    } else {
        $error = "Error al eliminar.";
    }
}

// === OBTENER ENVÍOS ===
$search = $_GET['search'] ?? '';
$orderby = $_GET['orderby'] ?? '';
$where = '';
$params = [];

if ($search) {
    $where = "WHERE id = ? OR departamento LIKE ? OR provincia LIKE ? OR distrito LIKE ?";
    
    // Verificar si la búsqueda es numérica (para ID)
    if (is_numeric($search)) {
        $params = [$search, "%$search%", "%$search%", "%$search%"];
    } else {
        $params = [0, "%$search%", "%$search%", "%$search%"];
    }
}

// Determinar ordenamiento
$order = 'departamento ASC, provincia ASC, distrito ASC';
switch($orderby) {
    case 'departamento_asc': $order = 'departamento ASC'; break;
    case 'departamento_desc': $order = 'departamento DESC'; break;
    case 'precio_asc': $order = 'precio_envio ASC'; break;
    case 'precio_desc': $order = 'precio_envio DESC'; break;
}

$sql = "SELECT * FROM envios $where ORDER BY $order";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$envios = $stmt->fetchAll();

// Verificar si cada envío tiene pedidos asociados
foreach ($envios as &$envio) {
    // Verificar si algún pedido tiene esta dirección de envío (búsqueda aproximada)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE direccion_envio LIKE ?");
    $stmt->execute(['%' . $envio['distrito'] . '%']);
    $envio['tiene_pedidos'] = $stmt->fetchColumn() > 0;
}
unset($envio); // Romper referencia

$pageTitle = 'Gestión de Envíos';
include 'layout_header.php';
?>

<div class="content-card">
    <h3 class="mb-4"><i class="bi bi-truck me-2"></i>Configuración de Envíos</h3>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                if ($_GET['success'] == 'inhabilitado') echo '<i class="bi bi-check-circle me-2"></i>Envío inhabilitado correctamente. No aparecerá como opción para nuevos pedidos.';
                if ($_GET['success'] == 'habilitado') echo '<i class="bi bi-check-circle me-2"></i>Envío habilitado correctamente. Ahora está disponible para nuevos pedidos.';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Barra de búsqueda -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Buscar por ID, departamento, provincia o distrito..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                    <?php if ($search): ?>
                        <a href="envios.php" class="btn btn-outline-secondary ms-2">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <select class="form-select d-inline-block w-auto me-2" onchange="window.location.href='?orderby=' + this.value + '<?= $search ? '&search=' . urlencode($search) : '' ?>'">
                    <option value="">Ordenar por...</option>
                    <option value="departamento_asc" <?= ($_GET['orderby'] ?? '') == 'departamento_asc' ? 'selected' : '' ?>>Departamento (A-Z)</option>
                    <option value="departamento_desc" <?= ($_GET['orderby'] ?? '') == 'departamento_desc' ? 'selected' : '' ?>>Departamento (Z-A)</option>
                    <option value="precio_asc" <?= ($_GET['orderby'] ?? '') == 'precio_asc' ? 'selected' : '' ?>>Precio (Menor a Mayor)</option>
                    <option value="precio_desc" <?= ($_GET['orderby'] ?? '') == 'precio_desc' ? 'selected' : '' ?>>Precio (Mayor a Menor)</option>
                </select>
                <!-- BOTÓN AGREGAR -->
                <a href="#modalAgregar" class="btn btn-primary" data-bs-toggle="modal"><i class="bi bi-truck me-1"></i>Agregar Envío</a>
            </div>
        </div>

        <!-- TABLA -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Departamento</th>
                        <th>Provincia</th>
                        <th>Distrito</th>
                        <th>Domicilio Olva</th>
                        <th>Agencia Olva</th>
                        <th>Domicilio Shalom</th>
                        <th>Domicilio Shalom</th>
                        <th>Agencia Shalom</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($envios as $e): ?>
                        <tr style="<?= $e['habilitado'] == 0 ? 'opacity: 0.5; background-color: #f8f9fa;' : '' ?>">
                            <td><?= $e['id'] ?></td>
                            <td><?= htmlspecialchars($e['departamento']) ?></td>
                            <td><?= htmlspecialchars($e['provincia']) ?></td>
                            <td>
                                <?= htmlspecialchars($e['distrito']) ?>
                                <?php if ($e['habilitado'] == 0): ?>
                                    <span class="badge bg-secondary ms-1">Inhabilitado</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $e['precio_domicilio_olva'] > 0 ? 'S/ ' . number_format($e['precio_domicilio_olva'], 2) : '<span class="text-muted">No disponible</span>' ?></td>
                            <td><?= $e['precio_agencia_olva'] > 0 ? 'S/ ' . number_format($e['precio_agencia_olva'], 2) : '<span class="text-muted">No disponible</span>' ?></td>
                            <td><?= $e['precio_domicilio_shalom'] > 0 ? 'S/ ' . number_format($e['precio_domicilio_shalom'], 2) : '<span class="text-muted">No disponible</span>' ?></td>
                            <td><?= $e['precio_agencia_shalom'] > 0 ? 'S/ ' . number_format($e['precio_agencia_shalom'], 2) : '<span class="text-muted">No disponible</span>' ?></td>
                            <td>
                                <?php if ($e['habilitado'] == 1): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editarEnvio(<?= $e['id'] ?>)" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                
                                <?php if ($e['habilitado'] == 1): ?>
                                    <!-- Botón Inhabilitar -->
                                    <button class="btn btn-sm btn-warning btn-inhabilitar-envio" 
                                       data-id="<?= $e['id'] ?>"
                                       data-distrito="<?= htmlspecialchars($e['distrito']) ?>"
                                       data-provincia="<?= htmlspecialchars($e['provincia']) ?>"
                                       data-departamento="<?= htmlspecialchars($e['departamento']) ?>"
                                       title="Inhabilitar (no disponible para nuevos pedidos)">
                                        <i class="bi bi-pause-circle"></i>
                                    </button>
                                <?php else: ?>
                                    <!-- Botón Habilitar -->
                                    <button class="btn btn-sm btn-success btn-habilitar-envio" 
                                       data-id="<?= $e['id'] ?>"
                                       data-distrito="<?= htmlspecialchars($e['distrito']) ?>"
                                       data-provincia="<?= htmlspecialchars($e['provincia']) ?>"
                                       data-departamento="<?= htmlspecialchars($e['departamento']) ?>"
                                       title="Habilitar (disponible para nuevos pedidos)">
                                        <i class="bi bi-play-circle"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (!$e['tiene_pedidos']): ?>
                                    <!-- Solo mostrar eliminar si NO tiene pedidos -->
                                    <button class="btn btn-sm btn-danger btn-eliminar-envio-final" 
                                       data-id="<?= $e['id'] ?>"
                                       data-distrito="<?= htmlspecialchars($e['distrito']) ?>"
                                       data-provincia="<?= htmlspecialchars($e['provincia']) ?>"
                                       data-departamento="<?= htmlspecialchars($e['departamento']) ?>"
                                       title="Eliminar permanentemente">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <!-- Tooltip explicativo -->
                                    <button class="btn btn-sm btn-secondary" 
                                       disabled
                                       title="No se puede eliminar: hay pedidos registrados con esta dirección de envío"
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
                    <h5 class="modal-title">Agregar Envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Departamento</label>
                            <input type="text" name="departamento" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Provincia</label>
                            <input type="text" name="provincia" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Distrito</label>
                            <input type="text" name="distrito" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio Envío a Domicilio - Olva Courier</label>
                            <input type="number" step="0.01" name="precio_domicilio_olva" class="form-control" placeholder="0.00 = No disponible" value="0">
                            <small class="text-muted">Ingrese 0 si no está disponible</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio Recojo en Agencia - Olva Courier</label>
                            <input type="number" step="0.01" name="precio_agencia_olva" class="form-control" placeholder="0.00 = No disponible" value="0">
                            <small class="text-muted">Ingrese 0 si no está disponible</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio Envío a Domicilio - Shalom</label>
                            <input type="number" step="0.01" name="precio_domicilio_shalom" class="form-control" placeholder="0.00 = No disponible" value="0">
                            <small class="text-muted">Ingrese 0 si no está disponible</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio Recojo en Agencia - Shalom</label>
                            <input type="number" step="0.01" name="precio_agencia_shalom" class="form-control" placeholder="0.00 = No disponible" value="0">
                            <small class="text-muted">Ingrese 0 si no está disponible</small>
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
                    <h5 class="modal-title">Editar Envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditar">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label class="form-label">Departamento</label>
                            <input type="text" id="edit_departamento" name="departamento" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Provincia</label>
                            <input type="text" id="edit_provincia" name="provincia" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Distrito</label>
                            <input type="text" id="edit_distrito" name="distrito" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio Envío a Domicilio - Olva Courier</label>
                            <input type="number" step="0.01" id="edit_precio_domicilio_olva" name="precio_domicilio_olva" class="form-control" placeholder="0.00 = No disponible">
                            <small class="text-muted">Ingrese 0 si no está disponible</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio Recojo en Agencia - Olva Courier</label>
                            <input type="number" step="0.01" id="edit_precio_agencia_olva" name="precio_agencia_olva" class="form-control" placeholder="0.00 = No disponible">
                            <small class="text-muted">Ingrese 0 si no está disponible</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio Envío a Domicilio - Shalom</label>
                            <input type="number" step="0.01" id="edit_precio_domicilio_shalom" name="precio_domicilio_shalom" class="form-control" placeholder="0.00 = No disponible">
                            <small class="text-muted">Ingrese 0 si no está disponible</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio Recojo en Agencia - Shalom</label>
                            <input type="number" step="0.01" id="edit_precio_agencia_shalom" name="precio_agencia_shalom" class="form-control" placeholder="0.00 = No disponible">
                            <small class="text-muted">Ingrese 0 si no está disponible</small>
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
        let envios = <?= json_encode($envios) ?>;

        function editarEnvio(id) {
            const env = envios.find(e => e.id == id);
            if (env) {
                document.getElementById('edit_id').value = env.id;
                document.getElementById('edit_departamento').value = env.departamento;
                document.getElementById('edit_provincia').value = env.provincia;
                document.getElementById('edit_distrito').value = env.distrito;
                document.getElementById('edit_precio_domicilio_olva').value = env.precio_domicilio_olva;
                document.getElementById('edit_precio_agencia_olva').value = env.precio_agencia_olva;
                document.getElementById('edit_precio_domicilio_shalom').value = env.precio_domicilio_shalom;
                document.getElementById('edit_precio_agencia_shalom').value = env.precio_agencia_shalom;
                new bootstrap.Modal(document.getElementById('modalEditar')).show();
            }

            document.getElementById('formEditar').onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch('envios.php', { method: 'POST', body: formData })
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

            // Botones de INHABILITAR envío
            document.querySelectorAll('.btn-inhabilitar-envio').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const distrito = this.getAttribute('data-distrito');
                    const provincia = this.getAttribute('data-provincia');
                    const departamento = this.getAttribute('data-departamento');
                    const destino = `${distrito}, ${provincia}, ${departamento}`;
                    
                    showConfirmModal(
                        `<strong>¿Inhabilitar el envío a "${destino}"?</strong><br><br>` +
                        `<div style="text-align: left; display: inline-block;">` +
                        `Al inhabilitar este envío:<br>` +
                        `• Ya NO aparecerá como opción en el checkout<br>` +
                        `• Los clientes NO podrán seleccionar esta dirección<br>` +
                        `• Se mantiene en la base de datos<br>` +
                        `• Puedes habilitarlo nuevamente cuando quieras<br><br>` +
                        `<strong style="color: #856404;">💡 Recomendado para suspender temporalmente envíos a una zona.</strong>` +
                        `</div>`,
                        'bi bi-truck',
                        '#ffc107',
                        () => window.location.href = `envios.php?action=inhabilitar&id=${id}`
                    );
                });
            });

            // Botones de HABILITAR envío
            document.querySelectorAll('.btn-habilitar-envio').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const distrito = this.getAttribute('data-distrito');
                    const provincia = this.getAttribute('data-provincia');
                    const departamento = this.getAttribute('data-departamento');
                    const destino = `${distrito}, ${provincia}, ${departamento}`;
                    
                    showConfirmModal(
                        `<strong>¿Habilitar el envío a "${destino}"?</strong><br><br>` +
                        `<div style="text-align: left; display: inline-block;">` +
                        `Al habilitar este envío:<br>` +
                        `• Aparecerá nuevamente como opción en el checkout<br>` +
                        `• Los clientes podrán seleccionar esta dirección<br>` +
                        `• Estará disponible inmediatamente<br><br>` +
                        `<strong style="color: #28a745;">✓ El envío estará activo inmediatamente.</strong>` +
                        `</div>`,
                        'bi bi-truck',
                        '#28a745',
                        () => window.location.href = `envios.php?action=habilitar&id=${id}`
                    );
                });
            });

            // Botones de ELIMINAR envío (solo si no tiene pedidos)
            document.querySelectorAll('.btn-eliminar-envio-final').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const distrito = this.getAttribute('data-distrito');
                    const provincia = this.getAttribute('data-provincia');
                    const departamento = this.getAttribute('data-departamento');
                    const destino = `${distrito}, ${provincia}, ${departamento}`;
                    
                    showConfirmModal(
                        `<strong>¿Está seguro de ELIMINAR PERMANENTEMENTE el envío a "${destino}"?</strong><br><br>` +
                        `<div style="text-align: left; display: inline-block;">` +
                        `Esta acción eliminará:<br>` +
                        `• El registro del envío de la base de datos<br>` +
                        `• Toda su información de precios<br>` +
                        `• No se puede recuperar después<br><br>` +
                        `<strong style="color: #dc3545;">⚠️ ESTA ACCIÓN NO SE PUEDE DESHACER.</strong><br><br>` +
                        `<span style="color: #856404;">💡 Consejo: Si solo quieres suspenderlo temporalmente, usa "Inhabilitar" en lugar de eliminar.</span>` +
                        `</div>`,
                        'bi bi-trash-fill',
                        '#dc3545',
                        () => {
                            const formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('id', id);
                            fetch('envios.php', { method: 'POST', body: formData })
                                .then(() => location.reload());
                        }
                    );
                });
            });

            window.eliminarEnvio = function(id) {
                const envio = envios.find(e => e.id == id);
                const destino = envio ? `${envio.distrito}, ${envio.provincia}, ${envio.departamento}` : 'este envío';
                
                showConfirmModal(
                    `<strong>¿Está seguro de eliminar el envío a "${destino}"?</strong><br><br>` +
                    `<div style="text-align: left; display: inline-block;">` +
                    `Esta acción eliminará:<br>` +
                    `• El registro del envío<br>` +
                    `• Toda su información de la base de datos<br><br>` +
                    `<strong style="color: #dc3545;">⚠️ Esta acción NO se puede deshacer.</strong>` +
                    `</div>`,
                    'bi bi-truck',
                    '#dc3545',
                    () => {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('id', id);
                        fetch('envios.php', { method: 'POST', body: formData })
                            .then(() => location.reload());
                    }
                );
            };

            document.getElementById('modalAgregar').addEventListener('shown.bs.modal', function() {
                this.querySelector('form').onsubmit = function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    fetch('envios.php', { method: 'POST', body: formData })
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