<?php
/**
 * Crear/Editar Herramienta
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Herramienta - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();
$errors = [];
$isEdit = false;
$tool = null;

// Verificar si es edición
if (isset($_GET['id'])) {
    $isEdit = true;
    $id = decryptId($_GET['id']);
    $sql = "SELECT * FROM tools WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $tool = $stmt->fetch();
    
    if (!$tool) {
        setFlash('error', 'Herramienta no encontrada');
        header('Location: index.php');
        exit();
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido';
    } else {
        $code = strtoupper(sanitizeDbInput($_POST['code'] ?? ''));
        $name = sanitizeDbInput($_POST['name'] ?? '');
        $brand = sanitizeDbInput($_POST['brand'] ?? '');
        $model = sanitizeDbInput($_POST['model'] ?? '');
        $serialNumber = sanitizeDbInput($_POST['serial_number'] ?? '');
        $purchaseDate = $_POST['purchase_date'] ?? null;
        $purchasePrice = (float)($_POST['purchase_price'] ?? 0);
        $currentStatus = $_POST['current_status'] ?? 'available';
        $conditionRating = $_POST['condition_rating'] ?? 'good';
        $location = sanitizeDbInput($_POST['location'] ?? '');
        $notes = sanitizeDbInput($_POST['notes'] ?? '');
        
        if (empty($code)) $errors[] = 'El código es obligatorio';
        if (empty($name)) $errors[] = 'El nombre es obligatorio';
        
        // Verificar código duplico
        if ($code && (!$isEdit || $code !== $tool['code'])) {
            $sql = "SELECT id FROM tools WHERE code = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$code]);
            if ($stmt->fetch()) {
                $errors[] = 'El código ya existe';
            }
        }
        
        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $sql = "UPDATE tools SET code=?, name=?, brand=?, model=?, serial_number=?, 
                            purchase_date=?, purchase_price=?, current_status=?, condition_rating=?, 
                            location=?, notes=? WHERE id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$code, $name, $brand, $model, $serialNumber, $purchaseDate, 
                                   $purchasePrice, $currentStatus, $conditionRating, $location, $notes, $id]);
                    setFlash('success', 'Herramienta actualizada');
                } else {
                    $sql = "INSERT INTO tools (code, name, brand, model, serial_number, purchase_date, 
                            purchase_price, current_status, condition_rating, location, notes) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$code, $name, $brand, $model, $serialNumber, $purchaseDate, 
                                   $purchasePrice, $currentStatus, $conditionRating, $location, $notes]);
                    setFlash('success', 'Herramienta registrada');
                }
                header('Location: index.php');
                exit();
            } catch (PDOException $e) {
                $errors[] = 'Error al guardar';
            }
        }
    }
}

$csrf_token = generateCsrfToken();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-tools me-2"></i><?php echo $isEdit ? 'Editar' : 'Nueva'; ?> Herramienta</h2>
    <a href="index.php" class="btn btn-outline-secondary">Volver</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <p class="mb-0"><i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="col-12 col-md-4">
                <label class="form-label">Código <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="code" 
                       value="<?php echo htmlspecialchars($tool['code'] ?? ($_POST['code'] ?? '')); ?>" required>
            </div>
            
            <div class="col-12 col-md-8">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" 
                       value="<?php echo htmlspecialchars($tool['name'] ?? ($_POST['name'] ?? '')); ?>" required>
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Marca</label>
                <input type="text" class="form-control" name="brand" 
                       value="<?php echo htmlspecialchars($tool['brand'] ?? ($_POST['brand'] ?? '')); ?>">
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Modelo</label>
                <input type="text" class="form-control" name="model" 
                       value="<?php echo htmlspecialchars($tool['model'] ?? ($_POST['model'] ?? '')); ?>">
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Número de Serial</label>
                <input type="text" class="form-control" name="serial_number" 
                       value="<?php echo htmlspecialchars($tool['serial_number'] ?? ($_POST['serial_number'] ?? '')); ?>">
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Fecha de Compra</label>
                <input type="date" class="form-control" name="purchase_date" 
                       value="<?php echo htmlspecialchars($tool['purchase_date'] ?? ($_POST['purchase_date'] ?? '')); ?>">
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Precio de Compra ($)</label>
                <input type="number" step="0.01" class="form-control" name="purchase_price" 
                       value="<?php echo htmlspecialchars($tool['purchase_price'] ?? ($_POST['purchase_price'] ?? '')); ?>">
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Estado</label>
                <select class="form-select" name="current_status">
                    <option value="available" <?php echo ($tool['current_status'] ?? '') === 'available' ? 'selected' : ''; ?>>Disponible</option>
                    <option value="loaned" <?php echo ($tool['current_status'] ?? '') === 'loaned' ? 'selected' : ''; ?>>Prestada</option>
                    <option value="maintenance" <?php echo ($tool['current_status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Mantenimiento</option>
                    <option value="damaged" <?php echo ($tool['current_status'] ?? '') === 'damaged' ? 'selected' : ''; ?>>Dañada</option>
                    <option value="lost" <?php echo ($tool['current_status'] ?? '') === 'lost' ? 'selected' : ''; ?>>Perdida</option>
                </select>
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Condición</label>
                <select class="form-select" name="condition_rating">
                    <option value="excellent" <?php echo ($tool['condition_rating'] ?? '') === 'excellent' ? 'selected' : ''; ?>>Excelente</option>
                    <option value="good" <?php echo ($tool['condition_rating'] ?? '') === 'good' ? 'selected' : ''; ?>>Buena</option>
                    <option value="fair" <?php echo ($tool['condition_rating'] ?? '') === 'fair' ? 'selected' : ''; ?>>Regular</option>
                    <option value="poor" <?php echo ($tool['condition_rating'] ?? '') === 'poor' ? 'selected' : ''; ?>>Mala</option>
                </select>
            </div>
            
            <div class="col-12 col-md-8">
                <label class="form-label">Ubicación</label>
                <input type="text" class="form-control" name="location" 
                       value="<?php echo htmlspecialchars($tool['location'] ?? ($_POST['location'] ?? '')); ?>">
            </div>
            
            <div class="col-12">
                <label class="form-label">Notas</label>
                <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($tool['notes'] ?? ($_POST['notes'] ?? '')); ?></textarea>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> <?php echo $isEdit ? 'Actualizar' : 'Guardar'; ?>
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
