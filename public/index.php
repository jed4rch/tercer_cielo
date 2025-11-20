<?php
require_once '../includes/init.php';
require_once '../includes/func_productos.php';
require_once '../includes/func_carrito.php';
require_once '../includes/func_banners.php';

// === REDIRECCIÓN SI ES ADMIN ===
if (isset($_SESSION['user_id']) && $_SESSION['rol'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

// === OBTENER BANNERS HABILITADOS ===
$banners = obtenerBanners(true);

// === CATEGORÍAS DESTACADAS (MÁS VENDIDAS CON PRODUCTOS EN STOCK) ===
$categorias = getPdo()->query("
    SELECT 
        c.id, 
        c.nombre, 
        c.imagen, 
        COUNT(DISTINCT p.id) as total_productos,
        COALESCE(SUM(pd.cantidad), 0) as total_vendido
    FROM categorias c
    LEFT JOIN productos p ON c.id = p.id_categoria AND p.stock > 0 AND p.habilitado = 1
    LEFT JOIN pedido_detalles pd ON p.id = pd.producto_id
    LEFT JOIN pedidos pe ON pd.pedido_id = pe.id AND pe.estado = 'entregado'
    WHERE c.habilitado = 1
    GROUP BY c.id, c.nombre, c.imagen
    HAVING total_productos > 0
    ORDER BY total_vendido DESC, c.nombre
    LIMIT 3
")->fetchAll();

// === PRODUCTOS DESTACADOS (3) ===
$destacados = getPdo()->query("
    SELECT p.*, c.nombre as categoria 
    FROM productos p 
    LEFT JOIN categorias c ON p.id_categoria = c.id 
    WHERE p.stock > 0 AND p.habilitado = 1 AND (c.habilitado = 1 OR c.habilitado IS NULL)
    ORDER BY p.id DESC 
    LIMIT 3
")->fetchAll();
?>

<?php
$titulo = 'Ferretería Tercer Cielo - Tu aliado en construcción';
include 'cabecera_unificada.php';
?>

<style>
    .hero {
        background: linear-gradient(135deg, rgba(0, 123, 255, 0.95) 0%, rgba(0, 86, 179, 0.95) 100%), url('../assets/img/frontis.png');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        color: white;
        padding: 140px 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
        animation: pulse 15s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 0.6; }
    }

    .hero .container {
        position: relative;
        z-index: 1;
    }

    .hero h1 {
        font-family: 'Montserrat', sans-serif;
        font-size: 3.8rem;
        font-weight: 800;
        margin-bottom: 1rem;
        text-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        letter-spacing: -1px;
    }

    .hero p {
        font-size: 1.4rem;
        max-width: 700px;
        margin: 0 auto 2.5rem;
        opacity: 0.95;
        font-weight: 400;
    }

    .hero .btn {
        padding: 0.875rem 2.5rem;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .hero .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }

    .section-title {
        font-family: 'Montserrat', sans-serif;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        position: relative;
        display: inline-block;
        margin-bottom: 1rem;
        font-weight: 700;
        font-size: 2.5rem;
    }

    .section-title:after {
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

    .producto-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 123, 255, 0.1);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 123, 255, 0.1);
        background: white;
    }

    .producto-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 35px rgba(0, 123, 255, 0.25);
        border-color: rgba(0, 123, 255, 0.3);
    }

    .producto-img {
        height: 220px;
        object-fit: contain;
        background: #f8f9fa;
        padding: 10px;
        transition: transform 0.3s ease;
    }

    .producto-card:hover .producto-img {
        transform: scale(1.05);
    }

    .badge-stock {
        position: absolute;
        top: 10px;
        right: 10px;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        font-size: 0.75rem;
        padding: 6px 12px;
        border-radius: 20px;
        z-index: 10;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
    }

    .precio {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .categoria-card,
    .producto-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 123, 255, 0.1);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 123, 255, 0.1);
        background: white;
    }

    .categoria-card:hover,
    .producto-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 35px rgba(0, 123, 255, 0.25);
        border-color: rgba(0, 123, 255, 0.3);
    }

    .categoria-img {
        height: 200px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .categoria-card:hover .categoria-img {
        transform: scale(1.1);
    }

    .badge-productos {
        position: absolute;
        top: 10px;
        right: 10px;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        font-size: 0.75rem;
        padding: 6px 12px;
        border-radius: 20px;
        z-index: 10;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
    }

    footer {
        background: var(--dark);
        color: #ccc;
        margin-top: 4rem;
    }

    .social-icons a {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 5px;
        transition: all 0.3s ease;
    }

    .social-icons a:hover {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        transform: translateY(-3px);
        box-shadow: 0 4px 10px rgba(0, 123, 255, 0.4);
    }

    /* ESTILOS CARRUSEL DE BANNERS */
    .banner-carousel {
        position: relative;
        width: 100%;
        height: 640px;
        overflow: hidden;
    }

    .banner-carousel .carousel-item {
        height: 640px;
        transition: transform 1.2s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .banner-carousel .carousel-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: opacity 0.8s ease-in-out;
    }

    .banner-carousel .carousel-control-prev,
    .banner-carousel .carousel-control-next {
        width: 80px;
        opacity: 0;
        transition: all 0.3s ease;
    }

    .banner-carousel:hover .carousel-control-prev,
    .banner-carousel:hover .carousel-control-next {
        opacity: 1;
    }

    .banner-carousel .carousel-control-prev-icon,
    .banner-carousel .carousel-control-next-icon {
        background-color: rgba(0, 123, 255, 0.8);
        border-radius: 50%;
        padding: 20px;
        backdrop-filter: blur(10px);
    }

    .banner-carousel .carousel-indicators button {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.5);
        border: 2px solid white;
        transition: all 0.3s ease;
    }

    .banner-carousel .carousel-indicators button.active {
        background-color: #007bff;
        transform: scale(1.2);
    }

    @media (max-width: 768px) {
        .banner-carousel,
        .banner-carousel .carousel-item {
            height: 300px;
        }
    }
