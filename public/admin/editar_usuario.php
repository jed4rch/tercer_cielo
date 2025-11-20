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

$pdo = getPdo();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: usuarios.php');
    exit;
}

// === EDITAR ===
if ($_POST) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password = trim($_POST['password'] ?? '');

    try {
        if (!empty($password)) {
            // Validar contraseña fuerte
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
                throw new Exception('La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un caracter especial');
            }
            
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, password = ? WHERE id = ?");
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $success = $stmt->execute([$nombre, $email, $telefono, $hashed_password, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ? WHERE id = ?");
            $success = $stmt->execute([$nombre, $email, $telefono, $id]);
        }

        if ($success) {
            header('Location: usuarios.php?success=editado');
            exit;
        } else {
            $error = "Error al actualizar usuario.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// === OBTENER USUARIO ===
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();
if (!$usuario) {
    header('Location: usuarios.php');
    exit;
}

$pageTitle = 'Editar Usuario';
include 'layout_header.php';
?>

<div class="content-card">
    <h3 class="mb-4"><i class="bi bi-person-gear me-2"></i>Editar Usuario</h3>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Teléfono</label>
                <input type="tel" name="telefono" value="<?= htmlspecialchars($usuario['telefono']) ?>" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Nueva Contraseña</label>
                <div class="input-group">
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                           title="La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un caracter especial">
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="bi bi-eye-fill" id="eyeIcon"></i>
                    </button>
                </div>
                <div class="form-text">
                    Dejar en blanco para mantener la contraseña actual. La nueva contraseña debe tener:
                    <ul class="mb-0 small">
                        <li id="minLength" class="text-muted">Mínimo 8 caracteres</li>
                        <li id="hasUppercase" class="text-muted">Al menos una mayúscula</li>
                        <li id="hasLowercase" class="text-muted">Al menos una minúscula</li>
                        <li id="hasNumber" class="text-muted">Al menos un número</li>
                        <li id="hasSpecial" class="text-muted">Al menos un caracter especial (@$!%*?&)</li>
                    </ul>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

    // Validación en tiempo real de la contraseña
    const password = document.getElementById('password');
    const minLength = document.getElementById('minLength');
    const hasUppercase = document.getElementById('hasUppercase');
    const hasLowercase = document.getElementById('hasLowercase');
    const hasNumber = document.getElementById('hasNumber');
    const hasSpecial = document.getElementById('hasSpecial');

    password.addEventListener('input', function() {
        const val = this.value;
        
        if (val.length === 0) {
            // Si el campo está vacío, mostrar todos en gris
            [minLength, hasUppercase, hasLowercase, hasNumber, hasSpecial].forEach(element => {
                element.classList.remove('text-danger', 'text-success');
                element.classList.add('text-muted');
            });
            this.setCustomValidity('');
            return;
        }

        // Verificar cada requisito
        if (val.length >= 8) {
            minLength.classList.remove('text-danger', 'text-muted');
            minLength.classList.add('text-success');
        } else {
            minLength.classList.remove('text-success', 'text-muted');
            minLength.classList.add('text-danger');
        }

        if (/[A-Z]/.test(val)) {
            hasUppercase.classList.remove('text-danger', 'text-muted');
            hasUppercase.classList.add('text-success');
        } else {
            hasUppercase.classList.remove('text-success', 'text-muted');
            hasUppercase.classList.add('text-danger');
        }

        if (/[a-z]/.test(val)) {
            hasLowercase.classList.remove('text-danger', 'text-muted');
            hasLowercase.classList.add('text-success');
        } else {
            hasLowercase.classList.remove('text-success', 'text-muted');
            hasLowercase.classList.add('text-danger');
        }

        if (/\d/.test(val)) {
            hasNumber.classList.remove('text-danger', 'text-muted');
            hasNumber.classList.add('text-success');
        } else {
            hasNumber.classList.remove('text-success', 'text-muted');
            hasNumber.classList.add('text-danger');
        }

        if (/[@$!%*?&]/.test(val)) {
            hasSpecial.classList.remove('text-danger', 'text-muted');
            hasSpecial.classList.add('text-success');
        } else {
            hasSpecial.classList.remove('text-success', 'text-muted');
            hasSpecial.classList.add('text-danger');
        }

        // Validar si cumple con todos los requisitos
        const isValid = val.length >= 8 && 
                       /[A-Z]/.test(val) && 
                       /[a-z]/.test(val) && 
                       /\d/.test(val) && 
                       /[@$!%*?&]/.test(val);

        if (isValid || val.length === 0) {
            this.setCustomValidity('');
        } else {
            this.setCustomValidity('La contraseña no cumple con los requisitos');
        }
    });
    </script>
</div>

<?php include 'layout_footer.php'; ?>