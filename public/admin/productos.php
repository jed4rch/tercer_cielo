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
        $stmt = $pdo->prepare("UPDATE productos SET habilitado = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        // Inhabilitar banners enlazados a este producto
        $stmt = $pdo->prepare("UPDATE banners SET habilitado = 0 WHERE tipo_enlace = 'producto' AND enlace_id = ?");
        $stmt->execute([$id]);
        
        header('Location: productos.php?success=inhabilitado');
        exit;
    } elseif ($_GET['action'] === 'habilitar') {
        // Verificar si la categoría está habilitada
        $stmt = $pdo->prepare("SELECT c.habilitado, c.nombre FROM productos p JOIN categorias c ON p.id_categoria = c.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $resultado = $stmt->fetch();
        
        if ($resultado && $resultado['habilitado'] == 0) {
            // La categoría está inhabilitada
            header('Location: productos.php?error=categoria_inhabilitada&categoria=' . urlencode($resultado['nombre']));
            exit;
        }
        
        // Habilitar el producto
        $stmt = $pdo->prepare("UPDATE productos SET habilitado = 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        // Habilitar banners enlazados a este producto
        $stmt = $pdo->prepare("UPDATE banners SET habilitado = 1 WHERE tipo_enlace = 'producto' AND enlace_id = ?");
        $stmt->execute([$id]);
        
        header('Location: productos.php?success=habilitado');
        exit;
    }
}

// Procesar búsqueda
$search = $_GET['search'] ?? '';
$orderby = $_GET['orderby'] ?? '';
$where = '';
$params = [];

if ($search) {
    $where = "WHERE p.id = ? OR p.nombre LIKE ? OR c.nombre LIKE ? OR p.descripcion LIKE ?";
    
    // Verificar si la búsqueda es numérica (para ID)
    if (is_numeric($search)) {
        $params = [$search, "%$search%", "%$search%", "%$search%"];
    } else {
        $params = [0, "%$search%", "%$search%", "%$search%"];
    }
}

// Determinar ordenamiento
$order = 'p.nombre ASC';
switch($orderby) {
    case 'nombre_asc': $order = 'p.nombre ASC'; break;
    case 'nombre_desc': $order = 'p.nombre DESC'; break;
    case 'precio_asc': $order = 'p.precio ASC'; break;
    case 'precio_desc': $order = 'p.precio DESC'; break;
    case 'stock_asc': $order = 'p.stock ASC'; break;
    case 'stock_desc': $order = 'p.stock DESC'; break;
}

$sql = "SELECT p.*, c.nombre as categoria FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id $where ORDER BY $order";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

// Verificar si cada producto tiene pedidos o movimientos
foreach ($productos as &$producto) {
    // Verificar si tiene pedidos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedido_detalles WHERE producto_id = ?");
    $stmt->execute([$producto['id']]);
    $producto['tiene_pedidos'] = $stmt->fetchColumn() > 0;
    
    // Verificar si tiene movimientos de inventario
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM movimientos_inventario WHERE id_producto = ?");
    $stmt->execute([$producto['id']]);
    $producto['tiene_movimientos'] = $stmt->fetchColumn() > 0;
}
unset($producto); // Romper referencia

$pageTitle = 'Gestión de Productos';
include 'layout_header.php';
?>

<div class="content-card">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            if ($_GET['success'] == 'inhabilitado') echo '<i class="bi bi-check-circle me-2"></i>Producto inhabilitado correctamente. No se mostrará a los clientes.';
            if ($_GET['success'] == 'habilitado') echo '<i class="bi bi-check-circle me-2"></i>Producto habilitado correctamente. Ahora es visible para los clientes.';
            if ($_GET['success'] == 'eliminado') echo '<i class="bi bi-check-circle me-2"></i>Producto eliminado permanentemente.';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            if ($_GET['error'] == 'categoria_inhabilitada') {
                echo '<i class="bi bi-exclamation-triangle me-2"></i>No se puede habilitar este producto porque su categoría "' . htmlspecialchars($_GET['categoria'] ?? '') . '" está inhabilitada. Por favor, habilite primero la categoría o cambie el producto a otra categoría.';
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-box-seam-fill me-2"></i>Inventario de Productos</h3>
        <a href="agregar_producto.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Agregar Producto
        </a>
    </div>
        
        <!-- Barra de búsqueda -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Buscar por ID, nombre, categoría o descripción..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                    <?php if ($search): ?>
                        <a href="productos.php" class="btn btn-outline-secondary ms-2">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <select class="form-select d-inline-block w-auto" onchange="window.location.href='?orderby=' + this.value + '<?= $search ? '&search=' . urlencode($search) : '' ?>'">
                    <option value="">Ordenar por...</option>
                    <option value="nombre_asc" <?= ($_GET['orderby'] ?? '') == 'nombre_asc' ? 'selected' : '' ?>>Nombre (A-Z)</option>
                    <option value="nombre_desc" <?= ($_GET['orderby'] ?? '') == 'nombre_desc' ? 'selected' : '' ?>>Nombre (Z-A)</option>
                    <option value="precio_asc" <?= ($_GET['orderby'] ?? '') == 'precio_asc' ? 'selected' : '' ?>>Precio (Menor a Mayor)</option>
                    <option value="precio_desc" <?= ($_GET['orderby'] ?? '') == 'precio_desc' ? 'selected' : '' ?>>Precio (Mayor a Menor)</option>
                    <option value="stock_asc" <?= ($_GET['orderby'] ?? '') == 'stock_asc' ? 'selected' : '' ?>>Stock (Menor a Mayor)</option>
                    <option value="stock_desc" <?= ($_GET['orderby'] ?? '') == 'stock_desc' ? 'selected' : '' ?>>Stock (Mayor a Menor)</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                        <tr style="<?= $p['habilitado'] == 0 ? 'opacity: 0.5; background-color: #f8f9fa;' : '' ?>">
                            <td><?= $p['id'] ?></td>
                            <td>
                                <?= htmlspecialchars($p['nombre']) ?>
                                <?php if ($p['habilitado'] == 0): ?>
                                    <span class="badge bg-secondary ms-1">Inhabilitado</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['categoria']) ?></td>
                            <td>S/ <?= number_format($p['precio'], 2) ?></td>
                            <td><?= $p['stock'] ?></td>
                            <td>
                                <?php if ($p['habilitado'] == 1): ?>
                                    <span class="badge bg-success"><i class="bi bi-eye"></i> Visible</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="bi bi-eye-slash"></i> Oculto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="editar_producto.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                
                                <?php if ($p['habilitado'] == 1): ?>
                                    <!-- Botón Inhabilitar -->
                                    <button class="btn btn-sm btn-warning btn-inhabilitar-producto" 
                                       data-id="<?= $p['id'] ?>"
                                       data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                       title="Inhabilitar (ocultar del público)">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                <?php else: ?>
                                    <!-- Botón Habilitar -->
                                    <button class="btn btn-sm btn-success btn-habilitar-producto" 
                                       data-id="<?= $p['id'] ?>"
                                       data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                       title="Habilitar (mostrar al público)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (!$p['tiene_pedidos'] && !$p['tiene_movimientos']): ?>
                                    <!-- Solo mostrar eliminar si NO tiene pedidos ni movimientos -->
                                    <button class="btn btn-sm btn-danger btn-eliminar-producto" 
                                       data-id="<?= $p['id'] ?>"
                                       data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                       title="Eliminar permanentemente">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <!-- Tooltip explicativo -->
                                    <button class="btn btn-sm btn-secondary" 
                                       disabled
                                       title="No se puede eliminar: tiene pedidos o movimientos de inventario registrados"
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

        // Botones de INHABILITAR producto
        document.querySelectorAll('.btn-inhabilitar-producto').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                showConfirmModal(
                    `<strong>¿Inhabilitar el producto "${nombre}"?</strong><br><br>` +
                    `<div style="text-align: left; display: inline-block;">` +
                    `Al inhabilitar este producto:<br>` +
                    `• El producto NO aparecerá en el catálogo público<br>` +
                    `• Los clientes NO podrán comprarlo<br>` +
                    `• <strong>Todos los banners</strong> enlazados a este producto se inhabilitarán automáticamente<br>` +
                    `• Se mantiene en la base de datos<br>` +
                    `• Puedes habilitarlo nuevamente cuando quieras<br><br>` +
                    `<strong style="color: #856404;">⚠️ Los banners relacionados se desactivarán.</strong>` +
                    `</div>`,
                    'bi bi-eye-slash-fill',
                    '#ffc107',
                    () => window.location.href = `productos.php?action=inhabilitar&id=${id}`
                );
            });
        });

        // Botones de HABILITAR producto
        document.querySelectorAll('.btn-habilitar-producto').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                showConfirmModal(
                    `<strong>¿Habilitar el producto "${nombre}"?</strong><br><br>` +
                    `<div style="text-align: left; display: inline-block;">` +
                    `Al habilitar este producto:<br>` +
                    `• El producto aparecerá en el catálogo público<br>` +
                    `• Los clientes podrán verlo y comprarlo<br>` +
                    `• <strong>Todos los banners</strong> enlazados a este producto se habilitarán automáticamente<br>` +
                    `• Estará disponible en búsquedas<br><br>` +
                    `<strong style="color: #28a745;">✓ Todo se activará inmediatamente.</strong>` +
                    `</div>`,
                    'bi bi-eye-fill',
                    '#28a745',
                    () => window.location.href = `productos.php?action=habilitar&id=${id}`
                );
            });
        });

        // Botones de ELIMINAR producto
        document.querySelectorAll('.btn-eliminar-producto').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                showConfirmModal(
                    `<strong>¿Está seguro de ELIMINAR PERMANENTEMENTE el producto "${nombre}"?</strong><br><br>` +
                    `<div style="text-align: left; display: inline-block;">` +
                    `Esta acción eliminará:<br>` +
                    `• El producto de la base de datos<br>` +
                    `• Todas sus imágenes del servidor<br>` +
                    `• No se puede recuperar después<br><br>` +
                    `<strong style="color: #dc3545;">⚠️ ESTA ACCIÓN NO SE PUEDE DESHACER.</strong><br><br>` +
                    `<span style="color: #856404;">💡 Consejo: Si solo quieres ocultarlo temporalmente, usa "Inhabilitar" en lugar de eliminar.</span>` +
                    `</div>`,
                    'bi bi-trash-fill',
                    '#dc3545',
                    () => window.location.href = `eliminar_producto.php?id=${id}`
                );
            });
        });

        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>