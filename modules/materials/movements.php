<?php
/**
 * Movimientos de Materiales
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Movimientos - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();
$errors = [];

// Obtener material
$materialId = isset($_GET['material']) ? (int)base64_decode($_GET['material']) : 0;
$material = null;

if ($materialId > 0) {
    $sql = "SELECT * FROM materials WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$materialId]);
    $material = $stmt->fetch();
}

// Procesar nuevo movimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido';
    } else {
        $matId = (int)($_POST['material_id'] ?? 0);
        $movementType = $_POST['movement_type'] ?? 'in';
        $quantity = (float)($_POST['quantity'] ?? 0);
        $movementDate = $_POST['movement_date'] ?? date('Y-m-d H:i:s');
        $referenceType = sanitizeDbInput($_POST['reference_type'] ?? '');
        $notes = sanitizeDbInput($_POST['notes'] ?? '');
        
        if ($matId <= 0) $errors[] = 'Material no válido';
        if ($quantity <= 0) $errors[] = 'La cantidad debe ser mayor a 0';
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Registrar movimiento
                $sql = "INSERT INTO material_movements (material_id, movement_type, quantity, movement_date, 
                        reference_type, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$matId, $movementType, $quantity, $movementDate, $referenceType, $notes, getCurrentUserId()]);
                
                // Actualizar stock
                if ($movementType === 'in') {
                    $sql = "UPDATE materials SET current_stock = current_stock + ? WHERE id = ?";
                } else {
                    $sql = "UPDATE materials SET current_stock = current_stock - ? WHERE id = ?";
                }
                $stmt = $conn->prepare($sql);
                $stmt->execute([$quantity, $matId]);
                
                $conn->commit();
                setFlash('success', 'Movimiento registrado');
                
                // Recargar material
                $sql = "SELECT * FROM materials WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$matId]);
                $material = $stmt->fetch();
                
            } catch (Exception $e) {
                $conn->rollBack();
                $errors[] = 'Error al registrar';
            }
        }
    }
}

// Obtener movimientos del material
if ($materialId > 0) {
    $sql = "SELECT mm.*, u.full_name as created_by_name
            FROM material_movements mm
            LEFT JOIN users u ON mm.created_by = u.id
            WHERE mm.material_id = ?
            ORDER BY mm.movement_date DESC
            LIMIT 50";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$materialId]);
    $movements = $stmt->fetchAll();
} else {
    $movements = [];
}

// Obtener todos los materiales para el selector
$sql = "SELECT id, code, name FROM materials WHERE status = 'active' ORDER BY name";
$allMaterials = $conn->query($sql)->fetchAll();

$csrf_token = generateCsrfToken();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-arrow-left-right me-2"></i>Movimientos de Materiales</h2>
    <a href="index.php" class="btn btn-outline-secondary">Volver</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php echo implode('<br>', $errors); ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Formulario -->
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Nuevo Movimiento</h5>
            </div>
            <div class="card-body">
                <?php if ($material): ?>
                    <div class="alert alert-info">
                        <strong>Material:</strong> <?php echo htmlspecialchars($material['name']); ?><br>
                        <strong>Stock actual:</strong> <?php echo number_format($material['current_stock'], 2); ?> <?php echo htmlspecialchars($material['unit_of_measure']); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="material_id" value="<?php echo $materialId; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Material</label>
                        <select class="form-select" name="material_id" onchange="location.href='movements.php?material='+this.value" required>
                            <option value="">Seleccionar</option>
                            <?php foreach ($allMaterials as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo $materialId == $m['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['code']); ?> - <?php echo htmlspecialchars($m['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo de Movimiento</label>
                        <select class="form-select" name="movement_type" required>
                            <option value="in">Entrada (Agregar)</option>
                            <option value="out">Salida (Retirar)</option>
                            <option value="adjustment">Ajuste</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cantidad</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" name="quantity" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="datetime-local" class="form-control" name="movement_date" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Referencia</label>
                        <input type="text" class="form-control" name="reference_type" placeholder="ej: Compra, Proyecto X">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> Registrar Movimiento
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Historial -->
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Historial de Movimientos</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($movements) > 0): ?>
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Referencia</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movements as $mov): ?>
                                <tr>
                                    <td><?php echo formatDateTime($mov['movement_date']); ?></td>
                                    <td>
                                        <?php 
                                        $typeClass = $mov['movement_type'] === 'in' ? 'bg-success' : ($mov['movement_type'] === 'out' ? 'bg-danger' : 'bg-warning');
                                        $typeLabel = $mov['movement_type'] === 'in' ? 'Entrada' : ($mov['movement_type'] === 'out' ? 'Salida' : 'Ajuste');
                                        ?>
                                        <span class="badge <?php echo $typeClass; ?>"><?php echo $typeLabel; ?></span>
                                    </td>
                                    <td>
                                        <strong class="<?php echo $mov['movement_type'] === 'in' ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $mov['movement_type'] === 'in' ? '+' : '-'; ?><?php echo number_format($mov['quantity'], 2); ?>
                                        </strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($mov['reference_type'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($mov['created_by_name'] ?? 'Sistema'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-2">Seleccione un material para ver sus movimientos</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
