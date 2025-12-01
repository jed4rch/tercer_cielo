<?php
require_once '../includes/init.php';
require_once '../includes/func_productos.php';

// === REDIRECCIÓN SI ES ADMIN ===
if (isset($_SESSION['user_id']) && $_SESSION['rol'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

// === UNIFICAR PARÁMETROS ===
$categoria_id = $_GET['categoria'] ?? ($_GET['cat'] ?? '');
$busqueda = trim($_GET['q'] ?? '');
$orden = $_GET['orden'] ?? 'nombre_asc';

// === CONSULTA CON VENTAS ===
$sql = "SELECT p.*, COALESCE(c.nombre, 'Sin categoría') as categoria,
               COALESCE(SUM(pd.cantidad), 0) as total_vendido
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id
        LEFT JOIN pedido_detalles pd ON p.id = pd.producto_id
        LEFT JOIN pedidos ped ON pd.pedido_id = ped.id AND ped.estado = 'entregado'
        WHERE p.habilitado = 1 AND (c.habilitado = 1 OR c.habilitado IS NULL)";
$params = [];

if ($busqueda !== '') {
    $sql .= " AND p.nombre LIKE ?";
    $params[] = "%$busqueda%";
}
if ($categoria_id !== '' && is_numeric($categoria_id)) {
    $sql .= " AND p.id_categoria = ?";
    $params[] = $categoria_id;
}

$sql .= " GROUP BY p.id";

// === APLICAR ORDENAMIENTO ===
switch ($orden) {
    case 'precio_asc':
        $sql .= " ORDER BY p.precio ASC";
        break;
    case 'precio_desc':
        $sql .= " ORDER BY p.precio DESC";
        break;
    case 'nuevo':
        $sql .= " ORDER BY p.id DESC"; // Asumiendo que ID más alto = más nuevo
        break;
    case 'vendido':
        $sql .= " ORDER BY total_vendido DESC";
        break;
    case 'nombre_asc':
        $sql .= " ORDER BY p.nombre ASC";
        break;
    case 'nombre_desc':
        $sql .= " ORDER BY p.nombre DESC";
        break;
    default:
        $sql .= " ORDER BY p.nombre ASC";
}

$stmt = getPdo()->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

// === CATEGORÍAS PARA FILTRO ===
$categorias = getPdo()->query("SELECT id, nombre FROM categorias WHERE habilitado = 1 ORDER BY nombre")->fetchAll();

// === TÍTULO DINÁMICO ===
$titulo_pagina = 'Catálogo de Productos';
if ($busqueda !== '') {
    $titulo_pagina = "Resultados para: \"$busqueda\"";
} elseif ($categoria_id !== '' && is_numeric($categoria_id)) {
    $stmt = getPdo()->prepare("SELECT nombre FROM categorias WHERE id = ?");
    $stmt->execute([$categoria_id]);
    $cat = $stmt->fetch();
    $titulo_pagina = $cat ? $cat['nombre'] : 'Categoría';
} else {
    $titulo_pagina = 'Todos los Productos';
}
?>

<?php
$titulo = 'Catálogo - ' . $titulo_pagina;
include 'cabecera_unificada.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 4rem 0;
        text-align: center;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('../assets/img/frontis.png');
        background-size: cover;
        background-position: center;
        opacity: 0.1;
        z-index: 0;
    }

    .page-header .container {
        position: relative;
        z-index: 1;
    }

    .page-header h1 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        font-size: 3rem;
        margin-bottom: 0.5rem;
    }

    .page-header .lead {
        font-size: 1.2rem;
        opacity: 0.95;
    }

    .section-title {
        font-family: 'Montserrat', sans-serif;
        color: var(--primary);
        position: relative;
        display: inline-block;
        margin-bottom: 2rem;
    }

    .section-title:after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background: var(--secondary);
    }

    .producto-card {
        transition: all 0.3s ease;
        border: none;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        background: white;
    }

    .producto-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
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
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        font-size: 0.75rem;
        padding: 6px 12px;
        border-radius: 20px;
        z-index: 10;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
    }

    .precio {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .btn-agregar {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border: none;
        color: white !important;
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
    }

    .btn-agregar:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 123, 255, 0.4);
        background: linear-gradient(135deg, #0056b3 0%, #004494 100%);
    }

    .btn-agregar:disabled {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        cursor: not-allowed;
        opacity: 0.65;
    }

    .btn-outline-primary {
        color: #007bff;
        border: 2px solid #007bff;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-outline-primary:hover {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border-color: transparent;
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
    }

    .btn-outline-secondary {
        transition: all 0.3s ease;
    }

    .btn-outline-secondary:hover {
        transform: translateY(-2px);
    }

    .sin-resultados {
        text-align: center;
        padding: 4rem 2rem;
        color: #6c757d;
    }

    .sin-resultados i {
        font-size: 4rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }

    .filter-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.1);
        border: 1px solid rgba(0, 123, 255, 0.1);
        transition: all 0.3s ease;
    }

    .filter-card:hover {
        box-shadow: 0 6px 20px rgba(0, 123, 255, 0.15);
        transform: translateY(-2px);
    }

    .filter-card .input-group-text {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border: none;
    }

    .filter-card .form-control,
    .filter-card .form-select {
        border: 1px solid rgba(0, 123, 255, 0.2);
    }

    .filter-card .form-control:focus,
    .filter-card .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
    }

    footer {
        background: var(--dark);
        color: #ccc;
        margin-top: 5rem;
        padding: 3rem 0 1rem;
    }

    .social-icons a {
        width: 38px;
        height: 38px;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 4px;
        transition: all 0.3s ease;
    }

    .social-icons a:hover {
        background: var(--secondary);
        transform: translateY(-3px);
    }

    @media (max-width: 768px) {
        .page-header h1 {
            font-size: 2.2rem;
        }

        .filter-card {
            margin-bottom: 1.5rem;
        }
    }
