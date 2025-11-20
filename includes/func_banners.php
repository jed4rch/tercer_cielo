<?php
require_once __DIR__ . '/../config/conexion.php';

/**
 * Obtener todos los banners
 * @param bool $solo_habilitados Si true, solo retorna banners habilitados
 * @return array
 */
function obtenerBanners($solo_habilitados = false) {
    $pdo = getPdo();
    $sql = "SELECT b.*, 
            CASE 
                WHEN b.tipo_enlace = 'producto' THEN p.nombre
                WHEN b.tipo_enlace = 'categoria' THEN c.nombre
                ELSE NULL
            END as enlace_nombre
            FROM banners b
            LEFT JOIN productos p ON b.tipo_enlace = 'producto' AND b.enlace_id = p.id
            LEFT JOIN categorias c ON b.tipo_enlace = 'categoria' AND b.enlace_id = c.id";
    
    if ($solo_habilitados) {
        $sql .= " WHERE b.habilitado = 1";
    }
    
    $sql .= " ORDER BY b.orden ASC, b.id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener un banner por ID
 * @param int $id
 * @return array|false
 */
function obtenerBannerPorId($id) {
    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Agregar un nuevo banner
 * @param array $datos
 * @return int|false ID del banner creado o false
 */
function agregarBanner($datos) {
    $pdo = getPdo();
    
    // Validar que la imagen esté presente
    if (empty($datos['imagen'])) {
        return false;
    }
    
    // Obtener el último orden
    $stmt = $pdo->query("SELECT MAX(orden) as max_orden FROM banners");
    $max_orden = $stmt->fetch(PDO::FETCH_ASSOC)['max_orden'];
    $nuevo_orden = $max_orden ? $max_orden + 1 : 1;
    
    // Solo usar columnas que existen en la BD: imagen, tipo_enlace, enlace_id, habilitado, orden
    $sql = "INSERT INTO banners (imagen, tipo_enlace, enlace_id, habilitado, orden) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute([
        $datos['imagen'],
        $datos['tipo_enlace'] ?? 'ninguno',
        $datos['enlace_id'] ?? null,
        $datos['habilitado'] ?? 1,
        $nuevo_orden
    ]);
    
    return $resultado ? $pdo->lastInsertId() : false;
}

/**
 * Actualizar un banner existente
 * @param int $id
 * @param array $datos
 * @return bool
 */
function actualizarBanner($id, $datos) {
    $pdo = getPdo();
    
    $campos = [];
    $valores = [];
    
    if (isset($datos['imagen'])) {
        $campos[] = "imagen = ?";
        $valores[] = $datos['imagen'];
    }
    
    // titulo y descripcion no existen en la BD, los ignoramos
    
    if (isset($datos['tipo_enlace'])) {
        $campos[] = "tipo_enlace = ?";
        $valores[] = $datos['tipo_enlace'];
    }
    
    if (isset($datos['enlace_id'])) {
        $campos[] = "enlace_id = ?";
        $valores[] = $datos['enlace_id'];
    }
    
    if (isset($datos['habilitado'])) {
        $campos[] = "habilitado = ?";
        $valores[] = $datos['habilitado'];
    }
    
    if (isset($datos['orden'])) {
        $campos[] = "orden = ?";
        $valores[] = $datos['orden'];
    }
    
    if (empty($campos)) {
        return false;
    }
    
    $valores[] = $id;
    $sql = "UPDATE banners SET " . implode(", ", $campos) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($valores);
}

/**
 * Eliminar un banner
 * @param int $id
 * @return bool
 */
function eliminarBanner($id) {
    $pdo = getPdo();
    
    // Obtener la imagen antes de eliminar
    $banner = obtenerBannerPorId($id);
    if (!$banner) {
        return false;
    }
    
    // Eliminar el registro
    $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
    $resultado = $stmt->execute([$id]);
    
    // Si se eliminó correctamente, eliminar el archivo de imagen
    if ($resultado && !empty($banner['imagen'])) {
        $ruta_imagen = __DIR__ . '/../public/' . $banner['imagen'];
        if (file_exists($ruta_imagen)) {
            unlink($ruta_imagen);
        }
    }
    
    return $resultado;
}

/**
 * Cambiar el estado de un banner (habilitado/deshabilitado)
 * @param int $id
 * @param int $habilitado 0 o 1
 * @return bool
 */
function cambiarEstadoBanner($id, $habilitado) {
    $pdo = getPdo();
    $stmt = $pdo->prepare("UPDATE banners SET habilitado = ? WHERE id = ?");
    return $stmt->execute([$habilitado, $id]);
}

/**
 * Subir imagen de banner
 * @param array $archivo Archivo de $_FILES
 * @return string|false Ruta relativa de la imagen o false
 */
function subirImagenBanner($archivo) {
    // Validar que se haya subido un archivo
    if (!isset($archivo['tmp_name']) || empty($archivo['tmp_name'])) {
        return false;
    }
    
    // Validar el tipo de archivo
    $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $archivo['tmp_name']);
    // finfo_close() ya no es necesario en PHP 8+ (se cierra automáticamente)
    
    if (!in_array($mime, $tipos_permitidos)) {
        return false;
    }
    
    // Validar el tamaño (máximo 5MB)
    if ($archivo['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    // Generar nombre único
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = 'banner_' . time() . '_' . uniqid() . '.' . $extension;
    
    // Ruta de destino
    $directorio = __DIR__ . '/../public/uploads/banners/';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }
    
    $ruta_destino = $directorio . $nombre_archivo;
    
    // Mover el archivo
    if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        // Retornar la ruta relativa
        return 'uploads/banners/' . $nombre_archivo;
    }
    
    return false;
}

/**
 * Obtener lista de productos para el selector
 * @return array
 */
function obtenerProductosParaBanner() {
    $pdo = getPdo();
    $stmt = $pdo->query("SELECT id, nombre, precio FROM productos ORDER BY nombre ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener lista de categorías para el selector
 * @return array
 */
function obtenerCategoriasParaBanner() {
    $pdo = getPdo();
    $stmt = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener URL del enlace según el tipo
 * @param string $tipo_enlace
 * @param int $enlace_id
 * @return string|null
 */
function obtenerUrlEnlaceBanner($tipo_enlace, $enlace_id) {
    if ($tipo_enlace === 'ninguno' || empty($enlace_id)) {
        return null;
    }
    
    if ($tipo_enlace === 'producto') {
        return 'producto.php?id=' . $enlace_id;
    }
    
    if ($tipo_enlace === 'categoria') {
        return 'catalogo.php?categoria=' . $enlace_id;
    }
    
    return null;
}
