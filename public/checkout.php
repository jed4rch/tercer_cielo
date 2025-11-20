<?php
$titulo = "Checkout - Finalizar Compra";
require_once '../includes/init.php';
require_once '../includes/func_carrito.php';

// === REDIRECCIÓN SI ES ADMIN ===
if (isset($_SESSION['user_id']) && $_SESSION['rol'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$carrito = get_carrito();
if (empty($carrito)) {
    header('Location: index.php');
    exit;
}

$total_productos = get_total_carrito();
$usuario_id = $_SESSION['user_id'];

$pdo = getPdo();
$stmt = $pdo->prepare("SELECT nombre, email, telefono FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

$direccion_tienda = "Av. Guardia Civil mza. A lote. 1 urb. Villa Universitaria (Frente a. Violeta Ruesta) Piura - Piura - Castilla";
$telefono_tienda = "+51 945 913 352";

// === RUTAS DE IMÁGENES ===
$logo_yape = '../assets/img/yapeLogo.png';
$logo_plin = '../assets/img/plinLogo.png';
$qr_yape   = '../assets/img/yape.jpg';
$qr_plin   = '../assets/img/plin.jpg';

// === CARGAR DATOS DE ENVÍO ===
$departamentos = $pdo->query("SELECT DISTINCT departamento FROM envios WHERE habilitado = 1 ORDER BY departamento")->fetchAll(PDO::FETCH_COLUMN);

$mensaje = '';
$envio_seleccionado = ['precio' => 0, 'distrito' => '', 'provincia' => '', 'departamento' => ''];
$total_final = $total_productos;

if ($_POST) {
    $metodo_envio = $_POST['metodo_envio'] ?? '';
    $tipo_entrega = $metodo_envio; // domicilio, agencia, tienda
    $agencia_envio = $_POST['agencia_envio'] ?? 'olva';
    $metodo_pago = $_POST['metodo_pago'] ?? 'yape';
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $distrito_id = $_POST['distrito_id'] ?? 0;

    $direccion_final = '';
    $envio_info = '';

    if ($tipo_entrega === 'domicilio' || $tipo_entrega === 'agencia') {
        // Validar campos comunes
        if (empty($telefono) || $distrito_id <= 0) {
            $mensaje = '<div class="alert alert-danger">Completa todos los campos requeridos</div>';
        } elseif ($tipo_entrega === 'domicilio' && empty($direccion)) {
            $mensaje = '<div class="alert alert-danger">Ingresa la dirección de entrega</div>';
        } else {
            // Determinar campo de precio según tipo y agencia
            $campo_precio = '';
            if ($tipo_entrega === 'domicilio' && $agencia_envio === 'olva') {
                $campo_precio = 'precio_domicilio_olva';
            } elseif ($tipo_entrega === 'agencia' && $agencia_envio === 'olva') {
                $campo_precio = 'precio_agencia_olva';
            } elseif ($tipo_entrega === 'domicilio' && $agencia_envio === 'shalom') {
                $campo_precio = 'precio_domicilio_shalom';
            } elseif ($tipo_entrega === 'agencia' && $agencia_envio === 'shalom') {
                $campo_precio = 'precio_agencia_shalom';
            }

            $stmt = $pdo->prepare("SELECT departamento, provincia, distrito, $campo_precio AS precio FROM envios WHERE id = ?");
            $stmt->execute([$distrito_id]);
            $envio = $stmt->fetch();
            
            if (!$envio || $envio['precio'] <= 0) {
                $mensaje = '<div class="alert alert-danger">Distrito no válido o no disponible para esta modalidad</div>';
            } else {
                $envio_seleccionado = [
                    'precio' => $envio['precio'],
                    'distrito' => $envio['distrito'],
                    'provincia' => $envio['provincia'],
                    'departamento' => $envio['departamento']
                ];
                $envio_info = "{$envio['departamento']}, {$envio['provincia']}, {$envio['distrito']}";
                
                if ($tipo_entrega === 'domicilio') {
                    $direccion_final = "$direccion, $envio_info";
                } else {
                    // Para agencia, solo guardamos la ubicación, no el texto "Recojo en agencia"
                    $direccion_final = $envio_info;
                }
                
                $total_final = $total_productos + $envio_seleccionado['precio'];
            }
        }
    } elseif ($tipo_entrega === 'tienda') {
        if (empty($telefono)) {
            $mensaje = '<div class="alert alert-danger">Ingresa tu teléfono para recojo</div>';
        } else {
            $direccion_final = $direccion_tienda;
            $envio_info = "Recojo en tienda";
            $agencia_envio = ''; // No aplica agencia para recojo en tienda
        }
    }

    if (!$mensaje && in_array($metodo_pago, ['yape', 'plin']) && isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['comprobante'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $mensaje = '<div class="alert alert-danger">Solo imágenes (JPG, PNG, WEBP)</div>';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $mensaje = '<div class="alert alert-danger">Imagen muy grande (máx 5MB)</div>';
        } else {
            // === GENERAR CÓDIGO ===
            $codigo = 'PED-' . date('Y') . '-' . str_pad($pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn() + 1, 3, '0', STR_PAD_LEFT);
            $filename = $codigo . '.' . $ext;
            $comprobante_path = 'uploads/comprobantes/' . $filename;
            $destino = __DIR__ . '/' . $comprobante_path;

            error_log("CHECKOUT: Intentando guardar en: $destino");

            // === SOLO SI SE GUARDA EL ARCHIVO → CONTINUAR ===
            if (move_uploaded_file($file['tmp_name'], $destino)) {
                error_log("CHECKOUT: Comprobante guardado correctamente: $destino");

                // === ACTUALIZAR TELEFONO USUARIO ===
                $pdo->prepare("UPDATE usuarios SET telefono = ? WHERE id = ?")
                    ->execute([$telefono, $usuario_id]);

                // === INSERTAR PEDIDO ===
                $stmt = $pdo->prepare("INSERT INTO pedidos (codigo, usuario_id, total, metodo_pago, metodo_envio, tipo_envio, agencia_envio, direccion_envio, telefono, comprobante, estado, precio_envio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $codigo,
                    $usuario_id,
                    $total_final,
                    $metodo_pago,
                    $tipo_entrega === 'tienda' ? 'recojo' : 'envio',
                    $tipo_entrega,
                    $agencia_envio,
                    $direccion_final,
                    $telefono,
                    $comprobante_path,
                    'pendiente_pago',
                    $envio_seleccionado['precio']
                ]);
                $pedido_id = $pdo->lastInsertId();

                // === DETALLES ===
                $stmt_detalle = $pdo->prepare("INSERT INTO pedido_detalles (pedido_id, producto_id, nombre, precio, cantidad) VALUES (?, ?, ?, ?, ?)");
                foreach ($carrito as $id => $item) {
                    $stmt_detalle->execute([$pedido_id, $id, $item['nombre'], $item['precio'], $item['cantidad']]);
                    $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")->execute([$item['cantidad'], $id]);
                }

                // === LIMPIAR CARRITO ===
                unset($_SESSION['carrito']);

                // === CORREO CON IMAGEN ===
                $asunto = "Comprobante recibido - $codigo";
                
                // Determinar texto de entrega
                $icono_entrega = '';
                $titulo_entrega = '';
                $texto_entrega = '';
                
                if ($tipo_entrega === 'domicilio') {
                    $agencia_nombre = $agencia_envio === 'olva' ? 'Olva Courier' : 'Shalom';
                    $icono_entrega = '🚚';
                    $titulo_entrega = "Envío a domicilio ($agencia_nombre)";
                    $texto_entrega = $direccion_final;
                } elseif ($tipo_entrega === 'agencia') {
                    $agencia_nombre = $agencia_envio === 'olva' ? 'Olva Courier' : 'Shalom';
                    $icono_entrega = '📦';
                    $titulo_entrega = "Recojo en agencia $agencia_nombre";
                    $texto_entrega = $direccion_final;
                } else {
                    $icono_entrega = '🏪';
                    $titulo_entrega = "Recojo en tienda";
                    $texto_entrega = $direccion_tienda;
                }
                
                $cuerpo = "
                <!DOCTYPE html>
                <html lang='es'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <style>
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
                        .header h1 { margin: 0; font-size: 28px; }
                        .content { padding: 30px 20px; }
                        .order-code { background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; }
                        .order-code strong { color: #667eea; font-size: 20px; }
                        .info-grid { display: table; width: 100%; margin: 20px 0; }
                        .info-row { display: table-row; }
                        .info-label { display: table-cell; padding: 10px 0; font-weight: bold; color: #555; width: 40%; }
                        .info-value { display: table-cell; padding: 10px 0; color: #333; }
                        .total-box { background: #e8f5e9; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center; }
                        .total-box .amount { font-size: 32px; color: #2e7d32; font-weight: bold; }
                        .alert-box { background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; margin: 20px 0; }
                        .alert-box h3 { margin: 0 0 10px 0; color: #856404; font-size: 18px; }
                        .alert-box p { margin: 0; color: #856404; line-height: 1.5; }
                        .delivery-box { background: #e3f2fd; border-radius: 8px; padding: 20px; margin: 20px 0; }
                        .delivery-box h3 { margin: 0 0 10px 0; color: #1565c0; font-size: 16px; }
                        .delivery-box p { margin: 5px 0; color: #424242; }
                        .comprobante-section { text-align: center; margin: 20px 0; }
                        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
                        .divider { height: 1px; background: #e0e0e0; margin: 20px 0; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🎉 ¡Pago Recibido!</h1>
                            <p style='margin: 10px 0 0 0; font-size: 16px;'>Gracias por tu compra en Tercer Cielo</p>
                        </div>
                        
                        <div class='content'>
                            <div class='order-code'>
                                <strong>Pedido: $codigo</strong>
                            </div>
                            
                            <div class='total-box'>
                                <p style='margin: 0 0 10px 0; font-size: 14px; color: #666;'>TOTAL PAGADO</p>
                                <div class='amount'>S/ " . number_format($total_final, 2) . "</div>
                            </div>
                            
                            <div class='info-grid'>
                                <div class='info-row'>
                                    <div class='info-label'>💰 Subtotal:</div>
                                    <div class='info-value'>S/ " . number_format($total_productos, 2) . "</div>
                                </div>
                                " . ($envio_seleccionado['precio'] > 0 ? "
                                <div class='info-row'>
                                    <div class='info-label'>🚚 Envío:</div>
                                    <div class='info-value'>S/ " . number_format($envio_seleccionado['precio'], 2) . "</div>
                                </div>" : "") . "
                                <div class='info-row'>
                                    <div class='info-label'>💳 Método de pago:</div>
                                    <div class='info-value'>" . strtoupper($metodo_pago) . "</div>
                                </div>
                            </div>
                            
                            <div class='divider'></div>
                            
                            <div class='delivery-box'>
                                <h3>$icono_entrega $titulo_entrega</h3>
                                <p><strong>Dirección:</strong> $texto_entrega</p>
                            </div>
                            
                            <div class='alert-box'>
                                <h3>⏳ Verificación en proceso</h3>
                                <p>Tu comprobante está siendo verificado por nuestro equipo. Te notificaremos por correo cuando tu pedido sea aprobado y procedamos con el envío.</p>
                            </div>
                            
                            <div class='comprobante-section'>
                                <h3 style='color: #667eea;'>📸 Tu comprobante de pago</h3>
                                {{COMPROBANTE_IMG}}
                            </div>
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

                enviar_correo($usuario['email'], $usuario['nombre'], $asunto, $cuerpo, $comprobante_path);

                header("Location: exito.php?codigo=$codigo&estado=pendiente");
                exit;
            } else {
                // === FALLÓ EL GUARDADO ===
                $mensaje = '<div class="alert alert-danger">Error al guardar el comprobante. Intenta de nuevo.</div>';
                error_log("ERROR: move_uploaded_file falló. Origen: {$file['tmp_name']} → Destino: $destino");
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?></title>
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="57x57" href="../assets/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="../assets/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="../assets/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="../assets/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="../assets/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="../assets/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="../assets/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="../assets/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="../assets/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="../assets/favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .checkout-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 20px rgba(0, 123, 255, 0.3);
        }

        .checkout-header a {
            color: white;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .checkout-header a:hover {
            transform: scale(1.05);
        }

        .checkout-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            font-weight: 600;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 123, 255, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 1.25rem 1.5rem;
            border: none;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .qr-img {
            width: 200px;
            border: 3px solid #007bff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.2);
            transition: all 0.3s ease;
        }

        .qr-img:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
        }

        .qr-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 3px 15px rgba(0, 123, 255, 0.1);
            transition: all 0.3s ease;
        }

        .qr-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.15);
        }

        .metodo-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid #e9ecef !important;
            border-radius: 12px;
            padding: 1.5rem !important;
        }

        .metodo-card:hover {
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.15);
            transform: translateY(-3px);
        }

        .metodo-card.selected {
            border: 3px solid #007bff !important;
            background: linear-gradient(135deg, rgba(0, 123, 255, 0.05) 0%, rgba(0, 86, 179, 0.05) 100%);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.2);
        }

        .agencia-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border-radius: 12px;
        }

        .agencia-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.15);
        }

        .agencia-card.selected {
            border: 3px solid #007bff !important;
            background: linear-gradient(135deg, rgba(0, 123, 255, 0.05) 0%, rgba(0, 86, 179, 0.05) 100%);
        }

        .comprobante-preview {
            max-width: 250px;
            margin-top: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
        }

        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
        }

        .btn-success {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
            background: linear-gradient(135deg, #0056b3 0%, #004494 100%);
        }

        .btn-outline-secondary {
            border: 2px solid #6c757d;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 12px;
            border: none;
        }

        .alert-info {
            background: linear-gradient(135deg, rgba(0, 123, 255, 0.1) 0%, rgba(0, 86, 179, 0.1) 100%);
            color: #004085;
            border-left: 4px solid #007bff;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(200, 35, 51, 0.1) 100%);
            border-left: 4px solid #dc3545;
        }

        .product-item {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }

        .product-item:hover {
            background: #e9ecef;
        }

        #loading-overlay {
            background: linear-gradient(135deg, rgba(0, 123, 255, 0.95) 0%, rgba(0, 86, 179, 0.95) 100%);
        }

        .section-divider {
            border-top: 2px solid #e9ecef;
            margin: 2rem 0;
            position: relative;
        }

        .section-divider::after {
            content: '';
            position: absolute;
            top: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }

        .payment-number {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 700;
            display: inline-block;
            margin-top: 0.5rem;
        }

        .upload-label {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            border: 2px dashed #007bff;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-label:hover {
            background: rgba(0, 123, 255, 0.05);
            border-color: #0056b3;
        }

        @media (max-width: 768px) {
            .qr-img {
                width: 150px;
            }
        }
    </style>
</head>

<body class="bg-light">

    <div class="checkout-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="d-flex align-items-center gap-2">
                <img src="../assets/img/logo.png" alt="Logo" height="40" style="filter: brightness(0) invert(1);">
                Tercer Cielo
            </a>
            <div class="checkout-badge">
                <i class="bi bi-shield-check"></i> Pago Seguro
            </div>
        </div>
    </div>

    <div style="height: 20px;"></div>

    <div class="container my-5">
        <div class="row">
            <!-- FORMULARIO -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5><i class="bi bi-truck"></i> Método de Entrega</h5>
                    </div>
                    <div class="card-body">
                        <?php echo $mensaje; ?>
                        <form method="POST" enctype="multipart/form-data" id="checkoutForm">
                            <!-- TIPO DE ENTREGA -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_entrega" value="domicilio" id="tipo_domicilio" checked>
                                        <label class="form-check-label" for="tipo_domicilio"><strong>Envío a domicilio</strong></label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_entrega" value="agencia" id="tipo_agencia">
                                        <label class="form-check-label" for="tipo_agencia"><strong>Recojo en agencia</strong></label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_entrega" value="tienda" id="tipo_tienda">
                                        <label class="form-check-label" for="tipo_tienda"><strong>Recojo en tienda</strong></label>
                                    </div>
                                </div>
                            </div>

                            <!-- SELECCIÓN DE AGENCIA (solo visible para domicilio y agencia) -->
                            <div id="selector-agencia" class="mb-4">
                                <label class="form-label"><strong>Selecciona la agencia de envío:</strong></label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card agencia-card p-3 text-center" data-agencia="olva" style="cursor: pointer; border: 2px solid #dee2e6;">
                                            <h5 class="mb-0">🚚 Olva Courier</h5>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card agencia-card p-3 text-center" data-agencia="shalom" style="cursor: pointer; border: 2px solid #dee2e6;">
                                            <h5 class="mb-0">📦 Shalom</h5>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="agencia_envio" id="agencia_input" value="olva">
                            </div>
                            <input type="hidden" name="metodo_envio" id="tipo_envio_input" value="domicilio">

                            <!-- DATOS DE ENVÍO (domicilio y agencia) -->
                            <div id="datos-envio">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label>Departamento <span class="text-danger">*</span></label>
                                        <select class="form-select" id="departamento" required>
                                            <option value="">Seleccionar</option>
                                            <?php foreach ($departamentos as $d): ?>
                                                <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label>Provincia <span class="text-danger">*</span></label>
                                        <select class="form-select" id="provincia" required disabled>
                                            <option value="">Primero elige departamento</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label>Distrito <span class="text-danger">*</span></label>
                                        <select class="form-select" id="distrito" name="distrito_id" required disabled>
                                            <option value="">Primero elige provincia</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Solo para envío a domicilio -->
                                <div class="mb-3" id="campo-direccion">
                                    <label>Dirección exacta <span class="text-danger">*</span></label>
                                    <textarea name="direccion" id="direccion-input" class="form-control" rows="2"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label>Teléfono <span class="text-danger">*</span></label>
                                    <input type="tel" name="telefono" id="telefono-envio" class="form-control" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" required>
                                </div>
                                <div id="costo-envio" class="alert alert-info" style="display:none;">
                                    <strong>Envío: S/ <span id="precio-envio">0.00</span></strong>
                                </div>
                            </div>

                            <!-- RECOJO EN TIENDA -->
                            <div id="datos-recojo" style="display:none;" class="alert alert-info">
                                <strong>Recoger en:</strong><br>
                                <?= nl2br(htmlspecialchars($direccion_tienda)) ?><br>
                                <small>Tel: <?= $telefono_tienda ?></small>
                                <div class="mt-2">
                                    <label>Teléfono de contacto <span class="text-danger">*</span></label>
                                    <input type="tel" name="telefono" id="telefono-recojo" class="form-control" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="section-divider"></div>
                            <h6 class="mb-3" style="color: #007bff; font-weight: 700;"><i class="bi bi-credit-card"></i> Método de Pago</h6>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <div class="card metodo-card text-center" data-metodo="yape">
                                        <img src="<?= $logo_yape ?>" alt="Yape" style="width:80px; margin: 0 auto;">
                                        <p class="mt-3 mb-0"><strong>Yape</strong></p>
                                        <small class="text-muted">Pago instantáneo</small>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card metodo-card text-center" data-metodo="plin">
                                        <img src="<?= $logo_plin ?>" alt="Plin" style="width:80px; margin: 0 auto;">
                                        <p class="mt-3 mb-0"><strong>Plin</strong></p>
                                        <small class="text-muted">Transferencia rápida</small>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="metodo_pago" id="metodo_pago_input" value="yape">

                            <!-- QR FIJOS -->
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <div class="qr-section text-center">
                                        <h6 class="text-primary mb-3"><i class="bi bi-qr-code"></i> Escanea con Yape</h6>
                                        <img src="<?= $qr_yape ?>" alt="QR Yape" class="qr-img">
                                        <div class="payment-number mt-3">
                                            <i class="bi bi-phone"></i> +51 966 535 611
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="qr-section text-center">
                                        <h6 class="text-success mb-3"><i class="bi bi-qr-code"></i> Escanea con Plin</h6>
                                        <img src="<?= $qr_plin ?>" alt="QR Plin" class="qr-img">
                                        <div class="payment-number mt-3" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                            <i class="bi bi-phone"></i> +51 966 535 611
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- COMPROBANTE -->
                            <div class="mb-4">
                                <label class="form-label" style="font-weight: 700; color: #007bff;">
                                    <i class="bi bi-camera"></i> Captura del pago <span class="text-danger">*</span>
                                </label>
                                <div class="upload-label" onclick="document.getElementById('comprobante').click();">
                                    <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #007bff;"></i>
                                    <p class="mb-0 mt-2"><strong>Haz clic para subir la captura</strong></p>
                                    <small class="text-muted">Formatos: JPG, PNG, WEBP (Máx. 5MB)</small>
                                </div>
                                <input type="file" name="comprobante" id="comprobante" class="form-control d-none" accept="image/*" required>
                                <div class="text-center">
                                    <img id="preview" class="comprobante-preview" style="display:none;">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100">
                                Confirmar y Enviar Comprobante
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Volver</a>
                        </form>
                    </div>
                </div>
            </div>

            <!-- RESUMEN -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5><i class="bi bi-receipt"></i> Resumen del Pedido</h5>
                    </div>
                    <div class="card-body">
                        <!-- Lista de productos -->
                        <div class="mb-3">
                            <h6 class="text-muted mb-3"><i class="bi bi-bag"></i> Productos:</h6>
                            <?php foreach($carrito as $item): 
                                // Extraer solo la primera parte del nombre antes de " – " o " - "
                                $nombre_corto = preg_split('/\s+[–-]\s+/', $item['nombre'])[0];
                                // Limitar a 25 caracteres aproximadamente
                                if (strlen($nombre_corto) > 25) {
                                    $nombre_corto = substr($nombre_corto, 0, 25);
                                }
                            ?>
                                <div class="product-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-truncate" style="max-width: 65%;" title="<?= htmlspecialchars($item['nombre']) ?>">
                                            <i class="bi bi-box text-primary"></i> <?= htmlspecialchars($nombre_corto) ?>
                                        </small>
                                        <span class="badge" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">x<?= $item['cantidad'] ?></span>
                                        <small class="fw-bold text-primary">S/ <?= number_format($item['precio'] * $item['cantidad'], 2) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="bi bi-tag"></i> Subtotal:</span>
                            <span class="fw-bold">S/ <?= number_format($total_productos, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2" id="envio-resumen" style="display:none;">
                            <span><i class="bi bi-truck"></i> Envío:</span>
                            <span class="fw-bold text-primary">S/ <span id="envio-total">0.00</span></span>
                        </div>
                        <div class="mb-2" id="entrega-resumen-container" style="display:none;">
                            <small class="text-muted d-block"><strong><i class="bi bi-pin-map"></i> Entrega:</strong></small>
                            <small id="entrega-resumen" class="text-primary fw-bold"></small>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, rgba(0, 123, 255, 0.1) 0%, rgba(0, 86, 179, 0.1) 100%); padding: 1rem; border-radius: 10px;">
                            <strong style="font-size: 1.1rem;"><i class="bi bi-cash-stack"></i> Total:</strong>
                            <strong style="font-size: 1.5rem; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">S/ <span id="total-final"><?= number_format($total_productos, 2) ?></span></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">© <?= date('Y') ?> Tercer Cielo</p>
        </div>
    </footer>

    <!-- Overlay de carga -->
    <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center;">
        <div class="text-center">
            <div class="spinner-border text-light" style="width: 4rem; height: 4rem;" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-white mt-3 fs-5" id="loading-message">Procesando pago y enviando comprobante...</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tipoEntregaRadios = document.querySelectorAll('input[name="tipo_entrega"]');
            const selectorAgencia = document.getElementById('selector-agencia');
            const agenciaCards = document.querySelectorAll('.agencia-card');
            const agenciaInput = document.getElementById('agencia_input');
            const tipoEnvioInput = document.getElementById('tipo_envio_input');
            const datosEnvio = document.getElementById('datos-envio');
            const datosRecojo = document.getElementById('datos-recojo');
            const campoDireccion = document.getElementById('campo-direccion');
            const direccionInput = document.getElementById('direccion-input');
            const telefonoEnvio = document.getElementById('telefono-envio');
            const telefonoRecojo = document.getElementById('telefono-recojo');
            const comprobanteInput = document.getElementById('comprobante');
            const preview = document.getElementById('preview');
            const entregaResumen = document.getElementById('entrega-resumen');
            const entregaContainer = document.getElementById('entrega-resumen-container');
            const checkoutForm = document.getElementById('checkoutForm');
            const loadingOverlay = document.getElementById('loading-overlay');

            let precioEnvio = 0;
            let tipoActual = 'domicilio';
            let agenciaActual = 'olva';
            const totalProductos = <?= $total_productos ?>;

            function previewImage(input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(input.files[0]);
                } else {
                    preview.style.display = 'none';
                }
            }

            comprobanteInput.addEventListener('change', function() {
                previewImage(this);
            });

            // Manejar selección de agencia
            agenciaCards.forEach(card => {
                card.addEventListener('click', function() {
                    agenciaCards.forEach(c => c.style.border = '2px solid #dee2e6');
                    this.style.border = '3px solid #0d6efd';
                    agenciaActual = this.dataset.agencia;
                    agenciaInput.value = agenciaActual;
                    
                    // Recargar distritos con nueva agencia
                    if (tipoActual !== 'tienda') {
                        cargarDistritos();
                    }
                });
            });

            // Manejar cambio de tipo de entrega
            tipoEntregaRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    tipoActual = this.value;
                    tipoEnvioInput.value = tipoActual;
                    
                    if (tipoActual === 'tienda') {
                        // Recojo en tienda
                        selectorAgencia.style.display = 'none';
                        datosEnvio.style.display = 'none';
                        datosRecojo.style.display = 'block';
                        actualizarRequired(false);
                        precioEnvio = 0;
                        actualizarTotal();
                        entregaContainer.style.display = 'none';
                    } else {
                        // Domicilio o Agencia
                        selectorAgencia.style.display = 'block';
                        datosEnvio.style.display = 'block';
                        datosRecojo.style.display = 'none';
                        
                        // Mostrar/ocultar campo dirección
                        if (tipoActual === 'agencia') {
                            campoDireccion.style.display = 'none';
                            direccionInput.required = false;
                        } else {
                            campoDireccion.style.display = 'block';
                            direccionInput.required = true;
                        }
                        
                        actualizarRequired(true);
                        cargarDistritos();
                    }
                });
            });

            function actualizarRequired(esEnvio) {
                const selectsEnvio = [
                    document.getElementById('departamento'),
                    document.getElementById('provincia'),
                    document.getElementById('distrito')
                ];

                selectsEnvio.forEach(select => {
                    if (select) {
                        select.required = esEnvio;
                        if (esEnvio) {
                            if (select.id === 'departamento') {
                                select.disabled = false;
                            } else {
                                select.disabled = true;
                                select.selectedIndex = 0;
                            }
                        } else {
                            select.removeAttribute('required');
                            select.selectedIndex = 0;
                            select.disabled = true;
                        }
                    }
                });

                telefonoEnvio.required = esEnvio;
                telefonoRecojo.required = !esEnvio;

                if (!esEnvio) {
                    direccionInput.value = '';
                    document.getElementById('costo-envio').style.display = 'none';
                    precioEnvio = 0;
                    entregaContainer.style.display = 'none';
                    actualizarTotal();
                }
            }

            function actualizarTotal() {
                const total = totalProductos + precioEnvio;
                document.getElementById('envio-resumen').style.display = precioEnvio > 0 ? 'flex' : 'none';
                document.getElementById('envio-total').textContent = precioEnvio.toFixed(2);
                document.getElementById('total-final').textContent = total.toFixed(2);
            }

            function actualizarEntregaResumen() {
                const dept = document.getElementById('departamento').value;
                const prov = document.getElementById('provincia').value;
                const distOption = document.getElementById('distrito').selectedOptions[0];
                const dist = distOption ? distOption.textContent.split(' (S/')[0] : '';
                const agenciaNombre = agenciaActual === 'olva' ? 'Olva Courier' : 'Shalom';

                if (dept && prov && dist) {
                    let texto = '';
                    if (tipoActual === 'domicilio') {
                        const direccion = direccionInput.value.trim();
                        texto = `Envío a domicilio (${agenciaNombre}): ${direccion}, ${dept}, ${prov}, ${dist}`;
                    } else if (tipoActual === 'agencia') {
                        texto = `Recojo en agencia ${agenciaNombre}: ${dept}, ${prov}, ${dist}`;
                    }
                    
                    entregaResumen.textContent = texto;
                    entregaContainer.style.display = 'block';
                } else {
                    entregaContainer.style.display = 'none';
                }
            }

            function cargarDistritos() {
                const dept = document.getElementById('departamento').value;
                const prov = document.getElementById('provincia').value;
                const distSelect = document.getElementById('distrito');
                
                if (!dept || !prov) return;
                
                distSelect.innerHTML = '<option value="">Cargando...</option>';
                distSelect.disabled = true;
                
                fetch(`get_distritos.php?departamento=${encodeURIComponent(dept)}&provincia=${encodeURIComponent(prov)}&tipo=${tipoActual}&agencia=${agenciaActual}`)
                    .then(r => r.json())
                    .then(data => {
                        distSelect.innerHTML = '<option value="">Seleccionar</option>';
                        if (data.length === 0) {
                            distSelect.innerHTML = '<option value="">No disponible para esta modalidad</option>';
                            distSelect.disabled = true;
                        } else {
                            data.forEach(d => {
                                distSelect.innerHTML += `<option value="${d.id}" data-precio="${d.precio}">${d.distrito} (S/ ${d.precio})</option>`;
                            });
                            distSelect.disabled = false;
                        }
                    });
            }

            document.getElementById('departamento').addEventListener('change', function() {
                const dept = this.value;
                const provSelect = document.getElementById('provincia');
                const distSelect = document.getElementById('distrito');
                
                provSelect.innerHTML = '<option value="">Seleccionar provincia</option>';
                provSelect.disabled = true;
                distSelect.innerHTML = '<option value="">Seleccionar distrito</option>';
                distSelect.disabled = true;
                
                if (!dept) return;
                
                provSelect.disabled = false;
                provSelect.innerHTML = '<option value="">Cargando...</option>';
                
                fetch(`get_provincias.php?departamento=${encodeURIComponent(dept)}`)
                    .then(r => r.json())
                    .then(data => {
                        provSelect.innerHTML = '<option value="">Seleccionar</option>';
                        data.forEach(p => {
                            provSelect.innerHTML += `<option value="${p}">${p}</option>`;
                        });
                        provSelect.disabled = false;
                    });
            });

            document.getElementById('provincia').addEventListener('change', function() {
                cargarDistritos();
            });

            document.getElementById('distrito').addEventListener('change', function() {
                const option = this.options[this.selectedIndex];
                precioEnvio = option ? parseFloat(option.dataset.precio || 0) : 0;
                document.getElementById('precio-envio').textContent = precioEnvio.toFixed(2);
                document.getElementById('costo-envio').style.display = precioEnvio > 0 ? 'block' : 'none';
                actualizarTotal();
                actualizarEntregaResumen();
            });

            direccionInput.addEventListener('input', actualizarEntregaResumen);

            document.querySelectorAll('.metodo-card').forEach(card => {
                card.addEventListener('click', () => {
                    document.querySelectorAll('.metodo-card').forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    document.getElementById('metodo_pago_input').value = card.dataset.metodo;
                });
            });

            // Manejar envío del formulario con animación de carga
            checkoutForm.addEventListener('submit', function(e) {
                // Mostrar overlay de carga
                loadingOverlay.style.display = 'flex';
                
                // El formulario se enviará normalmente (no se previene default)
                // El overlay permanecerá visible durante la redirección
            });

            // Inicializar
            actualizarRequired(true);
            document.querySelector('.agencia-card[data-agencia="olva"]').click();
            document.querySelector('.metodo-card[data-metodo="yape"]').click();
        });
    </script>
</body>

</html>