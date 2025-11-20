<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Desde includes/ → sube a TERCER_CIELO/
require_once __DIR__ . '/../vendor/autoload.php';

function enviar_correo($destino, $nombre, $asunto, $cuerpo_html, $comprobante_path = null, $pdf_adjunto = null)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jedarchdj@gmail.com';
        $mail->Password   = 'tvihyxolbbfqhtiu';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPDebug  = 0; // Desactivar debug

        $mail->setFrom('jedarchdj@gmail.com', 'Tercer Cielo');
        $mail->addAddress($destino, $nombre);

        $mail->isHTML(true);
        $mail->Subject = $asunto;

        // === RUTA CORRECTA: desde includes/ a public/uploads/comprobantes/ ===
        $ruta_completa = '../public/' . $comprobante_path;

        if ($comprobante_path && file_exists($ruta_completa)) {
            $cid = 'comprobante_' . uniqid();
            $mail->addEmbeddedImage($ruta_completa, $cid, basename($comprobante_path));
            $cuerpo_html = str_replace(
                '{{COMPROBANTE_IMG}}',
                "<img src='cid:$cid' alt='Comprobante de pago' style='max-width:300px; border:1px solid #ddd; border-radius:8px; display:block; margin:15px 0;'>",
                $cuerpo_html
            );
        } else {
            $cuerpo_html = str_replace(
                '{{COMPROBANTE_IMG}}',
                '<p style="color:#d9534f; font-weight:bold;">Error: No se encontro el comprobante en el servidor.</p>',
                $cuerpo_html
            );
        }

        // === ADJUNTAR PDF SI EXISTE ===
        if ($pdf_adjunto) {
            $pdf_path = __DIR__ . '/../public/' . $pdf_adjunto;
            if (file_exists($pdf_path)) {
                $mail->addAttachment($pdf_path, basename($pdf_adjunto));
            }
        }

        $mail->Body    = $cuerpo_html;
        $mail->AltBody = strip_tags($cuerpo_html);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Solo log a archivo, no output
        return false;
    }
}
