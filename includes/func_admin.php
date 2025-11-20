<?php
// RUTA ABSOLUTA DESDE INCLUDES A CONFIG
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/func_correo.php';

/**
 * Obtiene estadísticas principales del dashboard administrativo
 * 
 * @return array Arreglo con contadores de:
 *               - usuarios: Total de usuarios registrados
 *               - productos: Total de productos con stock > 0
 *               - pedidos: Total de pedidos en el sistema
 *               - ventas: Suma total de pedidos entregados
 */
function get_estadisticas_admin() {
    $pdo = getPdo();
    $stats = [];
    $stats['usuarios'] = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $stats['productos'] = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock > 0")->fetchColumn();
    $stats['pedidos'] = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
    $stats['ventas'] = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE estado = 'entregado'")->fetchColumn();
    return $stats;
}

/**
 * Obtiene lista de pedidos con filtros y ordenamiento
 * 
 * @param string|null $estado Estado del pedido (pendiente, aprobado, rechazado, enviado, entregado)
 *                            Si es 'pendiente', incluye también 'pendiente_pago'
 * @param string $search Término de búsqueda en código de pedido, nombre o email
 * @param string $orderby Criterio de ordenamiento: fecha_asc, fecha_desc, total_asc, 
 *                        total_desc, estado_asc, estado_desc
 * @return array Lista de pedidos con información del usuario
 */
function get_pedidos_admin($estado = null, $search = '', $orderby = '') {
    $pdo = getPdo();
    $sql = "SELECT p.*, u.nombre as usuario FROM pedidos p JOIN usuarios u ON p.usuario_id = u.id";
    $params = [];
    $where = [];
    
    if ($estado) {
        // Si el filtro es "pendiente", incluir ambos estados: pendiente y pendiente_pago
        if ($estado === 'pendiente') {
            $where[] = "p.estado IN ('pendiente', 'pendiente_pago')";
        } else {
            $where[] = "p.estado = ?";
            $params[] = $estado;
        }
    }
    
    if ($search) {
        $where[] = "(p.codigo LIKE ? OR u.nombre LIKE ? OR u.email LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    // Determinar ordenamiento
    $order = 'p.creado_en DESC';
    switch($orderby) {
        case 'fecha_asc': $order = 'p.creado_en ASC'; break;
        case 'fecha_desc': $order = 'p.creado_en DESC'; break;
        case 'total_asc': $order = 'p.total ASC'; break;
        case 'total_desc': $order = 'p.total DESC'; break;
        case 'estado_asc': $order = 'p.estado ASC'; break;
        case 'estado_desc': $order = 'p.estado DESC'; break;
    }
    
    $sql .= " ORDER BY $order";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Actualiza el estado de un pedido y envía notificación por correo
 * 
 * @param int $pedido_id ID del pedido
 * @param string $nuevo_estado Nuevo estado del pedido
 * @return bool True si se actualizó correctamente
 */
function actualizar_estado_pedido($pedido_id, $nuevo_estado) {
    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT estado FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido_id]);
    $anterior = $stmt->fetchColumn();
    
    if ($anterior !== false) {
        $pdo->prepare("INSERT INTO historial_pedidos (pedido_id, estado_anterior, estado_nuevo) VALUES (?, ?, ?)")
            ->execute([$pedido_id, $anterior, $nuevo_estado]);
    }

    $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?")->execute([$nuevo_estado, $pedido_id]);

    // Correo
    $stmt = $pdo->prepare("SELECT u.email, u.nombre FROM usuarios u JOIN pedidos p ON u.id = p.usuario_id WHERE p.id = ?");
    $stmt->execute([$pedido_id]);
    $user = $stmt->fetch();
    if ($user) {
        $cuerpo = "Tu pedido ha cambiado a " . ucfirst($nuevo_estado) . ". Te contactaremos pronto.";
        enviar_correo($user['email'], $user['nombre'], "Estado actualizado", $cuerpo);
    }

    return true;
}

/**
 * Genera reporte de ventas por periodo
 */
