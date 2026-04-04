<?php
/**
 * Crear Nuevo Pago a Sujeto Excluido
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/correlation_utils.php';

$pageTitle = 'Nuevo Pago - Orellana';
$conn = getDbConnection();
$errors = [];

// Obtener sujeto excluido si viene en la URL
$subjectId = isset($_GET['subject']) ? (int)base64_decode($_GET['subject']) : 0;

// Obtener sujetos activos para el dropdown
$sql = "SELECT id, CONCAT(first_name, ' ', last_name) as full_name, occupation
        FROM excluded_subjects
        WHERE status = 'active'
        ORDER BY last_name, first_name";
$subjects = $conn->query($sql)->fetchAll();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        $subjectId = (int)($_POST['subject_id'] ?? 0);
        $serviceDate = $_POST['service_date'] ?? '';
        $serviceDescription = sanitizeDbInput($_POST['service_description'] ?? '');
        $periodDescription = sanitizeDbInput($_POST['period_description'] ?? '');
        $grossAmount = (float)($_POST['gross_amount'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $status = $_POST['status'] ?? 'pending';
        $notes = sanitizeDbInput($_POST['notes'] ?? '');

        // Validaciones
        if ($subjectId <= 0) {
            $errors[] = 'Debe seleccionar un sujeto excluido';
        }
        if (empty($serviceDate)) {
            $errors[] = 'La fecha del servicio es obligatoria';
        }
        if (empty($serviceDescription)) {
            $errors[] = 'La descripción del servicio es obligatoria';
        }
        if ($grossAmount <= 0) {
            $errors[] = 'El monto bruto debe ser mayor a 0';
        }

        // Calcular retención automáticamente
        $withholdingTax = calculateWithholdingTax($grossAmount);
        $netAmount = $grossAmount - $withholdingTax;

        if (empty($errors)) {
            try {
                // Iniciar transacción
                $conn->beginTransaction();

                // Generar correlativo fiscal
                $correlation = generateCorrelation($conn, 'excluded_payment');
                $invoiceNumber = $correlation['correlation'];

                // Insertar pago
                $sql = "INSERT INTO excluded_payments
                        (subject_id, invoice_number, service_date, service_description, period_description,
                         gross_amount, withholding_tax, net_amount, payment_method, status, notes, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $subjectId,
                    $invoiceNumber,
                    $serviceDate,
                    $serviceDescription,
                    $periodDescription,
                    $grossAmount,
                    $withholdingTax,
                    $netAmount,
                    $paymentMethod,
                    $status,
                    $notes,
                    getCurrentUserId()
                ]);

                $paymentId = $conn->lastInsertId();

                // Confirmar transacción
                $conn->commit();

                setFlash('success', 'Pago registrado exitosamente. Factura: ' . $invoiceNumber);
                header('Location: print.php?id=' . base64_encode($paymentId));
                exit();

            } catch (Exception $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $errors[] = 'Error al registrar el pago: ' . $e->getMessage();
            }
        }
    }
}

$csrf_token = generateCsrfToken();

// Valores por defecto
$serviceDate = $_POST['service_date'] ?? date('Y-m-d');
$periodDescription = $_POST['period_description'] ?? 'Quincena 1-15 ' . strtolower(date('F Y'));
$paymentMethod = $_POST['payment_method'] ?? 'cash';
$status = $_POST['status'] ?? 'paid';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-plus-circle me-2"></i>Nuevo Pago a Sujeto Excluido</h2>
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
        <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Datos del Pago</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="row g-3">
                <!-- Sujeto Excluido -->
                <div class="col-12 col-md-6">
                    <label for="subject_id" class="form-label">Sujeto Excluido <span class="text-danger">*</span></label>
                    <select class="form-select" id="subject_id" name="subject_id" required>
                        <option value="">Seleccionar sujeto</option>
                        <?php foreach ($subjects as $subj): ?>
                            <option value="<?php echo $subj['id']; ?>" 
                                    <?php echo ($subjectId == $subj['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subj['full_name']); ?> 
                                (<?php echo htmlspecialchars($subj['occupation']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Seleccione un sujeto excluido</div>
                </div>
                
                <!-- Fecha del Servicio -->
                <div class="col-12 col-md-3">
                    <label for="service_date" class="form-label">Fecha del Servicio <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="service_date" name="service_date" 
                           value="<?php echo htmlspecialchars($serviceDate); ?>" required>
                </div>
                
                <!-- Método de Pago -->
                <div class="col-12 col-md-3">
                    <label for="payment_method" class="form-label">Método de Pago</label>
                    <select class="form-select" id="payment_method" name="payment_method">
                        <option value="cash" <?php echo $paymentMethod === 'cash' ? 'selected' : ''; ?>>Efectivo</option>
                        <option value="transfer" <?php echo $paymentMethod === 'transfer' ? 'selected' : ''; ?>>Transferencia</option>
                    </select>
                </div>
                
                <!-- Período -->
                <div class="col-12">
                    <label for="period_description" class="form-label">Período del Servicio</label>
                    <input type="text" class="form-control" id="period_description" name="period_description" 
                           placeholder="ej: Quincena 1-15 enero 2026"
                           value="<?php echo htmlspecialchars($periodDescription); ?>">
                    <small class="text-muted">Descripción del período de servicio prestado</small>
                </div>
                
                <!-- Descripción del Servicio -->
                <div class="col-12">
                    <label for="service_description" class="form-label">Descripción del Servicio <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="service_description" name="service_description" 
                              rows="3" placeholder="Describa los servicios prestados" required><?php echo htmlspecialchars($_POST['service_description'] ?? ''); ?></textarea>
                    <div class="invalid-feedback">La descripción es obligatoria</div>
                </div>
                
                <!-- Monto Bruto -->
                <div class="col-12 col-md-4">
                    <label for="gross_amount" class="form-label">Monto Bruto ($) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="form-control currency-input" 
                           id="gross_amount" name="gross_amount" 
                           value="<?php echo htmlspecialchars($_POST['gross_amount'] ?? ''); ?>" required>
                    <div class="invalid-feedback">Ingrese un monto válido</div>
                </div>
                
                <!-- Retención (calculado automáticamente) -->
                <div class="col-12 col-md-4">
                    <label for="withholding_tax" class="form-label">Retención Renta (10%)</label>
                    <input type="text" class="form-control" id="withholding_tax" readonly 
                           value="$0.00">
                    <small id="withholding_message" class="text-success small">No aplica (≤ $462)</small>
                </div>
                
                <!-- Monto Neto (calculado automáticamente) -->
                <div class="col-12 col-md-4">
                    <label for="net_amount" class="form-label">Monto Neto a Pagar</label>
                    <input type="text" class="form-control" id="net_amount" readonly 
                           value="$0.00">
                </div>
                
                <!-- Estado -->
                <div class="col-12 col-md-6">
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-select" id="status" name="status">
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Pagado</option>
                    </select>
                </div>
                
                <!-- Notas -->
                <div class="col-12 col-md-6">
                    <label for="notes" class="form-label">Notas Adicionales</label>
                    <input type="text" class="form-control" id="notes" name="notes" 
                           placeholder="Notas opcionales"
                           value="<?php echo htmlspecialchars($_POST['notes'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Registrar Pago
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Cálculo automático de retención
document.getElementById('gross_amount').addEventListener('blur', function() {
    const grossAmount = parseFloat(this.value) || 0;
    const threshold = 462.00;
    const rate = 0.10;
    
    let withholding = 0;
    if (grossAmount > threshold) {
        withholding = grossAmount * rate;
    }
    
    const netAmount = grossAmount - withholding;
    
    document.getElementById('withholding_tax').value = '$' + withholding.toFixed(2);
    document.getElementById('net_amount').value = '$' + netAmount.toFixed(2);
    
    const message = document.getElementById('withholding_message');
    if (withholding > 0) {
        message.textContent = 'Retención de renta (10%): $' + withholding.toFixed(2);
        message.className = 'text-warning small';
    } else {
        message.textContent = 'No aplica retención (monto ≤ $462)';
        message.className = 'text-success small';
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
