<?php
/**
 * Nuevo Préstamo de Herramienta
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Nuevo Préstamo - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();
$errors = [];

// Obtener herramienta si viene en URL
$toolId = isset($_GET['tool']) ? (int)base64_decode($_GET['tool']) : 0;

// Obtener herramientas disponibles
$sql = "SELECT * FROM tools WHERE current_status = 'available' ORDER BY name";
$tools = $conn->query($sql)->fetchAll();

// Obtener sujetos activos
$sql = "SELECT id, CONCAT(first_name, ' ', last_name) as full_name FROM excluded_subjects WHERE status = 'active' ORDER BY last_name";
$subjects = $conn->query($sql)->fetchAll();

// Procesar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido';
    } else {
        $toolId = (int)($_POST['tool_id'] ?? 0);
        $subjectId = (int)($_POST['subject_id'] ?? 0);
        $loanDate = $_POST['loan_date'] ?? date('Y-m-d');
        $expectedReturnDate = $_POST['expected_return_date'] ?? '';
        $conditionAtLoan = $_POST['condition_at_loan'] ?? 'good';
        $notes = sanitizeDbInput($_POST['notes'] ?? '');
        
        if ($toolId <= 0) $errors[] = 'Seleccione una herramienta';
        if ($subjectId <= 0) $errors[] = 'Seleccione un sujeto';
        
        // Verificar que la herramienta esté disponible
        if ($toolId > 0) {
            $sql = "SELECT current_status FROM tools WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$toolId]);
            $tool = $stmt->fetch();
            if ($tool && $tool['current_status'] !== 'available') {
                $errors[] = 'La herramienta no está disponible';
            }
        }
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Crear préstamo
                $sql = "INSERT INTO tool_loans (tool_id, subject_id, loan_date, expected_return_date, 
                        condition_at_loan, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$toolId, $subjectId, $loanDate, $expectedReturnDate, $conditionAtLoan, $notes, getCurrentUserId()]);
                
                // Actualizar estado de herramienta
                $sql = "UPDATE tools SET current_status = 'loaned' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$toolId]);
                
                $conn->commit();
                setFlash('success', 'Préstamo registrado');
                header('Location: index.php');
                exit();
            } catch (Exception $e) {
                $conn->rollBack();
                $errors[] = 'Error al registrar';
            }
        }
    }
}

$csrf_token = generateCsrfToken();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-hand-index-thumb me-2"></i>Nuevo Préstamo</h2>
    <a href="index.php" class="btn btn-outline-secondary">Volver</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php echo implode('<br>', $errors); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="col-12 col-md-6">
                <label class="form-label">Herramienta <span class="text-danger">*</span></label>
                <select class="form-select" name="tool_id" required>
                    <option value="">Seleccionar herramienta</option>
                    <?php foreach ($tools as $tool): ?>
                        <option value="<?php echo $tool['id']; ?>" <?php echo $toolId == $tool['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tool['name']); ?> (<?php echo htmlspecialchars($tool['code']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-12 col-md-6">
                <label class="form-label">Prestado a <span class="text-danger">*</span></label>
                <select class="form-select" name="subject_id" required>
                    <option value="">Seleccionar sujeto</option>
                    <?php foreach ($subjects as $subj): ?>
                        <option value="<?php echo $subj['id']; ?>">
                            <?php echo htmlspecialchars($subj['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Fecha de Préstamo</label>
                <input type="date" class="form-control" name="loan_date" value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Fecha Esperada de Devolución</label>
                <input type="date" class="form-control" name="expected_return_date">
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Condición al Prestar</label>
                <select class="form-select" name="condition_at_loan">
                    <option value="excellent">Excelente</option>
                    <option value="good" selected>Buena</option>
                    <option value="fair">Regular</option>
                    <option value="poor">Mala</option>
                </select>
            </div>
            
            <div class="col-12">
                <label class="form-label">Notas</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Registrar Préstamo
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
