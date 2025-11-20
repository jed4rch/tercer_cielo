<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/func_admin.php';

/**
 * Tests para funcionalidad administrativa
 * 
 * Cobertura:
 * - Estadísticas del dashboard
 * - Gestión de pedidos
 * - Reportes de ventas
 * - Reportes de stock
 * - Contadores y estadísticas
 */
final class AdminTest extends TestCase {

    /**
     * Prepara el entorno antes de cada test
     */
    protected function setUp(): void {
        $pdo = getPdo();
        $this->assertInstanceOf(PDO::class, $pdo, 'BD no conectada');
    }

    /**
     * Test: Obtener estadísticas del dashboard
     * 
     * Verifica que retorna todas las estadísticas principales
     */
    public function testGetEstadisticasAdmin(): void {
        $stats = get_estadisticas_admin();
        
        $this->assertIsArray($stats, 'Debe retornar un array');
        $this->assertArrayHasKey('usuarios', $stats, 'Debe contener contador de usuarios');
        $this->assertArrayHasKey('productos', $stats, 'Debe contener contador de productos');
        $this->assertArrayHasKey('pedidos', $stats, 'Debe contener contador de pedidos');
        $this->assertArrayHasKey('ventas', $stats, 'Debe contener total de ventas');
        
        // Verificar que son valores numéricos
        $this->assertIsNumeric($stats['usuarios']);
        $this->assertIsNumeric($stats['productos']);
        $this->assertIsNumeric($stats['pedidos']);
        $this->assertIsNumeric($stats['ventas']);
        
        // Verificar que no son negativos
        $this->assertGreaterThanOrEqual(0, $stats['usuarios']);
        $this->assertGreaterThanOrEqual(0, $stats['productos']);
        $this->assertGreaterThanOrEqual(0, $stats['pedidos']);
        $this->assertGreaterThanOrEqual(0, $stats['ventas']);
    }

    /**
     * Test: Obtener todos los pedidos sin filtros
     * 
     * Verifica que retorna lista completa de pedidos
     */
    public function testGetPedidosAdminSinFiltros(): void {
        $pedidos = get_pedidos_admin();
        
        $this->assertIsArray($pedidos, 'Debe retornar un array');
        
        // Si hay pedidos, verificar estructura
        if (count($pedidos) > 0) {
            $this->assertArrayHasKey('id', $pedidos[0]);
            $this->assertArrayHasKey('usuario', $pedidos[0]);
            $this->assertArrayHasKey('total', $pedidos[0]);
            $this->assertArrayHasKey('estado', $pedidos[0]);
        }
    }

    /**
     * Test: Obtener pedidos filtrados por estado
     * 
     * Verifica que el filtro de estado funciona correctamente
     */
    public function testGetPedidosAdminConFiltroEstado(): void {
        $pedidosPendientes = get_pedidos_admin('pendiente');
        
        $this->assertIsArray($pedidosPendientes);
        
        // Verificar que todos tienen el estado correcto
        foreach ($pedidosPendientes as $pedido) {
            $this->assertContains($pedido['estado'], ['pendiente', 'pendiente_pago'],
                'Los pedidos filtrados deben tener estado pendiente o pendiente_pago');
        }
    }

    /**
     * Test: Obtener pedidos con búsqueda
     * 
     * Verifica que la búsqueda por texto funciona
     */
    public function testGetPedidosAdminConBusqueda(): void {
        // Buscar por un término común
        $pedidos = get_pedidos_admin(null, 'admin');
        
        $this->assertIsArray($pedidos);
        // Si hay resultados, verificar que contienen el término buscado
        foreach ($pedidos as $pedido) {
            $encontrado = (
                stripos($pedido['codigo'], 'admin') !== false ||
                stripos($pedido['usuario'], 'admin') !== false
            );
            $this->assertTrue($encontrado, 'El término buscado debe aparecer en los resultados');
        }
    }

    /**
     * Test: Obtener pedidos con ordenamiento
     * 
     * Verifica que los criterios de ordenamiento funcionan
     */
    public function testGetPedidosAdminConOrdenamiento(): void {
        $pedidos = get_pedidos_admin(null, '', 'total_desc');
        
        $this->assertIsArray($pedidos);
        
        // Verificar que están ordenados por total descendente
        if (count($pedidos) > 1) {
            for ($i = 0; $i < count($pedidos) - 1; $i++) {
                $this->assertGreaterThanOrEqual(
                    (float)$pedidos[$i + 1]['total'],
                    (float)$pedidos[$i]['total'],
                    'Los pedidos deben estar ordenados por total descendente'
                );
            }
        }
    }

    /**
     * Test: Actualizar estado de pedido
     * 
     * Verifica que se puede cambiar el estado de un pedido
     */
    public function testActualizarEstadoPedido(): void {
        $pdo = getPdo();
        
        // Crear un pedido de prueba
        $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, total, estado) VALUES (2, 100.00, 'pendiente')");
        $stmt->execute();
        $pedidoId = $pdo->lastInsertId();
        
        // Actualizar estado
        $resultado = actualizar_estado_pedido($pedidoId, 'aprobado');
        $this->assertTrue($resultado, 'La actualización debe ser exitosa');
        
