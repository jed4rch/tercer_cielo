<?php
require_once '../includes/init.php';

// === REDIRECCIÓN SI ES ADMIN ===
if (isset($_SESSION['user_id']) && $_SESSION['rol'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

$titulo = 'Contáctanos';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?> - Tercer Cielo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/favicon/favicon.png">
    <style>
        .contact-icon {
            width: 60px;
            height: 60px;
            background: #1a5d1a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
    </style>
</head>
<?php
$titulo = 'Contáctanos - Tercer Cielo';
include 'cabecera_unificada.php';
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    .page-header-contacto {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 4rem 0;
        text-align: center;
        border-radius: 0 0 30px 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        margin-bottom: 3rem;
    }

    .page-header-contacto h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .page-header-contacto p {
        font-size: 1.2rem;
        max-width: 700px;
        margin: 0 auto;
        opacity: 0.95;
    }

    .contact-card {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        height: 100%;
        text-align: center;
    }

    .contact-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .contact-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 2rem;
        margin: 0 auto 1.5rem;
        box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
    }

    .contact-card h5 {
        color: #007bff;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .contact-card p {
        color: #6c757d;
        margin: 0;
    }

    .contact-card a {
        color: #495057;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .contact-card a:hover {
        color: #007bff;
    }

    .form-card {
        background: white;
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .form-card h4 {
        color: #007bff;
        font-weight: 700;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-label i {
        color: #007bff;
    }

    .form-control, .form-select {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
    }

    .btn-enviar {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 0.85rem 2.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        width: 100%;
        justify-content: center;
    }

    .btn-enviar:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        color: white;
    }

    .btn-enviar:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .map-card {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .map-card h4 {
        color: #007bff;
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
    }

    .map-info {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 1.5rem;
        border-radius: 15px;
        margin-bottom: 1.5rem;
    }

    .map-info p {
        margin: 0 0 1rem 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: #495057;
        font-weight: 500;
    }

    .btn-maps {
        background: linear-gradient(135deg, #28a745 0%, #5cb85c 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }

    .btn-maps:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        color: white;
    }

    .map-container {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .info-adicional {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 3rem 0;
        border-radius: 20px;
        margin-top: 3rem;
    }

    .info-item {
        text-align: center;
        padding: 1.5rem;
    }

    .info-item i {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        opacity: 0.9;
    }

    .info-item h6 {
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .info-item p {
        margin: 0;
        opacity: 0.9;
    }

    @media (max-width: 768px) {
        .page-header-contacto h1 {
            font-size: 2rem;
        }
        
        .page-header-contacto p {
            font-size: 1rem;
        }
        
        .form-card {
            padding: 2rem 1.5rem;
        }
    }
</style>

    <!-- Header -->
    <div class="page-header-contacto">
        <div class="container">
            <h1><i class="bi bi-chat-dots-fill me-3"></i>Contáctanos</h1>
            <p>Estamos aquí para ayudarte. Comunícate con nosotros por cualquier medio y te responderemos a la brevedad.</p>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Información de Contacto -->
        <div class="row g-4 mb-5">
            <div class="col-lg-4 col-md-6">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <h5>Nuestra Ubicación</h5>
                    <p>Av. Guardia Civil mza. A lote. 1<br>
                    Urb. Villa Universitaria<br>
                    Castilla - Piura, Perú</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="bi bi-telephone-fill"></i>
                    </div>
                    <h5>Llámanos</h5>
                    <p><a href="tel:+51945913352">+51 945 913 352</a></p>
                    <p class="text-muted small mt-2">Lunes a Sábado<br>8:00 AM - 7:00 PM</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="bi bi-envelope-fill"></i>
                    </div>
                    <h5>Escríbenos</h5>
                    <p><a href="mailto:info@tercercielo.com">info@tercercielo.com</a></p>
                    <p class="text-muted small mt-2">Respuesta en 24 horas</p>
                </div>
            </div>
        </div>

        <!-- Formulario de Contacto -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8">
                <div class="form-card">
                    <h4><i class="bi bi-envelope-paper"></i>Envíanos un Mensaje</h4>
                    <form id="contactForm" action="procesar_contacto.php" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">
                                    <i class="bi bi-person"></i>
                                    Nombre completo *
                                </label>
                                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ingresa tu nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope"></i>
                                    Correo electrónico *
                                </label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="tu@email.com" required>
                            </div>
                            <div class="col-12">
                                <label for="asunto" class="form-label">
                                    <i class="bi bi-tag"></i>
                                    Asunto *
                                </label>
                                <input type="text" class="form-control" id="asunto" name="asunto" placeholder="¿En qué podemos ayudarte?" required>
                            </div>
                            <div class="col-12">
                                <label for="mensaje" class="form-label">
                                    <i class="bi bi-chat-text"></i>
                                    Mensaje *
                                </label>
                                <textarea class="form-control" id="mensaje" name="mensaje" rows="5" placeholder="Escribe tu mensaje aquí..." required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn-enviar">
                                    <span class="spinner-border spinner-border-sm d-none me-2"></span>
                                    <i class="bi bi-send-fill"></i>
                                    Enviar Mensaje
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mapa -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="map-card">
                    <h4><i class="bi bi-pin-map-fill"></i>Encuéntranos Aquí</h4>
                    <div class="map-info">
                        <p>
                            <i class="bi bi-geo-alt-fill text-primary"></i>
                            <strong>Urbanización Villa Universitaria, A - 1, Av. Guardia Civil, Castilla - Piura</strong>
                        </p>
                        <a href="https://maps.app.goo.gl/D6hzrGT2Ai9anBxP6" target="_blank" class="btn-maps">
                            <i class="bi bi-map"></i>Abrir en Google Maps
                        </a>
                    </div>
                    <div class="map-container">
                        <div class="ratio ratio-16x9">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1531.9930399238574!2d-80.58376609589602!3d-5.184047718267913!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x904a1100edcce541%3A0xa50243cf8676456a!2sFerreter%C3%ADa%20TERCER%20CIELO!5e0!3m2!1ses-419!2spe!4v1763340504636!5m2!1ses-419!2spe" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="info-adicional">
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <div class="info-item">
                            <i class="bi bi-clock-history"></i>
                            <h6>Horario de Atención</h6>
                            <p>Lunes a Sábado: 8:00 AM - 7:00 PM<br>Domingos: Cerrado</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item">
                            <i class="bi bi-headset"></i>
                            <h6>Soporte Rápido</h6>
                            <p>Respuesta inmediata por WhatsApp<br>+51 945 913 352</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item">
                            <i class="bi bi-shield-check"></i>
                            <h6>Garantía</h6>
                            <p>Todos nuestros productos<br>cuentan con garantía</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const button = form.querySelector('button[type="submit"]');
            const spinner = button.querySelector('.spinner-border');
            const originalText = button.textContent;

            // Deshabilitar el botón y mostrar spinner
            button.disabled = true;
            spinner.classList.remove('d-none');
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                });

                const data = await response.json();

                if (data.success) {
                    // Notificación personalizada
                    const modal = document.createElement('div');
                    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center';
                    modal.innerHTML = `
                        <div style="background:white;padding:40px;border-radius:20px;max-width:500px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:slideIn 0.3s ease">
                            <div style="width:80px;height:80px;background:linear-gradient(135deg,#28a745,#20c997);border-radius:50%;margin:0 auto 20px;display:flex;align-items:center;justify-content:center">
                                <i class="bi bi-check-circle" style="font-size:50px;color:white"></i>
                            </div>
                            <h3 style="color:#28a745;margin-bottom:15px;font-weight:700">¡Mensaje Enviado!</h3>
                            <p style="color:#666;font-size:16px;line-height:1.6;margin-bottom:25px">
                                Gracias por contactarnos. Hemos recibido tu mensaje correctamente.<br><br>
                                Nuestro equipo te responderá a la brevedad posible.
                            </p>
                            <button onclick="this.closest('div[style*=fixed]').remove()" 
                                    style="background:linear-gradient(135deg,#007bff,#0056b3);color:white;border:none;padding:12px 40px;border-radius:25px;font-weight:600;cursor:pointer;font-size:16px">
                                Entendido
                            </button>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    form.reset();
                } else {
                    throw new Error(data.mensaje || 'Error al enviar el mensaje');
                }
            } catch (error) {
                alert('✗ ' + (error.message || 'Error al enviar el mensaje. Por favor, intenta nuevamente.'));
            } finally {
                // Restaurar el botón
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-send-fill"></i> Enviar Mensaje';
            }
        });
    </script>
</body>
</html>