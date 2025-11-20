<?php
require_once __DIR__ . '/../config/conexion.php';

/**
 * Obtiene lista de productos, filtrado por categoría o búsqueda.
 * @param int|null $categoria ID de categoría.
 * @param string|null $busqueda Término de búsqueda en nombre.
 * @return array Lista de productos con categoría.
 */
function get_productos($categoria = null, $busqueda = null)
{
    $pdo = getPdo();
    if (!$pdo) return [];
    $sql = "SELECT p.*, c.nombre as cat_nombre FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id";
    $params = [];
    $where = false;
    if ($categoria) {
        $sql .= " WHERE p.id_categoria = ?";
        $params[] = $categoria;
        $where = true;
    }
    if ($busqueda) {
        $cond = $where ? " AND " : " WHERE ";
        $sql .= $cond . " p.nombre LIKE ?";
        $params[] = "%$busqueda%";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene un producto por ID.
 * @param int $id ID del producto.
 * @return array|null Producto o null si no existe.
 */
function get_producto_by_id($id)
{
    $pdo = getPdo();
    if (!$pdo) return null;
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Agrega un nuevo producto a BD.
 * @param string $nombre Nombre del producto.
 * @param string $descripcion Descripción.
 * @param float $precio Precio.
 * @param int $stock Stock inicial.
 * @param int $id_categoria ID categoría.
 * @param string|null $imagen Ruta imagen.
 * @return bool True si insert exitoso.
 */
function agregar_producto($nombre, $descripcion, $precio, $stock, $id_categoria, $imagen = null)
{
    $pdo = getPdo();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, id_categoria, imagen) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$nombre, $descripcion, $precio, $stock, $id_categoria, $imagen]);
}

/**
 * Actualiza un producto en BD.
 * @param int $id ID del producto.
 * @param string $nombre Nuevo nombre.
 * @param string $descripcion Nueva descripción.
 * @param float $precio Nuevo precio.
 * @param int $stock Nuevo stock.
 * @param int $id_categoria ID categoría.
 * @param string|null $imagen Nueva imagen (opcional).
 * @return bool True si update exitoso.
 */
function actualizar_producto($id, $nombre, $descripcion, $precio, $stock, $id_categoria, $imagen = null)
{
    $pdo = getPdo();
    if (!$pdo) return false;
    $sql = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, id_categoria = ? WHERE id = ?";
    $params = [$nombre, $descripcion, $precio, $stock, $id_categoria, $id];
    if ($imagen) {
        $sql = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, id_categoria = ?, imagen = ? WHERE id = ?";
        $params = [$nombre, $descripcion, $precio, $stock, $id_categoria, $imagen, $id];
    }
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Elimina un producto de BD.
 * @param int $id ID del producto.
 * @return bool True si delete exitoso.
 */
function eliminar_producto($id)
{
    $pdo = getPdo();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
    return $stmt->execute([$id]);
}

// Obtener todas las categorías con imagen
function get_categorias()
{
    $pdo = getPdo();
    $stmt = $pdo->query("SELECT id, nombre, imagen, descripcion FROM categorias ORDER BY nombre");
    return $stmt->fetchAll();
}
