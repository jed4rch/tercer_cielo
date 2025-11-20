<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/func_banners.php';

/**
 * Tests para funcionalidad de banners
 * 
 * Cobertura:
 * - Obtener banners
 * - Agregar nuevo banner
 * - Actualizar banner existente
 * - Eliminar banner
 * - Cambiar estado de banner
 * - Obtener datos para selectores
 */
final class BannersTest extends TestCase {
    private $testBannerId;

    /**
     * Prepara el entorno antes de cada test
     */
    protected function setUp(): void {
        $pdo = getPdo();
        $this->assertInstanceOf(PDO::class, $pdo, 'BD no conectada');
        $this->testBannerId = null;
    }

    /**
     * Test: Obtener todos los banners
     * 
     * Verifica que se pueden obtener todos los banners del sistema
     */
    public function testObtenerTodosBanners(): void {
        $banners = obtenerBanners();
        
        $this->assertIsArray($banners, 'Debe retornar un array');
        // Verificar que tiene estructura esperada si hay banners
        if (count($banners) > 0) {
            $this->assertArrayHasKey('id', $banners[0]);
            $this->assertArrayHasKey('imagen', $banners[0]);
            $this->assertArrayHasKey('habilitado', $banners[0]);
        }
    }

    /**
     * Test: Obtener solo banners habilitados
     * 
     * Verifica que el filtro de habilitados funciona correctamente
     */
    public function testObtenerSoloBannersHabilitados(): void {
        $banners = obtenerBanners(true);
        
        $this->assertIsArray($banners);
        foreach ($banners as $banner) {
            $this->assertEquals(1, $banner['habilitado'], 'Todos los banners deben estar habilitados');
        }
    }

    /**
     * Test: Agregar un nuevo banner
     * 
     * Verifica que se puede crear un banner con datos válidos
     */
    public function testAgregarBannerExitoso(): void {
        $datos = [
            'imagen' => 'uploads/banners/test_banner.jpg',
            'tipo_enlace' => 'ninguno',
            'habilitado' => 1
        ];
        
        $id = agregarBanner($datos);
        
        $this->assertIsNumeric($id, 'Debe retornar el ID del nuevo banner');
        $this->testBannerId = $id;
        
        // Verificar que se guardó correctamente
        $banner = obtenerBannerPorId($id);
        $this->assertEquals('uploads/banners/test_banner.jpg', $banner['imagen']);
        $this->assertEquals(1, $banner['habilitado']);
    }

    /**
     * Test: Agregar banner sin imagen falla
     * 
     * Verifica que no se puede crear un banner sin imagen
     */
    public function testAgregarBannerSinImagen(): void {
        $datos = [
            'tipo_enlace' => 'ninguno',
            'habilitado' => 1
        ];
        
        $resultado = agregarBanner($datos);
        $this->assertFalse($resultado, 'No debe permitir banner sin imagen');
    }

    /**
     * Test: Actualizar un banner existente
     * 
     * Verifica que se pueden actualizar los datos de un banner
     */
    public function testActualizarBannerExitoso(): void {
        // Crear banner de prueba
        $id = agregarBanner([
            'imagen' => 'uploads/banners/test.jpg',
            'tipo_enlace' => 'ninguno',
            'habilitado' => 1
        ]);
        $this->testBannerId = $id;
        
        // Actualizar
        $datosActualizados = [
            'imagen' => 'uploads/banners/test_updated.jpg',
            'tipo_enlace' => 'producto',
            'enlace_id' => 1
        ];
        
        $resultado = actualizarBanner($id, $datosActualizados);
        $this->assertTrue($resultado, 'La actualización debe ser exitosa');
        
        // Verificar cambios
        $banner = obtenerBannerPorId($id);
        $this->assertEquals('uploads/banners/test_updated.jpg', $banner['imagen']);
        $this->assertEquals('producto', $banner['tipo_enlace']);
        $this->assertEquals(1, $banner['enlace_id']);
    }

    /**
     * Test: Actualizar banner sin datos falla
     * 
     * Verifica que actualizar sin campos retorna false
     */
    public function testActualizarBannerSinDatos(): void {
        $resultado = actualizarBanner(999, []);
        $this->assertFalse($resultado, 'No debe actualizar sin datos');
    }

