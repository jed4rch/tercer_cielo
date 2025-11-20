<?php
require_once '../includes/init.php';

// === REDIRECCIÓN SI ES ADMIN ===
if (isset($_SESSION['user_id']) && $_SESSION['rol'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

$titulo = 'Nosotros';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?> - Tercer Cielo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/favicon/favicon.png">
    <style>
        .value-icon {
            width: 80px;
            height: 80px;
            background: #1a5d1a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }
    </style>
</head>
<?php
$titulo = 'Nosotros - Tercer Cielo';
include 'cabecera_unificada.php';
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    .hero-nosotros {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 4rem 0;
        text-align: center;
        border-radius: 0 0 30px 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .hero-nosotros h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .hero-nosotros p {
        font-size: 1.2rem;
        max-width: 700px;
        margin: 0 auto;
        opacity: 0.95;
    }

    .section-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #007bff;
        margin-bottom: 3rem;
        position: relative;
        display: inline-block;
    }

    .section-title::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border-radius: 2px;
    }

    .card-nosotros {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        background: white;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        height: 100%;
    }

    .card-nosotros:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    }

    .card-nosotros .card-body {
        padding: 2.5rem;
    }

    .value-icon {
        width: 90px;
        height: 90px;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 2.5rem;
        margin: 0 auto 1.5rem;
        box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
    }

    .card-nosotros h3 {
        color: #007bff;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .valor-item {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        height: 100%;
    }

    .valor-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    .valor-item i {
        font-size: 3rem;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 1rem;
    }

    .valor-item h5 {
        color: #495057;
        font-weight: 700;
        margin-bottom: 0.75rem;
    }

    .beneficio-card {
        background: white;
        padding: 2.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        text-align: center;
        height: 100%;
    }

    .beneficio-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    .beneficio-card i {
        font-size: 3.5rem;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 1rem;
    }

    .beneficio-card h5 {
        color: #007bff;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .tienda-showcase {
        background: white;
        padding: 3rem;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin: 4rem 0;
    }

    .tienda-img {
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        width: 100%;
        height: auto;
    }

    .stats-section {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 3rem 0;
        border-radius: 20px;
        margin: 3rem 0;
    }

    .stat-item {
        text-align: center;
        padding: 1.5rem;
    }

    .stat-number {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    @media (max-width: 768px) {
        .hero-nosotros h1 {
            font-size: 2.5rem;
        }
        
        .hero-nosotros p {
            font-size: 1.1rem;
        }
        
        .section-title {
            font-size: 2rem;
        }
    }
</style>

    <!-- Hero Section -->
    <div class="hero-nosotros">
        <div class="container">
            <h1><i class="bi bi-building me-3"></i>Conoce Tercer Cielo</h1>
            <p>Tu aliado de confianza en ferretería desde 2010. Comprometidos con la calidad, el servicio y tu satisfacción.</p>
        </div>
    </div>

    <div class="container my-5">
        <!-- Imagen de la Tienda -->
        <div class="tienda-showcase">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="../assets/img/frontis.png" alt="Ferretería Tercer Cielo" class="tienda-img">
                </div>
                <div class="col-lg-6">
                    <h2 class="mb-4" style="color: #007bff; font-weight: 700;">Nuestra Historia</h2>
                    <p class="lead mb-3">Desde <strong>2010</strong>, Ferretería Tercer Cielo se ha consolidado como un referente en el sector ferretero, ofreciendo productos de alta calidad y un servicio excepcional.</p>
                    <p class="text-muted mb-3">Con más de 13 años de experiencia, nos hemos ganado la confianza de miles de clientes en todo el Perú, desde constructores profesionales hasta familias que buscan mejorar sus hogares.</p>
                    <p class="text-muted">Nuestra misión es simple: proporcionar las mejores herramientas y materiales para que tus proyectos sean un éxito rotundo.</p>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="stats-section">
            <div class="container">
                <div class="row">
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <div class="stat-number"><i class="bi bi-calendar-check"></i> 13+</div>
                            <div class="stat-label">Años de Experiencia</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <div class="stat-number"><i class="bi bi-people"></i> 500+</div>
                            <div class="stat-label">Clientes Satisfechos</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <div class="stat-number"><i class="bi bi-box-seam"></i> 100+</div>
                            <div class="stat-label">Productos Disponibles</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <div class="stat-number"><i class="bi bi-truck"></i> 1000+</div>
                            <div class="stat-label">Entregas Realizadas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Misión y Visión -->
        <div class="text-center mb-5">
            <h2 class="section-title">Misión & Visión</h2>
        </div>
        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="card-nosotros">
                    <div class="card-body text-center">
                        <div class="value-icon">
                            <i class="bi bi-bullseye"></i>
                        </div>
                        <h3>Nuestra Misión</h3>
                        <p class="text-muted">Proporcionar productos de ferretería de alta calidad y un servicio excepcional, contribuyendo al éxito de los proyectos de nuestros clientes a través de soluciones innovadoras, asesoramiento experto y un compromiso inquebrantable con la excelencia.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card-nosotros">
                    <div class="card-body text-center">
                        <div class="value-icon">
                            <i class="bi bi-eye"></i>
                        </div>
                        <h3>Nuestra Visión</h3>
                        <p class="text-muted">Ser la ferretería líder en el mercado peruano, reconocida por nuestra excelencia en servicio, calidad de productos y compromiso con el desarrollo sostenible de la comunidad. Aspiramos a ser la primera opción para profesionales y familias en todo el país.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Valores -->
        <div class="text-center mb-5">
            <h2 class="section-title">Nuestros Valores</h2>
        </div>
        <div class="row g-4 mb-5">
            <div class="col-lg-3 col-md-6">
                <div class="valor-item text-center">
                    <i class="bi bi-shield-check"></i>
                    <h5>Integridad</h5>
                    <p class="text-muted mb-0">Actuamos con honestidad, transparencia y ética en todas nuestras operaciones.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="valor-item text-center">
                    <i class="bi bi-star"></i>
                    <h5>Excelencia</h5>
                    <p class="text-muted mb-0">Buscamos la mejora continua y la calidad superior en todo lo que hacemos.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="valor-item text-center">
                    <i class="bi bi-people"></i>
                    <h5>Servicio al Cliente</h5>
                    <p class="text-muted mb-0">Nos dedicamos a superar las expectativas y necesidades de nuestros clientes.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="valor-item text-center">
                    <i class="bi bi-heart"></i>
                    <h5>Compromiso</h5>
                    <p class="text-muted mb-0">Cumplimos nuestras promesas con responsabilidad y dedicación total.</p>
                </div>
            </div>
        </div>

        <!-- Por qué elegirnos -->
        <div class="text-center mb-5">
            <h2 class="section-title">¿Por Qué Elegirnos?</h2>
        </div>
        <div class="row g-4 mb-5">
            <div class="col-lg-4 col-md-6">
                <div class="beneficio-card">
                    <i class="bi bi-truck"></i>
                    <h5>Envío Rápido</h5>
                    <p class="text-muted mb-0">Entrega a todo el Perú en 24-48 horas. Tu pedido llegará cuando lo necesites.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="beneficio-card">
                    <i class="bi bi-award"></i>
                    <h5>Calidad Garantizada</h5>
                    <p class="text-muted mb-0">Trabajamos con las mejores marcas y garantizamos la autenticidad de todos nuestros productos.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="beneficio-card">
                    <i class="bi bi-headset"></i>
                    <h5>Soporte 24/7</h5>
                    <p class="text-muted mb-0">Nuestro equipo está siempre disponible para asesorarte y resolver tus dudas.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="beneficio-card">
                    <i class="bi bi-cash-coin"></i>
                    <h5>Mejores Precios</h5>
                    <p class="text-muted mb-0">Precios competitivos sin comprometer la calidad de nuestros productos.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="beneficio-card">
                    <i class="bi bi-box-seam"></i>
                    <h5>Amplio Stock</h5>
                    <p class="text-muted mb-0">Miles de productos disponibles para entrega inmediata en todo momento.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="beneficio-card">
                    <i class="bi bi-patch-check"></i>
                    <h5>Experiencia Comprobada</h5>
                    <p class="text-muted mb-0">Más de 13 años respaldando proyectos exitosos en todo el país.</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>