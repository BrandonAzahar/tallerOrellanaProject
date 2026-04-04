<?php
/**
 * Crear Nuevo Usuario
 * Estructuras y Remodelaciones Orellana
 * Solo administradores pueden crear usuarios
 */

$pageTitle = 'Nuevo Usuario - Orellana';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();
requireAdmin();

$errors = [];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'operator';
        $status = $_POST['status'] ?? 'active';

        // Validaciones
        if (empty($username)) {
            $errors[] = 'El nombre de usuario es obligatorio';
        } elseif (strlen($username) < 3) {
            $errors[] = 'El usuario debe tener al menos 3 caracteres';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'El usuario solo puede contener letras, números y guiones bajos';
        }

        if (empty($password)) {
            $errors[] = 'La contraseña es obligatoria';
        } elseif (strlen($password) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Las contraseñas no coinciden';
        }

        if (empty($fullName)) {
            $errors[] = 'El nombre completo es obligatorio';
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email no es válido';
        }

        if (!in_array($role, ['admin', 'operator'])) {
            $errors[] = 'El rol no es válido';
        }

        // Verificar usuario duplicado
        if (!empty($username)) {
            $sql = "SELECT id FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = 'El nombre de usuario ya está en uso';
            }
        }

        // Insertar usuario
        if (empty($errors)) {
            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT, ['cost' => PASSWORD_COST]);

                $sql = "INSERT INTO users (username, password, full_name, email, role, status)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$username, $hashedPassword, $fullName, $email, $role, $status]);

                setFlash('success', 'Usuario creado exitosamente');
                header('Location: index.php');
                exit();
            } catch (PDOException $e) {
                $errors[] = 'Error al crear el usuario: ' . $e->getMessage();
            }
        }
    }
}

$csrf_token = generateCsrfToken();
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-plus me-2"></i>Crear Nuevo Usuario</h2>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Volver
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Datos del Usuario</h5>
    </div>
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="row g-3">
                <!-- Usuario -->
                <div class="col-12 col-md-6">
                    <label for="username" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           placeholder="ej: jlopez" required>
                    <div class="invalid-feedback">El usuario es obligatorio</div>
                    <small class="text-muted">Solo letras, números y guiones bajos</small>
                </div>
                
                <!-- Nombre Completo -->
                <div class="col-12 col-md-6">
                    <label for="full_name" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                           placeholder="Nombre y apellido" required>
                    <div class="invalid-feedback">El nombre completo es obligatorio</div>
                </div>
                
                <!-- Email -->
                <div class="col-12 col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           placeholder="correo@ejemplo.com">
                </div>
                
                <!-- Rol -->
                <div class="col-12 col-md-6">
                    <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="operator" <?php echo ($_POST['role'] ?? '') === 'operator' ? 'selected' : ''; ?>>
                            Operador
                        </option>
                        <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>
                            Administrador
                        </option>
                    </select>
                    <div class="invalid-feedback">Seleccione un rol</div>
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Admin:</strong> Acceso completo. <strong>Operador:</strong> Acceso operativo.
                    </small>
                </div>
                
                <!-- Contraseña -->
                <div class="col-12 col-md-6">
                    <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="invalid-feedback">La contraseña es obligatoria</div>
                    <small class="text-muted">Mínimo 6 caracteres</small>
                </div>
                
                <!-- Confirmar Contraseña -->
                <div class="col-12 col-md-6">
                    <label for="confirm_password" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <div class="invalid-feedback">Confirme la contraseña</div>
                </div>
                
                <!-- Estado -->
                <div class="col-12 col-md-6">
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo ($_POST['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactive" <?php echo ($_POST['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Crear Usuario
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