</style>

    <!-- HEADER -->
    <header class="page-header">
        <div class="container">
            <h1><?= htmlspecialchars($titulo_pagina) ?></h1>
            <p class="lead">
                <?php if ($busqueda !== ''): ?>
                    Resultados de búsqueda para "<strong><?= htmlspecialchars($busqueda) ?></strong>"
                <?php elseif ($categoria_id !== '' && is_numeric($categoria_id)): ?>
                    Productos en la categoría
                    <strong>
                        <?= htmlspecialchars($cat['nombre'] ?? 'Categoría desconocida') ?>
                    </strong>
                <?php else: ?>
                    Explora todos nuestros productos de ferretería
                <?php endif; ?>
            </p>
        </div>
    </header>

    <div class="container mb-5">

        <!-- FILTROS -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="filter-card">
                    <form method="GET" class="row g-3 align-items-center">
                        <div class="col-md-7">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" class="form-control"
                                    placeholder="Buscar producto..."
                                    value="<?= htmlspecialchars($busqueda) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-primary w-100" type="submit">
                                <i class="bi bi-funnel"></i> Buscar
                            </button>
                        </div>
                        <?php if ($categoria_id): ?>
                            <div class="col-md-2">
                                <a href="catalogo.php" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="filter-card">
                    <form method="GET">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-tags"></i></span>
                            <select name="categoria" class="form-select" onchange="this.form.submit()">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $categoria_id == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($busqueda): ?>
                                <input type="hidden" name="q" value="<?= htmlspecialchars($busqueda) ?>">
                            <?php endif; ?>
                            <?php if ($orden): ?>
                                <input type="hidden" name="orden" value="<?= htmlspecialchars($orden) ?>">
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="filter-card">
                    <form method="GET">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-sort-down"></i></span>
                            <select name="orden" class="form-select" onchange="this.form.submit()">
                                <option value="nombre_asc" <?= $orden == 'nombre_asc' ? 'selected' : '' ?>>A-Z</option>
                                <option value="nombre_desc" <?= $orden == 'nombre_desc' ? 'selected' : '' ?>>Z-A</option>
                                <option value="precio_asc" <?= $orden == 'precio_asc' ? 'selected' : '' ?>>Menor precio</option>
                                <option value="precio_desc" <?= $orden == 'precio_desc' ? 'selected' : '' ?>>Mayor precio</option>
                                <option value="nuevo" <?= $orden == 'nuevo' ? 'selected' : '' ?>>Lo más nuevo</option>
                                <option value="vendido" <?= $orden == 'vendido' ? 'selected' : '' ?>>Más vendido</option>
                            </select>
                            <?php if ($busqueda): ?>
                                <input type="hidden" name="q" value="<?= htmlspecialchars($busqueda) ?>">
                            <?php endif; ?>
                            <?php if ($categoria_id): ?>
                                <input type="hidden" name="categoria" value="<?= htmlspecialchars($categoria_id) ?>">
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- RESULTADOS -->
        <?php if (empty($productos)): ?>
            <div class="sin-resultados">
                <i class="bi bi-search"></i>
                <h4>No se encontraron productos</h4>
                <p>Intenta con otro término o categoría.</p>
                <a href="catalogo.php" class="btn mt-3" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; font-weight: 600; border: none; padding: 0.75rem 2rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 15px rgba(0, 123, 255, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 10px rgba(0, 123, 255, 0.3)';">
                    <i class="bi bi-arrow-left"></i> Ver todos
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($productos as $p): ?>
                    <?php
                    // === STOCK DISPONIBLE ===
                    $en_carrito = $_SESSION['carrito'][$p['id']]['cantidad'] ?? 0;
                    $disponible = $p['stock'] - $en_carrito;
                    $sin_stock = $disponible <= 0;

                    // === IMAGEN ===
                    $img_src = getImageUrl($p['imagen']);
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="producto-card h-100 position-relative">
                            <?php if ($p['stock'] > 0): ?>
                                <span class="badge-stock">
                                    <i class="bi bi-check2"></i> En stock
                                </span>
                            <?php endif; ?>

                            <a href="producto.php?id=<?= $p['id'] ?>" class="text-decoration-none">
                                <img src="<?= htmlspecialchars($img_src) ?>"
                                    class="producto-img w-100"
                                    alt="<?= htmlspecialchars($p['nombre']) ?>"
                                    onerror="this.src='<?= ASSETS_URL ?>/assets/img/default-product.png';">
                            </a>

                            <div class="card-body d-flex flex-column p-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge" style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); font-weight: 600; padding: 0.5rem 1rem; border-radius: 8px;">
                                        <i class="bi bi-tag"></i> <?= htmlspecialchars($p['categoria']) ?>
                                    </span>
                                </div>

                                <h5 class="card-title mb-2">
                                    <a href="producto.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($p['nombre']) ?>
                                    </a>
                                </h5>

                                <p class="card-text text-muted small flex-grow-1">
                                    <?= strlen($p['descripcion'] ?? '') > 100
                                        ? substr(htmlspecialchars($p['descripcion']), 0, 100) . '...'
                                        : htmlspecialchars($p['descripcion'] ?? 'Sin descripción') ?>
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
                                    <!-- AGREGAR AL CARRITO -->
                                    <form class="add-form d-flex gap-2 mb-2" action="agregar_carrito.php">
                                        <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
                                        <input type="number" name="cantidad" value="1" min="1" max="<?= max(1, $disponible) ?>"
                                            class="form-control form-control-sm cantidad-input" style="width:70px;" <?= $sin_stock ? 'disabled' : '' ?> required>
                                        <button type="submit" class="btn btn-success flex-grow-1 btn-agregar" <?= $sin_stock ? 'disabled' : '' ?>>
                                            <span class="btn-text"><?= $sin_stock ? 'Sin stock' : 'Agregar' ?></span>
                                            <span class="spinner-border spinner-border-sm d-none"></span>
                                        </button>
                                    </form>

                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-check-circle" style="color: #007bff;"></i> 
                                            Disponible: <strong class="disponible-count" style="color: #007bff;"><?= $disponible ?></strong> / <?= $p['stock'] ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- CONTADOR -->
            <div class="text-center mt-5">
                <p class="text-muted">
                    Mostrando <strong><?= count($productos) ?></strong>
                    producto<?= count($productos) !== 1 ? 's' : '' ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Manejar formularios de agregar al carrito - SOLUCIÓN DEFINITIVA
    document.addEventListener('DOMContentLoaded', function() {
        // Remover completamente todos los event listeners existentes
        const originalForms = document.querySelectorAll('.add-form');
        originalForms.forEach(form => {
            const newForm = form.cloneNode(true);
            form.parentNode.replaceChild(newForm, form);
        });

        // Delegación de eventos única y control estricto
        let globalProcessing = false;
        
        document.addEventListener('click', function(e) {
            const button = e.target.closest('.btn-agregar');
            if (!button || button.disabled || globalProcessing) return;
            
            e.preventDefault();
            e.stopImmediatePropagation();
            e.stopPropagation();
            
            globalProcessing = true;
            
            const form = button.closest('.add-form');
            const buttonText = form.querySelector('.btn-text');
            const spinner = form.querySelector('.spinner-border');
            const cantidadInput = form.querySelector('.cantidad-input');
            const disponibleCount = form.closest('.card-body').querySelector('.disponible-count');
            const productoId = form.querySelector('input[name="producto_id"]').value;
            const cantidad = parseInt(cantidadInput.value) || 1;
            
            // Validar cantidad
            if (cantidad < 1) {
                globalProcessing = false;
                return;
            }
            
            // Mostrar loading inmediatamente
            button.disabled = true;
            buttonText.textContent = 'Agregando...';
            spinner.classList.remove('d-none');
            
            // Crear FormData manualmente
            const formData = new FormData();
            formData.append('producto_id', productoId);
            formData.append('cantidad', cantidad);
            
            // Hacer la petición
            fetch('agregar_carrito.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar TODOS los contadores del carrito inmediatamente
                    const contadores = document.querySelectorAll('#carrito-contador, #btn-contador, .carrito-contador');
                    contadores.forEach(contador => {
                        contador.textContent = data.items_count || 0;
                    });
                    
                    // Actualizar disponibilidad local inmediatamente SOLO para este producto
                    if (disponibleCount) {
                        const nuevaDisponibilidad = parseInt(disponibleCount.textContent) - cantidad;
                        disponibleCount.textContent = Math.max(0, nuevaDisponibilidad);
                        
                        // Actualizar el max del input
                        cantidadInput.max = Math.max(1, nuevaDisponibilidad);
                        
                        if (nuevaDisponibilidad <= 0) {
                            button.disabled = true;
                            buttonText.textContent = 'Sin stock';
                            cantidadInput.disabled = true;
                        } else {
                            // Asegurar que el botón permanezca habilitado si hay stock
                            button.disabled = false;
                            buttonText.textContent = 'Agregar';
                            cantidadInput.disabled = false;
                        }
                    }
                    
                    // Forzar la recarga del carrito lateral para sincronizar completamente
                    if (window.recargarCarrito) {
                        window.recargarCarrito();
                    }
                    
                    // Mostrar toast de éxito con colores correctos
                    if (window.mostrarToast) {
                        window.mostrarToast(data.message || 'Producto agregado al carrito', 'success');
                    } else {
                        mostrarToastCatalogo(data.message || 'Producto agregado al carrito', 'success');
                    }
                } else {
                    if (window.mostrarToast) {
                        window.mostrarToast(data.message || 'Error al agregar producto', 'danger');
                    } else {
                        mostrarToastCatalogo(data.message || 'Error al agregar producto', 'danger');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (window.mostrarToast) {
                    window.mostrarToast('Error de conexión', 'danger');
                } else {
                    mostrarToastCatalogo('Error de conexión', 'danger');
                }
            })
            .finally(() => {
                // Restaurar estado global
                globalProcessing = false;
                
                // Solo restaurar el botón si sigue existiendo (no se recargó la página)
                if (document.body.contains(button)) {
                    button.disabled = false;
                    buttonText.textContent = 'Agregar';
                    spinner.classList.add('d-none');
                }
            });
        }, { capture: true }); // Usar capture para interceptar el evento primero
    });

    // También prevenir el evento submit del formulario por si acaso
    document.addEventListener('submit', function(e) {
        if (e.target.classList.contains('add-form')) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, { capture: true });

    // Función de toast específica para catálogo que asegura colores correctos
    function mostrarToastCatalogo(msg, tipo = 'success') {
        const bgClass = tipo === 'success' ? 'bg-success' : 'bg-danger';
        const toastContainer = document.createElement('div');
        toastContainer.className = 'position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1070';
        
        toastContainer.innerHTML = `
            <div class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${tipo === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'} me-2"></i>
                        ${msg}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        document.body.appendChild(toastContainer);
        const toastElement = toastContainer.querySelector('.toast');
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 3000
        });
        toast.show();
        
        // Remover el toast del DOM después de que se oculte
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastContainer.remove();
        });
    }

    // Escuchar eventos de actualización del carrito para sincronizar contadores
    window.addEventListener('carritoActualizado', function(event) {
        const items_count = event.detail.items_count || 0;
        const contadores = document.querySelectorAll('#carrito-contador, #btn-contador, .carrito-contador');
        contadores.forEach(contador => {
            contador.textContent = items_count;
        });
        
        // Actualizar stock disponible en todas las tarjetas de productos
        document.querySelectorAll('.producto-card').forEach(card => {
            const input = card.querySelector('input[name="producto_id"]');
            if (!input) return;
            const id = input.value;
            
            // Buscar el producto en los datos del evento
            const enCarrito = event.detail.items?.[id]?.cantidad || 0;
            const disponibleEl = card.querySelector('.disponible-count');
            if (disponibleEl) {
                // Buscar el stock total en el texto completo del small
                const smallElement = disponibleEl.closest('small');
                if (!smallElement) return;
                
                const textoCompleto = smallElement.textContent;
                const match = textoCompleto.match(/\/ (\d+)/);
                if (!match) return;
                
                const stockTotal = parseInt(match[1]);
                const nuevaDisponibilidad = stockTotal - enCarrito;
                disponibleEl.textContent = Math.max(0, nuevaDisponibilidad);
                
                const button = card.querySelector('.btn-agregar');
                const buttonText = card.querySelector('.btn-text');
                const cantidadInput = card.querySelector('.cantidad-input');
                
                if (nuevaDisponibilidad <= 0) {
                    if (button) button.disabled = true;
                    if (buttonText) buttonText.textContent = 'Sin stock';
                    if (cantidadInput) {
                        cantidadInput.disabled = true;
                        cantidadInput.max = 1;
                    }
                } else {
                    if (button) button.disabled = false;
                    if (buttonText) buttonText.textContent = 'Agregar';
                    if (cantidadInput) {
                        cantidadInput.disabled = false;
                        cantidadInput.max = Math.max(1, nuevaDisponibilidad);
                    }
                }
            }
        });
    });

    // Inicializar dropdowns de Bootstrap - solución para menú de usuario
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar todos los dropdowns de Bootstrap
        var dropdownTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
        var dropdownList = dropdownTriggerList.map(function (dropdownTriggerEl) {
            return new bootstrap.Dropdown(dropdownTriggerEl);
        });

        // Solución específica para el menú de usuario en navbar
        const userDropdown = document.getElementById('navbarDropdown');
        if (userDropdown) {
            const dropdown = new bootstrap.Dropdown(userDropdown);
            
            // Forzar la apertura del dropdown si hay problemas
            userDropdown.addEventListener('click', function(e) {
                e.preventDefault();
                dropdown.toggle();
            });
        }
    });
    </script>
</body>

</html>