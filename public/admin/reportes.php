<?php
session_start();
require_once __DIR__ . '/../../includes/init.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['user_id'])) {
    header('Location: /tercer_cielo/public/login.php');
    exit;
}

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /tercer_cielo/public/index.php');
    exit;
}

require_once __DIR__ . '/../../includes/func_admin.php';

// Obtener categorías para filtros
$pdo = getPdo();
$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll();

// Procesar filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$tipo_reporte = $_GET['tipo_reporte'] ?? 'ventas';
$categoria_id = $_GET['categoria_id'] ?? '';

// Generar reporte según el tipo seleccionado
$reporte_data = [];
$titulo_reporte = '';

switch ($tipo_reporte) {
    case 'ventas':
        $reporte_data = generar_reporte_ventas($fecha_inicio, $fecha_fin);
        $titulo_reporte = "Reporte de Ventas ($fecha_inicio a $fecha_fin)";
        break;
        
    case 'stock':
        $reporte_data = generar_reporte_stock($categoria_id);
        $titulo_reporte = "Reporte de Stock" . ($categoria_id ? " - Categoría Específica" : "");
        break;
        
    case 'clientes':
        $reporte_data = generar_reporte_clientes();
        $titulo_reporte = "Reporte de Clientes";
        break;
        
    case 'productos_vendidos':
        $reporte_data = generar_reporte_productos_mas_vendidos($fecha_inicio, $fecha_fin);
        $titulo_reporte = "Productos Más Vendidos ($fecha_inicio a $fecha_fin)";
        break;
}

// Procesar exportación
if (isset($_GET['exportar'])) {
    $formato = $_GET['formato'] ?? 'pdf';
    exportar_reporte($reporte_data, $titulo_reporte, $formato, $tipo_reporte);
}

$pageTitle = 'Reportes y Estadísticas';
include 'layout_header.php';
?>

<style>
    @media print {
        .no-print, .sidebar, .top-bar, .btn-group {
            display: none !important;
        }
        body {
            margin: 0;
            padding: 20px;
        }
        .content-card {
            border: none;
            box-shadow: none;
        }
        table {
            font-size: 11px;
        }
        th, td {
            padding: 6px;
        }
    }
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .stat-card h6 {
        color: #6c757d;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
    }
    .stat-card h3 {
        color: #333;
        margin: 0;
    }
    .chart-container {
        position: relative;
        height: 300px;
        margin-top: 20px;
    }
</style>