        // Verificar que se actualizó
        $stmt = $pdo->prepare("SELECT estado FROM pedidos WHERE id = ?");
        $stmt->execute([$pedidoId]);
        $estado = $stmt->fetchColumn();
        
        $this->assertEquals('aprobado', $estado, 'El estado debe haberse actualizado');
        
        // Limpiar
        $pdo->prepare("DELETE FROM pedidos WHERE id = ?")->execute([$pedidoId]);
    }

    /**
     * Test: Generar reporte de ventas
     * 
     * Verifica que el reporte de ventas retorna datos correctos
     */
    public function testGenerarReporteVentas(): void {
        $fechaInicio = date('Y-m-01'); // Primer día del mes
        $fechaFin = date('Y-m-d');      // Hoy
        
        $reporte = generar_reporte_ventas($fechaInicio, $fechaFin);
        
        $this->assertIsArray($reporte, 'Debe retornar un array');
        $this->assertArrayHasKey('estadisticas', $reporte);
        $this->assertArrayHasKey('datos', $reporte);
        $this->assertArrayHasKey('datos_tabla', $reporte);
        $this->assertArrayHasKey('columnas', $reporte);
        
        // Verificar estructura de estadísticas
        $stats = $reporte['estadisticas'];
        $this->assertArrayHasKey('total_ventas', $stats);
        $this->assertArrayHasKey('total_pedidos', $stats);
        $this->assertArrayHasKey('pedidos_entregados', $stats);
        
        // Verificar que son valores numéricos
        $this->assertIsNumeric($stats['total_ventas']);
        $this->assertIsNumeric($stats['total_pedidos']);
        $this->assertIsNumeric($stats['pedidos_entregados']);
    }

    /**
     * Test: Generar reporte de stock
     * 
     * Verifica que el reporte de stock retorna productos con su inventario
     */
    public function testGenerarReporteStock(): void {
        $reporte = generar_reporte_stock();
        
        $this->assertIsArray($reporte, 'Debe retornar un array');
        $this->assertArrayHasKey('datos', $reporte);
        $this->assertArrayHasKey('columnas', $reporte);
        
        // Verificar que los datos son un array
        $this->assertIsArray($reporte['datos']);
        
        // Si hay productos, verificar estructura
        if (count($reporte['datos']) > 0) {
            $producto = $reporte['datos'][0];
            $this->assertArrayHasKey('nombre', $producto);
            $this->assertArrayHasKey('stock', $producto);
        }
    }

    /**
     * Test: Generar reporte de stock por categoría
     * 
     * Verifica que se puede filtrar el reporte por categoría
     */
    public function testGenerarReporteStockPorCategoria(): void {
        $categoriaId = 1; // Asumiendo que existe categoría con ID 1
        
        $reporte = generar_reporte_stock($categoriaId);
        
        $this->assertIsArray($reporte);
        $this->assertArrayHasKey('datos', $reporte);
        
        // Verificar que todos los productos pertenecen a la categoría
        foreach ($reporte['datos'] as $producto) {
            if (isset($producto['id_categoria'])) {
                $this->assertEquals($categoriaId, $producto['id_categoria'],
                    'Todos los productos deben pertenecer a la categoría filtrada');
            }
        }
    }

    /**
     * Test: Obtener estadísticas de stock
     * 
     * Verifica que retorna productos con stock mínimo y máximo
     */
    public function testGetEstadisticasStock(): void {
        $stats = get_estadisticas_stock();
        
        $this->assertIsArray($stats, 'Debe retornar un array');
        
        // Verificar que tiene las claves esperadas
        $this->assertArrayHasKey('minimo', $stats, 'Debe tener productos con stock mínimo');
        $this->assertArrayHasKey('maximo', $stats, 'Debe tener productos con stock máximo');
        
        // Verificar que son arrays
        $this->assertIsArray($stats['minimo']);
        $this->assertIsArray($stats['maximo']);
    }

    /**
     * Test: Obtener contadores de pedidos
     * 
     * Verifica que retorna conteo por estado
     */
    public function testGetContadoresPedidos(): void {
        $contadores = get_contadores_pedidos();
        
        $this->assertIsArray($contadores, 'Debe retornar un array');
        
        // Verificar que tiene contadores por estado
        $this->assertArrayHasKey('pendiente', $contadores);
        $this->assertArrayHasKey('aprobado', $contadores);
        $this->assertArrayHasKey('enviado', $contadores);
        $this->assertArrayHasKey('entregado', $contadores);
        
        // Verificar que son valores numéricos
        foreach ($contadores as $estado => $count) {
            $this->assertIsNumeric($count, "El contador de {$estado} debe ser numérico");
            $this->assertGreaterThanOrEqual(0, $count);
        }
    }

    /**
     * Test: Verificar que estadísticas son consistentes
     * 
     * Verifica que los contadores tienen lógica coherente
     */
    public function testEstadisticasConsistentes(): void {
        $stats = get_estadisticas_admin();
        
        // El total de productos con stock > 0 no puede ser mayor al total de productos
        $pdo = getPdo();
        $totalProductos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
        
        $this->assertLessThanOrEqual($totalProductos, $stats['productos'],
            'Los productos con stock no pueden superar el total de productos');
    }
}
