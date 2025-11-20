<?php
// includes/func_boleta.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/conexion.php';

function generar_boleta_pdf($pedido_id) {
    $logfile = __DIR__ . '/../logs/estado_error.log';
    @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | INICIO generar_boleta_pdf | pedido_id=$pedido_id\n", 3, $logfile);
    
    // Capturar cualquier salida no deseada
    ob_start();
    
    // Manejador de errores personalizado para capturar todo
    set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logfile) {
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | PHP Error [$errno]: $errstr en $errfile:$errline\n", 3, $logfile);
        return true; // No propagar el error
    });
    
    // Suprimir completamente todos los errores y warnings
    $old_error_reporting = error_reporting();
    error_reporting(0);
    ini_set('display_errors', '0');
    
    try {
        $pdo = getPdo();
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Conexión PDO OK\n", 3, $logfile);
        // Obtener información del pedido
        $stmt = $pdo->prepare("
            SELECT p.*, u.nombre, u.email, u.telefono 
            FROM pedidos p 
            INNER JOIN usuarios u ON p.usuario_id = u.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Pedido consultado\n", 3, $logfile);
        if (!$pedido) {
            @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Pedido no encontrado\n", 3, $logfile);
            ob_end_clean(); // Limpiar buffer antes de retornar
            return false;
        }
        // Obtener detalles del pedido
        $stmt = $pdo->prepare("SELECT * FROM pedido_detalles WHERE pedido_id = ?");
        $stmt->execute([$pedido_id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Detalles consultados\n", 3, $logfile);
        // Crear PDF
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Antes de crear TCPDF\n", 3, $logfile);
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | TCPDF creado\n", 3, $logfile);
        
        // Configuración del PDF
        $pdf->SetCreator('Ferretería Tercer Cielo');
        $pdf->SetAuthor('Ferretería Tercer Cielo');
        $pdf->SetTitle('Boleta de Venta - ' . $pedido['codigo']);
        $pdf->SetSubject('Boleta de Venta');
        
        // Eliminar encabezado y pie de página predeterminados
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Márgenes
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        
        // Agregar página
        $pdf->AddPage();
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Página agregada\n", 3, $logfile);
        
        // ========== ENCABEZADO CON LOGO Y DATOS DE LA EMPRESA ==========
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Iniciando encabezado\n", 3, $logfile);
        
        // Rectángulo superior azul
        try {
            $pdf->SetFillColor(41, 128, 185); // Azul corporativo
            $pdf->Rect(0, 0, 210, 40, 'F');
            @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Rectángulo dibujado\n", 3, $logfile);
        } catch (Exception $e) {
            @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Error en rectángulo: " . $e->getMessage() . "\n", 3, $logfile);
        }
        
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Sin logo\n", 3, $logfile);
        
        // Nombre de la empresa
        try {
            $pdf->SetTextColor(255, 255, 255); // Blanco
            $pdf->SetFont('helvetica', 'B', 20);
            $pdf->SetXY(15, 12);
            $pdf->Cell(180, 8, 'FERRETERÍA TERCER CIELO', 0, 1, 'C');
            @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Título empresa agregado\n", 3, $logfile);
        } catch (Exception $e) {
            @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Error en título: " . $e->getMessage() . "\n", 3, $logfile);
        }
        
        // Datos de contacto
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetX(15);
        $pdf->Cell(180, 5, 'Av. Guardia Civil mza. A lote. 1 urb. Villa Universitaria, Castilla - Piura', 0, 1, 'C');
        $pdf->SetX(15);
        $pdf->Cell(180, 5, 'Tel: +51 945 913 352 | Email: info@tercercielo.com', 0, 1, 'C');
        
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Datos de contacto agregados\n", 3, $logfile);
        
        // Resetear color de texto
        $pdf->SetTextColor(0, 0, 0);
        
        // Espacio después del encabezado
        $pdf->SetY(43);
        
        // ========== TÍTULO DE BOLETA CON FONDO ==========
        $pdf->SetFillColor(46, 204, 113); // Verde
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 12, 'BOLETA DE VENTA', 0, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->Ln(3);
        
        // ========== INFORMACIÓN DEL PEDIDO EN RECUADROS ==========
        // Recuadro con código y fecha
        $pdf->SetFillColor(236, 240, 241); // Gris claro
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetX(15);
        $pdf->Cell(85, 8, 'Código del Pedido', 1, 0, 'L', true);
        $pdf->Cell(95, 8, 'Fecha de Emisión', 1, 1, 'L', true);
        
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetX(15);
        $pdf->Cell(85, 8, '  ' . $pedido['codigo'], 1, 0, 'L');
        $pdf->Cell(95, 8, '  ' . date('d/m/Y', strtotime($pedido['creado_en'])), 1, 1, 'L');
        
        $pdf->Ln(5);
        
        // ========== DATOS DEL CLIENTE ==========
        $pdf->SetFillColor(52, 152, 219); // Azul
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, '  DATOS DEL CLIENTE', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        
        // Recuadro de datos del cliente
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(40, 7, 'Cliente:', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, $pedido['nombre'], 0, 1, 'L');
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(40, 7, 'Email:', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, $pedido['email'], 0, 1, 'L');
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(40, 7, 'Teléfono:', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, $pedido['telefono'], 0, 1, 'L');
        
        $pdf->Ln(5);
        
        // ========== TABLA DE PRODUCTOS ==========
        $pdf->SetFillColor(52, 152, 219); // Azul
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, '  DETALLE DE PRODUCTOS', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->Ln(2);
        
        // Encabezado de tabla con colores
        $pdf->SetFillColor(41, 128, 185); // Azul
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(80, 8, 'Producto', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Cantidad', 1, 0, 'C', true);
        $pdf->Cell(37, 8, 'Precio Unit.', 1, 0, 'C', true);
        $pdf->Cell(38, 8, 'Subtotal', 1, 1, 'C', true);
        
        // Contenido de la tabla
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);
        $fill = false;
        $total = 0;
        
        foreach ($detalles as $d) {
            $stmtProd = $pdo->prepare("SELECT nombre, precio FROM productos WHERE id = ?");
            $stmtProd->execute([$d['producto_id']]);
            $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);
            $nombre = $prod ? $prod['nombre'] : 'Producto';
            $precio = $prod ? $prod['precio'] : 0;
            $cantidad = $d['cantidad'];
            $subtotal = $precio * $cantidad;
            $total += $subtotal;
            
            // Alternar colores de fila
            if ($fill) {
                $pdf->SetFillColor(245, 245, 245); // Gris muy claro
            } else {
                $pdf->SetFillColor(255, 255, 255); // Blanco
            }
            
            $pdf->Cell(80, 7, ' ' . $nombre, 1, 0, 'L', true);
            $pdf->Cell(25, 7, $cantidad, 1, 0, 'C', true);
            $pdf->Cell(37, 7, 'S/ ' . number_format($precio, 2), 1, 0, 'C', true);
            $pdf->Cell(38, 7, 'S/ ' . number_format($subtotal, 2), 1, 1, 'C', true);
            
            $fill = !$fill;
        }
        
        // Fila de total con color destacado
        $pdf->SetFillColor(46, 204, 113); // Verde
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(142, 9, 'TOTAL A PAGAR', 1, 0, 'R', true);
        $pdf->Cell(38, 9, 'S/ ' . number_format($total, 2), 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->Ln(8);
        
        // ========== INFORMACIÓN DE PAGO, ESTADO Y ENVÍO ==========
        $pdf->SetFillColor(241, 196, 15); // Amarillo/Dorado
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        
        // Método de pago
        $pdf->SetX(15);
        $pdf->Cell(85, 8, '  Método de Pago', 1, 0, 'L', true);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(95, 8, '  ' . strtoupper($pedido['metodo_pago']), 1, 1, 'L');
        
        // Estado
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(85, 8, '  Estado del Pedido', 1, 0, 'L', true);
        
        // Color según el estado
        $estado = strtoupper($pedido['estado']);
        if ($estado === 'APROBADO' || $estado === 'ENTREGADO') {
            $pdf->SetFillColor(46, 204, 113); // Verde
            $pdf->SetTextColor(255, 255, 255);
        } elseif ($estado === 'PENDIENTE') {
            $pdf->SetFillColor(241, 196, 15); // Amarillo
            $pdf->SetTextColor(0, 0, 0);
        } elseif ($estado === 'ENVIADO') {
            $pdf->SetFillColor(52, 152, 219); // Azul
            $pdf->SetTextColor(255, 255, 255);
        } else {
            $pdf->SetFillColor(231, 76, 60); // Rojo
            $pdf->SetTextColor(255, 255, 255);
        }
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(95, 8, '  ' . $estado, 1, 1, 'L', true);
        
        // Resetear colores
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(241, 196, 15);
        
        // Modalidad de envío
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(85, 8, '  Modalidad de Envío', 1, 0, 'L', true);
        $pdf->SetFont('helvetica', '', 10);
        
        $modalidad = '';
        if ($pedido['tipo_envio'] === 'domicilio') {
            $modalidad = 'Envío a Domicilio';
        } elseif ($pedido['tipo_envio'] === 'agencia') {
            $modalidad = 'Recojo en Agencia';
        } else {
            $modalidad = 'Recojo en Tienda';
        }
        
        $pdf->Cell(95, 8, '  ' . $modalidad, 1, 1, 'L');
        
        // Agencia (si aplica)
        if (!empty($pedido['agencia_envio']) && $pedido['tipo_envio'] !== 'tienda') {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetX(15);
            $pdf->Cell(85, 8, '  Agencia', 1, 0, 'L', true);
            $pdf->SetFont('helvetica', '', 10);
            
            $agencia_nombre = $pedido['agencia_envio'] === 'olva' ? 'Olva Courier' : 'Shalom';
            $pdf->Cell(95, 8, '  ' . $agencia_nombre, 1, 1, 'L');
        }
        $pdf->Ln(10);
        
        // ========== PIE DE PÁGINA CON DISEÑO ==========
        // Línea decorativa
        $pdf->SetDrawColor(41, 128, 185);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        
        $pdf->Ln(5);
        
        // Mensaje de agradecimiento con fondo
        $pdf->SetFillColor(236, 240, 241);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, '¡Gracias por su compra!', 0, 1, 'C', true);
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'Visite nuestra página web: www.tercercielo.com', 0, 1, 'C');
        
        $pdf->Ln(5);
        
        // Información de contacto en el pie
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 4, 'Para consultas o reclamos, comuníquese con nosotros:', 0, 1, 'C');
        $pdf->Cell(0, 4, 'Tel: +51 945 913 352 | Email: info@tercercielo.com', 0, 1, 'C');
        
        // Guardar PDF
        $filename = 'boleta_' . $pedido['codigo'] . '.pdf';
        $filepath = __DIR__ . '/../public/uploads/boletas/' . $filename;
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | No se pudo crear directorio $dir\n", 3, $logfile);
                ob_end_clean(); // Limpiar buffer antes de retornar
                return false;
            }
        }
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Antes de guardar PDF\n", 3, $logfile);
        $pdf->Output($filepath, 'F');
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | PDF guardado en $filepath\n", 3, $logfile);
        
        // Restaurar manejador de errores
        restore_error_handler();
        
        // Limpiar buffer de salida
        ob_end_clean();
        
        // Restaurar configuración de errores
        error_reporting($old_error_reporting);
        ini_set('display_errors', '1');
        
        return 'uploads/boletas/' . $filename;
    } catch (Exception $e) {
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | ERROR Exception: " . $e->getMessage() . "\n", 3, $logfile);
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Trace: " . $e->getTraceAsString() . "\n", 3, $logfile);
        
        // Restaurar manejador de errores
        restore_error_handler();
        
        // Limpiar buffer en caso de error
        ob_end_clean();
        
        // Restaurar configuración de errores
        error_reporting($old_error_reporting);
        ini_set('display_errors', '1');
        
        return false;
    } catch (Error $e) {
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | ERROR Fatal: " . $e->getMessage() . "\n", 3, $logfile);
        @error_log(date('Y-m-d H:i:s') . " | func_boleta.php | Trace: " . $e->getTraceAsString() . "\n", 3, $logfile);
        
        // Restaurar manejador de errores
        restore_error_handler();
        
        // Limpiar buffer en caso de error
        ob_end_clean();
        
        // Restaurar configuración de errores
        error_reporting($old_error_reporting);
        ini_set('display_errors', '1');
        
        return false;
    }
}