<div class="content-card">
    <h3 class="mb-4"><i class="bi bi-bar-chart-fill me-2"></i>Reportes y Estadísticas</h3>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtros del Reporte</h5>
        </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Reporte</label>
                        <select name="tipo_reporte" class="form-select" onchange="this.form.submit()">
                            <option value="ventas" <?= $tipo_reporte == 'ventas' ? 'selected' : '' ?>>Ventas</option>
                            <option value="stock" <?= $tipo_reporte == 'stock' ? 'selected' : '' ?>>Stock</option>
                            <option value="clientes" <?= $tipo_reporte == 'clientes' ? 'selected' : '' ?>>Clientes</option>
                            <option value="productos_vendidos" <?= $tipo_reporte == 'productos_vendidos' ? 'selected' : '' ?>>Productos Más Vendidos</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?= $fecha_inicio ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?= $fecha_fin ?>">
                    </div>
                    
                    <?php if ($tipo_reporte == 'stock'): ?>
                    <div class="col-md-3">
                        <label class="form-label">Categoría</label>
                        <select name="categoria_id" class="form-select">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" <?= $categoria_id == $categoria['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar Reporte
                        </button>
                        
                        <?php if (!empty($reporte_data)): ?>
                        <div class="btn-group ms-2">
                            <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-download me-1"></i>Exportar
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="?<?= http_build_query(array_merge($_GET, ['exportar' => 1, 'formato' => 'pdf'])) ?>">
                                        <i class="fas fa-file-pdf me-2"></i>PDF
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="?<?= http_build_query(array_merge($_GET, ['exportar' => 1, 'formato' => 'excel'])) ?>">
                                        <i class="fas fa-file-excel me-2"></i>Excel
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($reporte_data)): ?>
            <!-- Estadísticas Rápidas -->
        <?php if (isset($reporte_data['estadisticas']) && !empty($reporte_data['estadisticas'])): ?>
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3"><i class="bi bi-speedometer2 me-2"></i>Estadísticas Rápidas</h4>
            </div>
            <?php foreach ($reporte_data['estadisticas'] as $key => $value): ?>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <h6><?= ucfirst(str_replace('_', ' ', $key)) ?></h6>
                    <h3>
                        <?php
                        // Determinar si es un valor monetario o de cantidad
                        $is_monetary = in_array($key, ['precio', 'ingresos', 'ventas', 'total_ventas', 'total_ingresos', 'total_compras', 'precio_promedio']);
                        $is_quantity = in_array($key, ['stock', 'total_productos', 'total_pedidos', 'pedidos_entregados', 'total_unidades_vendidas', 'total_vendido', 'productos_count', 'total_clientes', 'clientes_activos']);
                        
                        if (is_numeric($value)):
                            if ($is_monetary): ?>
                                S/ <?= number_format($value, 2) ?>
                            <?php elseif ($is_quantity): ?>
                                <?= number_format($value, 0) ?>
                            <?php else: ?>
                                <?= number_format($value, 0) ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <?= $value ?>
                        <?php endif; ?>
                    </h3>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Gráficos según tipo de reporte -->
        <div class="row mb-4">
            <?php if ($tipo_reporte == 'ventas' && !empty($reporte_data['datos'])): ?>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Ventas Diarias</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartVentasDiarias"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Distribución de Pedidos</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartPedidos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($tipo_reporte == 'stock' && !empty($reporte_data['datos'])): ?>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Productos por Stock</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartStock"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Nivel de Stock</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartNivelStock"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($tipo_reporte == 'productos_vendidos' && !empty($reporte_data['datos'])): ?>
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Top Productos Vendidos</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 400px;">
                                <canvas id="chartProductosVendidos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($tipo_reporte == 'clientes' && !empty($reporte_data['datos'])): ?>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-people me-2"></i>Distribución de Clientes</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartClientes"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-cart-check me-2"></i>Top Clientes por Compras</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartTopClientes"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

            <!-- Tabla de Datos -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= $titulo_reporte ?></h5>
                    <span class="badge bg-primary">
                        <?= count($reporte_data['datos']) ?> registros
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaReporte">
                            <thead class="table-dark">
                                <tr>
                                    <?php foreach ($reporte_data['columnas'] as $index => $columna): ?>
                                        <th style="cursor: pointer; user-select: none;" onclick="ordenarTabla(<?= $index ?>)" title="Click para ordenar">
                                            <?= $columna ?> <i class="bi bi-arrow-down-up ms-1"></i>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Usar datos_tabla si existe, sino usar datos (para compatibilidad con otros reportes)
                                $datos_mostrar = isset($reporte_data['datos_tabla']) ? $reporte_data['datos_tabla'] : $reporte_data['datos'];
                                if (empty($datos_mostrar)): 
                                ?>
                                    <tr>
                                        <td colspan="<?= count($reporte_data['columnas']) ?>" class="text-center text-muted">
                                            No hay datos para mostrar con los filtros seleccionados
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($datos_mostrar as $fila): ?>
                                        <tr>
                                            <?php foreach ($fila as $columna_nombre => $valor): ?>
                                                <td>
                                                    <?php if (is_numeric($valor)): ?>
                                                        <?php
                                                        // Determinar si la columna es de cantidad (sin decimales) o monetaria (con 2 decimales)
                                                        $columna_lower = strtolower($columna_nombre);
                                                        $is_quantity_col = strpos($columna_lower, 'stock') !== false ||
                                                                          strpos($columna_lower, 'unidades') !== false ||
                                                                          strpos($columna_lower, 'cantidad') !== false ||
                                                                          strpos($columna_lower, 'total_vendido') !== false ||
                                                                          strpos($columna_lower, 'productos_count') !== false ||
                                                                          strpos($columna_lower, 'total_pedidos') !== false;
                                                        
                                                        if ($is_quantity_col): ?>
                                                            <?= number_format($valor, 0) ?>
                                                        <?php else: ?>
                                                            <?= number_format($valor, 2) ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($valor) ?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Selecciona un tipo de reporte y aplica los filtros</h4>
                <p class="text-muted">Los datos se generarán automáticamente según tus selecciones</p>
            </div>
        <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Función para ordenar tabla
let ordenAscendente = {};

