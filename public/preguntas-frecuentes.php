<?php
require_once '../includes/init.php';

// === REDIRECCIÓN SI ES ADMIN ===
if (isset($_SESSION['user_id']) && $_SESSION['rol'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

$titulo = 'Preguntas Frecuentes';
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
        .accordion-button:not(.collapsed) {
            background-color: #e7f1ff;
            color: #1a5d1a;
        }
        .accordion-button:focus {
            border-color: #1a5d1a;
            box-shadow: 0 0 0 0.25rem rgba(26, 93, 26, 0.25);
        }
    </style>
</head>
<?php
$titulo = 'Preguntas Frecuentes - Tercer Cielo';
include 'cabecera_unificada.php';
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    .page-header-faq {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 4rem 0;
        text-align: center;
        border-radius: 0 0 30px 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        margin-bottom: 3rem;
    }

    .page-header-faq h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .page-header-faq p {
        font-size: 1.2rem;
        max-width: 700px;
        margin: 0 auto;
        opacity: 0.95;
    }

    .category-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        height: 100%;
        text-align: center;
        border: 3px solid transparent;
    }

    .category-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }

    .category-card.primary {
        border-color: #007bff;
    }

    .category-card.primary:hover {
        background: linear-gradient(135deg, rgba(0, 123, 255, 0.05) 0%, rgba(0, 86, 179, 0.05) 100%);
    }

    .category-card.success {
        border-color: #28a745;
    }

    .category-card.success:hover {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.05) 0%, rgba(92, 184, 92, 0.05) 100%);
    }

    .category-card.info {
        border-color: #17a2b8;
    }

    .category-card.info:hover {
        background: linear-gradient(135deg, rgba(23, 162, 184, 0.05) 0%, rgba(91, 192, 222, 0.05) 100%);
    }

    .category-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
    }

    .category-card.primary .category-icon {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
    }

    .category-card.success .category-icon {
        background: linear-gradient(135deg, #28a745 0%, #5cb85c 100%);
        color: white;
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    }

    .category-card.info .category-icon {
        background: linear-gradient(135deg, #17a2b8 0%, #5bc0de 100%);
        color: white;
        box-shadow: 0 5px 15px rgba(23, 162, 184, 0.3);
    }

    .category-card h5 {
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .section-header {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        margin: 3rem 0 1.5rem 0;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .section-header h4 {
        margin: 0;
        color: #007bff;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .accordion-item {
        border: none;
        margin-bottom: 1rem;
        border-radius: 15px !important;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        background: white;
    }

    .accordion-button {
        font-weight: 600;
        padding: 1.25rem 1.5rem;
        border-radius: 15px !important;
        background: white;
        color: #495057;
        font-size: 1.05rem;
    }

    .accordion-button:not(.collapsed) {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        box-shadow: none;
    }

    .accordion-button:focus {
        border-color: transparent;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
    }

    .accordion-button::after {
        filter: brightness(0) invert(0);
    }

    .accordion-button:not(.collapsed)::after {
        filter: brightness(0) invert(1);
    }

    .accordion-body {
        padding: 1.5rem;
        background: #f8f9fa;
    }

    .accordion-body ol li,
    .accordion-body ul li {
        margin-bottom: 0.5rem;
    }

    .alert-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        border: none;
        border-left: 4px solid #17a2b8;
    }

    .alert-warning {
        background: linear-gradient(135deg, #fff3cd 0%, #ffe8a1 100%);
        border: none;
        border-left: 4px solid #ffc107;
    }

    .badge {
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .page-header-faq h1 {
            font-size: 2rem;
        }
        
        .page-header-faq p {
            font-size: 1rem;
        }
    }
</style>

    <!-- Header -->
    <div class="page-header-faq">
        <div class="container">
            <h1>
                <img src="../assets/img/logo.png" alt="Logo Tercer Cielo" height="60" class="me-3" style="filter: brightness(0) invert(1);">
                Preguntas Frecuentes
            </h1>
            <p>Encuentra respuestas rápidas a las dudas más comunes sobre compras, envíos, pagos y más.</p>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Categorías de FAQ -->
        <div class="row g-4 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="category-card primary">
                    <div class="category-icon">
                        <i class="bi bi-cart-check-fill"></i>
                    </div>
                    <h5>Compras</h5>
                    <p class="text-muted mb-0">Proceso de compra y carrito</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="category-card success">
                    <div class="category-icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h5>Envíos</h5>
                    <p class="text-muted mb-0">Modalidades y tiempos</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="category-card info">
                    <div class="category-icon">
                        <i class="bi bi-credit-card-fill"></i>
                    </div>
                    <h5>Pagos</h5>
                    <p class="text-muted mb-0">Métodos y seguridad</p>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="accordion" id="accordionFAQ">
                    <!-- Sección: Compras -->
                    <div class="section-header">
                        <h4><i class="bi bi-cart-check-fill"></i>Sobre las Compras</h4>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                <i class="bi bi-1-circle me-2"></i> ¿Cómo realizo una compra en Tercer Cielo?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                <strong>Es muy sencillo:</strong>
                                <ol class="mt-2">
                                    <li>Navega por nuestro catálogo y selecciona los productos que deseas</li>
                                    <li>Haz clic en "Agregar al carrito" en cada producto</li>
                                    <li>Revisa tu carrito haciendo clic en el ícono del carrito</li>
                                    <li>Completa el proceso de checkout seleccionando tu método de envío y pago</li>
                                    <li>Recibirás un correo de confirmación con los detalles de tu pedido</li>
                                </ol>
                                <div class="alert alert-info mt-3">
                                    <i class="bi bi-info-circle"></i> <strong>Tip:</strong> Puedes seguir el estado de tu pedido desde tu perfil en "Mis Pedidos"
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStock">
                                <i class="bi bi-box-seam me-2"></i> ¿Cómo sé si un producto tiene stock?
                            </button>
                        </h2>
                        <div id="collapseStock" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                En cada producto verás un indicador de stock:
                                <ul class="mt-2">
                                    <li><span class="badge bg-success">En stock</span> - Disponible para compra inmediata</li>
                                    <li><span class="badge bg-danger">Agotado</span> - No disponible temporalmente</li>
                                </ul>
                                <p class="mb-0">Además, al agregar al carrito te mostramos las unidades disponibles en tiempo real.</p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePedido">
                                <i class="bi bi-clock-history me-2"></i> ¿Puedo modificar o cancelar mi pedido?
                            </button>
                        </h2>
                        <div id="collapsePedido" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                Puedes cancelar tu pedido <strong>solo si está en estado "Pendiente"</strong> antes de ser aprobado. Una vez aprobado y en preparación, no es posible cancelarlo.
                                <div class="alert alert-warning mt-3">
                                    <i class="bi bi-exclamation-triangle"></i> Para solicitar una cancelación, contáctanos inmediatamente al <strong>968 045 028</strong> o a <strong>tercercielo.boutique@gmail.com</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Envíos -->
                    <div class="section-header">
                        <h4><i class="bi bi-truck"></i>Sobre los Envíos</h4>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                <i class="bi bi-geo-alt me-2"></i> ¿Qué modalidades de envío tienen?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                Ofrecemos <strong>3 modalidades</strong> para que elijas la que mejor te convenga:
                                <div class="row mt-3 g-3">
                                    <div class="col-md-4">
                                        <div class="card border-primary">
                                            <div class="card-body text-center">
                                                <i class="bi bi-house-door fs-2 text-primary"></i>
                                                <h6 class="mt-2">🏪 Recojo en Tienda</h6>
                                                <p class="small text-muted mb-0">Gratis - Recoge en nuestro local</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-info">
                                            <div class="card-body text-center">
                                                <i class="bi bi-building fs-2 text-info"></i>
                                                <h6 class="mt-2">📦 Recojo en Agencia</h6>
                                                <p class="small text-muted mb-0">Olva Courier o Shalom</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-success">
                                            <div class="card-body text-center">
                                                <i class="bi bi-truck fs-2 text-success"></i>
                                                <h6 class="mt-2">🚚 Envío a Domicilio</h6>
                                                <p class="small text-muted mb-0">Entrega en tu dirección</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTime">
                                <i class="bi bi-alarm me-2"></i> ¿Cuánto demora el envío?
                            </button>
                        </h2>
                        <div id="collapseTime" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                Los tiempos varían según la modalidad y destino:
                                <table class="table table-bordered mt-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Modalidad</th>
                                            <th>Tiempo estimado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Recojo en Tienda</strong></td>
                                            <td>Inmediato (una vez aprobado)</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Agencia - Piura</strong></td>
                                            <td>1-2 días hábiles</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Agencia - Otras ciudades</strong></td>
                                            <td>3-5 días hábiles</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Domicilio - Piura</strong></td>
                                            <td>1-3 días hábiles</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Domicilio - Lima</strong></td>
                                            <td>3-5 días hábiles</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Domicilio - Provincias</strong></td>
                                            <td>5-7 días hábiles</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p class="mb-0"><i class="bi bi-envelope"></i> Recibirás notificaciones por correo en cada etapa del envío.</p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCosto">
                                <i class="bi bi-cash-coin me-2"></i> ¿Cuánto cuesta el envío?
                            </button>
                        </h2>
                        <div id="collapseCosto" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                <ul>
                                    <li><strong>Recojo en Tienda:</strong> GRATIS</li>
                                    <li><strong>Recojo en Agencia:</strong> El costo se calcula según la agencia y destino</li>
                                    <li><strong>Envío a Domicilio:</strong> El costo se calcula según la distancia y peso</li>
                                </ul>
                                <p>El costo exacto se muestra antes de confirmar tu pedido en el proceso de checkout.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Pagos -->
                    <div class="section-header">
                        <h4><i class="bi bi-credit-card-fill"></i>Sobre los Pagos</h4>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                <i class="bi bi-wallet2 me-2"></i> ¿Qué métodos de pago aceptan?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                <p class="fw-bold mb-3">Aceptamos las siguientes billeteras digitales:</p>
                                <div class="row mt-3 g-3">
                                    <div class="col-md-6">
                                        <div class="card border-primary">
                                            <div class="card-body text-center">
                                                <i class="bi bi-phone-fill text-primary" style="font-size: 3rem;"></i>
                                                <h5 class="mt-3 mb-2" style="color: #6C2C91; font-weight: 700;">Yape</h5>
                                                <p class="mb-2"><strong>Número:</strong></p>
                                                <p class="h5 text-primary">968 045 028</p>
                                                <small class="text-muted">Pago instantáneo y seguro</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-success">
                                            <div class="card-body text-center">
                                                <i class="bi bi-wallet2 text-success" style="font-size: 3rem;"></i>
                                                <h5 class="mt-3 mb-2" style="color: #00A884; font-weight: 700;">Plin</h5>
                                                <p class="mb-2"><strong>Número:</strong></p>
                                                <p class="h5 text-success">968 045 028</p>
                                                <small class="text-muted">Transferencia rápida y fácil</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-warning mt-4">
                                    <i class="bi bi-exclamation-triangle-fill"></i> <strong>Importante:</strong> Debes subir el comprobante de pago (captura de pantalla) en el proceso de checkout para que tu pedido sea aprobado y procesado.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseComprobante">
                                <i class="bi bi-receipt me-2"></i> ¿Entregan boleta o factura?
                            </button>
                        </h2>
                        <div id="collapseComprobante" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                Sí, emitimos <strong>boletas de venta electrónicas</strong> que recibirás por correo una vez aprobado tu pedido.
                                <p class="mt-2">La boleta incluye:</p>
                                <ul>
                                    <li>Detalle de productos</li>
                                    <li>Precios y totales</li>
                                    <li>Información de envío</li>
                                    <li>Método de pago</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Devoluciones y Garantías -->
                    <h4 class="mb-3 mt-5"><i class="bi bi-arrow-return-left text-warning"></i> Devoluciones y Garantías</h4>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                                <i class="bi bi-arrow-counterclockwise me-2"></i> ¿Cuál es la política de devoluciones?
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                Aceptamos devoluciones dentro de los <strong>7 días</strong> posteriores a la recepción del producto, siempre que:
                                <ul class="mt-2">
                                    <li>✅ El producto esté <strong>sin usar</strong> y en su empaque original</li>
                                    <li>✅ Tengas el <strong>comprobante de compra</strong></li>
                                    <li>✅ El producto <strong>no presente daños</strong> ni señales de uso</li>
                                    <li>✅ Incluya todos los accesorios y manuales originales</li>
                                </ul>
                                <div class="alert alert-info mt-3">
                                    <strong>¿Cómo solicitar una devolución?</strong><br>
                                    Contáctanos vía WhatsApp al <strong>968 045 028</strong> o por correo a <strong>tercercielo.boutique@gmail.com</strong> con tu número de pedido y motivo de la devolución.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive">
                                <i class="bi bi-shield-check me-2"></i> ¿Qué garantía ofrecen los productos?
                            </button>
                        </h2>
                        <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                Todos nuestros productos incluyen:
                                <div class="row mt-3 g-3">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <i class="bi bi-check-circle fs-1 text-success"></i>
                                            <h6 class="mt-2">Garantía de Calidad</h6>
                                            <p class="small text-muted">Productos auténticos y verificados</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <i class="bi bi-calendar-check fs-1 text-primary"></i>
                                            <h6 class="mt-2">Garantía de 7 días</h6>
                                            <p class="small text-muted">Satisfacción garantizada</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <i class="bi bi-headset fs-1 text-info"></i>
                                            <h6 class="mt-2">Soporte Post-venta</h6>
                                            <p class="small text-muted">Te ayudamos después de tu compra</p>
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-3 mb-0"><small class="text-muted">La garantía cubre defectos de fabricación. No aplica para daños por mal uso, caídas o modificaciones.</small></p>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Cuenta y Seguridad -->
                    <h4 class="mb-3 mt-5"><i class="bi bi-person-circle text-danger"></i> Cuenta y Seguridad</h4>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCuenta">
                                <i class="bi bi-person-plus me-2"></i> ¿Necesito crear una cuenta para comprar?
                            </button>
                        </h2>
                        <div id="collapseCuenta" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                Sí, necesitas crear una cuenta para realizar compras. Esto te permite:
                                <ul class="mt-2">
                                    <li>📦 Ver el historial de tus pedidos</li>
                                    <li>🔄 Rastrear el estado de tus envíos</li>
                                    <li>⚡ Realizar compras más rápidas</li>
                                    <li>📧 Recibir ofertas exclusivas</li>
                                </ul>
                                <p class="mb-0">El registro es <strong>rápido y gratuito</strong>.</p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeguridad">
                                <i class="bi bi-lock me-2"></i> ¿Es seguro comprar en Tercer Cielo?
                            </button>
                        </h2>
                        <div id="collapseSeguridad" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                <strong>¡Absolutamente seguro!</strong> Tu información está protegida:
                                <ul class="mt-2">
                                    <li>🔒 Conexión segura (HTTPS)</li>
                                    <li>🛡️ Tus datos personales están encriptados</li>
                                    <li>💳 No almacenamos información de tarjetas</li>
                                    <li>✅ Cumplimos con estándares de seguridad</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contacto adicional -->
                <div class="text-center mt-5">
                    <p class="mb-3">¿No encontraste lo que buscabas?</p>
                    <a href="contacto.php" class="btn btn-primary">Contáctanos</a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>