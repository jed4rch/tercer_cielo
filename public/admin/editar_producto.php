<?php
require_once '../../includes/init.php';
require_once '../../includes/func_imagenes.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /tercer_cielo/public/login.php');
    exit;
}

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /tercer_cielo/public/index.php');
    exit;
}

$pdo = getPdo();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: productos.php');
    exit;
}

// === EDITAR ===
if ($_POST) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $stock = (int)$_POST['stock'];
    $categoria = (int)$_POST['categoria'];
    
    // Obtener imagen actual
    $stmt = $pdo->prepare("SELECT imagen FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $productoActual = $stmt->fetch();
    $imagen = $productoActual['imagen'];
    
    // Manejo de la imagen principal
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $resultado = guardarImagen($_FILES['imagen'], 'productos', 800, 800);
        if ($resultado['success']) {
            // Eliminar imagen anterior si existe
            if ($imagen) {
                eliminarImagen($imagen);
            }
            $imagen = $resultado['path'];
        } else {
            $error = $resultado['message'];
        }
    }
    
    // Manejo de descuento
    $precio_anterior = null;
    $porcentaje_descuento = null;
    
    if (isset($_POST['aplicar_descuento']) && $_POST['aplicar_descuento'] == '1') {
        $precio_anterior = (float)$_POST['precio_anterior'];
        if ($precio_anterior > 0 && $precio_anterior > $precio) {
            $porcentaje_descuento = round((($precio_anterior - $precio) / $precio_anterior) * 100);
        }
    }

    if (!isset($error)) {
        $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, precio_anterior = ?, porcentaje_descuento = ?, stock = ?, id_categoria = ?, imagen = ? WHERE id = ?");
        if ($stmt->execute([$nombre, $descripcion, $precio, $precio_anterior, $porcentaje_descuento, $stock, $categoria, $imagen, $id])) {
            header('Location: productos.php?success=editado');
            exit;
        } else {
            $error = "Error al actualizar producto.";
        }
    }
}

// === OBTENER PRODUCTO ===
$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$id]);
$producto = $stmt->fetch();
if (!$producto) {
    header('Location: productos.php');
    exit;
}

// === CATEGORÍAS ===
$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll();

$pageTitle = 'Editar Producto';
include 'layout_header.php';
?>

<style>
    .image-preview {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 4px;
    }
    .image-item {
        position: relative;
        display: inline-block;
        margin: 5px;
    }
    .delete-image {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        text-align: center;
        line-height: 24px;
        cursor: pointer;
    }
    .image-item.main-image::after {
        content: 'Principal';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.7);
        color: white;
        text-align: center;
        padding: 2px;
        font-size: 12px;
    }