function ordenarTabla(columna) {
    const tabla = document.getElementById('tablaReporte');
    if (!tabla) return;
    
    const tbody = tabla.getElementsByTagName('tbody')[0];
    const filas = Array.from(tbody.getElementsByTagName('tr'));
    
    // Alternar dirección de ordenamiento
    ordenAscendente[columna] = !ordenAscendente[columna];
    const ascendente = ordenAscendente[columna];
    
    filas.sort((a, b) => {
        const celdaA = a.getElementsByTagName('td')[columna];
        const celdaB = b.getElementsByTagName('td')[columna];
        
        if (!celdaA || !celdaB) return 0;
        
        let valorA = celdaA.textContent.trim();
        let valorB = celdaB.textContent.trim();
        
        // Intentar convertir a número si es posible
        const numA = parseFloat(valorA.replace(/[^\d.-]/g, ''));
        const numB = parseFloat(valorB.replace(/[^\d.-]/g, ''));
        
        if (!isNaN(numA) && !isNaN(numB)) {
            return ascendente ? numA - numB : numB - numA;
        }
        
        // Comparar como texto
        if (ascendente) {
            return valorA.localeCompare(valorB);
        } else {
            return valorB.localeCompare(valorA);
        }
    });
    
    // Reordenar filas en la tabla
    filas.forEach(fila => tbody.appendChild(fila));
    
    // Actualizar iconos de ordenamiento
    const headers = tabla.getElementsByTagName('thead')[0].getElementsByTagName('th');
    for (let i = 0; i < headers.length; i++) {
        const icon = headers[i].querySelector('i');
        if (icon) {
            if (i === columna) {
                icon.className = ascendente ? 'bi bi-arrow-up ms-1' : 'bi bi-arrow-down ms-1';
            } else {
                icon.className = 'bi bi-arrow-down-up ms-1';
            }
        }
    }
}

