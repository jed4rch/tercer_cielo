<?php
require_once '../includes/init.php';

// === REDIRECCIÓN SI ES ADMIN ===
if (isset($_SESSION['user_id']) && $_SESSION['rol'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

$titulo = 'Libro de Reclamaciones - Tercer Cielo';
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
</head>
<?php
$titulo = 'Libro de Reclamaciones - Tercer Cielo';
include 'cabecera_unificada.php';
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    .page-header-libro {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 4rem 0;
        text-align: center;
        border-radius: 0 0 30px 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        margin-bottom: 3rem;
    }

    .page-header-libro img {
        max-height: 100px;
        margin-bottom: 1.5rem;
        background: white;
        padding: 0.5rem;
        border-radius: 10px;
    }

    .page-header-libro h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .page-header-libro p {
        font-size: 1.1rem;
        max-width: 700px;
        margin: 0 auto;
        opacity: 0.95;
    }

    .form-card {
        background: white;
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .section-title {
        color: #dc3545;
        font-weight: 700;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 3px solid #dc3545;
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
        color: #dc3545;
    }

    .form-control, .form-select {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15);
    }

    .form-check-input:checked {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .form-check-label {
        font-size: 0.95rem;
        color: #495057;
    }

    .btn-submit {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 0.85rem 2.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        color: white;
    }

    .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .info-box {
        background: linear-gradient(135deg, #fff3cd 0%, #ffe8a1 100%);
        border-left: 4px solid #ffc107;
        padding: 1.5rem;
        border-radius: 10px;
        margin-top: 2rem;
    }

    .info-box p {
        margin: 0;
        color: #856404;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .info-box strong {
        color: #856404;
    }

    .tipo-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        border-left: 4px solid #17a2b8;
        padding: 1rem;
        border-radius: 10px;
        margin-top: 1rem;
        font-size: 0.9rem;
    }

    .tipo-info strong {
        color: #0c5460;
    }

    .tipo-info p {
        margin: 0.5rem 0 0 0;
        color: #0c5460;
    }

    @media (max-width: 768px) {
        .page-header-libro h1 {
            font-size: 1.8rem;
        }
        
        .page-header-libro p {
            font-size: 1rem;
        }
        
        .form-card {
            padding: 2rem 1.5rem;
        }
    }
</style>

    <!-- Header -->
    <div class="page-header-libro">
        <div class="container">
            <img src="../assets/img/libroReclamaciones.jpeg" alt="Libro de Reclamaciones">
            <h1><i class="bi bi-journal-text me-2"></i>Libro de Reclamaciones</h1>
            <p>Registra aquí tu reclamo o queja. Nos comprometemos a atenderlo en el menor tiempo posible conforme a la ley.</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="form-card">
                    <form id="reclamacionForm" action="procesar_reclamacion.php" method="POST">
                        <!-- Datos del Consumidor -->
                        <div class="section-title">
                            <i class="bi bi-person-fill"></i>
                            <span>1. Identificación del Consumidor Reclamante</span>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-person"></i>
                                    Nombre completo *
                                </label>
                                <input type="text" class="form-control" name="nombre" placeholder="Nombres y apellidos completos" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-card-text"></i>
                                    Documento de identidad *
                                </label>
                                <input type="text" class="form-control" name="documento" placeholder="DNI (8 dígitos)" 
                                       pattern="[0-9]{8}" maxlength="8" 
                                       title="Ingrese 8 dígitos numéricos" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-telephone"></i>
                                    Teléfono *
                                </label>
                                <input type="tel" class="form-control" name="telefono" placeholder="9 dígitos" 
                                       pattern="[0-9]{9}" maxlength="9" 
                                       title="Ingrese 9 dígitos numéricos" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-envelope"></i>
                                    Correo electrónico *
                                </label>
                                <input type="email" class="form-control" name="email" placeholder="tu@email.com" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    <i class="bi bi-geo-alt"></i>
                                    Dirección completa *
                                </label>
                                <input type="text" class="form-control" name="direccion" placeholder="Calle, número, distrito, provincia, departamento" required>
                            </div>
                        </div>

                        <!-- Detalle de la Reclamación -->
                        <div class="section-title">
                            <i class="bi bi-file-text-fill"></i>
                            <span>2. Identificación del Bien Contratado</span>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label">
                                    <i class="bi bi-tag"></i>
                                    Tipo *
                                </label>
                                <select class="form-select" name="tipo" required>
                                    <option value="">Seleccione el tipo...</option>
                                    <option value="Reclamo">Reclamo</option>
                                    <option value="Queja">Queja</option>
                                </select>
                                <div class="tipo-info">
                                    <strong><i class="bi bi-info-circle"></i> Diferencia:</strong>
                                    <p><strong>Reclamo:</strong> Disconformidad relacionada al producto o servicio.</p>
                                    <p><strong>Queja:</strong> Disconformidad no relacionada al producto o servicio, sino a la atención al público.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-receipt"></i>
                                    Número de pedido
                                </label>
                                <input type="text" class="form-control" name="pedido" placeholder="Ej: PED-2025-00123 (opcional)">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-currency-dollar"></i>
                                    Monto reclamado
                                </label>
                                <input type="text" class="form-control" name="monto" placeholder="S/ 0.00 (opcional)">
                            </div>
                        </div>

                        <!-- Detalle del Reclamo -->
                        <div class="section-title">
                            <i class="bi bi-chat-left-text-fill"></i>
                            <span>3. Detalle de la Reclamación y Pedido del Consumidor</span>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label">
                                    <i class="bi bi-file-earmark-text"></i>
                                    Descripción detallada *
                                </label>
                                <textarea class="form-control" name="descripcion" rows="4" placeholder="Describa con el mayor detalle posible el motivo de su reclamo o queja..." required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    <i class="bi bi-lightbulb"></i>
                                    Pedido del consumidor *
                                </label>
                                <textarea class="form-control" name="solucion" rows="3" placeholder="Indique qué solución espera recibir..." required></textarea>
                            </div>
                        </div>

                        <!-- Términos -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terminos" required>
                            <label class="form-check-label" for="terminos">
                                <strong>Declaro que la información proporcionada es verdadera</strong> y acepto que la respuesta sea enviada a mi correo electrónico en un plazo no mayor a 30 días calendario.
                            </label>
                        </div>

                        <button type="submit" class="btn-submit">
                            <span class="spinner-border spinner-border-sm d-none me-2"></span>
                            <i class="bi bi-send-fill"></i>
                            Enviar Reclamación
                        </button>
                    </form>
                </div>

                <!-- Información Adicional -->
                <div class="info-box">
                    <p>
                        <i class="bi bi-shield-check me-2"></i>
                        <strong>Nota Legal:</strong> De conformidad con el Código de Protección y Defensa del Consumidor (Ley N° 29571), 
                        el proveedor debe dar respuesta al reclamo en un plazo no mayor a <strong>treinta (30) días calendario</strong>, 
                        pudiendo ser extendido por otro igual cuando la naturaleza del reclamo lo justifique. 
                        La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('reclamacionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const button = form.querySelector('button[type="submit"]');
            const spinner = button.querySelector('.spinner-border');

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
                            <h3 style="color:#28a745;margin-bottom:15px;font-weight:700">¡Reclamación Registrada!</h3>
                            <p style="color:#666;font-size:16px;line-height:1.6;margin-bottom:25px">
                                Tu reclamación ha sido registrada exitosamente en nuestro <strong>Libro de Reclamaciones</strong>.<br><br>
                                Recibirás una respuesta en tu correo electrónico en un plazo máximo de <strong>30 días calendario</strong>.
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
                    throw new Error(data.mensaje || 'Error al enviar la reclamación');
                }
            } catch (error) {
                alert('✗ ' + (error.message || 'Error al enviar la reclamación. Por favor, intenta nuevamente.'));
            } finally {
                // Restaurar el botón
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-send-fill"></i> Enviar Reclamación';
            }
        });
    </script>
</body>
</html>