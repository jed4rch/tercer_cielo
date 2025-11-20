<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/func_productos.php';

final class ProductoTest extends TestCase {
    private $testId;

    /**
     * SetUp: Limpia temp ID.
     * Cómo funciona: Temp ID null para isolation.
     * Qué espera: Estado inicial limpio.
     */
    protected function setUp(): void {
        $this->testId = null;
    }

    /**
     * Test para agregar_producto: Prueba insert y get.
     * Cómo funciona: Llama agregar_producto, get_producto_by_id, check nombre/precio.
     * Qué espera: true execute, equals 'Test Tool' nombre, equals 25.00 precio.
     */
    public function testAgregarProducto(): void {
        global $pdo;
        $result = agregar_producto('Test Tool', 'Desc test', 25.00, 15, 1, 'test.jpg');
        $this->assertTrue($result);
        $this->testId = $pdo->lastInsertId();
        $prod = get_producto_by_id((int)$this->testId);
        $this->assertEquals('Test Tool', $prod['nombre']);
        $this->assertEquals(25.00, $prod['precio']);
        // Cleanup
        eliminar_producto((int)$this->testId);
    }

    /**
     * Test para actualizar_producto: Prueba update stock/nombre.
     * Cómo funciona: Update ID 1, get_producto_by_id, check stock.
     * Qué espera: true execute, equals 60 stock.
     */
    public function testActualizarProducto(): void {
        global $pdo;
        $origStock = get_producto_by_id(1)['stock'];
        actualizar_producto(1, 'Martillo Updated', 'Updated desc', 20.00, 60, 1);
        $prod = get_producto_by_id(1);
        $this->assertEquals(60, $prod['stock']);
        // Revert
        actualizar_producto(1, 'Martillo', 'Martillo de punta fina', 15.50, $origStock, 1);
    }

    /**
     * Test para eliminar_producto: Prueba delete temp.
     * Cómo funciona: Agrega temp, llama eliminar_producto, check get_producto_by_id null.
     * Qué espera: true execute, null producto.
     */
    public function testEliminarProducto(): void {
        global $pdo;
        agregar_producto('Temp Prod', '', 10, 5, 1, null);
        $tempId = $pdo->lastInsertId();
        $result = eliminar_producto((int)$tempId);
        $this->assertTrue($result);
        $this->assertFalse(get_producto_by_id((int)$tempId));
    }

    /**
     * Test para get_productos: Prueba búsqueda.
     * Cómo funciona: Llama get_productos con busqueda, check count y nombre.
     * Qué espera: count >0, contains 'Martillo'.
     */
    public function testBusquedaProductos(): void {
        $result = get_productos(null, 'Martillo');
        $this->assertGreaterThan(0, count($result));
        $this->assertStringContainsString('Martillo', $result[0]['nombre']);
    }

    /**
     * TearDown: Limpia temp ID.
     * Cómo funciona: Elimina temp si existe.
     * Qué espera: BD limpia.
     */
    protected function tearDown(): void {
        if ($this->testId) eliminar_producto((int)$this->testId);
    }
}
?>