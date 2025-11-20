<?php
require_once '../../includes/init.php';
require_once '../../includes/func_banners.php';

// Solo admins
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Verificar ID
if (!isset($_GET['id'])) {
    header('Location: banners.php?error=' . urlencode('ID de banner no especificado'));
    exit;
}

$banner_id = intval($_GET['id']);
$banner = obtenerBannerPorId($banner_id);

if (!$banner) {
    header('Location: banners.php?error=' . urlencode('Banner no encontrado'));
    exit;
}

$productos = obtenerProductosParaBanner();
$categorias = obtenerCategoriasParaBanner();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errores = [];
    
    $datos_actualizacion = [];
    
    // Verificar si se subió nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        // Validar dimensiones
        $imagen_temp = $_FILES['imagen']['tmp_name'];
        list($ancho, $alto) = getimagesize($imagen_temp);
        
        if ($ancho < 1280 || $alto < 400) {
            $errores[] = 'La imagen debe tener al menos 1280x400 píxeles. Recomendado: 1920x640';
        } else {
            // Subir nueva imagen
            $ruta_imagen = subirImagenBanner($_FILES['imagen']);
            
            if ($ruta_imagen) {
                // Eliminar imagen anterior
                $ruta_anterior = __DIR__ . '/../' . $banner['imagen'];
                if (file_exists($ruta_anterior)) {
                    unlink($ruta_anterior);
                }
                
                $datos_actualizacion['imagen'] = $ruta_imagen;
            } else {
                $errores[] = 'Error al subir la nueva imagen';
            }
        }
    }
    
    // Actualizar otros campos
    $datos_actualizacion['titulo'] = trim($_POST['titulo'] ?? '');
    $datos_actualizacion['descripcion'] = trim($_POST['descripcion'] ?? '');
    $datos_actualizacion['habilitado'] = isset($_POST['habilitado']) ? 1 : 0;
    
    // Validar tipo de enlace
    $tipo_enlace = $_POST['tipo_enlace'] ?? 'ninguno';
    $enlace_id = null;
    
    if ($tipo_enlace === 'producto') {
        $enlace_id = intval($_POST['producto_id'] ?? 0);
        if ($enlace_id <= 0) {
            $errores[] = 'Debe seleccionar un producto';
        }
    } elseif ($tipo_enlace === 'categoria') {
        $enlace_id = intval($_POST['categoria_id'] ?? 0);
        if ($enlace_id <= 0) {
            $errores[] = 'Debe seleccionar una categoría';
        }
    }
    
    $datos_actualizacion['tipo_enlace'] = $tipo_enlace;
    $datos_actualizacion['enlace_id'] = $enlace_id;
    
    if (empty($errores)) {
        $resultado = actualizarBanner($banner_id, $datos_actualizacion);
        
        if ($resultado) {
            header('Location: banners.php?success=editado');
            exit;
        } else {
            $errores[] = 'Error al actualizar el banner';
        }
    }
}

$pageTitle = 'Editar Banner';
include 'layout_header.php';
?>

<!-- Contenido específico de Editar Banner -->
<style>
    .form-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 123, 255, 0.1);
            margin-bottom: 1.5rem;
        }

        .preview-image {
            max-width: 100%;
            max-height: 320px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2);
        }

        .upload-area {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(0, 123, 255, 0.02);
        }

        .upload-area:hover {
            background: rgba(0, 123, 255, 0.05);
            border-color: #0056b3;
        }

        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }
</style>

    <div class="container mt-4 mb-5">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="bi bi-pencil me-2"></i>Editar Banner</h3>
                <a href="banners.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>

            <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <!-- Sección: Imagen Actual -->
            <div class="form-section">
                <h5 class="mb-3 text-primary"><i class="bi bi-image"></i> Imagen Actual</h5>
                <div class="text-center mb-3">
                    <img src="../<?= htmlspecialchars($banner['imagen']) ?>" 
                         alt="Banner actual" 
                         class="preview-image">
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Cambiar imagen (Opcional)</strong><br>
                    Solo sube una nueva imagen si deseas reemplazar la actual. Si no subes nada, se mantendrá la imagen actual.
                </div>

                <div class="upload-area" onclick="document.getElementById('imagen').click()">
                    <i class="bi bi-cloud-upload" style="font-size: 3rem; color: #007bff;"></i>
                    <p class="mb-0 mt-2"><strong>Haz clic para seleccionar nueva imagen</strong></p>
                    <small class="text-muted">Dimensiones recomendadas: 1920 x 640 píxeles</small>
                </div>

                <input type="file" 
                       name="imagen" 
                       id="imagen" 
                       class="form-control d-none" 
                       accept="image/*">

                <div class="text-center mt-3">
                    <img id="preview" class="preview-image" style="display:none;">
                </div>
            </div>

            <!-- Sección: Enlace -->
            <div class="form-section">
                <h5 class="mb-3 text-primary"><i class="bi bi-link-45deg"></i> Enlace del Banner</h5>

                <div class="mb-3">
                    <label class="form-label">Tipo de enlace</label>
                    <select name="tipo_enlace" id="tipo_enlace" class="form-select">
                        <option value="ninguno" <?= $banner['tipo_enlace'] === 'ninguno' ? 'selected' : '' ?>>
                            Sin enlace
                        </option>
                        <option value="producto" <?= $banner['tipo_enlace'] === 'producto' ? 'selected' : '' ?>>
                            Enlazar a producto específico
                        </option>
                        <option value="categoria" <?= $banner['tipo_enlace'] === 'categoria' ? 'selected' : '' ?>>
                            Enlazar a categoría específica
                        </option>
                    </select>
                </div>

                <!-- Selector de Producto -->
                <div id="selector-producto" class="mb-3" style="display: none;">
                    <label class="form-label">Seleccionar Producto</label>
                    <select name="producto_id" class="form-select">
                        <option value="">-- Seleccione un producto --</option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?= $producto['id'] ?>" 
                                <?= $banner['enlace_id'] == $producto['id'] && $banner['tipo_enlace'] === 'producto' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($producto['nombre']) ?> - S/ <?= number_format($producto['precio'], 2) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Selector de Categoría -->
                <div id="selector-categoria" class="mb-3" style="display: none;">
                    <label class="form-label">Seleccionar Categoría</label>
                    <select name="categoria_id" class="form-select">
                        <option value="">-- Seleccione una categoría --</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>"
                                <?= $banner['enlace_id'] == $categoria['id'] && $banner['tipo_enlace'] === 'categoria' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <div class="form-check">
                    <input type="checkbox" 
                           name="habilitado" 
                           class="form-check-input" 
                           id="habilitado"
                           <?= $banner['habilitado'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="habilitado">
                        <strong>Banner habilitado</strong>
                    </label>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-save"></i> Actualizar Banner
                </button>
                <a href="banners.php" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </form>
        </div>
    </div>
</div>
<!-- Fin Content Area -->
</div>
<!-- Fin Main Content -->

<script>
        // Preview de imagen
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Toggle selectores de enlace
        const tipoEnlace = document.getElementById('tipo_enlace');
        const selectorProducto = document.getElementById('selector-producto');
        const selectorCategoria = document.getElementById('selector-categoria');

        function actualizarSelectores() {
            const valor = tipoEnlace.value;
            
            selectorProducto.style.display = valor === 'producto' ? 'block' : 'none';
            selectorCategoria.style.display = valor === 'categoria' ? 'block' : 'none';
        }

        tipoEnlace.addEventListener('change', actualizarSelectores);
        actualizarSelectores(); // Ejecutar al cargar
    </script>

<?php include 'layout_footer.php'; ?>
