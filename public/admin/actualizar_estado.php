<?php
// Forzar supresión de errores y limpiar buffers
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
if (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');

require_once '../../includes/init.php';
require_once '../../includes/func_correo.php';
require_once '../../includes/func_inventario.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (empty($_POST['pedido_id']) || empty($_POST['estado'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$pedido_id = (int)$_POST['pedido_id'];
$nuevo_estado = $_POST['estado'];

// Validar estado
$estados_validos = ['pendiente', 'aprobado', 'rechazado', 'enviado', 'entregado'];
if (!in_array($nuevo_estado, $estados_validos)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Estado inválido']);
    exit;
}

try {
    $pdo = getPdo();
    $pdo->beginTransaction();

    // Obtener estado actual y datos del pedido
    $stmt = $pdo->prepare("
        SELECT p.*, u.email, u.nombre
        FROM pedidos p
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        throw new Exception('Pedido no encontrado');
    }

    // Determinar el estado anterior
    $estado_anterior = !empty($pedido['estado']) ? $pedido['estado'] : 'pendiente';
    if ($estado_anterior === 'pendiente_pago') {
        $estado_anterior = 'pendiente';
    }

    // Manejo de stock según cambio de estado
    if (($estado_anterior === 'rechazado' && $nuevo_estado !== 'rechazado') ||
        ($estado_anterior !== 'rechazado' && $nuevo_estado === 'rechazado')) {
        
        // Obtener detalles del pedido
        $stmt_detalles = $pdo->prepare("
            SELECT producto_id, cantidad
            FROM pedido_detalles
            WHERE pedido_id = ?
        ");
        $stmt_detalles->execute([$pedido_id]);
        $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($detalles as $detalle) {
            $producto_id = $detalle['producto_id'];
            $cantidad = $detalle['cantidad'];
            
            if ($nuevo_estado === 'rechazado') {
                // Rechazar: devolver stock al inventario usando función refactorizada
                if (!registrar_movimiento($producto_id, 'entrada', $cantidad, $pdo)) {
                    throw new Exception("Error al registrar movimiento de entrada para producto ID: $producto_id");
                }
            } else {
                // Cambiar de rechazado a otro estado: volver a reducir stock
                // Verificar stock disponible antes
                $stmt_check = $pdo->prepare("SELECT stock FROM productos WHERE id = ?");
                $stmt_check->execute([$producto_id]);
                $stock_actual = $stmt_check->fetchColumn();
                
                if ($stock_actual < $cantidad) {
                    throw new Exception("Stock insuficiente para el producto ID: $producto_id");
                }
                
                // Registrar movimiento de salida usando función refactorizada
                if (!registrar_movimiento($producto_id, 'salida', $cantidad, $pdo)) {
                    throw new Exception("Error al registrar movimiento de salida para producto ID: $producto_id");
                }
            }
        }
    }

    // Registrar en historial
    $stmt = $pdo->prepare("
        INSERT INTO historial_pedidos (pedido_id, estado_anterior, estado_nuevo, fecha_cambio)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$pedido_id, $estado_anterior, $nuevo_estado]);

    // Actualizar estado del pedido
    $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $pedido_id]);

    // Enviar correo según el estado
    $asunto = '';
    $mensaje = '';
    $boleta_adjunta = null;
    
    switch ($nuevo_estado) {
        case 'aprobado':
            // Log de inicio de solicitud
            @error_log(date('Y-m-d H:i:s') . ' | actualizar_estado.php | INICIO | pedido_id=' . ($_POST['pedido_id'] ?? 'N/A') . ' estado=' . ($_POST['estado'] ?? 'N/A') . "\n", 3, __DIR__ . '/../../logs/estado_error.log');
            
            // Antes de generar boleta
            @error_log(date('Y-m-d H:i:s') . ' | actualizar_estado.php | Antes de generar boleta | pedido_id=' . $pedido_id . "\n", 3, __DIR__ . '/../../logs/estado_error.log');
            
            // Generar boleta en PDF
            require_once '../../includes/func_boleta.php';
            try {
                // Capturar cualquier salida durante la generación de la boleta
                ob_start();
                $boleta_path = generar_boleta_pdf($pedido_id);
                ob_end_clean();
                
                if ($boleta_path) {
                    $boleta_adjunta = $boleta_path;
                    @error_log(date('Y-m-d H:i:s') . ' | actualizar_estado.php | Boleta generada: ' . $boleta_path . "\n", 3, __DIR__ . '/../../logs/estado_error.log');
                } else {
                    @error_log(date('Y-m-d H:i:s') . ' | actualizar_estado.php | No se pudo generar boleta' . "\n", 3, __DIR__ . '/../../logs/estado_error.log');
                }
            } catch (Exception $e) {
                ob_end_clean();
                @error_log(date('Y-m-d H:i:s') . ' | actualizar_estado.php | Error generando boleta: ' . $e->getMessage() . "\n", 3, __DIR__ . '/../../logs/estado_error.log');
                // Continuar sin PDF adjunto
            }
            
            // Después de generar boleta
            @error_log(date('Y-m-d H:i:s') . ' | actualizar_estado.php | Después de generar boleta | pedido_id=' . $pedido_id . "\n", 3, __DIR__ . '/../../logs/estado_error.log');
            
            // Obtener detalles del pedido para el correo
            $stmt_detalles = $pdo->prepare("
                SELECT pd.*, p.nombre as producto_nombre, p.precio
                FROM pedido_detalles pd
                INNER JOIN productos p ON pd.producto_id = p.id
                WHERE pd.pedido_id = ?
            ");
            $stmt_detalles->execute([$pedido_id]);
            $detalles_pedido = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
            
            // Construir tabla de productos para el correo
            $productos_html = '';
            $subtotal_productos = 0;
            foreach ($detalles_pedido as $detalle) {
                $precio_unit = $detalle['precio'];
                $cantidad = $detalle['cantidad'];
                $subtotal_item = $precio_unit * $cantidad;
                $subtotal_productos += $subtotal_item;
                
                $productos_html .= "
                    <tr>
                        <td style='padding: 12px; border-bottom: 1px solid #e0e0e0;'>{$detalle['producto_nombre']}</td>
                        <td style='padding: 12px; border-bottom: 1px solid #e0e0e0; text-align: center;'>{$cantidad}</td>
                        <td style='padding: 12px; border-bottom: 1px solid #e0e0e0; text-align: right;'>S/ " . number_format($precio_unit, 2) . "</td>
                        <td style='padding: 12px; border-bottom: 1px solid #e0e0e0; text-align: right; font-weight: bold;'>S/ " . number_format($subtotal_item, 2) . "</td>
                    </tr>
                ";
            }
            
            // Determinar modalidad y agencia
            $modalidad_envio = '';
            $agencia_info = '';
            $proximos_pasos = '';
            
            if ($pedido['tipo_envio'] === 'domicilio') {
                $modalidad_envio = '🚚 Envío a Domicilio';
                if (!empty($pedido['agencia_envio'])) {
                    $agencia_nombre = $pedido['agencia_envio'] === 'olva' ? 'Olva Courier' : 'Shalom';
                    $agencia_info = "<tr><td>📦 Agencia:</td><td>$agencia_nombre</td></tr>";
                }
                $proximos_pasos = "
                    <div class='next-steps'>
                        <h3>📦 Próximos pasos:</h3>
                        <ul>
                            <li>Tu pedido será preparado y empacado cuidadosamente</li>
                            <li>El pedido será enviado a la dirección indicada</li>
                            <li>Recibirás una notificación cuando sea despachado</li>
                            <li>Un repartidor lo entregará en tu domicilio</li>
                            <li>Revisa tu boleta adjunta en este correo (PDF)</li>
                        </ul>
                    </div>
                ";
            } elseif ($pedido['tipo_envio'] === 'agencia') {
                $modalidad_envio = '📦 Recojo en Agencia';
                if (!empty($pedido['agencia_envio'])) {
                    $agencia_nombre = $pedido['agencia_envio'] === 'olva' ? 'Olva Courier' : 'Shalom';
                    $agencia_info = "<tr><td>📦 Agencia:</td><td>$agencia_nombre</td></tr>";
                }
                $proximos_pasos = "
                    <div class='next-steps'>
                        <h3>📦 Próximos pasos:</h3>
                        <ul>
                            <li>Tu pedido será preparado y empacado cuidadosamente</li>
                            <li>El pedido será enviado a la agencia seleccionada</li>
                            <li>Recibirás una notificación cuando llegue a la agencia</li>
                            <li>Podrás recogerlo presentando tu DNI y el código del pedido</li>
                            <li>Revisa tu boleta adjunta en este correo (PDF)</li>
                        </ul>
                    </div>
                ";
            } else {
                $modalidad_envio = '🏪 Recojo en Tienda';
                $agencia_info = '';
                $proximos_pasos = "
                    <div class='next-steps'>
                        <h3>🏪 Próximos pasos:</h3>
                        <ul>
                            <li>Tu pedido será preparado y estará listo para recoger</li>
                            <li>Recibirás una notificación cuando esté disponible</li>
                            <li>Dirígete a nuestra tienda con tu DNI y el código del pedido</li>
                            <li>Horario de atención: Lunes a Viernes 9:00 AM - 6:00 PM, Sábados 9:00 AM - 1:00 PM</li>
                            <li>Dirección: Av. Guardia Civil mza. A lote. 1 urb. Villa Universitaria, Castilla - Piura</li>
                            <li>Revisa tu boleta adjunta en este correo (PDF)</li>
                        </ul>
                    </div>
                ";
            }
            
            $asunto = "Pedido {$pedido['codigo']} Aprobado" . ($boleta_adjunta ? " - Boleta Adjunta" : "");
            $mensaje = "
                <!DOCTYPE html>
                <html lang='es'>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                        .header { background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%); color: white; padding: 30px 20px; text-align: center; }
                        .header h1 { margin: 0; font-size: 28px; }
                        .content { padding: 30px 20px; }
                        .success-box { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; margin: 20px 0; border-radius: 5px; }
                        .order-code { background: #f8f9fa; border-left: 4px solid #4caf50; padding: 15px; margin: 20px 0; }
                        .order-code strong { color: #4caf50; font-size: 20px; }
                        .total-box { background: #e8f5e9; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center; }
                        .total-box .amount { font-size: 32px; color: #2e7d32; font-weight: bold; }
                        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        .info-table td { padding: 12px; border-bottom: 1px solid #e0e0e0; }
                        .info-table td:first-child { font-weight: bold; color: #555; width: 40%; }
                        .productos-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
                        .productos-table th { background: #2e7d32; color: white; padding: 12px; text-align: left; }
                        .productos-table td { padding: 12px; border-bottom: 1px solid #e0e0e0; }
                        .next-steps { background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; margin: 20px 0; }
                        .next-steps h3 { margin: 0 0 15px 0; color: #856404; }
                        .next-steps ul { margin: 10px 0; padding-left: 20px; color: #856404; }
                        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🎉 ¡Pedido Aprobado!</h1>
                            <p style='margin: 10px 0 0 0; font-size: 16px;'>Tu compra ha sido confirmada</p>
                        </div>
                        
                        <div class='content'>
                            <div class='success-box'>
                                <h2 style='margin: 0 0 10px 0; color: #2e7d32;'>Estimado/a {$pedido['nombre']},</h2>
                                <p style='margin: 0; font-size: 16px;'>Nos complace informarte que tu pedido ha sido aprobado exitosamente. Hemos adjuntado tu boleta de venta en formato PDF.</p>
                            </div>
                            
                            <div class='order-code'>
                                <strong>Pedido: {$pedido['codigo']}</strong>
                            </div>
                            
                            <h3 style='color: #2e7d32; margin: 20px 0 10px 0;'>📦 Productos del Pedido</h3>
                            <table class='productos-table'>
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th style='text-align: center; width: 80px;'>Cant.</th>
                                        <th style='text-align: right; width: 100px;'>Precio</th>
                                        <th style='text-align: right; width: 100px;'>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    $productos_html
                                </tbody>
                            </table>
                            
                            <div class='total-box'>
                                <p style='margin: 0 0 10px 0; font-size: 14px; color: #666;'>TOTAL DE TU COMPRA</p>
                                <div class='amount'>S/ " . number_format($pedido['total'], 2) . "</div>
                            </div>
                            
                            <table class='info-table'>
                                <tr>
                                    <td>💰 Subtotal Productos:</td>
                                    <td>S/ " . number_format($subtotal_productos, 2) . "</td>
                                </tr>
                                <tr>
                                    <td>🚚 Costo de Envío:</td>
                                    <td>S/ " . number_format($pedido['precio_envio'], 2) . "</td>
                                </tr>
                                <tr>
                                    <td>📍 Modalidad:</td>
                                    <td>$modalidad_envio</td>
                                </tr>
                                $agencia_info
                                <tr style='background: #f5f5f5;'>
                                    <td style='font-size: 18px;'><strong>Total:</strong></td>
                                    <td style='font-size: 18px; color: #2e7d32;'><strong>S/ " . number_format($pedido['total'], 2) . "</strong></td>
                                </tr>
                            </table>
                            
                            $proximos_pasos
                        </div>
                        
                        <div class='footer'>
                            <p><strong>Ferretería Tercer Cielo</strong></p>
                            <p>Av. Guardia Civil mza. A lote. 1 urb. Villa Universitaria, Castilla - Piura</p>
                            <p>📞 +51 945 913 352 | 📧 info@tercercielo.com</p>
                            <p style='margin-top: 15px; color: #999;'>© " . date('Y') . " Tercer Cielo. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            break;
        case 'rechazado':
            $asunto = "Pedido {$pedido['codigo']} Rechazado";
            $mensaje = "
                <!DOCTYPE html>
                <html lang='es'>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                        .header { background: linear-gradient(135deg, #f44336 0%, #c62828 100%); color: white; padding: 30px 20px; text-align: center; }
                        .content { padding: 30px 20px; }
                        .alert-box { background: #ffebee; border-left: 4px solid #f44336; padding: 20px; margin: 20px 0; border-radius: 5px; }
                        .contact-box { background: #e3f2fd; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center; }
                        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Información sobre tu pedido</h1>
                        </div>
                        <div class='content'>
                            <div class='alert-box'>
                                <h2 style='margin: 0 0 10px 0; color: #c62828;'>Estimado/a {$pedido['nombre']},</h2>
                                <p style='font-size: 16px;'>Lamentamos informarte que tu pedido con código <strong>{$pedido['codigo']}</strong> ha sido rechazado.</p>
                            </div>
                            
                            <div style='background: #fff3cd; border-radius: 8px; padding: 20px; margin: 20px 0;'>
                                <h3 style='margin: 0 0 15px 0; color: #856404;'>Posibles motivos del rechazo:</h3>
                                <ul style='color: #856404; margin: 0; padding-left: 20px;'>
                                    <li>Comprobante de pago incompleto o ilegible</li>
                                    <li>Monto del pago no coincide con el total del pedido</li>
                                    <li>Productos sin stock disponible</li>
                                    <li>Información de entrega incompleta o incorrecta</li>
                                    <li>Problemas con la verificación del comprobante</li>
                                </ul>
                            </div>
                            
                            <div class='contact-box'>
                                <h3 style='color: #1565c0;'>¿Necesitas ayuda?</h3>
                                <p style='margin-bottom: 15px;'>Contáctanos para aclarar el motivo específico y resolver cualquier inconveniente:</p>
                                <p style='margin: 5px 0;'><strong>📞 Teléfono:</strong> +51 945 913 352</p>
                                <p style='margin: 5px 0;'><strong>📧 Email:</strong> info@tercercielo.com</p>
                                <p style='margin: 15px 0 5px 0;'><strong>Horario de atención:</strong></p>
                                <p style='margin: 0;'>Lunes a Viernes: 9:00 AM - 6:00 PM</p>
                                <p style='margin: 0;'>Sábados: 9:00 AM - 1:00 PM</p>
                            </div>
                        </div>
                        <div class='footer'>
                            <p>© " . date('Y') . " Tercer Cielo. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            break;
        case 'enviado':
            $asunto = "Pedido {$pedido['codigo']} en Camino";
            
            // Determinar tipo de entrega y contenido específico
            $icono_entrega = '';
            $titulo_entrega = '';
            $info_entrega = '';
            $detalles_envio = '';
            
            if ($pedido['tipo_envio'] === 'domicilio') {
                $agencia_nombre = $pedido['agencia_envio'] === 'olva' ? 'Olva Courier' : 'Shalom';
                $icono_entrega = '🚚';
                $titulo_entrega = "Envío a domicilio con $agencia_nombre";
                $info_entrega = $pedido['direccion_envio'];
                $detalles_envio = "
                    <div class='info-box'>
                        <h3 style='color: #856404; margin-top: 0;'>📦 Información del envío</h3>
                        <ul style='color: #856404; margin: 10px 0; padding-left: 20px;'>
                            <li>Tu pedido será entregado por un repartidor en tu domicilio</li>
                            <li>Recibirás una llamada del courier cuando estén cerca</li>
                            <li>Ten tu DNI a la mano para recibir el pedido</li>
                            <li>Verifica que el paquete esté en buen estado antes de firmar</li>
                        </ul>
                    </div>
                ";
            } elseif ($pedido['tipo_envio'] === 'agencia') {
                $agencia_nombre = $pedido['agencia_envio'] === 'olva' ? 'Olva Courier' : 'Shalom';
                $asunto = "Tu pedido {$pedido['codigo']} llegará pronto a la agencia";
                $icono_entrega = '📦';
                $titulo_entrega = "En camino a agencia $agencia_nombre";
                $info_entrega = $pedido['direccion_envio'];
                $detalles_envio = "
                    <div class='info-box'>
                        <h3 style='color: #856404; margin-top: 0;'>📦 Recojo en Agencia $agencia_nombre</h3>
                        <ul style='color: #856404; margin: 10px 0; padding-left: 20px;'>
                            <li>Tu pedido está en camino a la agencia más cercana</li>
                            <li>Te notificaremos cuando esté disponible para recoger</li>
                            <li>Para recoger necesitarás: <strong>DNI + Código del pedido ({$pedido['codigo']})</strong></li>
                            <li>Recuerda que tienes 7 días para recoger tu pedido</li>
                            <li>Horarios de atención: Consulta directamente con $agencia_nombre</li>
                        </ul>
                        <p style='color: #856404; margin: 10px 0 0 0;'><strong>💡 Tip:</strong> Guarda este correo, lo necesitarás para identificar tu pedido en la agencia.</p>
                    </div>
                ";
            } else {
                $icono_entrega = '🏪';
                $titulo_entrega = "Recojo en tienda";
                $info_entrega = $pedido['direccion_envio'];
                $detalles_envio = "
                    <div class='info-box'>
                        <h3 style='color: #856404; margin-top: 0;'>📞 Próximamente te contactaremos</h3>
                        <p style='color: #856404; margin-bottom: 0;'>Nuestro equipo se pondrá en contacto contigo para coordinar los detalles finales de la entrega.</p>
                    </div>
                ";
            }
            
            $mensaje = "
                <!DOCTYPE html>
                <html lang='es'>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                        .header { background: linear-gradient(135deg, #2196f3 0%, #1565c0 100%); color: white; padding: 30px 20px; text-align: center; }
                        .content { padding: 30px 20px; }
                        .shipping-box { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; margin: 20px 0; border-radius: 5px; }
                        .info-box { background: #fff3cd; border-radius: 8px; padding: 20px; margin: 20px 0; }
                        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>$icono_entrega ¡Tu pedido está en camino!</h1>
                        </div>
                        <div class='content'>
                            <div class='shipping-box'>
                                <h2 style='margin: 0 0 10px 0; color: #1565c0;'>Estimado/a {$pedido['nombre']},</h2>
                                <p style='font-size: 16px;'>Tu pedido con código <strong>{$pedido['codigo']}</strong> ha sido enviado y está en proceso de entrega.</p>
                            </div>
                            
                            <h3 style='color: #1565c0;'>$icono_entrega $titulo_entrega</h3>
                            <p><strong>Dirección:</strong> $info_entrega</p>
                            
                            $detalles_envio
                        </div>
                        <div class='footer'>
                            <p><strong>Ferretería Tercer Cielo</strong></p>
                            <p>📞 +51 945 913 352 | 📧 info@tercercielo.com</p>
                            <p>© " . date('Y') . " Tercer Cielo. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            break;
        case 'entregado':
            $asunto = "Pedido {$pedido['codigo']} Entregado";
            $mensaje = "
                <!DOCTYPE html>
                <html lang='es'>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                        .header { background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%); color: white; padding: 30px 20px; text-align: center; }
                        .content { padding: 30px 20px; }
                        .success-box { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; margin: 20px 0; border-radius: 5px; }
                        .thanks-box { background: linear-gradient(135deg, #fff8e1 0%, #ffe082 100%); border-radius: 8px; padding: 25px; margin: 20px 0; text-align: center; }
                        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>✅ ¡Pedido Entregado!</h1>
                            <p style='margin: 10px 0 0 0; font-size: 16px;'>Tu compra ha sido completada</p>
                        </div>
                        <div class='content'>
                            <div class='success-box'>
                                <h2 style='margin: 0 0 10px 0; color: #2e7d32;'>Estimado/a {$pedido['nombre']},</h2>
                                <p style='font-size: 16px;'>Confirmamos que tu pedido con código <strong>{$pedido['codigo']}</strong> ha sido entregado exitosamente.</p>
                            </div>
                            
                            <div class='thanks-box'>
                                <h2 style='margin: 0 0 15px 0; color: #f57c00;'>🎉 ¡Gracias por tu preferencia!</h2>
                                <p style='margin: 0; font-size: 16px; color: #e65100;'>Esperamos que disfrutes de tu compra. Tu confianza es muy importante para nosotros.</p>
                            </div>
                            
                            <p style='text-align: center; margin: 30px 0; font-size: 14px; color: #666;'>
                                Si tienes algún comentario o sugerencia sobre tu experiencia,<br>
                                no dudes en contactarnos al <strong>+51 945 913 352</strong> o <strong>info@tercercielo.com</strong>
                            </p>
                        </div>
                        <div class='footer'>
                            <p><strong>Ferretería Tercer Cielo</strong></p>
                            <p>Av. Guardia Civil mza. A lote. 1 urb. Villa Universitaria, Castilla - Piura</p>
                            <p>📞 +51 945 913 352 | 📧 info@tercercielo.com</p>
                            <p style='margin-top: 15px; color: #999;'>© " . date('Y') . " Tercer Cielo. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            break;
    }

    // Enviar correo si hay mensaje
    if ($mensaje && $asunto) {
        enviar_correo($pedido['email'], $pedido['nombre'], $asunto, $mensaje, null, $boleta_adjunta);
    }

    $pdo->commit();
    
    // Limpiar cualquier buffer residual antes de enviar JSON
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    echo json_encode(['success' => true]);
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Log detallado en archivo
    @error_log(date('Y-m-d H:i:s') . ' | actualizar_estado.php | Exception: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine() . "\n", 3, __DIR__ . '/../../logs/estado_error.log');
    
    // Limpiar cualquier buffer residual
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit;
} catch (Error $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    @error_log(date('Y-m-d H:i:s') . ' | actualizar_estado.php | Error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine() . "\n", 3, __DIR__ . '/../../logs/estado_error.log');
    
    // Limpiar cualquier buffer residual
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error fatal: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit;
}