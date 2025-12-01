<?php
require_once '../includes/init.php';
require_once '../includes/func_productos.php';
require_once '../includes/func_carrito.php';

// === REDIRECCIÓN SI ES ADMIN ===
if (isset($_SESSION['user_id']) && $_SESSION['rol'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

$producto_id = (int)($_GET['id'] ?? 0);

if ($producto_id <= 0) {
    header('Location: catalogo.php');
    exit;
}

$pdo = getPdo();

// Obtener producto con su categoría
$stmt = $pdo->prepare("
    SELECT p.*, c.nombre as categoria_nombre
    FROM productos p 
    LEFT JOIN categorias c ON p.id_categoria = c.id 
    WHERE p.id = ?
");
$stmt->execute([$producto_id]);
$producto = $stmt->fetch();

if (!$producto) {
    header('Location: catalogo.php');
    exit;
}

// Obtener imágenes adicionales del producto
$stmt = $pdo->prepare("SELECT * FROM producto_imagenes WHERE producto_id = ?");
$stmt->execute([$producto_id]);
$imagenes = $stmt->fetchAll();

// Obtener productos relacionados (misma categoría)
$stmt = $pdo->prepare("
    SELECT p.*, c.nombre as categoria_nombre 
    FROM productos p 
    LEFT JOIN categorias c ON p.id_categoria = c.id
    WHERE p.id_categoria = ? AND p.id != ? 
    ORDER BY RAND() 
    LIMIT 4
");
$stmt->execute([$producto['id_categoria'], $producto_id]);
$productos_relacionados = $stmt->fetchAll();
?>

<?php
$titulo = htmlspecialchars($producto['nombre']) . ' - Tercer Cielo';
include 'cabecera_unificada.php';
?>

<!-- Agregamos Fancybox para el zoom de imágenes -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css">
<style>
    .product-gallery-thumb {
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.3s;
        border-radius: 8px;
        overflow: hidden;
    }
    .product-gallery-thumb:hover,
    .product-gallery-thumb.active {
        border-color: #007bff;
        box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
    }
    .product-gallery-main {
        margin-bottom: 1.5rem;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 450px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 123, 255, 0.1);
        overflow: hidden;
    }
    .product-gallery-main img {
        width: 100%;
        height: 450px;
        object-fit: contain;
        padding: 20px;
    }
    .product-gallery-thumb img {
        width: 80px;
        height: 80px;
        object-fit: cover;
    }
    .gallery-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: linear-gradient(135deg, rgba(0, 123, 255, 0.9) 0%, rgba(0, 86, 179, 0.9) 100%);
        color: white;
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        cursor: pointer;
        z-index: 10;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
    }
    .gallery-arrow:hover {
        background: linear-gradient(135deg, rgba(0, 86, 179, 1) 0%, rgba(0, 68, 148, 1) 100%);
        transform: translateY(-50%) scale(1.15);
        box-shadow: 0 6px 15px rgba(0, 123, 255, 0.5);
    }
    .gallery-arrow.prev {
        left: 10px;
    }
    .gallery-arrow.next {
        right: 10px;
    }
    .gallery-arrow i {
        font-size: 20px;
    }
    .related-product-card {
        transition: transform 0.3s;
    }
    .related-product-card:hover {
        transform: translateY(-5px);
    }
    .stock-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 1;
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
    .bg-success-gradient {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
    }
    .bg-danger-gradient {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
    }
</style>

    <div class="container my-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb" style="background: linear-gradient(135deg, rgba(0, 123, 255, 0.05) 0%, rgba(0, 86, 179, 0.05) 100%); padding: 1rem; border-radius: 8px;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color: #007bff; font-weight: 500;">Inicio</a></li>
                <li class="breadcrumb-item"><a href="catalogo.php" class="text-decoration-none" style="color: #007bff; font-weight: 500;">Catálogo</a></li>
                <?php if ($producto['categoria_nombre']): ?>
                    <li class="breadcrumb-item"><a href="catalogo.php?categoria=<?= $producto['id_categoria'] ?>" class="text-decoration-none" style="color: #007bff; font-weight: 500;"><?= htmlspecialchars($producto['categoria_nombre']) ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" style="font-weight: 600;"><?= htmlspecialchars($producto['nombre']) ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Galería de Imágenes -->
            <div class="col-md-6">
                <div class="product-gallery-main text-center mb-3">
                    <button class="gallery-arrow prev" onclick="previousImage()" id="prevArrow">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <a href="<?= htmlspecialchars(getImageUrl($producto['imagen'])) ?>" 
                       data-fancybox="gallery"
                       data-caption="<?= htmlspecialchars($producto['nombre']) ?>">
                        <img src="<?= htmlspecialchars(getImageUrl($producto['imagen'])) ?>" 
                             class="img-fluid" 
                             id="mainImage"
                             alt="<?= htmlspecialchars($producto['nombre']) ?>">
                    </a>
                    <button class="gallery-arrow next" onclick="nextImage()" id="nextArrow">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <div class="d-flex gap-2 overflow-auto" id="thumbnailContainer">
                    <div class="product-gallery-thumb active" data-index="0" onclick="changeMainImageByIndex(0)">
                        <img src="<?= htmlspecialchars(getImageUrl($producto['imagen'])) ?>" 
                             class="img-thumbnail" 
                             alt="Thumbnail">
                    </div>
                    <?php $index = 1; foreach ($imagenes as $img): ?>
                        <div class="product-gallery-thumb" data-index="<?= $index ?>" onclick="changeMainImageByIndex(<?= $index ?>)">
                            <img src="<?= htmlspecialchars(getImageUrl($img['url_imagen'])) ?>" 
                                 class="img-thumbnail" 
                                 alt="Thumbnail">
                        </div>
                    <?php $index++; endforeach; ?>
                </div>
            </div>

            <!-- Información del Producto -->
            <div class="col-md-6">
                <h1 class="h2 mb-3" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700;"><?= htmlspecialchars($producto['nombre']) ?></h1>
                
                <div class="mb-3" style="background: linear-gradient(135deg, rgba(0, 123, 255, 0.05) 0%, rgba(0, 86, 179, 0.05) 100%); padding: 1rem; border-radius: 10px; border-left: 4px solid #007bff;">
                    <?php if ($producto['precio_anterior'] && $producto['porcentaje_descuento']): ?>
                        <div class="mb-2">
                            <span class="h5 text-muted text-decoration-line-through me-2">S/ <?= number_format($producto['precio_anterior'], 2) ?></span>
                            <span class="badge bg-danger" style="font-size: 0.9rem; padding: 0.5rem 1rem; border-radius: 8px;"><i class="bi bi-percent"></i> -<?= $producto['porcentaje_descuento'] ?>%</span>
                        </div>
                    <?php endif; ?>
                    <span class="h3 me-2" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700;">S/ <?= number_format($producto['precio'], 2) ?></span>
                    <?php if ($producto['stock'] > 0): ?>
                        <span class="badge" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); font-weight: 600; padding: 0.5rem 1rem; border-radius: 8px;"><i class="bi bi-check-circle"></i> En stock</span>
                    <?php else: ?>
                        <span class="badge" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); font-weight: 600; padding: 0.5rem 1rem; border-radius: 8px;"><i class="bi bi-x-circle"></i> Agotado</span>
                    <?php endif; ?>
                </div>

                <div class="mb-4" style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 123, 255, 0.1);">
                    <h5 class="mb-3" style="color: #007bff; font-weight: 600; border-bottom: 2px solid #007bff; padding-bottom: 0.5rem;"><i class="bi bi-file-text"></i> Descripción</h5>
                    <p class="text-muted" style="line-height: 1.8;"><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
                </div>

                <?php 
                $en_carrito = 0;
                if (isset($_SESSION['carrito'])) {
                    foreach ($_SESSION['carrito'] as $item) {
                        if ($item['id'] == $producto['id']) {
                            $en_carrito = $item['cantidad'];
                            break;
                        }
                    }
                }
                $disponible = $producto['stock'] - $en_carrito;
                $sin_stock = $disponible <= 0;
                ?>

                <?php if (!$sin_stock): ?>
                <form class="add-form d-flex gap-2 mb-4" action="agregar_carrito.php" method="POST">
                    <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                    <div class="col-auto">
                        <input type="number" 
                               name="cantidad" 
                               value="1" 
                               min="1" 
                               max="<?= max(1, $disponible) ?>"
                               class="form-control cantidad-input" 
                               style="width:100px; border: 2px solid #007bff; font-weight: 600;" 
                               required>
                    </div>
                    <div class="col">
                        <button type="submit" class="btn w-100 btn-agregar" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; font-weight: 600; border: none; padding: 0.75rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 15px rgba(0, 123, 255, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 10px rgba(0, 123, 255, 0.3)';">
                            <span class="btn-text">
                                <i class="bi bi-cart-plus"></i> Agregar al Carrito
                            </span>
                            <span class="spinner-border spinner-border-sm d-none"></span>
                        </button>
                    </div>
                </form>
                <?php endif; ?>

                <div class="alert <?= $sin_stock ? 'alert-danger' : '' ?>" style="<?= $sin_stock ? '' : 'background: linear-gradient(135deg, rgba(0, 123, 255, 0.1) 0%, rgba(0, 86, 179, 0.1) 100%); border: 1px solid rgba(0, 123, 255, 0.2); color: #007bff;' ?> border-radius: 8px;">
                    <small style="font-weight: 600;">
                        <i class="bi bi-info-circle"></i>
                        <?php if ($sin_stock): ?>
                            No hay unidades disponibles
                        <?php else: ?>
                            Disponible: <strong class="disponible-count" style="color: #007bff;"><?= $disponible ?></strong> / <?= $producto['stock'] ?> unidades
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>

            </div>
        </div>

        <?php if (!empty($productos_relacionados)): ?>
            <div class="mt-5 text-center">
                <h3 class="mb-4" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700;">Productos Relacionados</h3>
                <div class="row row-cols-2 row-cols-md-4 g-4 justify-content-center">
                    <?php foreach ($productos_relacionados as $prod): ?>
                        <div class="col">
                            <div class="card h-100 related-product-card" style="border: 1px solid rgba(0, 123, 255, 0.1); border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.1); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 25px rgba(0, 123, 255, 0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0, 123, 255, 0.1)';">
                                <?php if ($prod['stock'] > 0): ?>
                                    <span class="badge stock-badge" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);"><i class="bi bi-check-circle"></i> En stock</span>
                                <?php else: ?>
                                    <span class="badge stock-badge" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);"><i class="bi bi-x-circle"></i> Agotado</span>
                                <?php endif; ?>
                                
                                <a href="producto.php?id=<?= $prod['id'] ?>" class="text-decoration-none">
                                    <img src="<?= htmlspecialchars(getImageUrl($prod['imagen'])) ?>" 
                                         class="card-img-top" 
                                         style="height: 160px; object-fit: contain; padding: 10px; background: #f8f9fa;"
                                         alt="<?= htmlspecialchars($prod['nombre']) ?>">
                                </a>
                                
                                <div class="card-body text-center px-2 py-3">
                                    <h5 class="card-title mb-2">
                                        <a href="producto.php?id=<?= $prod['id'] ?>" class="text-decoration-none text-dark" style="font-weight: 600;">
                                            <?= htmlspecialchars($prod['nombre']) ?>
                                        </a>
                                    </h5>
                                    <?php if ($prod['precio_anterior'] && $prod['porcentaje_descuento']): ?>
                                        <div class="mb-1">
                                            <span class="text-muted text-decoration-line-through" style="font-size: 0.85rem;">S/ <?= number_format($prod['precio_anterior'], 2) ?></span>
                                            <span class="badge bg-danger ms-1" style="font-size: 0.7rem;">-<?= $prod['porcentaje_descuento'] ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                    <p class="card-text mb-0" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700; font-size: 1.1rem;">S/ <?= number_format($prod['precio'], 2) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <?php include 'footer.php'; ?>

    <?php include 'carrito_lateral.php'; ?>

    <!-- Container para los toasts -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script>
    // Inicializar Fancybox
    Fancybox.bind("[data-fancybox]", {
        // Opciones de configuración
    });

    // Array de imágenes para navegación
    const imageGallery = [
        '<?= htmlspecialchars(getImageUrl($producto['imagen'])) ?>'
        <?php foreach ($imagenes as $img): ?>,
        '<?= htmlspecialchars(getImageUrl($img['url_imagen'])) ?>'
        <?php endforeach; ?>
    ];
    let currentImageIndex = 0;

    // Función para cambiar imagen por índice
    function changeMainImageByIndex(index) {
        currentImageIndex = index;
        const imageUrl = imageGallery[index];
        
        // Actualizar imagen principal
        document.getElementById('mainImage').src = imageUrl;
        document.getElementById('mainImage').parentElement.href = imageUrl;
        
        // Actualizar estado activo de las miniaturas
        document.querySelectorAll('.product-gallery-thumb').forEach(t => t.classList.remove('active'));
        const activeThumb = document.querySelector(`.product-gallery-thumb[data-index="${index}"]`);
        if (activeThumb) {
            activeThumb.classList.add('active');
            // Scroll para que la miniatura activa sea visible
            activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
        
        // Actualizar visibilidad de flechas
        updateArrowsVisibility();
    }

    // Función para cambiar la imagen principal (compatibilidad)
    function changeMainImage(thumb, imageUrl) {
        const index = parseInt(thumb.getAttribute('data-index'));
        changeMainImageByIndex(index);
    }

    // Función para ir a la imagen anterior
    function previousImage() {
        if (currentImageIndex > 0) {
            changeMainImageByIndex(currentImageIndex - 1);
        }
    }

    // Función para ir a la siguiente imagen
    function nextImage() {
        if (currentImageIndex < imageGallery.length - 1) {
            changeMainImageByIndex(currentImageIndex + 1);
        }
    }

    // Función para actualizar visibilidad de flechas
    function updateArrowsVisibility() {
        const prevArrow = document.getElementById('prevArrow');
        const nextArrow = document.getElementById('nextArrow');
        
        if (imageGallery.length <= 1) {
            prevArrow.style.display = 'none';
            nextArrow.style.display = 'none';
        } else {
            prevArrow.style.display = currentImageIndex === 0 ? 'none' : 'flex';
            nextArrow.style.display = currentImageIndex === imageGallery.length - 1 ? 'none' : 'flex';
        }
    }

    // Inicializar visibilidad de flechas al cargar
    document.addEventListener('DOMContentLoaded', function() {
        updateArrowsVisibility();
    });

    // Soporte para navegación con teclado
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            previousImage();
        } else if (e.key === 'ArrowRight') {
            nextImage();
        }
    });

    // Función para actualizar el UI con el nuevo stock disponible
    function actualizarUIStock(nuevoDisponible, stockTotal) {
        const form = document.querySelector('.add-form');
        if (!form) return;

        const disponibleElement = document.querySelector('.disponible-count');
        const alertaStock = document.querySelector('.alert');
        const inputCantidad = form.querySelector('.cantidad-input');

        // Actualizar contador disponible
        disponibleElement.textContent = nuevoDisponible;
        
        // Actualizar máximo del input
        inputCantidad.max = Math.max(1, nuevoDisponible);
        inputCantidad.value = Math.min(inputCantidad.value, nuevoDisponible);

        // Actualizar alerta
        if (nuevoDisponible <= 0) {
            form.remove();
            alertaStock.classList.remove('alert-info');
            alertaStock.classList.add('alert-danger');
            alertaStock.querySelector('small').innerHTML = '<i class="bi bi-info-circle"></i> No hay unidades disponibles';
        } else {
            alertaStock.classList.remove('alert-danger');
            alertaStock.classList.add('alert-info');
            alertaStock.querySelector('small').innerHTML = `<i class="bi bi-info-circle"></i> Disponible: <strong class="disponible-count">${nuevoDisponible}</strong> / ${stockTotal} unidades`;
        }
    }

    // Gestión del carrito - Solo para formularios de producto
    // El formulario ahora usa la clase 'add-form' y será manejado por el event listener global en carrito_lateral.php

    // Escuchar eventos del carrito lateral para actualizar stock disponible
    window.addEventListener('carritoActualizado', function(event) {
        const items = event.detail.items || {};
        const productoId = <?= $producto['id'] ?>;
        const stockTotal = <?= $producto['stock'] ?>;
        const cantidadEnCarrito = items[productoId] ? items[productoId].cantidad : 0;
        const disponible = stockTotal - cantidadEnCarrito;

        const dispEl = document.querySelector('.disponible-count');
        const cantInput = document.querySelector('.cantidad-input');
        const btn = document.querySelector('.btn-agregar');
        const alertaStock = document.querySelector('.alert');

        if (dispEl) dispEl.textContent = disponible;
        if (cantInput) {
            cantInput.max = Math.max(1, disponible);
            if (parseInt(cantInput.value) > disponible) {
                cantInput.value = Math.min(cantInput.value, disponible);
            }
        }

        if (btn) {
            btn.disabled = disponible <= 0;
            const text = btn.querySelector('.btn-text');
            if (text) text.textContent = 'Agregar al Carrito';
        }
        if (alertaStock) {
            if (disponible <= 0) {
                alertaStock.classList.remove('alert-info');
                alertaStock.classList.add('alert-danger');
                alertaStock.querySelector('small').innerHTML = '<i class="bi bi-info-circle"></i> No hay unidades disponibles';
            } else {
                alertaStock.classList.remove('alert-danger');
                alertaStock.classList.add('alert-info');
                alertaStock.querySelector('small').innerHTML = `<i class="bi bi-info-circle"></i> Disponible: <strong class="disponible-count">${disponible}</strong> / ${stockTotal} unidades`;
            }
        }
    });

    // Función para mostrar toasts (solo uno a la vez)
    let toastActual = null;
    function mostrarToast(mensaje, tipo = 'success') {
        // Cerrar toast anterior si existe
        if (toastActual) {
            const bsToast = bootstrap.Toast.getInstance(toastActual);
            if (bsToast) {
                bsToast.hide();
            }
            toastActual.remove();
        }
        
        const toastContainer = document.querySelector('.toast-container');
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${tipo} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${mensaje}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
        toastActual = toast;
        
        toast.addEventListener('hidden.bs.toast', () => {
            if (toast === toastActual) {
                toastActual = null;
            }
            toast.remove();
        });
    }

    // Función para recargar el carrito sin disparar evento
    async function recargarCarritoSinEvento() {
        const response = await fetch('get_carrito_lateral.php');
        const data = await response.json();
        
        const carritoBody = document.getElementById('carrito-body');
        if (carritoBody) {
            carritoBody.innerHTML = data.html;
        }
        return data;
    }

    // Función para recargar el carrito con evento
    async function recargarCarrito() {
        const data = await recargarCarritoSinEvento();
        window.dispatchEvent(new CustomEvent('carritoActualizado', {
            detail: {
                items: data.items,
                total: data.total,
                itemsCount: data.items_count
            }
        }));
        return data;
    }
    </script>
</body>
</html>