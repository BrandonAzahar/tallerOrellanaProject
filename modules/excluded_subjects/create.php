<?php
/**
 * Crear Nuevo Sujeto Excluido
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/audit_utils.php';
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Nuevo Sujeto Excluido - Orellana';
$conn = getDbConnection();
$errors = [];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        // Obtener y sanitizar datos
        $dui = !empty($_POST['dui']) ? sanitizeDbInput($_POST['dui']) : null;
        $nit = !empty($_POST['nit']) ? sanitizeDbInput($_POST['nit']) : null;
        $firstName = sanitizeDbInput($_POST['first_name'] ?? '');
        $lastName = sanitizeDbInput($_POST['last_name'] ?? '');
        $phone = sanitizeDbInput($_POST['phone'] ?? '');
        $address = sanitizeDbInput($_POST['address'] ?? '');
        $occupation = sanitizeDbInput($_POST['occupation'] ?? '');
        $status = $_POST['status'] ?? 'active';

        // Validaciones
        if (empty($firstName)) {
            $errors[] = 'El nombre es obligatorio';
        }
        if (empty($lastName)) {
            $errors[] = 'El apellido es obligatorio';
        }
        if ($dui && !validateDUI($dui)) {
            $errors[] = 'DUI inválido. Formato: XXXXXXXX-X';
        }
        if ($nit && !validateNIT($nit)) {
            $errors[] = 'NIT inválido. Formato: XXXX-XXXXXX-XXX-X';
        }
        if ($phone && !validatePhone($phone)) {
            $errors[] = 'Teléfono inválido. Debe tener 8 dígitos';
        }

        // Verificar DUI duplicado
        if ($dui) {
            $sql = "SELECT id FROM excluded_subjects WHERE dui = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$dui]);
            if ($stmt->fetch()) {
                $errors[] = 'El DUI ya está registrado';
            }
        }

        // Insertar si no hay errores
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO excluded_subjects (dui, nit, first_name, last_name, phone, address, occupation, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$dui, $nit, $firstName, $lastName, $phone, $address, $occupation, $status]);

                $newId = $conn->lastInsertId();

                // Registrar en logs de auditoría
                logAudit($conn, 'create', 'excluded_subjects', $newId, 'excluded_subjects', null, [
                    'dui' => $dui,
                    'nit' => $nit,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'occupation' => $occupation
                ]);

                setFlash('success', 'Sujeto excluido registrado exitosamente');
                header('Location: index.php');
                exit();
            } catch (PDOException $e) {
                $errors[] = 'Error al registrar: ' . ($e->getCode() == 23000 ? 'DUI duplicado' : $e->getMessage());
            }
        }
    }
}

$csrf_token = generateCsrfToken();
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-plus me-2"></i>Nuevo Sujeto Excluido</h2>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Volver al Listado
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
        <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Datos del Sujeto Excluido</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="row g-3">
                <!-- Nombre -->
                <div class="col-12 col-md-6">
                    <label for="first_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" 
                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                    <div class="invalid-feedback">El nombre es obligatorio</div>
                </div>
                
                <!-- Apellido -->
                <div class="col-12 col-md-6">
                    <label for="last_name" class="form-label">Apellido <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                    <div class="invalid-feedback">El apellido es obligatorio</div>
                </div>
                
                <!-- DUI -->
                <div class="col-12 col-md-6">
                    <label for="dui" class="form-label">DUI</label>
                    <input type="text" class="form-control dui-input" id="dui" name="dui" 
                           placeholder="XXXXXXXX-X" maxlength="10"
                           value="<?php echo htmlspecialchars($_POST['dui'] ?? ''); ?>">
                    <small class="text-muted">Formato: 8 dígitos + guión + verificador</small>
                </div>
                
                <!-- NIT -->
                <div class="col-12 col-md-6">
                    <label for="nit" class="form-label">NIT</label>
                    <input type="text" class="form-control nit-input" id="nit" name="nit" 
                           placeholder="XXXX-XXXXXX-XXX-X" maxlength="20"
                           value="<?php echo htmlspecialchars($_POST['nit'] ?? ''); ?>">
                    <small class="text-muted">Formato: XXXX-XXXXXX-XXX-X</small>
                </div>
                
                <!-- Teléfono -->
                <div class="col-12 col-md-6">
                    <label for="phone" class="form-label">Teléfono</label>
                    <input type="text" class="form-control phone-input" id="phone" name="phone" 
                           placeholder="XXXX-XXXX" maxlength="9"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                
                <!-- Ocupación (MODIFICACIÓN: TextBox de texto libre convencional) -->
                <div class="col-12 col-md-6">
                    <label for="occupation" class="form-label">Ocupación <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="occupation" name="occupation" 
                           placeholder="ej: Soldador, Electricista, Albañil"
                           value="<?php echo htmlspecialchars($_POST['occupation'] ?? ''); ?>" required>
                    <div class="invalid-feedback">La ocupación es obligatoria</div>
                </div>
                
                <!-- Dirección -->
                <div class="col-12">
                    <label for="address" class="form-label">Dirección</label>
                    <textarea class="form-control" id="address" name="address" rows="2" 
                              placeholder="Dirección completa"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
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
                    <i class="bi bi-save me-2"></i>Guardar Sujeto
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