</style>

    <?php if (!empty($banners)): ?>
        <!-- CARRUSEL DE BANNERS -->
        <div id="bannersCarousel" class="carousel slide banner-carousel" data-bs-ride="carousel" data-bs-interval="5000">
            <!-- Indicadores -->
            <div class="carousel-indicators">
                <?php foreach ($banners as $index => $banner): ?>
                    <button type="button" 
                            data-bs-target="#bannersCarousel" 
                            data-bs-slide-to="<?= $index ?>" 
                            <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?>
                            aria-label="Banner <?= $index + 1 ?>">
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Slides -->
            <div class="carousel-inner">
                <?php foreach ($banners as $index => $banner): 
                    $url_enlace = obtenerUrlEnlaceBanner($banner['tipo_enlace'], $banner['enlace_id']);
                ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <?php if ($url_enlace): ?>
                            <a href="<?= htmlspecialchars($url_enlace) ?>">
                                <img src="<?= htmlspecialchars($banner['imagen']) ?>" 
                                     alt="<?= htmlspecialchars($banner['titulo'] ?? 'Banner') ?>"
                                     class="d-block w-100">
                            </a>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars($banner['imagen']) ?>" 
                                 alt="<?= htmlspecialchars($banner['titulo'] ?? 'Banner') ?>"
                                 class="d-block w-100">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Controles -->
            <button class="carousel-control-prev" type="button" data-bs-target="#bannersCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#bannersCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
        </div>
    <?php else: ?>
        <!-- HERO (Mostrar solo si no hay banners) -->
        <section class="hero">
            <div class="container">
                <h1>FERRETERÍA TERCER CIELO</h1>
                <p class="lead">Tu aliado confiable en herramientas, materiales de construcción y soluciones para el hogar</p>
                <div>
                    <a href="catalogo.php" class="btn btn-lg me-3" style="background: white; color: #007bff; font-weight: 600; border: none;">Explorar Catálogo</a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="btn btn-lg" style="background: rgba(255, 255, 255, 0.15); color: white; font-weight: 600; border: 2px solid white; backdrop-filter: blur(10px);">Unete Gratis</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <!-- CATEGORÍAS DESTACADAS -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Categorías Destacadas</h2>
                <p class="text-muted">Explora nuestras secciones más populares</p>
            </div>
            <div class="row g-4">
                <?php foreach ($categorias as $cat): ?>
                    <div class="col-md-6 col-lg-4">
                        <a href="catalogo.php?categoria=<?= $cat['id'] ?>" class="text-decoration-none">
                            <div class="categoria-card h-100 position-relative">
                                <span class="badge-productos">
                                    <?= $cat['total_productos'] ?>
                                    producto<?= $cat['total_productos'] !== 1 ? 's' : '' ?>
                                </span>

                                <?php if (!empty($cat['imagen'])): ?>
                                    <img src="<?= htmlspecialchars($cat['imagen']) ?>"
                                        class="card-img-top categoria-img"
                                        alt="<?= htmlspecialchars($cat['nombre']) ?>"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="bg-light align-items-center justify-content-center" style="height:200px; display:none;">
                                        <i class="bi bi-image fs-1 text-muted"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height:200px;">
                                        <i class="bi bi-image fs-1 text-muted"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column p-4 text-center">
                                    <h5 class="card-title mb-2 text-dark">
                                        <?= htmlspecialchars($cat['nombre']) ?>
                                    </h5>
                                    <p class="card-text text-muted small flex-grow-1">
                                        <?= !empty($cat['descripcion'])
                                            ? (strlen($cat['descripcion']) > 80
                                                ? substr(htmlspecialchars($cat['descripcion']), 0, 80) . '...'
                                                : htmlspecialchars($cat['descripcion']))
                                            : 'Explora esta categoría' ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- PRODUCTOS DESTACADOS -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Nuevos Productos</h2>
                <p class="text-muted">Lo más nuevo y recomendado</p>
            </div>
            <div class="row g-4">
                <?php foreach ($destacados as $p): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="producto-card h-100 position-relative" data-producto-id="<?= $p['id'] ?>" data-stock-total="<?= $p['stock'] ?>">
                            <?php if ($p['stock'] > 0): ?>
                                <span class="badge-stock">
                                    <i class="bi bi-check2"></i> En stock
                                </span>
                            <?php endif; ?>

                            <?php
                            $img_src = !empty($p['imagen'])
                                ? $p['imagen']
                                : '../assets/img/default-product.jpg';
                            ?>
                            <a href="producto.php?id=<?= $p['id'] ?>" class="text-decoration-none">
                                <img src="<?= htmlspecialchars($img_src) ?>"
                                    class="producto-img w-100"
                                    alt="<?= htmlspecialchars($p['nombre']) ?>"
                                    onerror="this.src='../assets/img/default-product.jpg';">
                            </a>

                            <div class="card-body d-flex flex-column p-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); font-weight: 600; padding: 0.5rem 1rem; border-radius: 8px;"><i class="bi bi-tag"></i> <?= htmlspecialchars($p['categoria'] ?? 'Sin categoría') ?></span>
                                </div>
                                <h5 class="card-title mb-2">
                                    <a href="producto.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($p['nombre']) ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted small flex-grow-1">
                                    <?= !empty($p['descripcion'])
                                        ? (strlen($p['descripcion']) > 80
                                            ? substr(htmlspecialchars($p['descripcion']), 0, 80) . '...'
                                            : htmlspecialchars($p['descripcion']))
                                        : 'Sin descripción' ?>
                                </p>
                                <div class="mt-auto">
                                    <?php if ($p['precio_anterior'] && $p['porcentaje_descuento']): ?>
                                        <div class="mb-1">
                                            <span class="text-muted text-decoration-line-through" style="font-size: 0.85rem;">S/ <?= number_format($p['precio_anterior'], 2) ?></span>
                                            <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">-<?= $p['porcentaje_descuento'] ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                    <p class="precio mb-1">S/ <?= number_format($p['precio'], 2) ?></p>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); font-weight: 500;">
                                            <i class="bi bi-box-seam"></i> Stock: <?= $p['stock'] ?>
                                        </span>
                                    </div>
                                    <form class="add-form d-flex gap-2 mb-2" action="agregar_carrito.php">
                                        <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
                                        <input type="number" name="cantidad" value="1" min="1" max="<?= $p['stock'] ?>"
                                            class="form-control form-control-sm cantidad-input" style="width:70px; border: 2px solid #007bff;" required>
                                        <button type="submit" class="btn flex-grow-1 btn-agregar" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; font-weight: 600; border: none; box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);" <?= $p['stock'] <= 0 ? 'disabled' : '' ?>>
                                            <span class="btn-text">Agregar</span>
                                            <span class="spinner-border spinner-border-sm d-none"></span>
                                        </button>
                                    </form>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-check-circle" style="color: #007bff;"></i> 
                                            Disponible: <strong class="disponible-count" style="color: #007bff;"><?= $p['stock'] ?></strong> / <?= $p['stock'] ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($destacados)): ?>
                    <div class="col-12 text-center text-muted">
                        <p>No hay productos destacados en este momento.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="text-center mt-5">
                <a href="catalogo.php" class="btn btn-lg" style="color: #007bff; border: 2px solid #007bff; font-weight: 600; padding: 0.875rem 2.5rem; border-radius: 50px; transition: all 0.3s ease;" onmouseover="this.style.background='linear-gradient(135deg, #007bff 0%, #0056b3 100%)'; this.style.color='white'; this.style.borderColor='transparent'; this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 20px rgba(0, 123, 255, 0.4)';" onmouseout="this.style.background='transparent'; this.style.color='#007bff'; this.style.borderColor='#007bff'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    Ver todos los productos
                </a>
            </div>
        </div>
    </section>
    <?php include 'footer.php'; ?>
</body>

</html>