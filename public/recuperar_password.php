<?php
require_once '../includes/init.php';
require_once '../includes/func_usuarios.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // <-- Carga PHPMailer

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $usuario = get_usuario_by_email($email);

    if ($usuario) {
        // Verificar si el usuario está inactivo
        if (isset($usuario['activo']) && $usuario['activo'] == 0) {
            $error = "Esta cuenta está inhabilitada. No puedes recuperar contraseña.";
        } else {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hora

            $pdo = getPdo();
            $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $usuario['id']]);

            // --- ENVIAR CORREO CON PHPMailer ---
            $mail = new PHPMailer(true);
            try {
                // Configuración SMTP
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'jedarchdj@gmail.com';           // TU GMAIL
                $mail->Password   = 'tvihyxolbbfqhtiu'; // APP PASSWORD
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Remitente y destinatario
                $mail->setFrom('jedarchdj@gmail.com', 'Tercer Cielo');
                $mail->addAddress($email);

                // Contenido
                $link = "http://localhost/tercer_cielo/public/reset_password.php?token=$token";
                $mail->isHTML(true);
                $mail->Subject = 'Recuperar Contrasena - Tercer Cielo';
                $mail->Body    = "
                    <!DOCTYPE html>
                    <html lang='es'>
                    <head>
                        <meta charset='UTF-8'>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                        <style>
                            body {
                                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                                background-color: #f4f4f4;
                                margin: 0;
                                padding: 0;
                            }
                            .email-container {
                                max-width: 600px;
                                margin: 40px auto;
                                background: white;
                                border-radius: 20px;
                                overflow: hidden;
                                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                            }
                            .header {
                                background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
                                padding: 40px 20px;
                                text-align: center;
                                color: white;
                            }
                            .header h1 {
                                margin: 0;
                                font-size: 28px;
                                font-weight: 600;
                            }
                            .content {
                                padding: 40px 30px;
                            }
                            .content p {
                                color: #555;
                                line-height: 1.6;
                                font-size: 16px;
                            }
                            .info-box {
                                background: #dbeafe;
                                border-left: 4px solid #3b82f6;
                                padding: 20px;
                                margin: 20px 0;
                                border-radius: 5px;
                            }
                            .info-box p {
                                margin: 0;
                                color: #333;
                            }
                            .btn-container {
                                text-align: center;
                                margin: 30px 0;
                            }
                            .btn {
                                display: inline-block;
                                padding: 15px 40px;
                                background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
                                color: white !important;
                                text-decoration: none;
                                border-radius: 10px;
                                font-weight: 600;
                                font-size: 16px;
                                box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
                            }
                            .footer {
                                background: #f8f9fa;
                                padding: 20px;
                                text-align: center;
                                color: #666;
                                font-size: 14px;
                            }
                            .divider {
                                height: 1px;
                                background: #e0e0e0;
                                margin: 20px 0;
                            }
                        </style>
                    </head>
                    <body>
                        <div class='email-container'>
                            <div class='header'>
                                <h1>🔑 Recuperar Contraseña</h1>
                            </div>
                            <div class='content'>
                                <p>Hola,</p>
                                <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>Tercer Cielo</strong>.</p>
                                
                                <div class='info-box'>
                                    <p><strong>⚠️ Importante:</strong> Si no solicitaste este cambio, puedes ignorar este correo. Tu contraseña no cambiará.</p>
                                </div>
                                
                                <p>Para crear una nueva contraseña, haz clic en el botón de abajo:</p>
                                
                                <div class='btn-container'>
                                    <a href='$link' class='btn'>Restablecer Contraseña</a>
                                </div>
                                
                                <div class='divider'></div>
                                
                                <p style='font-size: 14px; color: #999;'>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
                                <p style='font-size: 13px; color: #3b82f6; word-break: break-all;'>$link</p>
                                
                                <div class='info-box' style='background: #fff3cd; border-left-color: #ffc107;'>
                                    <p style='font-size: 14px;'><strong>⏰ Este enlace expira en 1 hora</strong> por razones de seguridad.</p>
                                </div>
                            </div>
                            <div class='footer'>
                                <p><strong>Tercer Cielo Boutique</strong></p>
                                <p>Av. Guardia Civil mza. A lote. 1 urb. Villa Universitaria, Castilla - Piura</p>
                                <p>📞 968 045 028 | 📧 tercercielo.boutique@gmail.com</p>
                                <p style='margin-top: 15px; font-size: 12px;'>© " . date('Y') . " Tercer Cielo. Todos los derechos reservados.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";

                $mail->send();
                $success = "Se envió un enlace de recuperación a tu correo.";
            } catch (Exception $e) {
                $error = "Error al enviar correo: {$mail->ErrorInfo}";
            }
        }
    } else {
        $error = "No existe una cuenta con ese correo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Tercer Cielo</title>
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
        .btn-recover {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-recover:hover {
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
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box i {
            color: #3b82f6;
            font-size: 20px;
        }
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loader-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            animation: slideUp 0.3s ease;
        }
        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid #e0e0e0;
            border-top: 5px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loader overlay -->
    <div class="loader-overlay" id="loaderOverlay">
        <div class="loader-content">
            <div class="spinner"></div>
            <h5>Enviando correo...</h5>
            <p class="text-muted mb-0">Por favor espera un momento</p>
        </div>
    </div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <i class="bi bi-key-fill" style="font-size: 48px;"></i>
                        <h2 class="mt-3">Recuperar contraseña</h2>
                        <p>Te enviaremos un enlace a tu correo</p>
                    </div>
                    
                    <div class="auth-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?= $success ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!isset($success)): ?>
                        <div class="info-box">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <small>Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña. El enlace expira en 1 hora.</small>
                        </div>

                        <form method="POST" id="recoverForm">
                            <div class="mb-4">
                                <label class="form-label">Correo electrónico</label>
                                <div class="input-group-icon">
                                    <i class="bi bi-envelope icon"></i>
                                    <input type="email" name="email" class="form-control" 
                                           placeholder="tu@correo.com" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-recover w-100">
                                <i class="bi bi-send me-2"></i>Enviar enlace de recuperación
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
        document.getElementById('recoverForm')?.addEventListener('submit', function() {
            document.getElementById('loaderOverlay').style.display = 'flex';
        });
    </script>
</body>
</html>