</style>
</head>
<body>
<div class="content-card">
    <h3 class="mb-4"><i class="bi bi-pencil-square me-2"></i>Editar Producto</h3>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Precio</label>
                <input type="number" step="0.01" name="precio" value="<?= $producto['precio'] ?>" class="form-control" id="precio" required>
            </div>
            
            <!-- Sección de Descuento -->
            <div class="mb-3" style="background: linear-gradient(135deg, rgba(0, 123, 255, 0.05) 0%, rgba(0, 86, 179, 0.05) 100%); padding: 1rem; border-radius: 10px; border-left: 4px solid #007bff;">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="aplicar_descuento" value="1" id="aplicarDescuento" <?= $producto['precio_anterior'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="aplicarDescuento">
                        <strong><i class="bi bi-percent"></i> Aplicar Descuento</strong>
                    </label>
                </div>
                <div id="descuentoFields" style="<?= $producto['precio_anterior'] ? '' : 'display: none;' ?>">
                    <div class="mb-3">
                        <label class="form-label">Precio Anterior (Sin Descuento)</label>
                        <input type="number" step="0.01" name="precio_anterior" value="<?= $producto['precio_anterior'] ?? '' ?>" class="form-control" id="precioAnterior" placeholder="Ingrese el precio sin descuento">
                        <small class="form-text text-muted">Este precio se mostrará tachado en el producto</small>
                    </div>
                    <div id="descuentoInfo" class="alert alert-info" style="<?= $producto['porcentaje_descuento'] ? '' : 'display: none;' ?>">
                        <i class="bi bi-info-circle"></i> <strong>Descuento:</strong> <span id="porcentajeDescuento"><?= $producto['porcentaje_descuento'] ?? 0 ?>%</span>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Stock</label>
                <input type="number" name="stock" value="<?= $producto['stock'] ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Categoría</label>
                <select name="categoria" class="form-control" required>
                    <option value="">Seleccionar</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $producto['id_categoria'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label">Imagen Principal</label>
                <?php if ($producto['imagen']): ?>
                    <div class="mb-2">
                        <div class="image-item main-image d-inline-block">
                            <img src="<?= htmlspecialchars($producto['imagen']) ?>" class="image-preview" alt="Imagen actual">
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">Imagen actual. Sube una nueva imagen para reemplazarla.</small>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" id="imagenInput">
                <small class="form-text text-muted">Formatos aceptados: JPG, PNG, GIF, WEBP. Máximo 5MB. La imagen se redimensionará automáticamente.</small>
                <div id="vistaPrevia" class="mt-3" style="display: none;">
                    <img id="imagenPrevia" src="" alt="Vista previa" style="max-width: 300px; max-height: 300px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Imágenes Adicionales</label>
                <div class="input-group mb-3">
                    <input type="file" class="form-control" id="additionalImageFile" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                    <button class="btn btn-outline-primary" type="button" id="addImage">
                        <i class="bi bi-plus-lg"></i> Agregar Imagen
                    </button>
                </div>
                <small class="form-text text-muted">Sube imágenes adicionales del producto (máximo 5MB cada una)</small>
                
                <div id="additionalImages" class="mt-3">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM producto_imagenes WHERE producto_id = ? ORDER BY orden");
                    $stmt->execute([$id]);
                    $imagenes = $stmt->fetchAll();
                    foreach ($imagenes as $img):
                    ?>
                        <div class="image-item" data-id="<?= $img['id'] ?>">
                            <img src="<?= htmlspecialchars($img['url_imagen']) ?>" class="image-preview" alt="Imagen adicional">
                            <span class="delete-image" onclick="deleteImage(<?= $img['id'] ?>)">&times;</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Actualizar Producto
            </button>
            <a href="productos.php" class="btn btn-secondary">
                <i class="bi bi-x-lg"></i> Cancelar
            </a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Manejo de descuento
    const aplicarDescuento = document.getElementById('aplicarDescuento');
    const descuentoFields = document.getElementById('descuentoFields');
    const precio = document.getElementById('precio');
    const precioAnterior = document.getElementById('precioAnterior');
    const descuentoInfo = document.getElementById('descuentoInfo');
    const porcentajeDescuento = document.getElementById('porcentajeDescuento');

    aplicarDescuento.addEventListener('change', function() {
        if (this.checked) {
            descuentoFields.style.display = 'block';
        } else {
            descuentoFields.style.display = 'none';
            descuentoInfo.style.display = 'none';
        }
    });

    function calcularDescuento() {
        const precioActual = parseFloat(precio.value) || 0;
        const precioAnt = parseFloat(precioAnterior.value) || 0;
        
        if (precioAnt > 0 && precioAnt > precioActual) {
            const descuento = Math.round(((precioAnt - precioActual) / precioAnt) * 100);
            porcentajeDescuento.textContent = descuento + '%';
            descuentoInfo.style.display = 'block';
        } else {
            descuentoInfo.style.display = 'none';
        }
    }

    precio.addEventListener('input', calcularDescuento);
    precioAnterior.addEventListener('input', calcularDescuento);

    // Vista previa de imagen principal
    document.getElementById('imagenInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagenPrevia').src = e.target.result;
                document.getElementById('vistaPrevia').style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            document.getElementById('vistaPrevia').style.display = 'none';
        }
    });

    document.getElementById('addImage').addEventListener('click', function() {
        const fileInput = document.getElementById('additionalImageFile');
        const file = fileInput.files[0];
        
        if (!file) {
            alert('Por favor, seleccione un archivo de imagen');
            return;
        }

        const formData = new FormData();
        formData.append('imagen', file);
        formData.append('producto_id', <?= $id ?>);
        formData.append('orden', document.querySelectorAll('.image-item').length);

        fetch('agregar_imagen_producto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('additionalImages');
                const div = document.createElement('div');
                div.className = 'image-item';
                div.dataset.id = data.id;
                div.innerHTML = `
                    <img src="${data.url}" class="image-preview" alt="Imagen adicional">
                    <span class="delete-image" onclick="deleteImage(${data.id})">&times;</span>
                `;
                container.appendChild(div);
                fileInput.value = '';
            } else {
                alert(data.error || 'Error al agregar la imagen');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al agregar la imagen');
        });
    });

    // Sistema de confirmación personalizado
    document.addEventListener('DOMContentLoaded', function() {
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        let pendingAction = null;

        function showConfirmModal(message, icon, iconColor, callback) {
            document.getElementById('confirmModalMessage').innerHTML = message;
            document.getElementById('confirmModalIcon').innerHTML = `<i class="${icon}" style="color: ${iconColor};"></i>`;
            
            pendingAction = callback;
            confirmModal.show();
        }

        document.getElementById('confirmModalButton').addEventListener('click', function() {
            if (pendingAction) {
                pendingAction();
                pendingAction = null;
            }
            confirmModal.hide();
        });

        window.deleteImage = function(id) {
            showConfirmModal(
                `<strong>¿Está seguro de eliminar esta imagen adicional?</strong><br><br>` +
                `<div style="text-align: left; display: inline-block;">` +
                `• La imagen se eliminará permanentemente del servidor<br>` +
                `• Esta acción NO se puede deshacer<br><br>` +
                `<strong style="color: #dc3545;">¿Desea continuar?</strong>` +
                `</div>`,
                'bi bi-image-fill',
                '#dc3545',
                () => {
                    fetch('eliminar_imagen_producto.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `imagen_id=${id}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const element = document.querySelector(`.image-item[data-id="${id}"]`);
                            if (element) element.remove();
                        } else {
                            alert(data.error || 'Error al eliminar la imagen');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al eliminar la imagen');
                    });
                }
            );
        };
    });
    </script>

<!-- Modal de confirmación personalizado -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0, 123, 255, 0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); border-radius: 15px 15px 0 0; border: none;">
                <h5 class="modal-title text-white" id="confirmModalTitle">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmar Acción
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div id="confirmModalIcon" class="mb-3" style="font-size: 3rem;"></div>
                <p id="confirmModalMessage" class="mb-0" style="font-size: 1.1rem; color: #495057;"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 10px;">
                    <i class="bi bi-x-lg me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary px-4" id="confirmModalButton" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); border: none; border-radius: 10px;">
                    <i class="bi bi-check-lg me-2"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
</div>

<?php include 'layout_footer.php'; ?>