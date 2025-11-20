<?php
// Función para iniciar sesión si no está activa (evita warnings en tests).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/conexion.php';

/**
 * Registra un nuevo usuario en la BD, hashea password y maneja UNIQUE email.
 * @param string $nombre Nombre del usuario.
 * @param string $email Email único.
 * @param string $telefono Teléfono opcional.
 * @param string $password Password a hashear.
 * @return bool True si insert exitoso, false si error (empty, duplicate, BD fail).
 */
function registrar_usuario($nombre, $email, $telefono, $password)
{
    // Validación app-level para campos obligatorios
    if (empty($nombre) || empty($email) || empty($password)) {
        return false;
    }

    $pdo = getPdo();
    if (!$pdo) return false;
    try {

        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, telefono, password) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$nombre, $email, $telefono, $hashed_pass]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Autentica un usuario con email y contraseña
 * 
 * @param string $email
 * @param string $password
 * @return array|false|string array con datos del usuario, false si falla, 'inactive' si cuenta deshabilitada
 */
function login_usuario(string $email, string $password)
{
    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol, activo FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Verificar si el usuario está activo
        if (isset($row['activo']) && $row['activo'] == 0) {
            return 'inactive'; // Usuario inactivo
        }
        
        // Verificar contraseña
        if (password_verify($password, $row['password'])) {
            // Devolvemos solo lo que necesitamos en la sesión
            return [
                'id'    => (int)$row['id'],
                'nombre' => $row['nombre'],
                'email' => $row['email'],
                'rol'   => $row['rol']
            ];
        }
    }
    return false;   // <-- siempre false, nunca null
}

/**
 * Obtiene usuario por ID desde BD.
 * @param int $id ID del usuario.
 * @return array|null Usuario array o null si no existe.
 */
function get_usuario_by_id($id)
{
    $pdo = getPdo();
    if (!$pdo) return null;
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function get_usuario_by_email($email)
{
    $pdo = getPdo();
    if (!$pdo) return null;
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Actualiza datos de usuario, opcional password hasheado.
 * @param int $id ID del usuario.
 * @param string $nombre Nuevo nombre.
 * @param string $email Nuevo email.
 * @param string $telefono Nuevo teléfono.
 * @param string|null $password Nuevo password (opcional).
 * @return bool True si update exitoso.
 */
function actualizar_usuario($id, $nombre, $email, $telefono, $password = null)
{
    $pdo = getPdo();
    if (!$pdo) return false;
    $params = [$nombre, $email, $telefono, $id];
    $sql = "UPDATE usuarios SET nombre = ?, email = ?, telefono = ? WHERE id = ?";
    if ($password) {
        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, password = ? WHERE id = ?";
        $params = [$nombre, $email, $telefono, $hashed_pass, $id];
    }
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}
/**
 * Valida contraseña robusta
 * @param string $pass
 * @return bool|string  true o mensaje de error
 */
function validar_contrasena_robusta(string $pass): bool|string
{
    if (strlen($pass) < 8) {
        return "La contraseña debe tener al menos 8 caracteres.";
    }
    if (!preg_match('/[A-Z]/', $pass)) {
        return "La contraseña debe contener al menos una letra mayúscula.";
    }
    if (!preg_match('/[!@#$%^&*]/', $pass)) {
        return "La contraseña debe contener al menos un carácter especial (!@#$%^&*).";
    }
    return true;
}

/**
 * Valida teléfono: solo números, máx 9 dígitos
 */
function validar_telefono(string $tel): bool|string
{
    if (!preg_match('/^\d+$/', $tel)) {
        return "El teléfono solo debe contener números.";
    }
    if (strlen($tel) > 9) {
        return "El teléfono no puede tener más de 9 dígitos.";
    }
    return true;
}

/**
 * Verifica si el usuario logueado es admin.
 * @return bool True si rol = 'admin'.
 */
function is_admin()
{
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

/**
 * Cierra sesión destruyendo la sesión.
 */
function logout_usuario()
{
    session_destroy();
}
