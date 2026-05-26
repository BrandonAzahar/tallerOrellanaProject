<?php
/**
 * Editar Sujeto Excluido
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Editar Sujeto Excluido - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/audit_utils.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();
$errors = [];

// Obtener ID y desencriptar
$id = isset($_GET['id']) ? decryptId($_GET['id']) : 0;

if ($id === false || $id <= 0) {
    setFlash('error', 'Sujeto no válido');
    header('Location: index.php');
    exit();
}

// Obtener datos del sujeto
$sql = "SELECT * FROM excluded_subjects WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$subject = $stmt->fetch();

if (!$subject) {
    setFlash('error', 'Sujeto no encontrado');
    header('Location: index.php');
    exit();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        $dui = !empty($_POST['dui']) ? sanitizeDbInput($_POST['dui']) : null;
        $nit = !empty($_POST['nit']) ? sanitizeDbInput($_POST['nit']) : null;
        $firstName = sanitizeDbInput($_POST['first_name'] ?? '');
        $lastName = sanitizeDbInput($_POST['last_name'] ?? '');
        $phone = sanitizeDbInput($_POST['phone'] ?? '');
        $address = sanitizeDbInput($_POST['address'] ?? '');
        $occupation = sanitizeDbInput($_POST['occupation'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        if (empty($firstName)) $errors[] = 'El nombre es obligatorio';
        if (empty($lastName)) $errors[] = 'El apellido es obligatorio';
        if ($dui && !validateDUI($dui)) $errors[] = 'DUI inválido';
        if ($nit && !validateNIT($nit)) $errors[] = 'NIT inválido. Formato: XXXX-XXXXXX-XXX-X (14 dígitos)';
        
        // Verificar DUI duplicado (excluyendo el actual)
        if ($dui && $dui !== $subject['dui']) {
            $sql = "SELECT id FROM excluded_subjects WHERE dui = ? AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$dui, $id]);
            if ($stmt->fetch()) {
                $errors[] = 'El DUI ya está registrado';
            }
        }
        
        if (empty($errors)) {
            try {
                // Guardar valores antiguos para el log
                $oldValues = [
                    'dui' => $subject['dui'],
                    'nit' => $subject['nit'],
                    'first_name' => $subject['first_name'],
                    'last_name' => $subject['last_name'],
                    'phone' => $subject['phone'],
                    'address' => $subject['address'],
                    'occupation' => $subject['occupation'],
                    'status' => $subject['status']
                ];

                $sql = "UPDATE excluded_subjects SET dui=?, nit=?, first_name=?, last_name=?,
                        phone=?, address=?, occupation=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$dui, $nit, $firstName, $lastName, $phone, $address, $occupation, $status, $id]);

                // Registrar en logs de auditoría
                $newValues = [
                    'dui' => $dui,
                    'nit' => $nit,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone,
                    'address' => $address,
                    'occupation' => $occupation,
                    'status' => $status
                ];
                logAudit($conn, 'update', 'excluded_subjects', $id, 'excluded_subjects', $oldValues, $newValues);

                setFlash('success', 'Sujeto excluido actualizado exitosamente');
                header('Location: index.php');
                exit();
            } catch (PDOException $e) {
                $errors[] = 'Error al actualizar';
            }
        }
    }
}

$csrf_token = generateCsrfToken();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil me-2"></i>Editar Sujeto Excluido</h2>
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
        <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Datos del Sujeto</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="first_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" 
                           value="<?php echo htmlspecialchars($subject['first_name']); ?>" required>
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="last_name" class="form-label">Apellido <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           value="<?php echo htmlspecialchars($subject['last_name']); ?>" required>
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="dui" class="form-label">DUI</label>
                    <input type="text" class="form-control dui-input" id="dui" name="dui" 
                           placeholder="XXXXXXXX-X" maxlength="10"
                           value="<?php echo htmlspecialchars($subject['dui'] ?? ''); ?>">
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="nit" class="form-label">NIT</label>
                    <input type="text" class="form-control nit-input" id="nit" name="nit" 
                           placeholder="XXXX-XXXXXX-XXX-X" maxlength="20"
                           value="<?php echo htmlspecialchars($subject['nit'] ?? ''); ?>">
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="phone" class="form-label">Teléfono</label>
                    <input type="text" class="form-control phone-input" id="phone" name="phone" 
                           placeholder="XXXX-XXXX" maxlength="9"
                           value="<?php echo htmlspecialchars($subject['phone'] ?? ''); ?>">
                </div>
                
                <!-- Ocupación (MODIFICACIÓN: TextBox de texto libre convencional) -->
                <div class="col-12 col-md-6">
                    <label for="occupation" class="form-label">Ocupación <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="occupation" name="occupation" 
                           placeholder="ej: Soldador, Electricista, Albañil"
                           value="<?php echo htmlspecialchars($subject['occupation'] ?? ''); ?>" required>
                    <div class="invalid-feedback">La ocupación es obligatoria</div>
                </div>
                
                <div class="col-12">
                    <label for="address" class="form-label">Dirección</label>
                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($subject['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo $subject['status'] === 'active' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactive" <?php echo $subject['status'] === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Actualizar
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