function generar_reporte_ventas($fecha_inicio, $fecha_fin) {
    $pdo = getPdo();
    
    // Estadísticas generales
    $estadisticas = [];
    
    // Si no hay fechas, mostrar todos los datos
    $whereFecha = '';
    $params = [];
    if ($fecha_inicio && $fecha_fin) {
        $whereFecha = "AND DATE(creado_en) BETWEEN ? AND ?";
        $params = [$fecha_inicio, $fecha_fin];
    }
    
    $sql = "
        SELECT COALESCE(SUM(total), 0)
        FROM pedidos
        WHERE estado = 'entregado'
        $whereFecha
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $estadisticas['total_ventas'] = $stmt->fetchColumn() ?: 0;
    
    $whereFechaSimple = $fecha_inicio && $fecha_fin ? "WHERE DATE(creado_en) BETWEEN ? AND ?" : "";
    
    $sql = "
        SELECT COUNT(*)
        FROM pedidos
        $whereFechaSimple
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $estadisticas['total_pedidos'] = $stmt->fetchColumn() ?: 0;
    
    $sql = "
        SELECT COUNT(*)
        FROM pedidos
        WHERE estado = 'entregado'
        $whereFecha
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $estadisticas['pedidos_entregados'] = $stmt->fetchColumn() ?: 0;

    // Datos detallados - Solo pedidos entregados para gráficos
    $sql = "
        SELECT
            p.codigo,
            u.nombre as cliente,
            p.total,
            p.estado,
            p.metodo_pago,
            DATE(p.creado_en) as fecha,
            COUNT(pd.id) as productos_count
        FROM pedidos p
        JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN pedido_detalles pd ON p.id = pd.pedido_id
        WHERE p.estado = 'entregado'
        $whereFecha
        GROUP BY p.id
        ORDER BY p.creado_en DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Datos de todos los pedidos para la tabla
    $sql = "
        SELECT
            p.codigo,
            u.nombre as cliente,
            p.total,
            p.estado,
            p.metodo_pago,
            DATE(p.creado_en) as fecha,
            COUNT(pd.id) as productos_count
        FROM pedidos p
        JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN pedido_detalles pd ON p.id = pd.pedido_id
        $whereFechaSimple
        GROUP BY p.id
        ORDER BY p.creado_en DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos_tabla = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'estadisticas' => $estadisticas,
        'datos' => $datos, // Solo entregados para gráficos
        'datos_tabla' => $datos_tabla, // Todos los pedidos para la tabla
        'columnas' => ['Código', 'Cliente', 'Total', 'Estado', 'Método Pago', 'Fecha', 'Productos']
    ];
}

/**
 * Genera reporte de stock con niveles críticos
 */
function generar_reporte_stock($categoria_id = '') {
    $pdo = getPdo();
    
    $sql = "
        SELECT
            p.id,
            p.nombre,
            c.nombre as categoria,
            p.stock,
            p.precio,
            CASE
                WHEN p.stock <= 5 THEN 'Crítico'
                WHEN p.stock <= 10 THEN 'Bajo'
                ELSE 'Normal'
            END as estado_stock
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id
        WHERE p.stock >= 0
    ";
    
    $params = [];
    if ($categoria_id) {
        $sql .= " AND p.id_categoria = ?";
        $params[] = $categoria_id;
    }
    
    $sql .= " ORDER BY p.stock ASC, p.nombre ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas
    $estadisticas = [];
    $estadisticas['total_productos'] = count($datos);
    $estadisticas['stock_critico'] = count(array_filter($datos, function($item) {
        return $item['estado_stock'] === 'Crítico';
    }));
    $estadisticas['stock_bajo'] = count(array_filter($datos, function($item) {
        return $item['estado_stock'] === 'Bajo';
    }));

    return [
        'estadisticas' => $estadisticas,
        'datos' => $datos,
        'columnas' => ['ID', 'Producto', 'Categoría', 'Stock', 'Precio', 'Estado Stock']
    ];
}

/**
 * Genera reporte de clientes
 */
