<?php
require_once '../includes/init.php';          // <-- session_start() seguro
require_once '../includes/func_usuarios.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        $error = "El campo de correo es obligatorio.";
    } elseif ($password === '') {
        $error = "La contraseña es obligatoria.";
    } else {
        $usuario = login_usuario($email, $password);
        if ($usuario === 'inactive') {
            $error = "Tu cuenta ha sido inhabilitada. Contacta al administrador.";
        } elseif ($usuario !== false && is_array($usuario)) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['nombre']  = $usuario['nombre'];
            $_SESSION['rol']     = $usuario['rol'];
            
            // Guardar el session_id en la base de datos para usuarios admin
            if ($usuario['rol'] === 'admin') {
                $pdo = getPdo();
                $session_id = session_id();
                $stmt = $pdo->prepare("UPDATE usuarios SET session_id = ? WHERE id = ?");
                $stmt->execute([$session_id, $usuario['id']]);
                
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = "Correo o contraseña incorrectos. Intenta de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - Tercer Cielo</title>
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
        }
        .auth-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
        .btn-login {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
            color: white;
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
        .link-primary {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
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
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <i class="bi bi-lock-fill" style="font-size: 48px;"></i>
                        <h2 class="mt-3">Bienvenido de nuevo</h2>
                        <p>Inicia sesión para continuar</p>
                    </div>
                    
                    <div class="auth-body">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                ¡Registro exitoso! Ahora puedes iniciar sesión.
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['error']) && $_GET['error'] === 'cuenta_inactiva'): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                Tu cuenta ha sido inhabilitada. Por favor, contacta al administrador.
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Correo electrónico</label>
                                <div class="input-group-icon">
                                    <i class="bi bi-envelope icon"></i>
                                    <input type="email" name="email" class="form-control" 
                                           placeholder="tu@correo.com" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <div class="input-group-icon position-relative">
                                    <i class="bi bi-shield-lock icon"></i>
                                    <input type="password" name="password" id="pass-login" 
                                           class="form-control" placeholder="Tu contraseña" required>
                                    <i class="bi bi-eye eye-toggle" id="eye-login"
                                       onclick="togglePassword('pass-login', 'eye-login')"></i>
                                </div>
                            </div>

                            <div class="text-end mb-4">
                                <a href="recuperar_password.php" class="link-primary">
                                    <i class="bi bi-question-circle me-1"></i>¿Olvidaste tu contraseña?
                                </a>
                            </div>

                            <button type="submit" class="btn btn-login w-100">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                            </button>
                        </form>

                        <div class="divider">
                            <span>o</span>
                        </div>

                        <div class="text-center">
                            <p class="mb-0">¿No tienes cuenta? 
                                <a href="register.php" class="link-primary">
                                    <i class="bi bi-person-plus me-1"></i>Regístrate ahora
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
