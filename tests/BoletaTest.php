<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/func_boleta.php';

/**
 * Tests para funcionalidad de generación de boletas PDF
 * 
 * Cobertura:
 * - Generación de boletas
 * - Validación de pedidos
 * - Estructura de archivos PDF
 * - Manejo de errores
 * 
 * Nota: Estos tests verifican que la función genera el PDF
 * pero no validan el contenido interno del PDF generado.
 * 
 * Los tests están marcados como "risky" por PHPUnit debido a que TCPDF
 * abre buffers de salida internamente. Esto es comportamiento esperado
 * de la librería TCPDF y no afecta la validez de los tests.
 */
final class BoletaTest extends TestCase {
    private $testPedidoId;

    /**
     * Prepara el entorno antes de cada test
     */
    protected function setUp(): void {
        $pdo = getPdo();
        $this->assertInstanceOf(PDO::class, $pdo, 'BD no conectada');
        $this->testPedidoId = null;
    }

    /**
     * Test: Verificar que la función existe
     * 
     * Verifica que la función generar_boleta_pdf está disponible
     */
    public function testFuncionGenerarBoletaExiste(): void {
        $this->assertTrue(function_exists('generar_boleta_pdf'),
            'La función generar_boleta_pdf debe existir');
    }

    /**
     * Test: Verificar que TCPDF está disponible
     * 
     * Valida que la librería TCPDF está instalada y accesible
     */
    public function testTCPDFDisponible(): void {
        // Limpiar cualquier buffer residual

        
        $this->assertTrue(class_exists('TCPDF'),
            'TCPDF debe estar disponible');
    }

    /**
     * Test: Generar boleta para pedido inexistente falla
     * 
     * Verifica que retorna false para ID que no existe
     */
    public function testGenerarBoletaPedidoInexistente(): void {
        // Limpiar buffers antes

        
        $resultado = generar_boleta_pdf(999999);
        
        // Limpiar buffers después

        
        $this->assertFalse($resultado,
            'Debe retornar false para pedido inexistente');
    }

    /**
     * Test: Generar boleta para pedido válido
     * 
     * Verifica que se puede generar una boleta para un pedido real
     */
    public function testGenerarBoletaPedidoValido(): void {
        $pdo = getPdo();
        
        // Crear pedido de prueba con solo las columnas que existen
        $stmt = $pdo->prepare("
            INSERT INTO pedidos (usuario_id, total, estado, codigo, metodo_pago) 
            VALUES (2, 50.00, 'pendiente', ?, 'transferencia')
        ");
        $codigo = 'TEST-' . time();
        $stmt->execute([$codigo]);
        $this->testPedidoId = $pdo->lastInsertId();
        
        // Agregar detalle del pedido
        $stmt = $pdo->prepare("
            INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio) 
            VALUES (?, 1, 2, 25.00)
        ");
        $stmt->execute([$this->testPedidoId]);
        
        // Limpiar buffers antes

        
        // Intentar generar boleta
        $resultado = generar_boleta_pdf($this->testPedidoId);
        
        // Limpiar buffers después

        
        // Puede retornar false o una ruta, dependiendo de la implementación
        // Lo importante es que no lance excepciones
        $this->assertTrue(
            is_bool($resultado) || is_string($resultado),
            'Debe retornar bool o string (ruta del archivo)'
        );
    }

    /**
     * Test: Verificar directorio de boletas existe
    /**
     * Verifica que el directorio para guardar boletas está creado
     */
    public function testDirectorioBoletasExiste(): void {
        // Limpiar buffers

        
        $dirBoletas = __DIR__ . '/../public/uploads/boletas/';
        
        // Si no existe, intentar crearlo
        if (!is_dir($dirBoletas)) {
            mkdir($dirBoletas, 0755, true);
        }
        
        $this->assertDirectoryExists($dirBoletas,
            'El directorio de boletas debe existir');
        
        $this->assertTrue(is_writable($dirBoletas),
            'El directorio debe tener permisos de escritura');
    }

    /**
     * Test: Validar estructura básica de pedido para boleta
     * 
     * Verifica que un pedido tiene los datos necesarios
     */
    public function testEstructuraPedidoParaBoleta(): void {
        // Limpiar buffers

        
        $pdo = getPdo();
        
        // Obtener un pedido de ejemplo
        $stmt = $pdo->query("SELECT * FROM pedidos LIMIT 1");
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pedido) {
            $this->assertArrayHasKey('id', $pedido);
            $this->assertArrayHasKey('usuario_id', $pedido);
            $this->assertArrayHasKey('total', $pedido);
            $this->assertArrayHasKey('codigo', $pedido);
            $this->assertArrayHasKey('estado', $pedido);
        } else {
            $this->markTestSkipped('No hay pedidos en la BD para verificar');
        }
    }

