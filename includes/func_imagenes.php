<?php
/**
 * Funciones para el manejo de imágenes
 * Validación, redimensionamiento y guardado de archivos de imagen
 */

/**
 * Valida si un archivo de imagen es válido
 * @param array $file Array $_FILES de la imagen
 * @return array ['success' => bool, 'message' => string]
 */
function validarImagen($file) {
    // Verificar que el archivo fue subido
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'message' => 'No se ha seleccionado ningún archivo'];
    }
    
    // Verificar errores de carga
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error al subir el archivo'];
    }
    
    // Verificar tamaño (máximo 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB en bytes
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB'];
    }
    
    // Verificar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido. Solo JPG, PNG, GIF y WEBP'];
    }
    
    // Verificar que es realmente una imagen
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'message' => 'El archivo no es una imagen válida'];
    }
    
    return ['success' => true, 'message' => 'Imagen válida'];
}

/**
 * Guarda una imagen en el servidor
 * @param array $file Array $_FILES de la imagen
 * @param string $directory Directorio donde guardar (relativo a public/uploads/)
 * @param int $maxWidth Ancho máximo para redimensionar (opcional)
 * @param int $maxHeight Alto máximo para redimensionar (opcional)
 * @return array ['success' => bool, 'message' => string, 'path' => string]
 */
function guardarImagen($file, $directory, $maxWidth = null, $maxHeight = null) {
    // Validar imagen
    $validacion = validarImagen($file);
    if (!$validacion['success']) {
        return $validacion;
    }
    
    // Crear directorio si no existe
    $uploadDir = __DIR__ . '/../public/uploads/' . $directory;
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'message' => 'Error al crear el directorio'];
        }
    }
    
    // Generar nombre único
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nombreUnico = uniqid() . '_' . time() . '.' . $extension;
    $rutaCompleta = $uploadDir . '/' . $nombreUnico;
    
    // Si se especifica redimensionamiento y GD está disponible
    if (($maxWidth || $maxHeight) && extension_loaded('gd')) {
        $resultado = redimensionarImagen($file['tmp_name'], $rutaCompleta, $maxWidth, $maxHeight);
        if (!$resultado['success']) {
            return $resultado;
        }
    } else {
        // Mover archivo sin redimensionar
        if (!move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
            return ['success' => false, 'message' => 'Error al guardar el archivo'];
        }
    }
    
    // Retornar ruta relativa desde public/
    $rutaRelativa = '/tercer_cielo/public/uploads/' . $directory . '/' . $nombreUnico;
    
    return [
        'success' => true,
        'message' => 'Imagen guardada exitosamente',
        'path' => $rutaRelativa,
        'filename' => $nombreUnico
    ];
}

/**
 * Redimensiona una imagen manteniendo la proporción
 * @param string $origen Ruta del archivo original
 * @param string $destino Ruta donde guardar la imagen redimensionada
 * @param int $maxWidth Ancho máximo
 * @param int $maxHeight Alto máximo
 * @return array ['success' => bool, 'message' => string]
 */
function redimensionarImagen($origen, $destino, $maxWidth = null, $maxHeight = null) {
    // Obtener información de la imagen
    list($width, $height, $type) = getimagesize($origen);
    
    // Crear imagen desde el origen según el tipo
    switch ($type) {
        case IMAGETYPE_JPEG:
            $imagenOrigen = imagecreatefromjpeg($origen);
            break;
        case IMAGETYPE_PNG:
            $imagenOrigen = imagecreatefrompng($origen);
            break;
        case IMAGETYPE_GIF:
            $imagenOrigen = imagecreatefromgif($origen);
            break;
        case IMAGETYPE_WEBP:
            $imagenOrigen = imagecreatefromwebp($origen);
            break;
        default:
            return ['success' => false, 'message' => 'Tipo de imagen no soportado'];
    }
    
    if (!$imagenOrigen) {
        return ['success' => false, 'message' => 'Error al procesar la imagen'];
    }
    
    // Calcular nuevas dimensiones manteniendo la proporción
    $nuevoWidth = $width;
    $nuevoHeight = $height;
    
    if ($maxWidth && $width > $maxWidth) {
        $nuevoWidth = $maxWidth;
        $nuevoHeight = ($height * $maxWidth) / $width;
    }
    
    if ($maxHeight && $nuevoHeight > $maxHeight) {
        $nuevoHeight = $maxHeight;
        $nuevoWidth = ($nuevoWidth * $maxHeight) / $nuevoHeight;
    }
    
    // Solo redimensionar si es necesario
    if ($nuevoWidth < $width || $nuevoHeight < $height) {
        // Crear nueva imagen
        $imagenNueva = imagecreatetruecolor($nuevoWidth, $nuevoHeight);
        
        // Preservar transparencia para PNG y GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($imagenNueva, false);
            imagesavealpha($imagenNueva, true);
            $transparent = imagecolorallocatealpha($imagenNueva, 255, 255, 255, 127);
            imagefilledrectangle($imagenNueva, 0, 0, $nuevoWidth, $nuevoHeight, $transparent);
        }
        
        // Redimensionar
        imagecopyresampled($imagenNueva, $imagenOrigen, 0, 0, 0, 0, $nuevoWidth, $nuevoHeight, $width, $height);
    } else {
        $imagenNueva = $imagenOrigen;
    }
    
    // Guardar según el tipo
    $resultado = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $resultado = imagejpeg($imagenNueva, $destino, 90);
            break;
        case IMAGETYPE_PNG:
            $resultado = imagepng($imagenNueva, $destino, 9);
            break;
        case IMAGETYPE_GIF:
            $resultado = imagegif($imagenNueva, $destino);
            break;
        case IMAGETYPE_WEBP:
            $resultado = imagewebp($imagenNueva, $destino, 90);
            break;
    }
    
    // Liberar memoria
    imagedestroy($imagenOrigen);
    if ($imagenNueva !== $imagenOrigen) {
        imagedestroy($imagenNueva);
    }
    
    if (!$resultado) {
        return ['success' => false, 'message' => 'Error al guardar la imagen redimensionada'];
    }
    
    return ['success' => true, 'message' => 'Imagen redimensionada exitosamente'];
}

/**
 * Elimina un archivo de imagen del servidor
 * @param string $path Ruta de la imagen a eliminar
 * @return bool True si se eliminó exitosamente
 */
function eliminarImagen($path) {
    if (empty($path)) {
        return false;
    }
    
    // Si es una ruta relativa desde public/, convertir a ruta absoluta
    if (strpos($path, '/tercer_cielo/public/') === 0) {
        $path = str_replace('/tercer_cielo/public/', __DIR__ . '/../public/', $path);
    }
    
    // Verificar que el archivo existe y está en el directorio de uploads
    if (file_exists($path) && strpos($path, 'uploads') !== false) {
        return unlink($path);
    }
    
    return false;
}

/**
 * Obtiene la extensión de archivo desde un mime type
 * @param string $mimeType Tipo MIME
 * @return string Extensión del archivo
 */
function obtenerExtensionPorMime($mimeType) {
    $mimeTypes = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    
    return $mimeTypes[$mimeType] ?? 'jpg';
}
