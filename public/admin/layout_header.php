<?php
// === CONTROL DE ACCESO ADMIN ===
if (!isset($_SESSION['user_id'])) {
    header('Location: /tercer_cielo/public/login.php');
    exit;
}

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /tercer_cielo/public/index.php'); // Redirigir clientes a la tienda
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Panel' ?> - Tercer Cielo</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../../assets/favicon/apple-touch-icon.png">
    <link rel="manifest" href="../../assets/favicon/site.webmanifest">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
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
                <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="bi bi-house-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="usuarios.php" class="<?= basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : '' ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Usuarios</span>
                </a>
            </li>
            <li>
                <a href="categorias.php" class="<?= basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : '' ?>">
                    <i class="bi bi-tags-fill"></i>
                    <span>Categorías</span>
                </a>
            </li>
            <li>
                <a href="productos.php" class="<?= basename($_SERVER['PHP_SELF']) == 'productos.php' ? 'active' : '' ?>">
                    <i class="bi bi-box-seam-fill"></i>
                    <span>Productos</span>
                </a>
            </li>
            <li>
                <a href="pedidos.php" class="<?= basename($_SERVER['PHP_SELF']) == 'pedidos.php' ? 'active' : '' ?>">
                    <i class="bi bi-cart-fill"></i>
                    <span>Pedidos</span>
                </a>
            </li>
            <li>
                <a href="envios.php" class="<?= basename($_SERVER['PHP_SELF']) == 'envios.php' ? 'active' : '' ?>">
                    <i class="bi bi-truck"></i>
                    <span>Envíos</span>
                </a>
            </li>
            <li>
                <a href="banners.php" class="<?= basename($_SERVER['PHP_SELF']) == 'banners.php' || basename($_SERVER['PHP_SELF']) == 'agregar_banner.php' || basename($_SERVER['PHP_SELF']) == 'editar_banner.php' ? 'active' : '' ?>">
                    <i class="bi bi-images"></i>
                    <span>Banners</span>
                </a>
            </li>
            <li>
                <a href="reportes.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : '' ?>">
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
            <h2><?= $pageTitle ?? 'Admin Panel' ?></h2>
            <div class="d-flex align-items-center">
                <span class="text-muted"><i class="bi bi-person-circle me-2"></i><?= $_SESSION['nombre'] ?? 'Admin' ?></span>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
