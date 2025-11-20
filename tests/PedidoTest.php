<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

// Cargar func_carrito y func_productos primero (no tienen actualizar_estado_pedido)
require_once __DIR__ . '/../includes/func_carrito.php';
require_once __DIR__ . '/../includes/func_productos.php';

// Verificar si la función ya existe antes de cargar func_pedidos
if (!function_exists('actualizar_estado_pedido')) {
    require_once __DIR__ . '/../includes/func_pedidos.php';
} else {
    // Si ya existe (cargada por bootstrap), solo cargar las otras funciones de func_pedidos
    // Necesitamos crear_pedido y get_pedidos_by_usuario manualmente
    require_once __DIR__ . '/../config/conexion.php';
    
    if (!function_exists('crear_pedido')) {
        function crear_pedido($id_usuario, $carrito_items) {
            if (empty($carrito_items)) return false;
            $pdo = getPdo();
            if (!$pdo) return false;
            
            try {
                $pdo->beginTransaction();
                $total = 0;
                foreach ($carrito_items as $prod_id => $item) {
                    $cant = is_array($item) ? $item['cantidad'] : $item;
                    $stmt = $pdo->prepare("SELECT precio, stock FROM productos WHERE id = ?");
                    $stmt->execute([$prod_id]);
                    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$prod || $prod['stock'] < $cant) {
                        $pdo->rollBack();
                        return false;
                    }
                    $total += $prod['precio'] * $cant;
                }
                
                $codigo = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
                $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, total, codigo, estado) VALUES (?, ?, ?, 'pendiente')");
                $stmt->execute([$id_usuario, $total, $codigo]);
                $pedido_id = $pdo->lastInsertId();
                
                foreach ($carrito_items as $prod_id => $item) {
                    $cant = is_array($item) ? $item['cantidad'] : $item;
                    $stmt = $pdo->prepare("SELECT precio FROM productos WHERE id = ?");
                    $stmt->execute([$prod_id]);
                    $precio = $stmt->fetchColumn();
                    $stmt = $pdo->prepare("INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$pedido_id, $prod_id, $cant, $precio]);
                    $stmt = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$cant, $prod_id]);
                }
                
                $pdo->commit();
                unset($_SESSION['carrito']);
                return (string)$pedido_id;
            } catch (Exception $e) {
                $pdo->rollBack();
                return false;
            }
        }
    }
    
    if (!function_exists('get_pedidos_by_usuario')) {
        function get_pedidos_by_usuario($id_usuario, $estado = null) {
            $pdo = getPdo();
            if (!$pdo) return [];
            $sql = "SELECT * FROM pedidos WHERE usuario_id = ?";
            $params = [$id_usuario];
            if ($estado) {
                $sql .= " AND estado = ?";
                $params[] = $estado;
            }
            $sql .= " ORDER BY creado_en DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

final class PedidoTest extends TestCase {
    private $tempPedidoId;
    private $stockInicial;

    /**
     * SetUp: Limpia carrito y prepara temp ID.
     * Cómo funciona: Assert PDO, unset carrito, temp ID null, guarda stock inicial.
     * Qué espera: BD conectada, carrito vacío.
     */
    protected function setUp(): void {
        $pdo = getPdo();
        $this->assertInstanceOf(PDO::class, $pdo, 'BD no conectada');
        unset($_SESSION['carrito']);
        $this->tempPedidoId = null;
        
        // Guardar stock inicial del producto de prueba
        $prod = get_producto_by_id(1);
        $this->stockInicial = $prod ? $prod['stock'] : 0;
    }

    /**
     * Test para crear_pedido: Prueba creación con carrito, stock bajado.
     * Cómo funciona: Set carrito, llama crear_pedido, check ID, empty carrito, stock.
     * Qué espera: isString ID, empty carrito, stock reducido en cantidad pedida.
     */
    public function testCrearPedidoExitoso(): void {
        $pdo = getPdo();
        
        // Verificar que hay stock disponible
        if ($this->stockInicial < 1) {
            $this->markTestSkipped('Producto sin stock suficiente para prueba');
        }
        
        $_SESSION['carrito'] = [1 => 1];  // Martillo, 1 unidad
        $id = crear_pedido(2, get_carrito());  // Usuario cliente ID 2
        
        $this->assertIsString($id, 'Debe retornar ID como string');
        $this->tempPedidoId = $id;
        $this->assertEmpty(get_carrito(), 'El carrito debe vaciarse después de crear pedido');
        
        $prod = get_producto_by_id(1);
        $this->assertEquals($this->stockInicial - 1, $prod['stock'], 
            'El stock debe reducirse en 1 unidad');
    }

    /**
     * Test para crear_pedido: Prueba error con carrito vacío.
     * Cómo funciona: Set carrito vacío, llama crear_pedido.
     * Qué espera: false (empty check).
     */
    public function testCrearPedidoCarritoVacio(): void {
        $_SESSION['carrito'] = [];
        $id = crear_pedido(2, []);
        $this->assertFalse($id);
    }

    /**
     * Test para actualizar_estado_pedido: Prueba update estado.
     * Cómo funciona: Insert temp pedido, actualiza a 'enviado', get_pedidos check estado.
     * Qué espera: equals 'enviado' y que la función retorne true.
     */
    public function testActualizarEstadoPedido(): void {
        $pdo = getPdo();
        $pdo->prepare("INSERT INTO pedidos (usuario_id, total, estado) VALUES (2, 10.00, 'pendiente')")->execute();
        $id = $pdo->lastInsertId();
        $this->tempPedidoId = $id;
        
        $resultado = actualizar_estado_pedido($id, 'enviado');
        $this->assertTrue($resultado, 'La actualización debe ser exitosa');
        
        $ped = get_pedidos_by_usuario(2, 'enviado')[0] ?? null;
        $this->assertNotNull($ped, 'Debe encontrar el pedido actualizado');
        $this->assertEquals('enviado', $ped['estado'], 'El estado debe ser enviado');
    }

    /**
     * TearDown: Limpia temp pedido y revert stock.
     * Cómo funciona: Delete temp, update stock a original.
     * Qué espera: BD limpia entre tests.
     */
    protected function tearDown(): void {
        $pdo = getPdo();
        if ($this->tempPedidoId) {
            // Eliminar detalles del pedido primero (por foreign key)
            $pdo->prepare("DELETE FROM pedido_detalles WHERE pedido_id = ?")->execute([$this->tempPedidoId]);
            $pdo->prepare("DELETE FROM pedidos WHERE id = ?")->execute([$this->tempPedidoId]);
            
            // Restaurar stock inicial
            if ($this->stockInicial > 0) {
                $pdo->prepare("UPDATE productos SET stock = ? WHERE id = 1")->execute([$this->stockInicial]);
            }
        }
        unset($_SESSION['carrito']);
        @session_destroy();  // Suprimir advertencia si sesión ya fue destruida
    }
}
?>