<?php if (!empty($reporte_data['datos'])): ?>
    
    <?php if ($tipo_reporte == 'ventas'): ?>
        // Gráfico de Ventas Diarias
        const datosVentas = <?= json_encode($reporte_data['datos']) ?>;
        
        // Agrupar ventas por fecha
        const ventasPorFecha = {};
        datosVentas.forEach(venta => {
            const fecha = venta.fecha; // Usar 'fecha' en lugar de 'fecha_pedido'
            if (!ventasPorFecha[fecha]) {
                ventasPorFecha[fecha] = {
                    total: 0,
                    cantidad: 0
                };
            }
            ventasPorFecha[fecha].total += parseFloat(venta.total);
            ventasPorFecha[fecha].cantidad += 1;
        });
        
        // Ordenar fechas cronológicamente
        const fechasOrdenadas = Object.keys(ventasPorFecha).sort();
        const totalesPorFecha = fechasOrdenadas.map(fecha => ventasPorFecha[fecha].total);
        
        // Formatear fechas para mejor visualización
        const fechasFormateadas = fechasOrdenadas.map(fecha => {
            const partes = fecha.split('-');
            return `${partes[2]}/${partes[1]}`;
        });
        
        new Chart(document.getElementById('chartVentasDiarias'), {
            type: 'line',
            data: {
                labels: fechasFormateadas,
                datasets: [{
                    label: 'Ventas (S/)',
                    data: totalesPorFecha,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#0d6efd'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const fecha = fechasOrdenadas[context.dataIndex];
                                const datos = ventasPorFecha[fecha];
                                return [
                                    'Total: S/ ' + datos.total.toFixed(2),
                                    'Pedidos: ' + datos.cantidad
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'S/ ' + value.toFixed(2);
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                }
            }
        });
        
        // Gráfico de Pedidos - Usar datos_tabla para mostrar todos los estados
        const estadosPedidos = <?= json_encode(array_column($reporte_data['datos_tabla'] ?? $reporte_data['datos'], 'estado') ?? []) ?>;
        const conteoEstados = estadosPedidos.reduce((acc, estado) => {
            acc[estado] = (acc[estado] || 0) + 1;
            return acc;
        }, {});
        
        // Mapeo de colores por estado
        const coloresPorEstado = {
            'pendiente': '#ffc107',
            'pendiente_pago': '#fd7e14',
            'aprobado': '#0dcaf0',
            'rechazado': '#dc3545',
            'enviado': '#0d6efd',
            'entregado': '#198754'
        };
        
        const estadosOrdenados = Object.keys(conteoEstados);
        const coloresEstados = estadosOrdenados.map(estado => coloresPorEstado[estado] || '#6c757d');
        
        new Chart(document.getElementById('chartPedidos'), {
            type: 'doughnut',
            data: {
                labels: estadosOrdenados.map(estado => {
                    // Capitalizar primera letra
                    return estado.charAt(0).toUpperCase() + estado.slice(1).replace('_', ' ');
                }),
                datasets: [{
                    data: Object.values(conteoEstados),
                    backgroundColor: coloresEstados,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const valor = context.parsed;
                                const porcentaje = ((valor / total) * 100).toFixed(1);
                                return context.label + ': ' + valor + ' (' + porcentaje + '%)';
                            }
                        }
                    }
                }
            }
        });
    
    <?php elseif ($tipo_reporte == 'stock'): ?>
        // Gráfico de Productos por Stock
        const productos = <?= json_encode(array_slice(array_column($reporte_data['datos'], 'nombre'), 0, 10)) ?>;
        const stocks = <?= json_encode(array_slice(array_column($reporte_data['datos'], 'stock'), 0, 10)) ?>;
        
        new Chart(document.getElementById('chartStock'), {
            type: 'bar',
            data: {
                labels: productos,
                datasets: [{
                    label: 'Stock Actual',
                    data: stocks,
                    backgroundColor: '#ffc107',
                    borderColor: '#ff9800',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Gráfico de Nivel de Stock (Crítico/Bajo/Normal)
        const stockCritico = stocks.filter(s => s < 10).length;
        const stockBajo = stocks.filter(s => s >= 10 && s < 50).length;
        const stockNormal = stocks.filter(s => s >= 50).length;
        
        new Chart(document.getElementById('chartNivelStock'), {
            type: 'pie',
            data: {
                labels: ['Crítico (<10)', 'Bajo (10-49)', 'Normal (≥50)'],
                datasets: [{
                    data: [stockCritico, stockBajo, stockNormal],
                    backgroundColor: ['#dc3545', '#ffc107', '#28a745']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    
    <?php elseif ($tipo_reporte == 'productos_vendidos'): ?>
        // Gráfico de Top Productos Vendidos
        const productosVendidos = <?= json_encode(array_slice(array_column($reporte_data['datos'], 'nombre'), 0, 10)) ?>;
        const cantidadesVendidas = <?= json_encode(array_slice(array_column($reporte_data['datos'], 'total_vendido'), 0, 10)) ?>;
        
        new Chart(document.getElementById('chartProductosVendidos'), {
            type: 'bar',
            data: {
                labels: productosVendidos,
                datasets: [{
                    label: 'Unidades Vendidas',
                    data: cantidadesVendidas,
                    backgroundColor: '#007bff',
                    borderColor: '#0056b3',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
    
    <?php elseif ($tipo_reporte == 'clientes'): ?>
        // Gráfico de Distribución de Clientes
        const datosClientes = <?= json_encode($reporte_data['datos']) ?>;
        
        let clientesActivos = 0;
        let clientesInactivos = 0;
        
        datosClientes.forEach(cliente => {
            const totalPedidos = parseInt(cliente.total_pedidos) || 0;
            if (totalPedidos > 0) {
                clientesActivos++;
            } else {
                clientesInactivos++;
            }
        });
        
        new Chart(document.getElementById('chartClientes'), {
            type: 'doughnut',
            data: {
                labels: ['Clientes Activos', 'Clientes Sin Pedidos'],
                datasets: [{
                    data: [clientesActivos, clientesInactivos],
                    backgroundColor: ['#28a745', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        // Gráfico de Top Clientes
        const datosOrdenados = [...datosClientes].sort((a, b) => {
            const pedidosA = parseInt(a.total_pedidos) || 0;
            const pedidosB = parseInt(b.total_pedidos) || 0;
            return pedidosB - pedidosA;
        });
        
        const topClientes = datosOrdenados.slice(0, 10);
        const nombresClientes = topClientes.map(c => c.nombre || 'Sin nombre');
        const pedidosClientes = topClientes.map(c => parseInt(c.total_pedidos) || 0);
        
        new Chart(document.getElementById('chartTopClientes'), {
            type: 'bar',
            data: {
                labels: nombresClientes,
                datasets: [{
                    label: 'Total Pedidos',
                    data: pedidosClientes,
                    backgroundColor: '#28a745',
                    borderColor: '#1e7e34',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    <?php endif; ?>
    
<?php endif; ?>
</script>

<?php include 'layout_footer.php'; ?>