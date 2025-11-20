<?php
require_once '../../includes/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /tercer_cielo/public/login.php');
    exit;
}

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /tercer_cielo/public/index.php');
    exit;
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');

    if (empty($nombre) || empty($email) || empty($password) || empty($telefono)) {
        $error = 'Todos los campos son obligatorios';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
        $error = 'El correo electrónico debe ser un correo de Gmail (@gmail.com)';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $error = 'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un caracter especial';
    } else {
        try {
            $pdo = getPdo();
            
            // Verificar si el correo ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'El correo electrónico ya está registrado';
            } else {
                // Insertar nuevo administrador
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, telefono, rol) VALUES (?, ?, ?, ?, 'admin')");
                $stmt->execute([
                    $nombre,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $telefono
                ]);
                
                $mensaje = 'Administrador creado exitosamente';
                
                // Redirigir después de 2 segundos
                header("refresh:2;url=usuarios.php");
            }
        } catch (PDOException $e) {
            $error = 'Error al crear el administrador';
        }
    }
}

$pageTitle = 'Agregar Administrador';
include 'layout_header.php';
?>

<div class="content-card">
    <h3 class="mb-4"><i class="bi bi-person-plus-fill me-2"></i>Agregar Nuevo Administrador</h3>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
                        <?php if ($mensaje): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($mensaje) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                                <div class="invalid-feedback">
                                    Por favor ingrese un nombre
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                    pattern="[a-z0-9._%+-]+@gmail\.com$" 
                                    title="Debe ser una dirección de correo de Gmail (@gmail.com)">
                                <div class="invalid-feedback">
                                    El correo electrónico debe ser una cuenta de Gmail (@gmail.com)
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           required 
                                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                                           title="La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un caracter especial">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye-fill" id="eyeIcon"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un caracter especial
                                </div>
                                <div class="form-text mt-2">
                                    <small class="text-muted">
                                        La contraseña debe cumplir con:
                                        <ul class="mb-0">
                                            <li id="minLength" class="text-danger">Mínimo 8 caracteres</li>
                                            <li id="hasUppercase" class="text-danger">Al menos una mayúscula</li>
                                            <li id="hasLowercase" class="text-danger">Al menos una minúscula</li>
                                            <li id="hasNumber" class="text-danger">Al menos un número</li>
                                            <li id="hasSpecial" class="text-danger">Al menos un caracter especial (@$!%*?&)</li>
                                        </ul>
                                    </small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" required pattern="[0-9]{9}" title="Debe ingresar 9 dígitos">
                                <div class="invalid-feedback">
                                    Por favor ingrese un número de teléfono válido (9 dígitos)
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Crear Administrador
                                </button>
                                <a href="usuarios.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Volver
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Validación del formulario
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()

    // Función para mostrar/ocultar contraseña
    document.getElementById('togglePassword').addEventListener('click', function() {
        const password = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        
        if (password.type === 'password') {
            password.type = 'text';
            eyeIcon.classList.remove('bi-eye-fill');
            eyeIcon.classList.add('bi-eye-slash-fill');
        } else {
            password.type = 'password';
            eyeIcon.classList.remove('bi-eye-slash-fill');
            eyeIcon.classList.add('bi-eye-fill');
        }
    });

    // Validación adicional para el correo electrónico
    document.getElementById('email').addEventListener('input', function() {
        const email = this.value;
        const isValid = email.toLowerCase().endsWith('@gmail.com');
        if (!isValid && email !== '') {
            this.setCustomValidity('Debe ser una cuenta de Gmail (@gmail.com)');
        } else {
            this.setCustomValidity('');
        }
    });

    // Validación del teléfono (9 dígitos)
    document.getElementById('telefono').addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9);
    });

    // Validación en tiempo real de la contraseña
    const password = document.getElementById('password');
    const minLength = document.getElementById('minLength');
    const hasUppercase = document.getElementById('hasUppercase');
    const hasLowercase = document.getElementById('hasLowercase');
    const hasNumber = document.getElementById('hasNumber');
    const hasSpecial = document.getElementById('hasSpecial');

    password.addEventListener('input', function() {
        const val = this.value;
        
        // Verificar cada requisito
        if (val.length >= 8) {
            minLength.classList.remove('text-danger');
            minLength.classList.add('text-success');
        } else {
            minLength.classList.remove('text-success');
            minLength.classList.add('text-danger');
        }

        if (/[A-Z]/.test(val)) {
            hasUppercase.classList.remove('text-danger');
            hasUppercase.classList.add('text-success');
        } else {
            hasUppercase.classList.remove('text-success');
            hasUppercase.classList.add('text-danger');
        }

        if (/[a-z]/.test(val)) {
            hasLowercase.classList.remove('text-danger');
            hasLowercase.classList.add('text-success');
        } else {
            hasLowercase.classList.remove('text-success');
            hasLowercase.classList.add('text-danger');
        }

        if (/\d/.test(val)) {
            hasNumber.classList.remove('text-danger');
            hasNumber.classList.add('text-success');
        } else {
            hasNumber.classList.remove('text-success');
            hasNumber.classList.add('text-danger');
        }

        if (/[@$!%*?&]/.test(val)) {
            hasSpecial.classList.remove('text-danger');
            hasSpecial.classList.add('text-success');
        } else {
            hasSpecial.classList.remove('text-success');
            hasSpecial.classList.add('text-danger');
        }

        // Validar si cumple con todos los requisitos
        const isValid = val.length >= 8 && 
                       /[A-Z]/.test(val) && 
                       /[a-z]/.test(val) && 
                       /\d/.test(val) && 
                       /[@$!%*?&]/.test(val);

        if (isValid) {
            this.setCustomValidity('');
        } else {
            this.setCustomValidity('La contraseña no cumple con los requisitos');
        }
    });
    </script>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>