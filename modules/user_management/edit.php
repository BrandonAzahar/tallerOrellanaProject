<?php
/**
 * Editar Usuario
 * Estructuras y Remodelaciones Orellana
 * Solo administradores pueden editar usuarios
 */

$pageTitle = 'Editar Usuario - Orellana';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();
requireAdmin();

$errors = [];

// Obtener ID
$id = isset($_GET['id']) ? (int)base64_decode($_GET['id']) : 0;

if ($id <= 0) {
    setFlash('error', 'Usuario no válido');
    header('Location: index.php');
    exit();
}

// No permitir editar el propio usuario desde aquí (usar perfil)
if ($id == getCurrentUserId()) {
    setFlash('info', 'Para editar tu propio usuario, usa la opción de perfil');
}

// Obtener datos del usuario
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'Usuario no encontrado');
    header('Location: index.php');
    exit();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'operator';
        $status = $_POST['status'] ?? 'active';
        
        // Validaciones
        if (empty($username)) {
            $errors[] = 'El nombre de usuario es obligatorio';
        } elseif (strlen($username) < 3) {
            $errors[] = 'El usuario debe tener al menos 3 caracteres';
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
        
        // Verificar usuario duplicado (excluyendo el actual)
        if (!empty($username) && $username !== $user['username']) {
            $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                $errors[] = 'El nombre de usuario ya está en uso';
            }
        }
        
        // Actualizar usuario
        if (empty($errors)) {
            try {
                $sql = "UPDATE users SET username=?, full_name=?, email=?, role=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$username, $fullName, $email, $role, $status, $id]);
                
                setFlash('success', 'Usuario actualizado exitosamente');
                header('Location: index.php');
                exit();
            } catch (PDOException $e) {
                $errors[] = 'Error al actualizar el usuario';
            }
        }
    }
}

$csrf_token = generateCsrfToken();
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil me-2"></i>Editar Usuario</h2>
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
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
            <div class="row g-3">
                <!-- Usuario -->
                <div class="col-12 col-md-6">
                    <label for="username" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                
                <!-- Nombre Completo -->
                <div class="col-12 col-md-6">
                    <label for="full_name" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                
                <!-- Email -->
                <div class="col-12 col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                </div>
                
                <!-- Rol -->
                <div class="col-12 col-md-6">
                    <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="operator" <?php echo $user['role'] === 'operator' ? 'selected' : ''; ?>>Operador</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </div>
                
                <!-- Estado -->
                <div class="col-12 col-md-6">
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                
                <!-- Última conexión -->
                <div class="col-12 col-md-6">
                    <label class="form-label">Último Acceso</label>
                    <input type="text" class="form-control" readonly 
                           value="<?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Nunca'; ?>">
                </div>
                
                <!-- Fecha de creación -->
                <div class="col-12 col-md-6">
                    <label class="form-label">Fecha de Creación</label>
                    <input type="text" class="form-control" readonly 
                           value="<?php echo formatDate($user['created_at'], 'd/m/Y H:i'); ?>">
                </div>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Actualizar Usuario
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
            </div>
        </form>
        
        <!-- Sección de cambio de contraseña -->
        <hr class="my-4">
        
        <h6><i class="bi bi-key me-2"></i>Cambiar Contraseña</h6>
        <p class="text-muted small">Deje en blanco si no desea cambiar la contraseña</p>
        
        <form method="POST" action="change_password.php" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
            <div class="col-12 col-md-6">
                <label for="new_password" class="form-label">Nueva Contraseña</label>
                <input type="password" class="form-control" id="new_password" name="new_password">
                <small class="text-muted">Mínimo 6 caracteres</small>
            </div>
            
            <div class="col-12 col-md-6">
                <label for="confirm_new_password" class="form-label">Confirmar Nueva Contraseña</label>
                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password">
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-key me-2"></i>Cambiar Contraseña
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
