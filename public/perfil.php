<?php
require_once '../includes/init.php';
require_once '../includes/func_usuarios.php';
require_once '../includes/func_carrito.php';

// === REDIRECCIÓN SI ES ADMIN ===
if (isset($_SESSION['user_id']) && $_SESSION['rol'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) header('Location: login.php');
$user = get_usuario_by_id($_SESSION['user_id']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validaciones
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@gmail.com')) {
        $error = "Solo se permiten correos de Gmail. Ejemplo: usuario@gmail.com";
    } elseif (!preg_match('/^[0-9]{9}$/', $telefono)) {
        $error = "El teléfono debe tener exactamente 9 dígitos numéricos.";
    } elseif ($password !== '' && $password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } elseif ($password !== '') {
        $val_pass = validar_contrasena_robusta($password);
        if ($val_pass !== true) {
            $error = $val_pass;
        }
    }

    if (!$error) {
        if (actualizar_usuario($_SESSION['user_id'], $nombre, $email, $telefono, $password)) {
            $_SESSION['nombre'] = $nombre;  // Actualizar nombre en sesión
            $user = get_usuario_by_id($_SESSION['user_id']);  // Refresh
            $success = 'Perfil actualizado correctamente.';
        } else {
            $error = 'Error al actualizar el perfil.';
        }
    }
}

$titulo = 'Mi Perfil - Tercer Cielo';
include 'cabecera_unificada.php';
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    .page-header-perfil {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 3rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 20px 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .page-header-perfil h2 {
        font-weight: 700;
        margin: 0;
        font-size: 2.5rem;
    }

    .page-header-perfil .subtitle {
        opacity: 0.9;
        margin-top: 0.5rem;
    }

    .perfil-card {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        background: white;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .perfil-card-header {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 2rem;
        border: none;
    }

    .perfil-card-header h4 {
        margin: 0;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.5rem;
    }

    .perfil-card-body {
        padding: 2.5rem;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-label i {
        color: #007bff;
    }

    .form-control {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
    }

    .input-group-custom {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
        z-index: 10;
        transition: color 0.3s ease;
    }

    .password-toggle:hover {
        color: #007bff;
    }

    .btn-actualizar {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-actualizar:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        color: white;
    }

    .btn-volver {
        background: white;
        color: #495057;
        border: 2px solid #e9ecef;
        border-radius: 25px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-volver:hover {
        background: #f8f9fa;
        border-color: #007bff;
        color: #007bff;
        transform: translateY(-2px);
    }

    .alert {
        border-radius: 15px;
        border: none;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 500;
    }

    .alert-success {
        background: linear-gradient(135deg, #28a745 0%, #5cb85c 100%);
        color: white;
    }

    .alert-danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
    }

    .text-muted {
        font-size: 0.85rem;
        margin-top: 0.25rem;
        display: block;
    }

    .form-section {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 15px;
        margin-bottom: 1.5rem;
    }

    .section-title {
        color: #007bff;
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    @media (max-width: 768px) {
        .page-header-perfil h2 {
            font-size: 2rem;
        }
        
        .perfil-card-body {
            padding: 1.5rem;
        }
    }
</style>

    <div class="page-header-perfil">
        <div class="container">
            <h2><i class="bi bi-person-circle me-3"></i>Mi Perfil</h2>
            <p class="subtitle mb-0">Administra tu información personal</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="perfil-card">
                    <div class="perfil-card-header">
                        <h4><i class="bi bi-person-badge"></i>Información Personal</h4>
                    </div>
                    <div class="perfil-card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill"></i>
                                <?= $success ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="perfilForm">
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="bi bi-person-vcard"></i>
                                    Datos Personales
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <i class="bi bi-person"></i>
                                            Nombre completo *
                                        </label>
                                        <input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <i class="bi bi-telephone"></i>
                                            Teléfono *
                                        </label>
                                        <input type="tel" name="telefono" value="<?= htmlspecialchars($user['telefono']) ?>" class="form-control"
                                            pattern="[0-9]{9}" title="Debe tener exactamente 9 dígitos numéricos" required>
                                        <small class="text-muted"><i class="bi bi-info-circle"></i> Debe tener 9 dígitos numéricos</small>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">
                                            <i class="bi bi-envelope"></i>
                                            Email *
                                        </label>
                                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control"
                                            pattern="[a-zA-Z0-9._%+-]+@gmail\.com$"
                                            title="Solo se permiten correos de Gmail (usuario@gmail.com)" required>
                                        <small class="text-muted"><i class="bi bi-info-circle"></i> Solo se permiten correos de Gmail (@gmail.com)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="section-title">
                                    <i class="bi bi-shield-lock"></i>
                                    Cambiar Contraseña (Opcional)
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <i class="bi bi-key"></i>
                                            Nueva contraseña
                                        </label>
                                        <div class="input-group-custom">
                                            <input type="password" name="password" id="password" class="form-control"
                                                placeholder="Dejar vacío para no cambiar" style="padding-right: 45px;">
                                            <i class="bi bi-eye password-toggle"
                                                id="eye-password"
                                                onclick="togglePassword('password', 'eye-password')"></i>
                                        </div>
                                        <small class="text-muted"><i class="bi bi-info-circle"></i> Mínimo 8 caracteres: 1 mayúscula, 1 número, 1 carácter especial</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <i class="bi bi-key-fill"></i>
                                            Confirmar contraseña
                                        </label>
                                        <div class="input-group-custom">
                                            <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                                                placeholder="Confirmar nueva contraseña" style="padding-right: 45px;">
                                            <i class="bi bi-eye password-toggle"
                                                id="eye-confirm"
                                                onclick="togglePassword('confirm_password', 'eye-confirm')"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-3 flex-wrap justify-content-between align-items-center">
                                <button type="submit" class="btn-actualizar">
                                    <i class="bi bi-check-circle-fill"></i>Actualizar Perfil
                                </button>
                                <a href="index.php" class="btn-volver">
                                    <i class="bi bi-arrow-left-circle"></i>Volver al Inicio
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        // Validación en tiempo real del formulario
        document.getElementById('perfilForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const email = document.querySelector('input[name="email"]').value;
            const telefono = document.querySelector('input[name="telefono"]').value;

            // Validar email Gmail
            if (!email.endsWith('@gmail.com')) {
                e.preventDefault();
                alert('Solo se permiten correos de Gmail (usuario@gmail.com)');
                return;
            }

            // Validar teléfono (9 dígitos)
            if (!/^[0-9]{9}$/.test(telefono)) {
                e.preventDefault();
                alert('El teléfono debe tener exactamente 9 dígitos numéricos.');
                return;
            }

            // Validar contraseñas si se están cambiando
            if (password !== '') {
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Las contraseñas no coinciden.');
                    return;
                }

                // Validar fortaleza de contraseña
                const hasUpperCase = /[A-Z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

                if (password.length < 8 || !hasUpperCase || !hasNumber || !hasSpecialChar) {
                    e.preventDefault();
                    alert('La contraseña debe tener al menos 8 caracteres, incluyendo una mayúscula, un número y un carácter especial.');
                    return;
                }
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>