function generar_reporte_clientes() {
    $pdo = getPdo();
    
    $stmt = $pdo->query("
        SELECT
            u.id,
            u.nombre,
            u.email,
            u.telefono,
            COUNT(p.id) as total_pedidos,
            COALESCE(SUM(p.total), 0) as total_compras,
            MAX(p.creado_en) as ultima_compra
        FROM usuarios u
        LEFT JOIN pedidos p ON u.id = p.usuario_id
        WHERE u.rol = 'cliente'
        GROUP BY u.id, u.nombre, u.email, u.telefono
        ORDER BY total_compras DESC
    ");
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas
    $estadisticas = [];
    $estadisticas['total_clientes'] = count($datos);
    $estadisticas['clientes_activos'] = count(array_filter($datos, function($item) {
        return $item['total_pedidos'] > 0;
    }));

    return [
        'estadisticas' => $estadisticas,
        'datos' => $datos,
        'columnas' => ['ID', 'Nombre', 'Email', 'Teléfono', 'Total Pedidos', 'Total Compras', 'Última Compra']
    ];
}

/**
 * Genera reporte de productos más vendidos
 */
function generar_reporte_productos_mas_vendidos($fecha_inicio, $fecha_fin) {
    $pdo = getPdo();
    
    // Si no hay fechas, mostrar todos los datos
    $whereFecha = '';
    $params = [];
    if ($fecha_inicio && $fecha_fin) {
        $whereFecha = "AND DATE(ped.creado_en) BETWEEN ? AND ?";
        $params = [$fecha_inicio, $fecha_fin];
    }
    
    $sql = "
        SELECT
            p.nombre,
            c.nombre as categoria,
            SUM(pd.cantidad) as total_vendido,
            SUM(pd.cantidad * pd.precio) as total_ingresos,
            AVG(pd.precio) as precio_promedio
        FROM pedido_detalles pd
        JOIN productos p ON pd.producto_id = p.id
        LEFT JOIN categorias c ON p.id_categoria = c.id
        JOIN pedidos ped ON pd.pedido_id = ped.id
        WHERE ped.estado = 'entregado'
        $whereFecha
        GROUP BY p.id, p.nombre, c.nombre
        ORDER BY total_vendido DESC
        LIMIT 20
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas
    $estadisticas = [];
    $estadisticas['total_productos'] = count($datos);
    $estadisticas['total_unidades_vendidas'] = array_sum(array_column($datos, 'total_vendido'));
    $estadisticas['total_ingresos'] = array_sum(array_column($datos, 'total_ingresos'));

    return [
        'estadisticas' => $estadisticas,
        'datos' => $datos,
        'columnas' => ['Producto', 'Categoría', 'Unidades Vendidas', 'Total Ingresos', 'Precio Promedio']
    ];
}

/**
 * Exporta reporte en diferentes formatos
 */
function exportar_reporte($reporte_data, $titulo, $formato, $tipo_reporte) {
    switch ($formato) {
        case 'csv':
            exportar_csv($reporte_data, $titulo, $tipo_reporte);
            break;
        case 'excel':
            exportar_excel($reporte_data, $titulo, $tipo_reporte);
            break;
        case 'pdf':
        default:
            exportar_pdf($reporte_data, $titulo, $tipo_reporte);
            break;
    }
}

/**
 * Exporta reporte a CSV
 */
function exportar_csv($reporte_data, $titulo, $tipo_reporte) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $tipo_reporte . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Encabezados
    fputcsv($output, [$titulo]);
    fputcsv($output, ['Generado: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []); // Línea vacía
    
    // Columnas
    fputcsv($output, $reporte_data['columnas']);
    
    // Datos
    foreach ($reporte_data['datos'] as $fila) {
        fputcsv($output, $fila);
    }
    
    fclose($output);
    exit;
}

/**
 * Exporta reporte a Excel (CSV con formato Excel)
 */
function exportar_excel($reporte_data, $titulo, $tipo_reporte) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $tipo_reporte . '_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr><th colspan='" . count($reporte_data['columnas']) . "'>" . $titulo . "</th></tr>";
    echo "<tr><th colspan='" . count($reporte_data['columnas']) . "'>Generado: " . date('Y-m-d H:i:s') . "</th></tr>";
    echo "<tr>";
    foreach ($reporte_data['columnas'] as $columna) {
        echo "<th>" . $columna . "</th>";
    }
    echo "</tr>";
    
    foreach ($reporte_data['datos'] as $fila) {
        echo "<tr>";
        foreach ($fila as $valor) {
            echo "<td>" . $valor . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

/**
 * Exporta reporte a HTML optimizado para impresión (que puede guardarse como PDF)
 */
function exportar_pdf($reporte_data, $titulo, $tipo_reporte) {
    // Generar HTML optimizado para impresión
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $tipo_reporte . '_' . date('Y-m-d') . '.html"');
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . $titulo . '</title>
        <style>
            @media print {
                body { margin: 0; padding: 15px; }
                .no-print { display: none !important; }
                .print-button { display: none !important; }
                .page-break { page-break-after: always; }
            }
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                color: #333;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #007bff;
                padding-bottom: 15px;
            }
            .header h1 {
                color: #007bff;
                margin: 0 0 10px 0;
                font-size: 24px;
            }
            .header p {
                color: #666;
                margin: 0;
                font-size: 14px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                font-size: 11px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 6px;
                text-align: left;
            }
            th {
                background-color: #007bff;
                color: white;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            .stats {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .stats h3 {
                margin-top: 0;
                color: #495057;
                font-size: 16px;
            }
            .print-button {
                background-color: #007bff;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                margin-bottom: 20px;
                font-size: 14px;
            }
            .instructions {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <button class="print-button no-print" onclick="window.print()">🖨️ Imprimir como PDF</button>
        
        <div class="instructions no-print">
            <strong>Instrucciones:</strong> Use el botón "Imprimir como PDF" arriba o presione Ctrl+P.
            En el diálogo de impresión, seleccione "Guardar como PDF" como destino.
        </div>
        
        <div class="header">
            <h1>' . $titulo . '</h1>
            <p><strong>Generado:</strong> ' . date('Y-m-d H:i:s') . '</p>
        </div>';
    
    // Mostrar estadísticas si existen
    if (isset($reporte_data['estadisticas']) && !empty($reporte_data['estadisticas'])) {
        $html .= '<div class="stats">';
        $html .= '<h3>Estadísticas del Reporte</h3>';
        foreach ($reporte_data['estadisticas'] as $key => $value) {
            $label = ucfirst(str_replace('_', ' ', $key));
            // Para estadísticas que no sean precios, ingresos o ventas, usar números enteros
            $is_monetary = in_array($key, ['precio', 'ingresos', 'ventas', 'total_ventas', 'total_ingresos', 'total_compras', 'precio_promedio']);
            
            if (is_numeric($value)) {
                if ($is_monetary) {
                    $display_value = 'S/ ' . number_format($value, 2);
                } else {
                    $display_value = number_format($value, 0);
                }
            } else {
                $display_value = $value;
            }
            $html .= '<p><strong>' . $label . ':</strong> ' . $display_value . '</p>';
        }
        $html .= '</div>';
    }
    
    $html .= '<table>
            <thead>
                <tr>';
    
    foreach ($reporte_data['columnas'] as $columna) {
        $html .= '<th>' . $columna . '</th>';
    }
    $html .= '</tr>
            </thead>
            <tbody>';
    
    if (empty($reporte_data['datos'])) {
        $html .= '<tr><td colspan="' . count($reporte_data['columnas']) . '" style="text-align: center;">No hay datos para mostrar</td></tr>';
    } else {
        foreach ($reporte_data['datos'] as $fila) {
            $html .= '<tr>';
            foreach ($fila as $valor) {
                if (is_numeric($valor)) {
                    // Determinar si la columna es monetaria
                    $columna_lower = strtolower(implode('', $reporte_data['columnas']));
                    $is_monetary_col = strpos($columna_lower, 'precio') !== false ||
                                      strpos($columna_lower, 'total') !== false ||
                                      strpos($columna_lower, 'ingreso') !== false;
                    
                    if ($is_monetary_col) {
                        $display_val = 'S/ ' . number_format($valor, 2);
                    } else {
                        $display_val = number_format($valor, 0);
                    }
                } else {
                    $display_val = htmlspecialchars($valor);
                }
                $html .= '<td>' . $display_val . '</td>';
            }
            $html .= '</tr>';
        }
    }
    
    $html .= '</tbody>
        </table>
        
        <script>
            // Auto-imprimir al cargar la página (opcional, comentado para no ser intrusivo)
            // window.onload = function() {
            //     window.print();
            // };
        </script>
    </body>
    </html>';
    
    echo $html;
    exit;
}

/**
 * Obtiene estadísticas de stock de productos
 */
function get_estadisticas_stock() {
    $pdo = getPdo();
    
    // Top 3 productos con stock mínimo
    $stmt = $pdo->query("
        SELECT nombre, stock 
        FROM productos 
        WHERE stock > 0 
        ORDER BY stock ASC 
        LIMIT 3
    ");
    $stock_minimo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top 3 productos con stock máximo
    $stmt = $pdo->query("
        SELECT nombre, stock 
        FROM productos 
        WHERE stock > 0 
        ORDER BY stock DESC 
        LIMIT 3
    ");
    $stock_maximo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'minimo' => $stock_minimo,
        'maximo' => $stock_maximo
    ];
}

/**
 * Obtiene productos más y menos vendidos
 */
function get_productos_vendidos() {
    $pdo = getPdo();
    
    // Productos más vendidos (top 3) - Solo productos que están en pedidos entregados
    $stmt = $pdo->query("
        SELECT p.nombre, SUM(pd.cantidad) as total_vendido
        FROM pedido_detalles pd
        INNER JOIN productos p ON pd.producto_id = p.id
        INNER JOIN pedidos ped ON pd.pedido_id = ped.id
        WHERE ped.estado = 'entregado'
        GROUP BY pd.producto_id, p.nombre
        ORDER BY total_vendido DESC
        LIMIT 3
    ");
    $mas_vendidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Productos menos vendidos (top 3 con menos ventas) - Solo productos que están en pedidos entregados
    $stmt = $pdo->query("
        SELECT p.nombre, SUM(pd.cantidad) as total_vendido
        FROM pedido_detalles pd
        INNER JOIN productos p ON pd.producto_id = p.id
        INNER JOIN pedidos ped ON pd.pedido_id = ped.id
        WHERE ped.estado = 'entregado'
        GROUP BY pd.producto_id, p.nombre
        ORDER BY total_vendido ASC
        LIMIT 3
    ");
    $menos_vendidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'mas_vendidos' => $mas_vendidos,
        'menos_vendidos' => $menos_vendidos
    ];
}

/**
 * Obtiene categorías más vendidas
 */
function get_categorias_vendidas() {
    $pdo = getPdo();
    
    $stmt = $pdo->query("
        SELECT c.nombre, COALESCE(SUM(pd.cantidad), 0) as total_vendido
        FROM categorias c
        LEFT JOIN productos p ON c.id = p.id_categoria
        LEFT JOIN pedido_detalles pd ON p.id = pd.producto_id
        LEFT JOIN pedidos ped ON pd.pedido_id = ped.id
        WHERE ped.estado = 'entregado' OR ped.estado IS NULL
        GROUP BY c.id, c.nombre
        ORDER BY total_vendido DESC
        LIMIT 3
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene contadores de pedidos por estado
 */
function get_contadores_pedidos() {
    $pdo = getPdo();
    
    $estados = ['pendiente', 'aprobado', 'rechazado', 'enviado', 'entregado'];
    $contadores = [];
    
    foreach ($estados as $estado) {
        // Para pendiente, incluir también pendiente_pago
        if ($estado === 'pendiente') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE estado IN ('pendiente', 'pendiente_pago')");
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE estado = ?");
            $stmt->execute([$estado]);
        }
        $contadores[$estado] = $stmt->fetchColumn();
    }
    
    return $contadores;
}
?>