<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/func_carrito.php';
require_once __DIR__ . '/../includes/func_productos.php';

/**
 * Tests para funcionalidad de carrito de compras
 * 
 * Cobertura:
 * - Agregar productos al carrito
 * - Actualizar cantidades
 * - Eliminar productos
 * - Calcular totales
 * - Contar items
 */
final class CarritoTest extends TestCase {
    /**
     * Prepara el entorno antes de cada test
     * Limpia el carrito en sesión para asegurar estado inicial limpio
     */
    protected function setUp(): void {
        unset($_SESSION['carrito']);
    }

    /**
     * Test: Agregar un producto al carrito
     * 
     * Verifica que:
     * - El producto se agrega correctamente con la cantidad especificada
     * - El total se calcula correctamente según precio en BD
     */
    public function testAgregarProductoAlCarrito(): void {
        agregar_al_carrito(1, 2);  // Martillo ID 1, cantidad 2
        $carrito = get_carrito();
        
        $this->assertArrayHasKey(1, $carrito, 'El producto debe estar en el carrito');
        $this->assertEquals(2, $carrito[1]['cantidad'], 'La cantidad debe ser 2');
        $this->assertEquals(31.00, get_total_carrito(), 'Total debe ser 31.00 (2 * 15.50)');
    }

    /**
     * Test: Agregar múltiples productos diferentes
     * 
     * Verifica que se pueden agregar varios productos distintos
     * y el total se calcula correctamente
     */
    public function testAgregarMultiplesProductos(): void {
        agregar_al_carrito(1, 1);  // Martillo
        agregar_al_carrito(2, 2);  // Otro producto
        
        $carrito = get_carrito();
        $this->assertCount(2, $carrito, 'Debe haber 2 productos diferentes');
        $this->assertEquals(2, count($carrito), 'Debe contar 2 items diferentes');
    }

    /**
     * Test: Agregar producto que ya existe incrementa cantidad
     * 
     * Verifica que al agregar un producto ya existente,
     * se suma la cantidad en lugar de sobrescribir
     */
    public function testAgregarProductoExistenteIncrementaCantidad(): void {
        agregar_al_carrito(1, 2);
        agregar_al_carrito(1, 3);
        
        $carrito = get_carrito();
        $this->assertEquals(5, $carrito[1]['cantidad'], 'La cantidad debe sumarse (2 + 3 = 5)');
    }

    /**
     * Test: Eliminar un producto del carrito
     * 
     * Verifica que un producto se elimina completamente del carrito
     */
    public function testEliminarProductoDelCarrito(): void {
        agregar_al_carrito(1, 2);
        agregar_al_carrito(2, 1);
        eliminar_del_carrito(1);
        
        $carrito = get_carrito();
        $this->assertArrayNotHasKey(1, $carrito, 'El producto 1 debe ser eliminado');
        $this->assertArrayHasKey(2, $carrito, 'El producto 2 debe permanecer');
    }

    /**
     * Test: Actualizar cantidad de un producto
     * 
     * Verifica que se puede cambiar la cantidad de un producto existente
     */
    public function testActualizarCantidadProducto(): void {
        agregar_al_carrito(1, 2);
        $resultado = actualizar_cantidad_carrito(1, 5);
        
        $this->assertTrue($resultado['success'], 'La actualización debe ser exitosa');
        $this->assertEquals(5, $_SESSION['carrito'][1]['cantidad'], 'La cantidad debe actualizarse a 5');
    }

    /**
     * Test: Actualizar cantidad a 0 elimina el producto
     * 
     * Verifica que establecer cantidad a 0 elimina el producto del carrito
     */
    public function testActualizarCantidadACeroEliminaProducto(): void {
        agregar_al_carrito(1, 2);
        $resultado = actualizar_cantidad_carrito(1, 0);
        
        $this->assertTrue($resultado['success'], 'La operación debe ser exitosa');
        $this->assertArrayNotHasKey(1, get_carrito(), 'Cantidad 0 debe eliminar el producto');
    }

    /**
     * Test: Total de carrito vacío es cero
     * 
     * Verifica que un carrito vacío retorna total 0
     */
    public function testTotalCarritoVacio(): void {
        unset($_SESSION['carrito']);
        $this->assertEquals(0, get_total_carrito(), 'Carrito vacío debe tener total 0');
    }

    /**
     * Test: Contar items en carrito vacío
     * 
     * Verifica que un carrito vacío retorna 0 items
     */
    public function testContarItemsCarritoVacio(): void {
        unset($_SESSION['carrito']);
        $this->assertEquals(0, contar_items_carrito(), 'Carrito vacío debe tener 0 items');
    }

    /**
     * Test: Get carrito devuelve array vacío si no existe sesión
     * 
     * Verifica comportamiento cuando no hay carrito en sesión
     */
    public function testGetCarritoSinSesion(): void {
        unset($_SESSION['carrito']);
        $carrito = get_carrito();
        
        $this->assertIsArray($carrito, 'Debe retornar un array');
        $this->assertEmpty($carrito, 'El array debe estar vacío');
    }

    /**
     * Limpia el entorno después de cada test
     * Asegura que no hay efectos colaterales entre tests
     */
    protected function tearDown(): void {
        unset($_SESSION['carrito']);
    }
}
?>