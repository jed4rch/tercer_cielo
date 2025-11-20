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

// === AGREGAR ===
if ($_POST) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $stock = (int)$_POST['stock'];
    $categoria = (int)$_POST['categoria'];
    
    // Manejo de la imagen principal
    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $resultado = guardarImagen($_FILES['imagen'], 'productos', 800, 800);
        if ($resultado['success']) {
            $imagen = $resultado['path'];
        } else {
            $error = $resultado['message'];
        }
    }
    
    if (!isset($error)) {
        $stmt = $pdo->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, id_categoria, imagen) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$nombre, $descripcion, $precio, $stock, $categoria, $imagen])) {
            header('Location: productos.php?success=agregado');
            exit;
        } else {
            $error = "Error al agregar producto.";
        }
    }
}

// === CATEGORÍAS ===
$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll();

$pageTitle = 'Agregar Producto';
include 'layout_header.php';
?>

<div class="content-card">
    <h3 class="mb-4"><i class="bi bi-plus-circle me-2"></i>Agregar Nuevo Producto</h3>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Precio</label>
                <input type="number" step="0.01" name="precio" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Stock</label>
                <input type="number" name="stock" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Categoría</label>
                <select name="categoria" class="form-control" required>
                    <option value="">Seleccionar</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Imagen Principal</label>
                <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" id="imagenInput">
                <small class="form-text text-muted">Formatos aceptados: JPG, PNG, GIF, WEBP. Máximo 5MB. La imagen se redimensionará automáticamente.</small>
                <div id="vistaPrevia" class="mt-3" style="display: none;">
                    <img id="imagenPrevia" src="" alt="Vista previa" style="max-width: 300px; max-height: 300px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Agregar</button>
            <a href="productos.php" class="btn btn-secondary"><i class="bi bi-x-lg"></i> Cancelar</a>
        </form>
</div>

<script>
// Vista previa de imagen
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
</script>

<?php include 'layout_footer.php'; ?>