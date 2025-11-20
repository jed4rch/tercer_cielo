<?php
require_once '../../includes/init.php';
require_once '../../includes/func_admin.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /tercer_cielo/public/login.php');
    exit;
}

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /tercer_cielo/public/index.php');
    exit;
}

$search = $_GET['search'] ?? '';
$orderby = $_GET['orderby'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$pedidos = get_pedidos_admin($filtro_estado ?: null, $search, $orderby);

$pageTitle = 'Gestión de Pedidos';
include 'layout_header.php';
?>

<div class="content-card">
    <h3 class="mb-4"><i class="bi bi-cart-fill me-2"></i>Pedidos Recibidos</h3>
        
        <!-- Barra de búsqueda y filtros -->
        <div class="row mb-4">
            <div class="col-md-4">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Buscar por código, usuario o email..." value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="estado" value="<?= htmlspecialchars($filtro_estado) ?>">
                    <input type="hidden" name="orderby" value="<?= htmlspecialchars($orderby) ?>">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                </form>
            </div>
            <div class="col-md-8 text-end">
                <select class="form-select d-inline-block w-auto me-2" onchange="window.location.href='?estado=' + this.value + '<?= $search ? '&search=' . urlencode($search) : '' ?><?= $orderby ? '&orderby=' . urlencode($orderby) : '' ?>'">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" <?= $filtro_estado == 'pendiente' ? 'selected' : '' ?>>⏱️ Pendiente</option>
                    <option value="aprobado" <?= $filtro_estado == 'aprobado' ? 'selected' : '' ?>>✅ Aprobado</option>
                    <option value="rechazado" <?= $filtro_estado == 'rechazado' ? 'selected' : '' ?>>❌ Rechazado</option>
                    <option value="enviado" <?= $filtro_estado == 'enviado' ? 'selected' : '' ?>>🚚 Enviado</option>
                    <option value="entregado" <?= $filtro_estado == 'entregado' ? 'selected' : '' ?>>✔️ Entregado</option>
                </select>
                <select class="form-select d-inline-block w-auto" onchange="window.location.href='?orderby=' + this.value + '<?= $search ? '&search=' . urlencode($search) : '' ?><?= $filtro_estado ? '&estado=' . urlencode($filtro_estado) : '' ?>'">
                    <option value="">Ordenar por...</option>
                    <option value="fecha_desc" <?= $orderby == 'fecha_desc' ? 'selected' : '' ?>>Fecha (Reciente a Antigua)</option>
                    <option value="fecha_asc" <?= $orderby == 'fecha_asc' ? 'selected' : '' ?>>Fecha (Antigua a Reciente)</option>
                    <option value="total_desc" <?= $orderby == 'total_desc' ? 'selected' : '' ?>>Total (Mayor a Menor)</option>
                    <option value="total_asc" <?= $orderby == 'total_asc' ? 'selected' : '' ?>>Total (Menor a Mayor)</option>
                    <option value="estado_asc" <?= $orderby == 'estado_asc' ? 'selected' : '' ?>>Estado (A-Z)</option>
                    <option value="estado_desc" <?= $orderby == 'estado_desc' ? 'selected' : '' ?>>Estado (Z-A)</option>
                </select>
                <?php if ($search || $filtro_estado): ?>
                    <a href="pedidos.php" class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-x-circle me-1"></i>Limpiar Filtros
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Usuario</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['codigo']) ?></td>
                            <td><?= htmlspecialchars($p['usuario']) ?></td>
                            <td>S/ <?= number_format($p['total'], 2) ?></td>
                            <td>
                                <?php
                                $badge_class = 'secondary';
                                $icon = 'hourglass-split';
                                switch($p['estado']) {
                                    case 'pendiente':
                                        $badge_class = 'warning'; // Amarillo
                                        $icon = 'clock-history';
                                        $texto_estado = 'Pendiente';
                                        break;
                                    case 'pendiente_pago':
                                        $badge_class = 'warning'; // Amarillo
                                        $icon = 'clock-history';
                                        $texto_estado = 'Pendiente';
                                        break;
                                    case 'aprobado':
                                        $badge_class = 'success'; // Verde
                                        $icon = 'check-circle';
                                        $texto_estado = 'Aprobado';
                                        break;
                                    case 'rechazado':
                                        $badge_class = 'danger'; // Rojo
                                        $icon = 'x-circle';
                                        $texto_estado = 'Rechazado';
                                        break;
                                    case 'enviado':
                                        $badge_class = 'info'; // Celeste
                                        $icon = 'truck';
                                        $texto_estado = 'Enviado';
                                        break;
                                    case 'entregado':
                                        $badge_class = 'primary'; // Azul
                                        $icon = 'check2-all';
                                        $texto_estado = 'Entregado';
                                        break;
                                }
                                ?>
                                <span class="badge bg-<?= $badge_class ?>">
                                    <i class="bi bi-<?= $icon ?> me-1"></i><?= $texto_estado ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($p['creado_en'])) ?></td>
                            <td>
                                <a href="detalles_pedido.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> Ver Detalles
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
</div>

<?php include 'layout_footer.php'; ?>