    /**
     * Test: Cambiar estado de un banner
     * 
     * Verifica que se puede habilitar/deshabilitar un banner
     */
    public function testCambiarEstadoBanner(): void {
        // Crear banner habilitado
        $id = agregarBanner([
            'imagen' => 'uploads/banners/test.jpg',
            'tipo_enlace' => 'ninguno',
            'habilitado' => 1
        ]);
        $this->testBannerId = $id;
        
        // Deshabilitar
        $resultado = cambiarEstadoBanner($id, 0);
        $this->assertTrue($resultado, 'Debe cambiar el estado correctamente');
        
        $banner = obtenerBannerPorId($id);
        $this->assertEquals(0, $banner['habilitado'], 'El banner debe estar deshabilitado');
        
        // Volver a habilitar
        cambiarEstadoBanner($id, 1);
        $banner = obtenerBannerPorId($id);
        $this->assertEquals(1, $banner['habilitado'], 'El banner debe estar habilitado');
    }

    /**
     * Test: Obtener banner por ID inexistente
     * 
     * Verifica que retorna false para ID que no existe
     */
    public function testObtenerBannerInexistente(): void {
        $banner = obtenerBannerPorId(999999);
        $this->assertFalse($banner, 'Debe retornar false para ID inexistente');
    }

    /**
     * Test: Eliminar un banner
     * 
     * Verifica que un banner se puede eliminar correctamente
     */
    public function testEliminarBanner(): void {
        // Crear banner de prueba
        $id = agregarBanner([
            'imagen' => 'uploads/banners/test_delete.jpg',
            'tipo_enlace' => 'ninguno',
            'habilitado' => 1
        ]);
        
        // Eliminar
        $resultado = eliminarBanner($id);
        $this->assertTrue($resultado, 'La eliminación debe ser exitosa');
        
        // Verificar que ya no existe
        $banner = obtenerBannerPorId($id);
        $this->assertFalse($banner, 'El banner no debe existir después de eliminarse');
        
        // No establecer testBannerId porque ya fue eliminado
    }

    /**
     * Test: Obtener productos para banner
     * 
     * Verifica que se obtiene lista de productos para el selector
     */
    public function testObtenerProductosParaBanner(): void {
        $productos = obtenerProductosParaBanner();
        
        $this->assertIsArray($productos, 'Debe retornar un array');
        if (count($productos) > 0) {
            $this->assertArrayHasKey('id', $productos[0]);
            $this->assertArrayHasKey('nombre', $productos[0]);
            $this->assertArrayHasKey('precio', $productos[0]);
        }
    }

    /**
     * Test: Obtener categorías para banner
     * 
     * Verifica que se obtiene lista de categorías para el selector
     */
    public function testObtenerCategoriasParaBanner(): void {
        $categorias = obtenerCategoriasParaBanner();
        
        $this->assertIsArray($categorias, 'Debe retornar un array');
        if (count($categorias) > 0) {
            $this->assertArrayHasKey('id', $categorias[0]);
            $this->assertArrayHasKey('nombre', $categorias[0]);
        }
    }

    /**
     * Test: Obtener URL de enlace de banner
     * 
     * Verifica que se genera la URL correcta según el tipo
     */
    public function testObtenerUrlEnlaceBanner(): void {
        // Tipo ninguno
        $url = obtenerUrlEnlaceBanner('ninguno', 0);
        $this->assertNull($url, 'Tipo ninguno debe retornar null');
        
        // Tipo producto
        $url = obtenerUrlEnlaceBanner('producto', 1);
        $this->assertEquals('producto.php?id=1', $url, 'URL de producto incorrecta');
        
        // Tipo categoría
        $url = obtenerUrlEnlaceBanner('categoria', 2);
        $this->assertEquals('catalogo.php?categoria=2', $url, 'URL de categoría incorrecta');
    }

    /**
     * Limpia el entorno después de cada test
     */
    protected function tearDown(): void {
        // Eliminar banner de prueba si existe
        if ($this->testBannerId) {
            $pdo = getPdo();
            $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
            $stmt->execute([$this->testBannerId]);
        }
    }
}
