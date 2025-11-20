<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/func_inventario.php';
require_once __DIR__ . '/../includes/func_productos.php';

/**
 * Tests para funcionalidad de inventario
 * 
 * Cobertura:
 * - Registrar movimientos de entrada
 * - Registrar movimientos de salida
 * - Actualización de stock
 * - Manejo de transacciones
 */
final class InventarioTest extends TestCase {
    private $testProductoId = 1; // Usar producto existente (Martillo)
    private $stockOriginal;

    /**
     * Prepara el entorno antes de cada test
     */
    protected function setUp(): void {
        $pdo = getPdo();
        $this->assertInstanceOf(PDO::class, $pdo, 'BD no conectada');
        
        // Guardar stock original
        $producto = get_producto_by_id($this->testProductoId);
        $this->stockOriginal = $producto['stock'];
    }

    /**
     * Test: Registrar movimiento de entrada
     * 
     * Verifica que una entrada suma al stock correctamente
     */
    public function testRegistrarMovimientoEntrada(): void {
        $cantidadEntrada = 10;
        
        $resultado = registrar_movimiento($this->testProductoId, 'entrada', $cantidadEntrada);
        
        $this->assertTrue($resultado, 'El movimiento debe registrarse correctamente');
        
        // Verificar que el stock aumentó
        $producto = get_producto_by_id($this->testProductoId);
        $stockEsperado = $this->stockOriginal + $cantidadEntrada;
        
        $this->assertEquals($stockEsperado, $producto['stock'], 
            "El stock debe aumentar de {$this->stockOriginal} a {$stockEsperado}");
    }

    /**
     * Test: Registrar movimiento de salida
     * 
     * Verifica que una salida resta del stock correctamente
     */
    public function testRegistrarMovimientoSalida(): void {
        $cantidadSalida = 5;
        
        // Asegurar que hay stock suficiente
        if ($this->stockOriginal < $cantidadSalida) {
            registrar_movimiento($this->testProductoId, 'entrada', 10);
            $this->stockOriginal = get_producto_by_id($this->testProductoId)['stock'];
        }
        
        $resultado = registrar_movimiento($this->testProductoId, 'salida', $cantidadSalida);
        
        $this->assertTrue($resultado, 'El movimiento debe registrarse correctamente');
        
        // Verificar que el stock disminuyó
        $producto = get_producto_by_id($this->testProductoId);
        $stockEsperado = $this->stockOriginal - $cantidadSalida;
        
        $this->assertEquals($stockEsperado, $producto['stock'],
            "El stock debe disminuir de {$this->stockOriginal} a {$stockEsperado}");
    }

    /**
     * Test: Registrar movimiento con cantidad cero falla
     * 
     * Verifica que no se permiten movimientos con cantidad 0
     */
    public function testRegistrarMovimientoConCantidadCero(): void {
        $resultado = registrar_movimiento($this->testProductoId, 'entrada', 0);
        
        $this->assertFalse($resultado, 'No debe permitir movimiento con cantidad 0');
    }

    /**
     * Test: Registrar movimiento con cantidad negativa falla
     * 
     * Verifica que no se permiten movimientos con cantidad negativa
     */
    public function testRegistrarMovimientoConCantidadNegativa(): void {
        $resultado = registrar_movimiento($this->testProductoId, 'entrada', -5);
        
        $this->assertFalse($resultado, 'No debe permitir movimiento con cantidad negativa');
    }

    /**
     * Test: Múltiples movimientos consecutivos
     * 
     * Verifica que múltiples movimientos actualizan el stock correctamente
     */
    public function testMultiplesMovimientos(): void {
        $stockInicial = $this->stockOriginal;
        
        // Entrada de 20
        registrar_movimiento($this->testProductoId, 'entrada', 20);
        $stockDespuesEntrada = get_producto_by_id($this->testProductoId)['stock'];
        $this->assertEquals($stockInicial + 20, $stockDespuesEntrada);
        
        // Salida de 10
        registrar_movimiento($this->testProductoId, 'salida', 10);
        $stockDespuesSalida = get_producto_by_id($this->testProductoId)['stock'];
        $this->assertEquals($stockDespuesEntrada - 10, $stockDespuesSalida);
        
        // Otra entrada de 5
        registrar_movimiento($this->testProductoId, 'entrada', 5);
        $stockFinal = get_producto_by_id($this->testProductoId)['stock'];
        $this->assertEquals($stockDespuesSalida + 5, $stockFinal);
    }

    /**
     * Test: Registrar movimiento con producto inexistente
     * 
     * Verifica el manejo de errores con ID inválido
     */
    public function testRegistrarMovimientoProductoInexistente(): void {
        $resultado = registrar_movimiento(999999, 'entrada', 10);
        
        $this->assertFalse($resultado, 'Debe fallar con producto inexistente');
    }

    /**
     * Test: Verificar que se registra en tabla movimientos_inventario
     * 
     * Verifica que los movimientos se guardan en el historial
     */
    public function testMovimientoSeRegistraEnTabla(): void {
        $pdo = getPdo();
        
        // Contar movimientos antes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM movimientos_inventario WHERE id_producto = ?");
        $stmt->execute([$this->testProductoId]);
        $countAntes = $stmt->fetchColumn();
        
        // Registrar movimiento
        registrar_movimiento($this->testProductoId, 'entrada', 5);
        
        // Contar movimientos después
        $stmt->execute([$this->testProductoId]);
        $countDespues = $stmt->fetchColumn();
        
        $this->assertEquals($countAntes + 1, $countDespues, 
            'Debe haber un nuevo registro en movimientos_inventario');
    }

    /**
     * Test: Movimientos con PDO externo (transacciones)
     * 
     * Verifica que funciona correctamente dentro de transacciones existentes
     */
    public function testMovimientoConPdoExterno(): void {
        $pdo = getPdo();
        
        try {
            $pdo->beginTransaction();
            
            $resultado1 = registrar_movimiento($this->testProductoId, 'entrada', 5, $pdo);
            $resultado2 = registrar_movimiento($this->testProductoId, 'salida', 3, $pdo);
            
            $this->assertTrue($resultado1, 'Primer movimiento debe ser exitoso');
            $this->assertTrue($resultado2, 'Segundo movimiento debe ser exitoso');
            
            $pdo->commit();
            
            // Verificar cambios
            $producto = get_producto_by_id($this->testProductoId);
            $stockEsperado = $this->stockOriginal + 5 - 3;
            $this->assertEquals($stockEsperado, $producto['stock']);
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->fail('La transacción falló: ' . $e->getMessage());
        }
    }

    /**
     * Limpia el entorno después de cada test
     * Restaura el stock original del producto
     */
    protected function tearDown(): void {
        $pdo = getPdo();
        
        // Restaurar stock original
        $stmt = $pdo->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $stmt->execute([$this->stockOriginal, $this->testProductoId]);
        
        // Limpiar movimientos de prueba (opcional, para mantener BD limpia)
        // Se podría implementar si se quiere eliminar los movimientos de test
    }
}
