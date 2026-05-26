<?php
/**
 * Editar Pago a Sujeto Excluido
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Editar Pago - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/audit_utils.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();
$errors = [];

// Obtener ID y desencriptar
$id = isset($_GET['id']) ? decryptId($_GET['id']) : 0;

if ($id === false || $id <= 0) {
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
                // Guardar valores antiguos
                $oldValues = [
                    'invoice_number' => $payment['invoice_number'],
                    'subject_id' => $payment['subject_id'],
                    'gross_amount' => $payment['gross_amount'],
                    'net_amount' => $payment['net_amount'],
                    'status' => $payment['status']
                ];

                $sql = "UPDATE excluded_payments SET
                        subject_id=?, service_date=?, service_description=?, period_description=?,
                        gross_amount=?, withholding_tax=?, net_amount=?, payment_method=?,
                        status=?, notes=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $subjectId, $serviceDate, $serviceDescription, $periodDescription,
                    $grossAmount, $withholdingTax, $netAmount, $paymentMethod, $status, $notes, $id
                ]);

                // Registrar en logs
                $newValues = [
                    'subject_id' => $subjectId,
                    'gross_amount' => $grossAmount,
                    'net_amount' => $netAmount,
                    'status' => $status
                ];
                logAudit($conn, 'update', 'excluded_payments', $id, 'excluded_payments', $oldValues, $newValues);

                setFlash('success', 'Pago actualizado exitosamente');
                header('Location: print.php?id=' . encryptId($id));
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
    <a href="print.php?id=<?php echo encryptId($id); ?>" class="btn btn-outline-primary">
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
                
                <!-- Período de Servicio (Selectores Dinámicos de Front-End) -->
                <!-- MODIFICACIÓN: Se reemplaza el campo de texto libre por selectores guiados de Período, Mes (abreviado) y Año -->
                <div class="col-12 col-md-9">
                    <label class="form-label d-block">Período del Servicio <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <!-- Selector de Quincena o Mes Completo -->
                        <div class="col-12 col-md-4">
                            <select class="form-select" id="select_period_part">
                                <option value="Quincena 1-15">Primera Quincena (Días 1-15)</option>
                                <option value="Quincena 16-31">Segunda Quincena (Días 16-31)</option>
                                <option value="Quincena 16-30">Segunda Quincena (Días 16-30)</option>
                                <option value="Mes completo">Mes Completo</option>
                            </select>
                        </div>
                        <!-- Selector de Mes (abreviado a 3 letras según formato requerido) -->
                        <div class="col-12 col-md-4">
                            <select class="form-select" id="select_period_month">
                                <option value="ene">Enero (ene)</option>
                                <option value="feb">Febrero (feb)</option>
                                <option value="mar">Marzo (mar)</option>
                                <option value="abr">Abril (abr)</option>
                                <option value="may">Mayo (may)</option>
                                <option value="jun">Junio (jun)</option>
                                <option value="jul">Julio (jul)</option>
                                <option value="ago">Agosto (ago)</option>
                                <option value="sep">Septiembre (sep)</option>
                                <option value="oct">Octubre (oct)</option>
                                <option value="nov">Noviembre (nov)</option>
                                <option value="dic">Diciembre (dic)</option>
                            </select>
                        </div>
                        <!-- Input de Año (MODIFICACIÓN: TextBox normal para mayor libertad de escritura) -->
                        <div class="col-12 col-md-4">
                            <input type="number" class="form-control" id="select_period_year" 
                                   value="<?php echo date('Y'); ?>" min="2000" max="2100" placeholder="Año">
                        </div>
                    </div>
                    <!-- Campo oculto que enviará la cadena formateada concatenada al backend (ej: "Quincena 1-15 may 2026") -->
                    <input type="hidden" id="period_description" name="period_description" value="<?php echo htmlspecialchars($payment['period_description'] ?? ''); ?>">
                    <!-- Vista previa en tiempo real para retroalimentación del usuario -->
                    <div class="mt-2 text-muted small">
                        <i class="bi bi-info-circle me-1"></i> Período generado automáticamente: <strong id="period_preview" class="text-primary"><?php echo htmlspecialchars($payment['period_description'] ?? ''); ?></strong>
                    </div>
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
                <a href="print.php?id=<?php echo encryptId($id); ?>" class="btn btn-outline-primary">
                    <i class="bi bi-printer me-2"></i>Ver Factura
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// MODIFICACIÓN: Cálculo automático de retención de renta del 10%
document.getElementById('gross_amount').addEventListener('input', function() {
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
});

// MODIFICACIÓN: Lógica de front-end para editar dinámicamente el período de pago
// Esta función precarga los selectores a partir de la cadena ya existente en la base de datos
// (ej: "Quincena 1-15 may 2026") y permite su modificación.
document.addEventListener('DOMContentLoaded', function() {
    const selectPart = document.getElementById('select_period_part');
    const selectMonth = document.getElementById('select_period_month');
    const selectYear = document.getElementById('select_period_year');
    const periodDescription = document.getElementById('period_description');
    const periodPreview = document.getElementById('period_preview');

    const monthAbbrs = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    
    // Obtener valor inicial que ya viene cargado de la base de datos
    const dbValue = periodDescription.value.trim();

    function parseAndSetPeriod(value) {
        if (!value) return;
        
        // El formato esperado es: "[Quincena XX-XX o Mes completo] [mes] [año]"
        // Por ejemplo: "Quincena 1-15 may 2026"
        const parts = value.split(' ');
        if (parts.length >= 3) {
            // Reconstruir la parte del período (por ejemplo, "Quincena" + "1-15" o "Mes" + "completo")
            let periodPart = "";
            let monthPart = "";
            let yearPart = "";

            if (parts[0] === 'Mes' && parts[1] === 'completo') {
                periodPart = "Mes completo";
                monthPart = parts[2];
                yearPart = parts[3];
            } else {
                periodPart = parts[0] + ' ' + parts[1]; // ej: "Quincena 1-15" o "Quincena 16-31"
                monthPart = parts[2];
                yearPart = parts[3];
            }

            // Seleccionar año directamente en el cuadro de texto (MODIFICACIÓN: TextBox)
            if (yearPart) {
                selectYear.value = yearPart;
            }

            // Seleccionar mes
            if (monthPart && monthAbbrs.includes(monthPart)) {
                selectMonth.value = monthPart;
            }

            // Seleccionar período
            if (periodPart) {
                // Ajustar dinámicamente si es quincena de fin de mes
                if (periodPart.startsWith('Quincena 16')) {
                    const monthIndex = monthAbbrs.indexOf(monthPart);
                    const yearVal = parseInt(yearPart);
                    const lastDay = new Date(yearVal, monthIndex + 1, 0).getDate();
                    const finalPartVal = `Quincena 16-${lastDay}`;
                    
                    // Configurar opción dinámica (index 1)
                    selectPart.options[1].value = finalPartVal;
                    selectPart.options[1].textContent = `Segunda Quincena (Días 16-${lastDay})`;
                    
                    // Si el valor guardado en BD es exactamente "Quincena 16-30", seleccionamos la tercera opción
                    if (periodPart === 'Quincena 16-30') {
                        selectPart.value = 'Quincena 16-30';
                    } else {
                        selectPart.value = finalPartVal;
                    }
                } else {
                    selectPart.value = periodPart;
                }
            }
        }
    }

    // Concatenar selectores y guardar
    function updatePeriodValue() {
        const part = selectPart.value;
        const month = selectMonth.value;
        const year = selectYear.value;
        
        // Si el usuario cambia el mes/año y estaba seleccionada la opción dinámica (índice 1), la recalculamos
        if (selectPart.selectedIndex === 1) {
            const monthIndex = monthAbbrs.indexOf(month);
            const yearVal = parseInt(year);
            const lastDay = new Date(yearVal, monthIndex + 1, 0).getDate();
            const formattedPart = `Quincena 16-${lastDay}`;
            
            selectPart.options[1].value = formattedPart;
            selectPart.options[1].textContent = `Segunda Quincena (Días 16-${lastDay})`;
            
            periodDescription.value = `${formattedPart} ${month} ${year}`;
        } else {
            periodDescription.value = `${part} ${month} ${year}`;
        }
        
        periodPreview.textContent = periodDescription.value;
    }

    // Escuchar eventos de cambio
    selectPart.addEventListener('change', updatePeriodValue);
    selectMonth.addEventListener('change', updatePeriodValue);
    selectYear.addEventListener('change', updatePeriodValue);
    selectYear.addEventListener('input', updatePeriodValue);

    // Precargar con el valor inicial de la base de datos
    if (dbValue) {
        parseAndSetPeriod(dbValue);
    }
    
    // Asegurar que la vista previa esté alineada
    updatePeriodValue();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
