<?php
require_once '../includes/init.php';
require_once '../includes/func_correo.php';

header('Content-Type: application/json');

try {
    // Validar campos requeridos
    $campos = ['nombre', 'email', 'asunto', 'mensaje'];
    foreach ($campos as $campo) {
        if (empty($_POST[$campo])) {
            throw new Exception("El campo $campo es requerido");
        }
    }

    $nombre = trim($_POST['nombre']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $asunto = trim($_POST['asunto']);
    $mensaje = trim($_POST['mensaje']);

    if (!$email) {
        throw new Exception('El correo electrónico no es válido');
    }

    // Preparar contenido del correo en HTML para la empresa
    $contenido_html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <style>
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background: #f5f5f5;
            }
            .container { 
                max-width: 600px; 
                margin: 20px auto; 
                background: white; 
                border-radius: 10px; 
                overflow: hidden; 
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            .header { 
                background: linear-gradient(135deg, #1a5d1a 0%, #2d8b2d 100%); 
                color: white; 
                padding: 30px 20px; 
                text-align: center;
            }
            .header h2 { 
                margin: 0; 
                font-size: 24px; 
                font-weight: 600;
            }
            .header .subtitle {
                margin-top: 5px;
                font-size: 14px;
                opacity: 0.9;
            }
            .content { 
                padding: 30px;
            }
            .section { 
                margin-bottom: 25px; 
                background: #f8f9fa; 
                padding: 20px; 
                border-radius: 8px;
                border-left: 4px solid #1a5d1a;
            }
            .section-title { 
                background: #1a5d1a; 
                color: white;
                padding: 10px 15px; 
                margin: -20px -20px 15px -20px; 
                font-weight: 600;
                font-size: 16px;
            }
            .field { 
                margin-bottom: 12px;
                padding: 10px;
                background: white;
                border-radius: 5px;
            }
            .field-label { 
                font-weight: 600; 
                color: #1a5d1a;
                display: inline-block;
                min-width: 80px;
            }
            .field-value {
                color: #555;
            }
            .footer {
                background: #f8f9fa;
                padding: 20px;
                text-align: center;
                color: #666;
                font-size: 13px;
            }
            .icon {
                font-size: 40px;
                margin-bottom: 10px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='icon'>&#128296;</div>
                <h2>NUEVO MENSAJE DE CONTACTO</h2>
                <div class='subtitle'>Ferreter&iacute;a Tercer Cielo</div>
            </div>
            <div class='content'>
                <div class='section'>
                    <div class='section-title'>&#128203; INFORMACI&Oacute;N DEL CONTACTO</div>
                    <div class='field'>
                        <span class='field-label'>&#128100; Nombre:</span>
                        <span class='field-value'>" . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . "</span>
                    </div>
                    <div class='field'>
                        <span class='field-label'>&#128231; Email:</span>
                        <span class='field-value'>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</span>
                    </div>
                    <div class='field'>
                        <span class='field-label'>&#127991; Asunto:</span>
                        <span class='field-value'>" . htmlspecialchars($asunto, ENT_QUOTES, 'UTF-8') . "</span>
                    </div>
                </div>
                
                <div class='section'>
                    <div class='section-title'>&#128172; MENSAJE</div>
                    <div class='field' style='min-height: 80px;'>
                        <div class='field-value'>" . nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8')) . "</div>
                    </div>
                </div>
                
                <div class='field' style='text-align: center; margin-top: 20px;'>
                    <span class='field-label'>&#128336; Fecha:</span>
                    <span class='field-value'>" . date('d/m/Y H:i:s') . "</span>
                </div>
            </div>
            <div class='footer'>
                <strong>Ferreter&iacute;a Tercer Cielo</strong><br>
                Urb. Villa Universitaria A-1, Av. Guardia Civil - Castilla, Piura<br>
                &#128241; +51 945 913 352
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Enviar correo a la empresa (tu correo)
    $enviado = enviar_correo(
        'jedarchdj@gmail.com', // Tu correo donde recibirás los mensajes de contacto
        'Tercer Cielo',
        'Nuevo Mensaje de Contacto: ' . $asunto,
        $contenido_html
    );

    if (!$enviado) {
        throw new Exception('Error al enviar el correo de contacto');
    }

    // Enviar confirmación al cliente
    $contenido_cliente = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background: #f5f5f5;
            }
            .container { 
                max-width: 600px; 
                margin: 20px auto; 
                background: white; 
                border-radius: 10px; 
                overflow: hidden; 
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            .header { 
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
                color: white; 
                padding: 30px 20px; 
                text-align: center;
            }
            .header h2 { 
                margin: 0; 
                font-size: 24px; 
                font-weight: 600;
            }
            .icon {
                font-size: 50px;
                margin-bottom: 10px;
            }
            .content { 
                padding: 30px;
            }
            .message-box {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                border-left: 4px solid #28a745;
                margin: 20px 0;
            }
            .footer {
                background: #f8f9fa;
                padding: 20px;
                text-align: center;
                color: #666;
                font-size: 13px;
            }
            .contact-info {
                margin-top: 15px;
                padding-top: 15px;
                border-top: 2px solid #e9ecef;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='icon'>✅</div>
                <h2>¡MENSAJE RECIBIDO!</h2>
            </div>
            <div class='content'>
                <p style='font-size: 16px;'>Estimado(a) <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
                
                <div class='message-box'>
                    <p style='margin: 0; font-size: 15px;'>
                        ¡Gracias por contactarnos! Hemos recibido tu mensaje correctamente y nuestro equipo de 
                        <strong>Ferretería Tercer Cielo</strong> se pondrá en contacto contigo a la brevedad.
                    </p>
                </div>
                
                <p style='color: #666;'>
                    Si tienes alguna consulta urgente, no dudes en llamarnos o escribirnos por WhatsApp.
                </p>
                
                <div class='contact-info'>
                    <strong style='color: #1a5d1a;'>📱 WhatsApp:</strong> +51 945 913 352<br>
                    <strong style='color: #1a5d1a;'>📧 Email:</strong> jedarchdj@gmail.com<br>
                    <strong style='color: #1a5d1a;'>📍 Ubicación:</strong> Urb. Villa Universitaria A-1, Av. Guardia Civil - Castilla, Piura
                </div>
                
                <p style='margin-top: 25px; text-align: center; color: #666;'>
                    Atentamente,<br>
                    <strong style='color: #1a5d1a; font-size: 18px;'>Equipo de Ferretería Tercer Cielo</strong><br>
                    <span style='font-size: 13px;'>🔨 Tu ferretería de confianza</span>
                </p>
            </div>
            <div class='footer'>
                <strong>Ferretería Tercer Cielo</strong><br>
                Urb. Villa Universitaria A-1, Av. Guardia Civil - Castilla, Piura<br>
                📱 +51 945 913 352 | 📧 jedarchdj@gmail.com
            </div>
        </div>
    </body>
    </html>
    ";

    enviar_correo(
        $email,
        $nombre,
        'Confirmacion de Contacto - Tercer Cielo',
        $contenido_cliente
    );

    echo json_encode([
        'success' => true,
        'mensaje' => 'Mensaje enviado correctamente'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
}