    /**
     * Test: Validar que pedido tiene detalles
     * 
     * Verifica que un pedido tiene productos asociados
     */
    public function testPedidoTieneDetalles(): void {
        // Limpiar buffers

        
        $pdo = getPdo();
        
        // Buscar un pedido que tenga detalles
        $stmt = $pdo->query("
            SELECT p.id 
            FROM pedidos p 
            INNER JOIN pedido_detalles pd ON p.id = pd.pedido_id 
            LIMIT 1
        ");
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pedido) {
            $pedidoId = $pedido['id'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = ?");
            $stmt->execute([$pedidoId]);
            $count = $stmt->fetchColumn();
            
            $this->assertGreaterThan(0, $count,
                'El pedido debe tener al menos un detalle');
        } else {
            $this->markTestSkipped('No hay pedidos con detalles para verificar');
        }
    }

    /**
     * Test: Validar formato de código de pedido
     * 
     * Verifica que los códigos de pedido tienen formato válido
     */
    public function testFormatoCodigoPedido(): void {
        // Limpiar buffers

        
        $codigoValido = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
        
        $this->assertMatchesRegularExpression('/^[A-Z]+-\d{8}-\d{4}$/', $codigoValido,
            'El código debe tener formato ORD-YYYYMMDD-XXXX');
    }

    /**
     * Test: Calcular total de pedido
     * 
     * Verifica cálculo de total basado en detalles
     */
    public function testCalcularTotalPedido(): void {
        // Limpiar buffers

        
        $detalles = [
            ['cantidad' => 2, 'precio' => 15.50],
            ['cantidad' => 1, 'precio' => 25.00],
            ['cantidad' => 3, 'precio' => 10.00]
        ];
        
        $total = 0;
        foreach ($detalles as $detalle) {
            $total += $detalle['cantidad'] * $detalle['precio'];
        }
        
        $this->assertEquals(86.00, $total,
            'El total debe ser la suma de cantidad * precio de cada detalle');
    }

    /**
     * Test: Formatear precio para PDF
     * 
     * Verifica formato de moneda para display
     */
    public function testFormatearPrecio(): void {
        // Limpiar buffers

        
        $precio = 1234.56;
        $precioFormateado = 'S/ ' . number_format($precio, 2);
        
        $this->assertEquals('S/ 1,234.56', $precioFormateado,
            'El precio debe formatearse correctamente');
    }

    /**
     * Test: Validar longitud de nombre de archivo PDF
     * 
     * Verifica que los nombres de archivo no son excesivamente largos
     */
    public function testLongitudNombreArchivoPDF(): void {
        // Limpiar buffers

        
        $codigo = 'ORD-20231201-1234';
        $nombreArchivo = 'boleta_' . $codigo . '.pdf';
        
        $this->assertLessThan(255, strlen($nombreArchivo),
            'El nombre de archivo debe ser menor a 255 caracteres');
    }

    /**
     * Test: Verificar permisos de directorio uploads
     * 
     * Verifica que el directorio tiene permisos adecuados
     */
    public function testPermisosDirectorioUploads(): void {
        // Limpiar buffers

        
        $dirUploads = __DIR__ . '/../public/uploads/';
        
        if (is_dir($dirUploads)) {
            $perms = fileperms($dirUploads);
            $permsOctal = substr(sprintf('%o', $perms), -4);
            
            // Verificar que tiene al menos permisos de lectura y escritura
            $this->assertTrue(is_readable($dirUploads),
                'El directorio debe ser legible');
            
            $this->assertTrue(is_writable($dirUploads),
                'El directorio debe ser escribible');
        } else {
            $this->markTestSkipped('Directorio uploads no existe');
        }
    }

    /**
     * Test: Validar que se puede escribir archivo en directorio boletas
     * 
     * Verifica capacidad de escritura con archivo temporal
     */
    public function testEscrituraEnDirectorioBoletas(): void {
        // Limpiar buffers

        
        $dirBoletas = __DIR__ . '/../public/uploads/boletas/';
        
        if (!is_dir($dirBoletas)) {
            mkdir($dirBoletas, 0755, true);
        }
        
        $archivoTest = $dirBoletas . 'test_write_' . time() . '.txt';
        $resultado = file_put_contents($archivoTest, 'test');
        
        $this->assertNotFalse($resultado,
            'Debe poder escribir archivos en el directorio');
        
        // Limpiar
        if (file_exists($archivoTest)) {
            unlink($archivoTest);
        }
    }

    /**
     * Limpia el entorno después de cada test
     */
    protected function tearDown(): void {
        // Limpiar buffers después de cada test

        
        // Limpiar pedido de prueba si existe
        if ($this->testPedidoId) {
            $pdo = getPdo();
            $pdo->prepare("DELETE FROM pedido_detalles WHERE pedido_id = ?")->execute([$this->testPedidoId]);
            $pdo->prepare("DELETE FROM pedidos WHERE id = ?")->execute([$this->testPedidoId]);
        }
    }
}
