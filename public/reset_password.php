<?php
require_once '../includes/init.php';
require_once '../includes/func_usuarios.php';

$token = $_GET['token'] ?? '';
$error = $success = $show_new_link = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($password !== $confirm) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $val_pass = validar_contrasena_robusta($password);
        if ($val_pass !== true) {
            $error = $val_pass;
        } else {
            $pdo = getPdo();
            $stmt = $pdo->prepare("SELECT id, password, reset_token FROM usuarios WHERE reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $error = "No puedes usar la misma contraseña anterior.";
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                    $stmt->execute([$hashed, $user['id']]);
                    $success = "¡Contraseña actualizada! Ya puedes iniciar sesión.";
                }
            } else {
                $error = "El enlace ha expirado o ya fue usado.";
                $show_new_link = true;
            }
        }
    }
} else {
    // Verificar token al cargar la página
    if ($token) {
        $pdo = getPdo();
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        if (!$stmt->fetch()) {
            $error = "El enlace ha expirado o ya fue usado.";
            $show_new_link = true;
        }
    } else {
        $error = "No se proporcionó un token válido.";
        $show_new_link = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Tercer Cielo</title>
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
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        .btn-reset {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-reset:hover {
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
            background: #dbeafe;
            padding: 10px;
            border-radius: 8px;
            border-left: 3px solid #3b82f6;
        }
        .success-icon {
            font-size: 60px;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <i class="bi bi-shield-lock-fill" style="font-size: 48px;"></i>
                        <h2 class="mt-3">Nueva Contraseña</h2>
                        <p>Crea una contraseña segura</p>
                    </div>
                    
                    <div class="auth-body">
                        <?php if ($success): ?>
                            <div class="text-center">
                                <i class="bi bi-check-circle-fill success-icon"></i>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    <?= $success ?>
                                </div>
                                <a href="login.php" class="btn btn-reset w-100">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                                </a>
                            </div>

                        <?php elseif ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>

                            <?php if ($show_new_link): ?>
                                <div class="text-center mt-3">
                                    <p class="mb-3">El enlace de recuperación ha expirado o ya fue usado.</p>
                                    <a href="recuperar_password.php" class="btn btn-reset w-100">
                                        <i class="bi bi-arrow-clockwise me-2"></i>Solicitar nuevo enlace
                                    </a>
                                </div>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nueva contraseña</label>
                                        <div class="input-group-icon position-relative">
                                            <i class="bi bi-lock icon"></i>
                                            <input type="password" name="password" id="pass-reset" 
                                                   class="form-control" placeholder="Tu nueva contraseña" 
                                                   required minlength="8">
                                            <i class="bi bi-eye eye-toggle" id="eye-reset" 
                                               onclick="togglePassword('pass-reset', 'eye-reset')"></i>
                                        </div>
                                        <div class="password-requirements">
                                            <i class="bi bi-info-circle me-1"></i>
                                            <strong>Requisitos:</strong> Mínimo 8 caracteres, 1 mayúscula, 1 carácter especial (!@#$%^&*)
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">Confirmar contraseña</label>
                                        <div class="input-group-icon">
                                            <i class="bi bi-lock-fill icon"></i>
                                            <input type="password" name="confirm" class="form-control" 
                                                   placeholder="Repite tu contraseña" required>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-reset w-100">
                                        <i class="bi bi-check-circle me-2"></i>Cambiar Contraseña
                                    </button>
                                </form>
                            <?php endif; ?>

                        <?php else: ?>
                            <!-- Formulario normal -->
                            <form method="POST">
                                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Nueva contraseña</label>
                                    <div class="input-group-icon position-relative">
                                        <i class="bi bi-lock icon"></i>
                                        <input type="password" name="password" id="pass-reset" 
                                               class="form-control" placeholder="Tu nueva contraseña" 
                                               required minlength="8">
                                        <i class="bi bi-eye eye-toggle" id="eye-reset" 
                                           onclick="togglePassword('pass-reset', 'eye-reset')"></i>
                                    </div>
                                    <div class="password-requirements">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <strong>Requisitos:</strong> Mínimo 8 caracteres, 1 mayúscula, 1 carácter especial (!@#$%^&*)
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Confirmar contraseña</label>
                                    <div class="input-group-icon">
                                        <i class="bi bi-lock-fill icon"></i>
                                        <input type="password" name="confirm" class="form-control" 
                                               placeholder="Repite tu contraseña" required>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-reset w-100">
                                    <i class="bi bi-check-circle me-2"></i>Cambiar Contraseña
                                </button>
                            </form>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <a href="login.php" class="link-primary">
                                <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión
                            </a>
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