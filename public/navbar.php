<?php
// public/navbar.php
require_once '../includes/init.php';
?>
<style>
    .navbar-custom {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        box-shadow: 0 4px 20px rgba(0, 123, 255, 0.3);
        padding: 0.75rem 0;
        position: sticky;
        top: 0;
        z-index: 1030;
        backdrop-filter: blur(10px);
    }

    .navbar-custom .navbar-brand {
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        font-size: 1.8rem;
        color: white !important;
        transition: all 0.3s ease;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .navbar-custom .navbar-brand:hover {
        transform: scale(1.05);
    }

    .navbar-custom .navbar-brand img {
        filter: brightness(0) invert(1);
        transition: all 0.3s ease;
    }

    .navbar-custom .nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
        font-weight: 500;
        padding: 0.5rem 1rem !important;
        margin: 0 0.25rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        position: relative;
    }

    .navbar-custom .nav-link:hover {
        color: white !important;
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }

    .navbar-custom .nav-link.active {
        background: rgba(255, 255, 255, 0.2);
        color: white !important;
    }

    .navbar-custom .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%) scaleX(0);
        width: 80%;
        height: 2px;
        background: white;
        transition: transform 0.3s ease;
    }

    .navbar-custom .nav-link:hover::after {
        transform: translateX(-50%) scaleX(1);
    }

    .navbar-custom .dropdown-menu {
        background: white;
        border: none;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 123, 255, 0.25);
        margin-top: 0.5rem;
        padding: 0.5rem;
        min-width: 220px;
    }

    .navbar-custom .dropdown-item {
        border-radius: 8px;
        padding: 0.6rem 1rem;
        font-weight: 500;
        color: #495057;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .navbar-custom .dropdown-item:hover {
        background: linear-gradient(135deg, rgba(0, 123, 255, 0.1) 0%, rgba(0, 86, 179, 0.1) 100%);
        color: #007bff;
        transform: translateX(5px);
    }

    .navbar-custom .dropdown-item.text-danger:hover {
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(200, 35, 51, 0.1) 100%);
        color: #dc3545;
    }

    .navbar-custom .dropdown-toggle::after {
        margin-left: 0.5rem;
        transition: transform 0.3s ease;
    }

    .navbar-custom .dropdown-toggle[aria-expanded="true"]::after {
        transform: rotate(180deg);
    }

    .navbar-custom .navbar-toggler {
        border: 2px solid rgba(255, 255, 255, 0.5);
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
    }

    .navbar-custom .navbar-toggler:focus {
        box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
    }

    .navbar-custom .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.5rem;
        font-size: 1.1rem;
    }

    @media (max-width: 991px) {
        .navbar-custom .nav-link {
            margin: 0.25rem 0;
        }

        .navbar-custom .dropdown-menu {
            background: rgba(255, 255, 255, 0.95);
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="../assets/img/logo.png" alt="Logo" height="70" class="me-2">
            <span>Tercer Cielo</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="catalogo.php">
                        <i class="bi bi-grid"></i> Catálogo
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="nosotros.php">
                        <i class="bi bi-info-circle"></i> Nosotros
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contacto.php">
                        <i class="bi bi-envelope"></i> Contáctanos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="preguntas-frecuentes.php">
                        <i class="bi bi-question-circle"></i> FAQ
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="user-avatar">
                                <i class="bi bi-person-fill"></i>
                            </span>
                            <?= htmlspecialchars($_SESSION['user_nombre'] ?? $_SESSION['nombre'] ?? 'Usuario') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person-circle"></i> Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="mis_pedidos.php"><i class="bi bi-bag-check"></i> Mis Pedidos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php" style="background: rgba(255, 255, 255, 0.15); margin-right: 0.5rem;">
                            <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php" style="background: white; color: #007bff !important; font-weight: 600;">
                            <i class="bi bi-person-plus"></i> Registrarse
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>