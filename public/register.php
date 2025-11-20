<?php
require_once '../includes/init.php';
require_once '../includes/func_usuarios.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    // Validaciones
    if (empty($nombre) || empty($email) || empty($password) || empty($confirm)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@gmail.com')) {
        $error = "Solo se permiten correos de Gmail. Ejemplo: usuario@gmail.com";
    } elseif ($password !== $confirm) {
        $error = "Las contraseñas no coinciden.";
    } elseif (get_usuario_by_email($email)) {
        $error = "El correo ya está registrado. <a href='login.php'>Inicia sesión</a> o <a href='recuperar_password.php'>recupera tu contraseña</a>.";
    } else {
        // Validar contraseña robusta
        $val_pass = validar_contrasena_robusta($password);
        if ($val_pass !== true) {
            $error = $val_pass;
        }
        // Validar teléfono
        elseif ($telefono !== '' && validar_telefono($telefono) !== true) {
            $error = validar_telefono($telefono);
        } else {
            // REGISTRO EXITOSO
            registrar_usuario($nombre, $email, $telefono, $password);
            $success = "¡Registro exitoso! Ahora puedes iniciar sesión.";
            header("Location: login.php?success=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Tercer Cielo</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
    <link rel="manifest" href="../assets/favicon/site.webmanifest">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 0;
        }
        .auth-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .auth-header {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        .auth-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 28px;
        }
        .auth-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .auth-body {
            padding: 40px;
        }
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }
        .input-group-icon {
            position: relative;
        }
        .input-group-icon .icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }
        .input-group-icon input {
            padding-left: 45px;
        }
        .btn-register {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
            color: white;
        }
        .link-primary {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        .link-primary:hover {
            color: #2563eb;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .eye-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 18px;
            z-index: 10;
        }
        .eye-toggle:hover {
            color: #3b82f6;
        }
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e0e0e0;
        }
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="auth-card">
                    <div class="auth-header">
                        <i class="bi bi-person-plus-fill" style="font-size: 48px;"></i>
                        <h2 class="mt-3">Crear cuenta</h2>
                        <p>Únete a nosotros y comienza a comprar</p>
                    </div>
                    
                    <div class="auth-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nombre completo</label>
                                    <div class="input-group-icon">
                                        <i class="bi bi-person icon"></i>
                                        <input type="text" name="nombre" class="form-control" 
                                               placeholder="Juan Pérez" required>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <div class="input-group-icon">
                                        <i class="bi bi-phone icon"></i>
                                        <input type="tel" name="telefono" class="form-control" 
                                               placeholder="987654321" pattern="[0-9]{0,9}" 
                                               title="Solo números, máx 9 dígitos" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Correo electrónico</label>
                                <div class="input-group-icon">
                                    <i class="bi bi-envelope icon"></i>
                                    <input type="email" name="email" class="form-control" 
                                           placeholder="tu@gmail.com" required>
                                </div>
                                <small class="text-muted">Solo correos de Gmail</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <div class="input-group-icon position-relative">
                                    <i class="bi bi-shield-lock icon"></i>
                                    <input type="password" name="password" id="pass-registro" 
                                           class="form-control" placeholder="Tu contraseña segura" 
                                           required minlength="8">
                                    <i class="bi bi-eye eye-toggle" id="eye-registro"
                                       onclick="togglePassword('pass-registro', 'eye-registro')"></i>
                                </div>
                                <div class="password-requirements">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Mínimo 8 caracteres, 1 mayúscula, 1 carácter especial (Ej: MiClave#123)
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Confirmar contraseña</label>
                                <div class="input-group-icon position-relative">
                                    <i class="bi bi-shield-check icon"></i>
                                    <input type="password" name="confirm" id="pass-confirm" 
                                           class="form-control" placeholder="Repite tu contraseña" required>
                                    <i class="bi bi-eye eye-toggle" id="eye-confirm"
                                       onclick="togglePassword('pass-confirm', 'eye-confirm')"></i>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-register w-100">
                                <i class="bi bi-check-circle me-2"></i>Crear mi cuenta
                            </button>
                        </form>

                        <div class="divider">
                            <span>o</span>
                        </div>

                        <div class="text-center">
                            <p class="mb-0">¿Ya tienes cuenta? 
                                <a href="login.php" class="link-primary">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Inicia sesión
                                </a>
                            </p>
                        </div>
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
    </script>
</body>

</html>
