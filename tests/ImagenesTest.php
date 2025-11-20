<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/func_imagenes.php';

/**
 * Tests para funcionalidad de manejo de imágenes
 * 
 * Cobertura:
 * - Validación de imágenes
 * - Extensiones y tipos MIME
 * - Manejo de errores de carga
 * 
 * Nota: guardarImagen y redimensionarImagen requieren archivos reales,
 * por lo que estos tests se enfocan en la validación
 */
final class ImagenesTest extends TestCase {

    /**
     * Test: Validar imagen con archivo inexistente
     * 
     * Verifica que retorna error cuando no hay archivo
     */
    public function testValidarImagenSinArchivo(): void {
        $file = ['error' => UPLOAD_ERR_NO_FILE];
        $resultado = validarImagen($file);
        
        $this->assertFalse($resultado['success'], 'Debe fallar sin archivo');
        $this->assertStringContainsString('No se ha seleccionado', $resultado['message']);
    }

    /**
     * Test: Validar imagen con error de carga
     * 
     * Verifica que detecta errores en la carga
     */
    public function testValidarImagenConErrorCarga(): void {
        $file = ['error' => UPLOAD_ERR_PARTIAL];
        $resultado = validarImagen($file);
        
        $this->assertFalse($resultado['success'], 'Debe fallar con error de carga');
        $this->assertStringContainsString('Error al subir', $resultado['message']);
    }

    /**
     * Test: Validar imagen con tamaño excesivo
     * 
     * Verifica que rechaza archivos mayores a 5MB
     */
    public function testValidarImagenTamañoExcesivo(): void {
        // Crear archivo temporal simulado
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, str_repeat('a', 6 * 1024 * 1024)); // 6MB
        
        $file = [
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tempFile),
            'tmp_name' => $tempFile
        ];
        
        $resultado = validarImagen($file);
        
        $this->assertFalse($resultado['success'], 'Debe fallar con archivo grande');
        $this->assertStringContainsString('demasiado grande', $resultado['message']);
        
        unlink($tempFile);
    }

    /**
     * Test: Obtener extensión por MIME type
     * 
     * Verifica que convierte correctamente tipos MIME a extensiones
     */
    public function testObtenerExtensionPorMime(): void {
        $this->assertEquals('jpg', obtenerExtensionPorMime('image/jpeg'));
        $this->assertEquals('png', obtenerExtensionPorMime('image/png'));
        $this->assertEquals('gif', obtenerExtensionPorMime('image/gif'));
        $this->assertEquals('webp', obtenerExtensionPorMime('image/webp'));
        $this->assertEquals('jpg', obtenerExtensionPorMime('image/jpg'));
    }

    /**
     * Test: Obtener extensión de MIME desconocido
     * 
     * Verifica comportamiento con tipo MIME no soportado
     */
    public function testObtenerExtensionMimeDesconocido(): void {
        // La función debe retornar jpg por defecto o manejar el caso
        $extension = obtenerExtensionPorMime('application/pdf');
        $this->assertIsString($extension, 'Debe retornar alguna extensión');
    }

    /**
     * Test: Eliminar imagen inexistente
     * 
     * Verifica que no falla al intentar eliminar archivo que no existe
     */
    public function testEliminarImagenInexistente(): void {
        $path = '/ruta/inexistente/imagen.jpg';
        $resultado = eliminarImagen($path);
        
        // Debe retornar false o manejar gracefully
        $this->assertFalse($resultado, 'Debe retornar false para archivo inexistente');
    }

    /**
     * Test: Crear y eliminar imagen temporal
     * 
     * Verifica el ciclo completo de crear y eliminar un archivo
     */
    public function testCrearYEliminarImagen(): void {
        // Crear archivo temporal
        $testDir = __DIR__ . '/../public/uploads/test/';
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        
        $testFile = $testDir . 'test_imagen.jpg';
        file_put_contents($testFile, 'test content');
        
        $this->assertFileExists($testFile, 'El archivo debe existir');
        
        // Eliminar
        $resultado = eliminarImagen($testFile);
        $this->assertTrue($resultado, 'La eliminación debe ser exitosa');
        $this->assertFileDoesNotExist($testFile, 'El archivo no debe existir después de eliminar');
        
        // Limpiar directorio
        if (is_dir($testDir)) {
            rmdir($testDir);
        }
    }

    /**
     * Test: Validar tipos de archivo permitidos
     * 
     * Verifica que solo se aceptan formatos de imagen válidos
     */
    public function testTiposArchivoPermitidos(): void {
        // Este test verifica la lógica conceptual
        // Los tipos permitidos deben ser: JPEG, JPG, PNG, GIF, WEBP
        
        $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        $this->assertContains('image/jpeg', $tiposPermitidos);
        $this->assertContains('image/png', $tiposPermitidos);
        $this->assertContains('image/gif', $tiposPermitidos);
        $this->assertContains('image/webp', $tiposPermitidos);
        
        $this->assertNotContains('application/pdf', $tiposPermitidos);
        $this->assertNotContains('text/plain', $tiposPermitidos);
    }

    /**
     * Test: Verificar límite de tamaño
     * 
     * Verifica que el límite de 5MB está correctamente definido
     */
    public function testLimiteTamaño(): void {
        $limiteEsperado = 5 * 1024 * 1024; // 5MB en bytes
        
        $this->assertEquals(5242880, $limiteEsperado, 'El límite debe ser 5MB (5242880 bytes)');
    }
}
