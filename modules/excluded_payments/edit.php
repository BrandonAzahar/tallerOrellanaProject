<?php
/**
 * Editar Pago a Sujeto Excluido
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Editar Pago - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();
$errors = [];

// Obtener ID y decodificar
$id = isset($_GET['id']) ? (int)base64_decode($_GET['id']) : 0;

if ($id <= 0) {
    setFlash('error', 'Pago no válido');
    header('Location: index.php');
    exit();
}

// Obtener datos del pago
$sql = "SELECT ep.*, es.first_name, es.last_name 
        FROM excluded_payments ep
        JOIN excluded_subjects es ON ep.subject_id = es.id
        WHERE ep.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$payment = $stmt->fetch();

if (!$payment) {
    setFlash('error', 'Pago no encontrado');
    header('Location: index.php');
    exit();
}

// Obtener sujetos activos
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
        
        if ($subjectId <= 0) $errors[] = 'Debe seleccionar un sujeto excluido';
        if (empty($serviceDate)) $errors[] = 'La fecha del servicio es obligatoria';
        if (empty($serviceDescription)) $errors[] = 'La descripción es obligatoria';
        if ($grossAmount <= 0) $errors[] = 'El monto bruto debe ser mayor a 0';
        
        $withholdingTax = calculateWithholdingTax($grossAmount);
        $netAmount = $grossAmount - $withholdingTax;
        
        if (empty($errors)) {
            try {
                $sql = "UPDATE excluded_payments SET 
                        subject_id=?, service_date=?, service_description=?, period_description=?,
                        gross_amount=?, withholding_tax=?, net_amount=?, payment_method=?, 
                        status=?, notes=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $subjectId, $serviceDate, $serviceDescription, $periodDescription,
                    $grossAmount, $withholdingTax, $netAmount, $paymentMethod, $status, $notes, $id
                ]);
                
                setFlash('success', 'Pago actualizado exitosamente');
                header('Location: print.php?id=' . base64_encode($id));
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
    <h2><i class="bi bi-pencil me-2"></i>Editar Pago</h2>
    <a href="print.php?id=<?php echo base64_encode($id); ?>" class="btn btn-outline-primary">
        <i class="bi bi-printer me-2"></i>Ver Factura
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
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="subject_id" class="form-label">Sujeto Excluido</label>
                    <select class="form-select" id="subject_id" name="subject_id" required>
                        <?php foreach ($subjects as $subj): ?>
                            <option value="<?php echo $subj['id']; ?>" 
                                    <?php echo $payment['subject_id'] == $subj['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subj['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="service_date" class="form-label">Fecha del Servicio</label>
                    <input type="date" class="form-control" id="service_date" name="service_date" 
                           value="<?php echo htmlspecialchars($payment['service_date']); ?>" required>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="payment_method" class="form-label">Método de Pago</label>
                    <select class="form-select" id="payment_method" name="payment_method">
                        <option value="cash" <?php echo $payment['payment_method'] === 'cash' ? 'selected' : ''; ?>>Efectivo</option>
                        <option value="transfer" <?php echo $payment['payment_method'] === 'transfer' ? 'selected' : ''; ?>>Transferencia</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <label for="period_description" class="form-label">Período</label>
                    <input type="text" class="form-control" id="period_description" name="period_description" 
                           value="<?php echo htmlspecialchars($payment['period_description'] ?? ''); ?>">
                </div>
                
                <div class="col-12">
                    <label for="service_description" class="form-label">Descripción del Servicio</label>
                    <textarea class="form-control" id="service_description" name="service_description" 
                              rows="3" required><?php echo htmlspecialchars($payment['service_description']); ?></textarea>
                </div>
                
                <div class="col-12 col-md-4">
                    <label for="gross_amount" class="form-label">Monto Bruto ($)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" 
                           id="gross_amount" name="gross_amount" 
                           value="<?php echo number_format($payment['gross_amount'], 2); ?>" required>
                </div>
                
                <div class="col-12 col-md-4">
                    <label for="withholding_tax" class="form-label">Retención</label>
                    <input type="text" class="form-control" id="withholding_tax" readonly 
                           value="$<?php echo number_format($payment['withholding_tax'], 2); ?>">
                </div>
                
                <div class="col-12 col-md-4">
                    <label for="net_amount" class="form-label">Monto Neto</label>
                    <input type="text" class="form-control" id="net_amount" readonly 
                           value="$<?php echo number_format($payment['net_amount'], 2); ?>">
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-select" id="status" name="status">
                        <option value="pending" <?php echo $payment['status'] === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="paid" <?php echo $payment['status'] === 'paid' ? 'selected' : ''; ?>>Pagado</option>
                        <option value="cancelled" <?php echo $payment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="notes" class="form-label">Notas</label>
                    <input type="text" class="form-control" id="notes" name="notes" 
                           value="<?php echo htmlspecialchars($payment['notes'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Actualizar
                </button>
                <a href="print.php?id=<?php echo base64_encode($id); ?>" class="btn btn-outline-primary">
                    <i class="bi bi-printer me-2"></i>Ver Factura
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
