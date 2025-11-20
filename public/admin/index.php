<?php
require_once '../../includes/init.php';
require_once '../../includes/func_admin.php';

// === CONTROL DE ACCESO ===
if (!isset($_SESSION['user_id'])) {
    header('Location: /tercer_cielo/public/login.php');
    exit;
}

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /tercer_cielo/public/index.php'); // Redirigir clientes a la tienda
    exit;
}

$stats = get_estadisticas_admin();
$stats_stock = get_estadisticas_stock();
$productos_vendidos = get_productos_vendidos();
$categorias_vendidas = get_categorias_vendidas();
$contadores_pedidos = get_contadores_pedidos();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dashboard</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../../assets/favicon/apple-touch-icon.png">
    <link rel="manifest" href="../../assets/favicon/site.webmanifest">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            padding: 20px 0;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h4 {
            color: #fff;
            font-weight: 600;
            margin: 0;
            font-size: 1.3rem;
        }

        .sidebar-header small {
            color: #94a3b8;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);
            color: #fff;
            border-left: 4px solid #6366f1;
            padding-left: 16px;
        }

        .sidebar-menu a i {
            font-size: 1.3rem;
            width: 35px;
            margin-right: 12px;
        }

        .sidebar-menu a span {
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            padding: 0;
        }

        .top-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .top-bar h2 {
            margin: 0;
            color: #1e293b;
            font-weight: 700;
        }

        .content-area {
            padding: 30px;
        }

        /* Cards Modernas */
        .card-stats {
            border: none;
            border-radius: 16px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }

        .card-stats::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.6));
        }

        .card-stats:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .card-stats .card-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px;
        }

        .stat-content h5 {
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            opacity: 0.9;
        }

        .stat-content h2 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            word-wrap: break-word;
            line-height: 1.2;
        }
        
        /* Ajuste específico para montos monetarios */
        .stat-content h2.money-stat {
            font-size: 1.5rem;
            white-space: nowrap;
            overflow: visible;
        }
        
        .stat-content {
            flex: 1;
            min-width: 0;
        }

        .stat-icon {
            font-size: 3rem;
            opacity: 0.3;
            flex-shrink: 0;
        }

        /* Chart Cards */
        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .chart-card .card-header {
            border: none;
            font-weight: 600;
            padding: 20px 25px;
            font-size: 1.1rem;
        }

        .chart-card .card-body {
            padding: 25px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Section Headers */
        .section-title {
            color: #fff;
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .sidebar-header h4,
            .sidebar-header small,
            .sidebar-menu a span {
                display: none;
            }

            .main-content {
                margin-left: 70px;
            }

            .sidebar-menu a {
                justify-content: center;
            }

            .sidebar-menu a i {
                margin-right: 0;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-speedometer2 me-2"></i>Admin Panel</h4>
            <small>Tercer Cielo</small>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="index.php" class="active">
                    <i class="bi bi-house-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="usuarios.php">
                    <i class="bi bi-people-fill"></i>
                    <span>Usuarios</span>
                </a>
            </li>
            <li>
                <a href="categorias.php">
                    <i class="bi bi-tags-fill"></i>
                    <span>Categorías</span>
                </a>
            </li>
            <li>
                <a href="productos.php">
                    <i class="bi bi-box-seam-fill"></i>
                    <span>Productos</span>
                </a>
            </li>
            <li>
                <a href="pedidos.php">
                    <i class="bi bi-cart-fill"></i>
                    <span>Pedidos</span>
                </a>
            </li>
            <li>
                <a href="envios.php">
                    <i class="bi bi-truck"></i>
                    <span>Envíos</span>
                </a>
            </li>
            <li>
                <a href="banners.php">
                    <i class="bi bi-images"></i>
                    <span>Banners</span>
                </a>
            </li>
            <li>
                <a href="reportes.php">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    <span>Reportes</span>
                </a>
            </li>
            <li style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                <a href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h2>Dashboard Administrador</h2>
            <div class="d-flex align-items-center">
                <span class="text-muted"><i class="bi bi-person-circle me-2"></i><?= $_SESSION['nombre'] ?? 'Admin' ?></span>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Tarjetas de Estadísticas Generales -->
            <h4 class="section-title">📊 Estadísticas Generales</h4>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card card-stats text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="card-body">
                            <div class="stat-content">
                                <h5>USUARIOS</h5>
                                <h2><?= $stats['usuarios'] ?></h2>
                            </div>
                            <i class="bi bi-people-fill stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card card-stats text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="card-body">
                            <div class="stat-content">
                                <h5>PRODUCTOS</h5>
                                <h2><?= $stats['productos'] ?></h2>
                            </div>
                            <i class="bi bi-box-seam-fill stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card card-stats text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <div class="card-body">
                            <div class="stat-content">
                                <h5>PEDIDOS TOTAL</h5>
                                <h2><?= $stats['pedidos'] ?></h2>
                            </div>
                            <i class="bi bi-cart-fill stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card card-stats text-white" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <div class="card-body">
                            <div class="stat-content">
                                <h5>VENTAS TOTAL</h5>
                                <h2 class="money-stat">S/ <?= number_format($stats['ventas'], 2) ?></h2>
                            </div>
                            <i class="bi bi-currency-dollar stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de Estados de Pedidos -->
            <h4 class="section-title mt-4">📦 Estados de Pedidos</h4>
            <div class="row">
                <div class="col-lg-2 col-md-4 col-6 mb-4">
                    <div class="card card-stats text-white" style="background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);">
                        <div class="card-body text-center flex-column">
                            <i class="bi bi-clock-fill" style="font-size: 2.5rem; opacity: 0.3; margin-bottom: 10px;"></i>
                            <h6 style="font-size: 0.8rem; font-weight: 600; margin-bottom: 5px;">Pendientes</h6>
                            <h3 style="font-size: 1.8rem; font-weight: 700; margin: 0;"><?= $contadores_pedidos['pendiente'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4">
                    <div class="card card-stats text-white" style="background: linear-gradient(135deg, #a8e6cf 0%, #3ecd5e 100%);">
                        <div class="card-body text-center flex-column">
                            <i class="bi bi-check-circle-fill" style="font-size: 2.5rem; opacity: 0.3; margin-bottom: 10px;"></i>
                            <h6 style="font-size: 0.8rem; font-weight: 600; margin-bottom: 5px;">Aprobados</h6>
                            <h3 style="font-size: 1.8rem; font-weight: 700; margin: 0;"><?= $contadores_pedidos['aprobado'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4">
                    <div class="card card-stats text-white" style="background: linear-gradient(135deg, #ffa69e 0%, #ff6b6b 100%);">
                        <div class="card-body text-center flex-column">
                            <i class="bi bi-x-circle-fill" style="font-size: 2.5rem; opacity: 0.3; margin-bottom: 10px;"></i>
                            <h6 style="font-size: 0.8rem; font-weight: 600; margin-bottom: 5px;">Rechazados</h6>
                            <h3 style="font-size: 1.8rem; font-weight: 700; margin: 0;"><?= $contadores_pedidos['rechazado'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card card-stats text-white" style="background: linear-gradient(135deg, #a8c0ff 0%, #3f2b96 100%);">
                        <div class="card-body text-center flex-column">
                            <i class="bi bi-truck" style="font-size: 2.5rem; opacity: 0.3; margin-bottom: 10px;"></i>
                            <h6 style="font-size: 0.8rem; font-weight: 600; margin-bottom: 5px;">Enviados</h6>
                            <h3 style="font-size: 1.8rem; font-weight: 700; margin: 0;"><?= $contadores_pedidos['enviado'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card card-stats text-white" style="background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);">
                        <div class="card-body text-center flex-column">
                            <i class="bi bi-check2-all" style="font-size: 2.5rem; opacity: 0.3; margin-bottom: 10px;"></i>
                            <h6 style="font-size: 0.8rem; font-weight: 600; margin-bottom: 5px;">Entregados</h6>
                            <h3 style="font-size: 1.8rem; font-weight: 700; margin: 0;"><?= $contadores_pedidos['entregado'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos Circulares -->
            <h4 class="section-title mt-4">📈 Análisis Visual</h4>
            <div class="row">
                <!-- Gráfico de Productos con Stock Mínimo -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="chart-card">
                        <div class="card-header bg-danger text-white">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Productos con Stock Mínimo
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartStockMinimo"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Productos con Stock Máximo -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="chart-card">
                        <div class="card-header bg-success text-white">
                            <i class="bi bi-check-circle-fill me-2"></i>Productos con Stock Máximo
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartStockMaximo"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Productos Más Vendidos -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="chart-card">
                        <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="bi bi-star-fill me-2"></i>Productos Más Vendidos
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartMasVendidos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Productos Menos Vendidos -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="chart-card">
                        <div class="card-header text-white" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <i class="bi bi-graph-down me-2"></i>Productos Menos Vendidos
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartMenosVendidos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Categorías Más Vendidas -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="chart-card">
                        <div class="card-header text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="bi bi-tags-fill me-2"></i>Categorías Más Vendidas
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartCategorias"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Estados de Pedidos -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="chart-card">
                        <div class="card-header text-white" style="background: linear-gradient(135deg, #a8c0ff 0%, #3f2b96 100%);">
                            <i class="bi bi-pie-chart-fill me-2"></i>Estados de Pedidos
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartPedidos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Configuración de colores
        const colores = {
            primarios: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'],
            minimo: ['#dc3545', '#ff6b6b', '#ff8787'],
            maximo: ['#28a745', '#5cb85c', '#82d882'],
            pedidos: ['#ffc107', '#28a745', '#dc3545', '#0d6efd', '#17a2b8']
        };

        // Gráfico de Productos con Stock Mínimo
        const ctxStockMinimo = document.getElementById('chartStockMinimo').getContext('2d');
        new Chart(ctxStockMinimo, {
            type: 'pie',
            data: {
                labels: [
                    <?php foreach($stats_stock['minimo'] as $prod): ?>
                        '<?= addslashes(substr($prod['nombre'], 0, 25)) ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach($stats_stock['minimo'] as $prod): ?>
                            <?= $prod['stock'] ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: colores.minimo,
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
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                return label + ': ' + value + ' unidades';
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Productos con Stock Máximo
        const ctxStockMaximo = document.getElementById('chartStockMaximo').getContext('2d');
        const stockMaximoLabels = [
            <?php foreach($stats_stock['maximo'] as $prod): ?>
                '<?= addslashes(substr($prod['nombre'], 0, 25)) ?>',
            <?php endforeach; ?>
        ];
        const stockMaximoData = [
            <?php foreach($stats_stock['maximo'] as $prod): ?>
                <?= $prod['stock'] ?>,
            <?php endforeach; ?>
        ];
        
        new Chart(ctxStockMaximo, {
            type: 'pie',
            data: {
                labels: stockMaximoLabels,
                datasets: [{
                    data: stockMaximoData,
                    backgroundColor: colores.maximo,
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
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                return label + ': ' + value + ' unidades';
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Estados de Pedidos
        const ctxPedidos = document.getElementById('chartPedidos').getContext('2d');
        new Chart(ctxPedidos, {
            type: 'doughnut',
            data: {
                labels: ['Pendientes', 'Aprobados', 'Rechazados', 'Enviados', 'Entregados'],
                datasets: [{
                    data: [
                        <?= $contadores_pedidos['pendiente'] ?>,
                        <?= $contadores_pedidos['aprobado'] ?>,
                        <?= $contadores_pedidos['rechazado'] ?>,
                        <?= $contadores_pedidos['enviado'] ?>,
                        <?= $contadores_pedidos['entregado'] ?>
                    ],
                    backgroundColor: ['#ffc107', '#28a745', '#dc3545', '#0d6efd', '#17a2b8'],
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
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Productos Más Vendidos
        const ctxMasVendidos = document.getElementById('chartMasVendidos').getContext('2d');
        const masVendidosLabels = [
            <?php foreach($productos_vendidos['mas_vendidos'] as $prod): ?>
                '<?= addslashes(substr($prod['nombre'], 0, 25)) ?>',
            <?php endforeach; ?>
        ];
        const masVendidosData = [
            <?php foreach($productos_vendidos['mas_vendidos'] as $prod): ?>
                <?= $prod['total_vendido'] ?>,
            <?php endforeach; ?>
        ];
        
        new Chart(ctxMasVendidos, {
            type: 'pie',
            data: {
                labels: masVendidosLabels,
                datasets: [{
                    data: masVendidosData,
                    backgroundColor: ['#0d6efd', '#4169e1', '#1e90ff'],
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
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                return label + ': ' + value + ' unidades';
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Productos Menos Vendidos
        <?php if (!empty($productos_vendidos['menos_vendidos'])): ?>
        const ctxMenosVendidos = document.getElementById('chartMenosVendidos').getContext('2d');
        const menosVendidosLabels = [
            <?php 
            foreach($productos_vendidos['menos_vendidos'] as $prod) {
                echo "'" . addslashes(substr($prod['nombre'], 0, 25)) . "',";
            }
            ?>
        ];
        const menosVendidosData = [
            <?php 
            foreach($productos_vendidos['menos_vendidos'] as $prod) {
                echo intval($prod['total_vendido']) . ",";
            }
            ?>
        ];
        
        new Chart(ctxMenosVendidos, {
            type: 'pie',
            data: {
                labels: menosVendidosLabels,
                datasets: [{
                    data: menosVendidosData,
                    backgroundColor: ['#ffc107', '#fd7e14', '#ffca2c'],
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
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                return label + ': ' + value + ' unidades vendidas';
                            }
                        }
                    }
                }
            }
        });
        <?php else: ?>
        document.getElementById('chartMenosVendidos').parentElement.innerHTML = '<p class="text-center text-muted mt-5">No hay ventas de productos aún</p>';
        <?php endif; ?>


        // Gráfico de Categorías Más Vendidas
        const ctxCategorias = document.getElementById('chartCategorias').getContext('2d');
        new Chart(ctxCategorias, {
            type: 'pie',
            data: {
                labels: [
                    <?php foreach($categorias_vendidas as $cat): ?>
                        '<?= addslashes($cat['nombre']) ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach($categorias_vendidas as $cat): ?>
                            <?= $cat['total_vendido'] ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: ['#17a2b8', '#5bc0de', '#87ceeb'],
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
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                return label + ': ' + value + ' unidades';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>