<?php
/**
 * Crear Nuevo Pago a Sujeto Excluido
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/audit_utils.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/correlation_utils.php';

$pageTitle = 'Nuevo Pago - Orellana';
$conn = getDbConnection();
$errors = [];

// Obtener sujeto excluido si viene en la URL
$subjectId = isset($_GET['subject']) ? decryptId($_GET['subject']) : 0;

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

                // Registrar en logs de auditoría
                logAudit($conn, 'create', 'excluded_payments', $paymentId, 'excluded_payments', null, [
                    'invoice_number' => $invoiceNumber,
                    'subject_id' => $subjectId,
                    'gross_amount' => $grossAmount,
                    'net_amount' => $netAmount,
                    'withholding_tax' => $withholdingTax
                ]);

                // Confirmar transacción
                $conn->commit();

                // MODIFICACIÓN: En lugar de redirigir directamente al formato de factura (impresión),
                // guardamos el registro y redirigimos al listado general de pagos (historial)
                setFlash('success', 'Pago registrado exitosamente. Factura: ' . $invoiceNumber);
                header('Location: index.php');
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
                
                <!-- Período de Servicio (Selectores Dinámicos de Front-End) -->
                <!-- MODIFICACIÓN: Se reemplaza el campo de texto libre por selectores guiados de Período (quincenas/mes completo), Mes (abreviado) y Año -->
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
                    <input type="hidden" id="period_description" name="period_description" value="<?php echo htmlspecialchars($periodDescription); ?>">
                    <!-- Vista previa en tiempo real para retroalimentación del usuario -->
                    <div class="mt-2 text-muted small">
                        <i class="bi bi-info-circle me-1"></i> Período generado automáticamente: <strong id="period_preview" class="text-primary"><?php echo htmlspecialchars($periodDescription); ?></strong>
                    </div>
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
// MODIFICACIÓN: Cálculo automático de retención de renta del 10%
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

// MODIFICACIÓN: Lógica de front-end para generar dinámicamente el período de pago
// Esta función concatena los selectores de Quincena, Mes abreviado y Año en una sola cadena
// para guardarla en la base de datos de manera limpia.
document.addEventListener('DOMContentLoaded', function() {
    const selectPart = document.getElementById('select_period_part');
    const selectMonth = document.getElementById('select_period_month');
    const selectYear = document.getElementById('select_period_year');
    const periodDescription = document.getElementById('period_description');
    const periodPreview = document.getElementById('period_preview');

    // Abreviaciones de los meses de 3 letras en español (formato requerido, ej: "may")
    const monthAbbrs = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    
    // Autocompletado del período inicial basado en la fecha de hoy o la fecha del servicio seleccionada
    const serviceDateInput = document.getElementById('service_date');

    function autodetectPeriodFromDate(dateString) {
        if (!dateString) return;
        const dateParts = dateString.split('-');
        if (dateParts.length !== 3) return;
        
        const yearStr = dateParts[0];
        const monthIndex = parseInt(dateParts[1]) - 1;
        const dayVal = parseInt(dateParts[2]);

        // Autoseleccionar mes y año
        selectMonth.value = monthAbbrs[monthIndex];
        selectYear.value = yearStr;

        // Determinar el último día de este mes en particular
        const lastDay = new Date(parseInt(yearStr), monthIndex + 1, 0).getDate();
        
        // Actualizar dinámicamente la segunda opción (índice 1) para que se adapte al mes real
        const formattedPart = `Quincena 16-${lastDay}`;
        selectPart.options[1].value = formattedPart;
        selectPart.options[1].textContent = `Segunda Quincena (Días 16-${lastDay})`;

        // Autoseleccionar quincena según el día del servicio
        if (dayVal <= 15) {
            selectPart.value = 'Quincena 1-15';
        } else {
            // Si el mes tiene exactamente 30 días, autoselecciona la tercera opción estática (16-30)
            if (lastDay === 30) {
                selectPart.value = 'Quincena 16-30';
            } else {
                // De lo contrario, autoselecciona la opción dinámica (16-lastDay)
                selectPart.value = formattedPart;
            }
        }
        
        updatePeriodValue();
    }

    // Función para concatenar los selectores y actualizar el valor oculto
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

    // Detectar cambios en los selectores del período
    selectPart.addEventListener('change', updatePeriodValue);
    selectMonth.addEventListener('change', updatePeriodValue);
    selectYear.addEventListener('change', updatePeriodValue);
    selectYear.addEventListener('input', updatePeriodValue);

    // Detectar si el usuario cambia la fecha de servicio, para re-sugerir el período ideal
    serviceDateInput.addEventListener('change', function() {
        autodetectPeriodFromDate(this.value);
    });

    // Inicializar autodetectando la fecha por defecto cargada
    autodetectPeriodFromDate(serviceDateInput.value);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
