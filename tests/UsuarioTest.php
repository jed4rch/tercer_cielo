<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/func_usuarios.php';

final class UsuarioTest extends TestCase {
    private $testEmail;
    private $testId;

    /**
     * SetUp: Re-setea la conexión PDO y genera un email único para el test.
     * Cómo funciona: Llama getPdo() para asegurar BD, genera email temporal para evitar duplicados.
     * Qué espera: PDO instance no null; email único para insert limpio.
     */
    protected function setUp(): void {
        $pdo = getPdo();
        $this->assertInstanceOf(PDO::class, $pdo, 'BD no conectada - Verifica MySQL y database.sql');
        $this->testEmail = 'test_' . uniqid() . '@example.com';
    }

    /**
     * Test para registrar_usuario: Prueba inserción exitosa con datos válidos.
     * Cómo funciona: Llama registrar_usuario con datos válidos, verifica ID insertado, obtiene usuario y checks nombre/rol/hash.
     * Qué espera: true en execute, equals 'Juan Pérez' en nombre, 'cliente' en rol, true en password_verify; cleanup DELETE.
     */
    public function testRegistroExitoso(): void {
        global $pdo;
        $result = registrar_usuario('Juan Pérez',  $this->testEmail, '987654321', 'pass123');
        $this->assertTrue($result);
        $this->testId = $pdo->lastInsertId();
        $user = get_usuario_by_id((int)$this->testId);
        $this->assertEquals('Juan Pérez', $user['nombre']);
        $this->assertEquals('cliente', $user['rol']);
        $this->assertTrue(password_verify('pass123', $user['password']));
        // Cleanup
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$this->testId]);
    }

    /**
     * Test para registrar_usuario: Prueba error por email duplicado.
     * Cómo funciona: Llama registrar_usuario con email existente, espera catch UNIQUE.
     * Qué espera: false en execute por PDOException (duplicate entry).
     */
    public function testRegistroEmailDuplicado(): void {
        $result = registrar_usuario('Ana López', 'admin@tercercielo.com', '123456789', 'pass456');
        $this->assertFalse($result);
    }

    /**
     * Test para registrar_usuario: Prueba error por campos vacíos.
     * Cómo funciona: Llama registrar_usuario con strings vacíos, espera validation app-level.
     * Qué espera: false por empty check antes de BD.
     */
    public function testRegistroCamposVacios(): void {
    $result = registrar_usuario('', '', '', '');
    $this->assertFalse($result);
    }

    /**
     * Test para registrar_usuario: Prueba hash y verify con password débil.
     * Cómo funciona: Llama registrar_usuario con pass corto, verifica ID, obtiene usuario y password_verify.
     * Qué espera: true en execute, true en verify (hash funciona con cualquier pass).
     */
    public function testRegistroPasswordDebil(): void {
        global $pdo;
        $result = registrar_usuario('Test User', $this->testEmail, '111', '123');
        $this->assertTrue($result);
        $this->testId = $pdo->lastInsertId();
        $user = get_usuario_by_id((int)$this->testId);
        $this->assertTrue(password_verify('123', $user['password']));
        // Cleanup
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$this->testId]);
    }

    /**
     * Test para login_usuario: Prueba login exitoso con credenciales correctas.
     * Cómo funciona: Llama login_usuario con admin, verifica result y rol en datos.
     * Qué espera: array con datos de usuario, 'admin' en rol.
     */
    public function testLoginExitoso(): void {
        $result = login_usuario('admin@tercercielo.com', 'admin123');
        $this->assertIsArray($result, 'Login exitoso debe retornar array');
        $this->assertEquals('admin', $result['rol'], 'El rol debe ser admin');
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('nombre', $result);
    }

    /**
     * Test para actualizar_usuario: Prueba update de datos con revert.
     * Cómo funciona: Obtiene original, actualiza, verifica cambio, revert.
     * Qué espera: true en update, equals nuevo nombre en get_usuario_by_id.
     */
    public function testActualizarUsuario(): void {
        $origUser = get_usuario_by_id(2);  // Cliente ejemplo
        
        // Si no existe usuario ID 2, crear uno temporal
        if (!$origUser) {
            $pdo = getPdo();
            $pdo->prepare("INSERT INTO usuarios (id, nombre, email, telefono, password, rol) VALUES (2, 'Cliente Test', 'cliente@test.com', '123456789', ?, 'cliente')")
                ->execute([password_hash('test123', PASSWORD_DEFAULT)]);
            $origUser = get_usuario_by_id(2);
        }
        
        $result = actualizar_usuario(2, 'Cliente Actualizado', 'updated@ejemplo.com', '999999999');
        $this->assertTrue($result, 'La actualización debe ser exitosa');
        
        $user = get_usuario_by_id(2);
        $this->assertIsArray($user, 'Debe retornar un array con datos de usuario');
        $this->assertEquals('Cliente Actualizado', $user['nombre'], 'El nombre debe actualizarse');
        
        // Revert
        actualizar_usuario(2, $origUser['nombre'], $origUser['email'], $origUser['telefono']);
    }

    /**
     * TearDown: Destruye sesión para isolation.
     * Cómo funciona: Llama session_destroy() después de cada test.
     * Qué espera: Sesión limpia entre tests.
     */
    protected function tearDown(): void {
        session_destroy();
    }
}
?>