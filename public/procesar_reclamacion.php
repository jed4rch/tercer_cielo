<?php
// phpcs:disable
require_once '../includes/init.php';
require_once '../includes/func_correo.php';

header('Content-Type: application/json');

try {
    // Validar campos requeridos
    $campos = ['nombre', 'documento', 'telefono', 'email', 'direccion', 'tipo', 'descripcion', 'solucion'];
    foreach ($campos as $campo) {
        if (empty($_POST[$campo])) {
            throw new Exception("El campo $campo es requerido");
        }
    }

    $nombre = trim($_POST['nombre']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('El correo electrónico no es válido');
    }

    // Generar número de seguimiento
    $numero_seguimiento = date('Ymd') . '-' . strtoupper(substr($_POST['tipo'], 0, 1)) . rand(1000, 9999);
    
    // Escapar variables para seguridad
    $tipo_esc = htmlspecialchars($_POST['tipo'], ENT_QUOTES, 'UTF-8');
    $nombre_esc = htmlspecialchars($_POST['nombre'], ENT_QUOTES, 'UTF-8');
    $documento_esc = htmlspecialchars($_POST['documento'], ENT_QUOTES, 'UTF-8');
    $telefono_esc = htmlspecialchars($_POST['telefono'], ENT_QUOTES, 'UTF-8');
    $email_esc = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');
    $direccion_esc = htmlspecialchars($_POST['direccion'], ENT_QUOTES, 'UTF-8');
    $descripcion_esc = nl2br(htmlspecialchars($_POST['descripcion'], ENT_QUOTES, 'UTF-8'));
    $solucion_esc = nl2br(htmlspecialchars($_POST['solucion'], ENT_QUOTES, 'UTF-8'));
    $fecha_actual = date('d/m/Y H:i:s');
    $tipo_mayus = strtoupper($tipo_esc);
    
    // Campos opcionales
    $pedido_html = '';
    if (!empty($_POST['pedido'])) {
        $pedido_esc = htmlspecialchars($_POST['pedido'], ENT_QUOTES, 'UTF-8');
        $pedido_html = "<div class='field'><span class='field-label'>N&uacute;mero de Pedido:</span><span class='field-value'>$pedido_esc</span></div>";
    }
    
    $monto_html = '';
    if (!empty($_POST['monto'])) {
        $monto_esc = htmlspecialchars($_POST['monto'], ENT_QUOTES, 'UTF-8');
        $monto_html = "<div class='field'><span class='field-label'>Monto Reclamado:</span><span class='field-value'>$monto_esc</span></div>";
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
                background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
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
            .alert-badge {
                background: #fff3cd;
                color: #856404;
                padding: 10px 20px;
                border-radius: 5px;
                font-weight: 600;
                display: inline-block;
                margin: 15px 0;
            }
            .tracking-badge {
                background: #d4edda;
                color: #155724;
                padding: 10px 20px;
                border-radius: 5px;
                font-weight: 700;
                display: inline-block;
                margin: 10px 0;
                font-size: 18px;
                letter-spacing: 1px;
            }
            .content { 
                padding: 30px;
            }
            .section { 
                margin-bottom: 25px; 
                background: #f8f9fa; 
                padding: 20px; 
                border-radius: 8px;
                border-left: 4px solid #dc3545;
            }
            .section-title { 
                background: #dc3545; 
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
                color: #dc3545;
                display: inline-block;
                min-width: 120px;
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
                <div class='icon'>&#9888;</div>
                <h2>LIBRO DE RECLAMACIONES</h2>
                <div class='subtitle'>Nueva $tipo_esc Registrada</div>
                <div class='alert-badge'>&#9200; Plazo de respuesta: 30 d&iacute;as calendario</div>
                <div class='tracking-badge'>&#128203; N&uacute;mero: $numero_seguimiento</div>
            </div>
            <div class='content'>
                <div class='section'>
                    <div class='section-title'>&#128100; DATOS DEL CONSUMIDOR</div>
                    <div class='field'>
                        <span class='field-label'>Nombre completo:</span>
                        <span class='field-value'>$nombre_esc</span>
                    </div>
                    <div class='field'>
                        <span class='field-label'>Documento:</span>
                        <span class='field-value'>$documento_esc</span>
                    </div>
                    <div class='field'>
                        <span class='field-label'>Tel&eacute;fono:</span>
                        <span class='field-value'>$telefono_esc</span>
                    </div>
                    <div class='field'>
                        <span class='field-label'>Email:</span>
                        <span class='field-value'>$email_esc</span>
                    </div>
                    <div class='field'>
                        <span class='field-label'>Direcci&oacute;n:</span>
                        <span class='field-value'>$direccion_esc</span>
                    </div>
                </div>
                
                <div class='section'>
                    <div class='section-title'>&#128203; DETALLE DE LA $tipo_mayus</div>
                    <div class='field'>
                        <span class='field-label'>Tipo:</span>
                        <span class='field-value' style='font-weight: 600; color: #dc3545;'>$tipo_esc</span>
                    </div>
                    $pedido_html
                    $monto_html
                    <div class='field' style='min-height: 60px;'>
                        <span class='field-label'>Descripci&oacute;n:</span><br>
                        <div class='field-value' style='margin-top: 10px;'>$descripcion_esc</div>
                    </div>
                    <div class='field' style='min-height: 60px;'>
                        <span class='field-label'>Soluci&oacute;n esperada:</span><br>
                        <div class='field-value' style='margin-top: 10px;'>$solucion_esc</div>
                    </div>
                </div>
                
                <div class='field' style='text-align: center; margin-top: 20px; background: #fff3cd; border-left: 4px solid #ffc107;'>
                    <span class='field-label'>&#128336; Fecha de registro:</span>
                    <span class='field-value'>$fecha_actual</span>
                </div>
            </div>
            <div class='footer'>
                <strong>Ferreter&iacute;a Tercer Cielo</strong><br>
                Urb. Villa Universitaria A-1, Av. Guardia Civil - Castilla, Piura<br>
                &#128241; +51 945 913 352 | &#128231; jedarchdj@gmail.com
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Enviar correo a la empresa (tu correo)
    $enviado = enviar_correo(
        'jedarchdj@gmail.com', // Tu correo donde recibirás las reclamaciones
        'Tercer Cielo',
        'Nueva Reclamacion - ' . $_POST['tipo'],
        $contenido_html
    );

    if (!$enviado) {
        throw new Exception('Error al enviar el correo de reclamación');
    }

    // Enviar confirmación al cliente
    $contenido_cliente = "
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
            .tracking-box {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                padding: 20px;
                border-radius: 8px;
                text-align: center;
                margin: 20px 0;
                border: 2px dashed #28a745;
            }
            .tracking-number {
                font-size: 24px;
                font-weight: 700;
                color: #28a745;
                letter-spacing: 2px;
                margin: 10px 0;
            }
            .message-box {
                background: #fff3cd;
                padding: 20px;
                border-radius: 8px;
                border-left: 4px solid #ffc107;
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
            .info-item {
                display: inline-block;
                margin: 10px 15px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='icon'>&#9989;</div>
                <h2>RECLAMACI&Oacute;N REGISTRADA</h2>
            </div>
            <div class='content'>
                <p style='font-size: 16px;'>Estimado(a) <strong>$nombre_esc</strong>,</p>
                
                <div class='tracking-box'>
                    <p style='margin: 0; font-size: 14px; color: #666;'>&#128203; N&uacute;mero de Seguimiento</p>
                    <div class='tracking-number'>$numero_seguimiento</div>
                    <p style='margin: 0; font-size: 13px; color: #666;'>Guarda este n&uacute;mero para dar seguimiento</p>
                </div>
                
                <p style='color: #555; font-size: 15px;'>
                    Tu <strong>$tipo_esc</strong> ha sido registrada exitosamente en nuestro 
                    <strong>Libro de Reclamaciones Oficial</strong> conforme a la Ley N&deg; 29571 - C&oacute;digo de Protecci&oacute;n y Defensa del Consumidor.
                </p>
                
                <div class='message-box'>
                    <p style='margin: 0; font-size: 15px; font-weight: 600; color: #856404;'>
                        &#9200; Plazo de Respuesta: 30 d&iacute;as calendario
                    </p>
                    <p style='margin: 10px 0 0 0; font-size: 14px; color: #856404;'>
                        Recibir&aacute;s nuestra respuesta en tu correo electr&oacute;nico dentro del plazo establecido por ley.
                    </p>
                </div>
                
                <p style='color: #666; font-size: 14px;'>
                    <strong>&iquest;Qu&eacute; sigue?</strong>
                </p>
                <ul style='color: #666; font-size: 14px;'>
                    <li>Nuestro equipo revisar&aacute; tu caso detalladamente</li>
                    <li>Te contactaremos para resolver tu situaci&oacute;n</li>
                    <li>Recibir&aacute;s una respuesta formal por correo</li>
                </ul>
                
                <div class='contact-info'>
                    <p style='color: #666; font-size: 14px; margin-bottom: 10px;'>
                        <strong>Si necesitas informaci&oacute;n adicional:</strong>
                    </p>
                    <div class='info-item'>
                        <strong style='color: #1a5d1a;'>&#128241; WhatsApp:</strong> +51 945 913 352
                    </div>
                    <div class='info-item'>
                        <strong style='color: #1a5d1a;'>&#128231; Email:</strong> jedarchdj@gmail.com
                    </div>
                </div>
                
                <p style='margin-top: 25px; text-align: center; color: #666;'>
                    Atentamente,<br>
                    <strong style='color: #1a5d1a; font-size: 18px;'>Equipo de Ferreter&iacute;a Tercer Cielo</strong><br>
                    <span style='font-size: 13px;'>&#128296; Comprometidos con tu satisfacci&oacute;n</span>
                </p>
            </div>
            <div class='footer'>
                <strong>Ferreter&iacute;a Tercer Cielo</strong><br>
                Urb. Villa Universitaria A-1, Av. Guardia Civil - Castilla, Piura<br>
                &#128241; +51 945 913 352 | &#128231; jedarchdj@gmail.com<br>
                <small style='color: #999; margin-top: 10px; display: block;'>
                    Este correo confirma la recepci&oacute;n de tu reclamaci&oacute;n seg&uacute;n Ley N&deg; 29571
                </small>
            </div>
        </div>
    </body>
    </html>
    ";

    enviar_correo(
        $email,
        $nombre,
        'Confirmacion de Reclamacion - Tercer Cielo',
        $contenido_cliente
    );

    echo json_encode([
        'success' => true,
        'mensaje' => 'Reclamacion enviada correctamente'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
}