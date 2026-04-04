<?php
/**
 * Crear/Editar Material
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Material - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();
$errors = [];
$isEdit = false;
$material = null;

if (isset($_GET['id'])) {
    $isEdit = true;
    $id = (int)base64_decode($_GET['id']);
    $sql = "SELECT * FROM materials WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $material = $stmt->fetch();
    if (!$material) {
        setFlash('error', 'Material no encontrado');
        header('Location: index.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido';
    } else {
        $code = strtoupper(sanitizeDbInput($_POST['code'] ?? ''));
        $name = sanitizeDbInput($_POST['name'] ?? '');
        $description = sanitizeDbInput($_POST['description'] ?? '');
        $category = sanitizeDbInput($_POST['category'] ?? '');
        $unitOfMeasure = sanitizeDbInput($_POST['unit_of_measure'] ?? 'unidad');
        $currentStock = (float)($_POST['current_stock'] ?? 0);
        $minStock = (float)($_POST['min_stock'] ?? 0);
        $unitPrice = (float)($_POST['unit_price'] ?? 0);
        $supplier = sanitizeDbInput($_POST['supplier'] ?? '');
        $location = sanitizeDbInput($_POST['location'] ?? '');
        
        if (empty($code)) $errors[] = 'El código es obligatorio';
        if (empty($name)) $errors[] = 'El nombre es obligatorio';
        
        if ($code && (!$isEdit || $code !== $material['code'])) {
            $sql = "SELECT id FROM materials WHERE code = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$code]);
            if ($stmt->fetch()) $errors[] = 'El código ya existe';
        }
        
        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $sql = "UPDATE materials SET code=?, name=?, description=?, category=?, unit_of_measure=?,
                            current_stock=?, min_stock=?, unit_price=?, supplier=?, location=? WHERE id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$code, $name, $description, $category, $unitOfMeasure, $currentStock, 
                                   $minStock, $unitPrice, $supplier, $location, $id]);
                    setFlash('success', 'Material actualizado');
                } else {
                    $sql = "INSERT INTO materials (code, name, description, category, unit_of_measure,
                            current_stock, min_stock, unit_price, supplier, location) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$code, $name, $description, $category, $unitOfMeasure, $currentStock, 
                                   $minStock, $unitPrice, $supplier, $location]);
                    setFlash('success', 'Material registrado');
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
    <h2><?php echo $isEdit ? 'Editar' : 'Nuevo'; ?> Material</h2>
    <a href="index.php" class="btn btn-outline-secondary">Volver</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php echo implode('<br>', $errors); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="col-12 col-md-4">
                <label class="form-label">Código (SKU) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="code" 
                       value="<?php echo htmlspecialchars($material['code'] ?? ($_POST['code'] ?? '')); ?>" required>
            </div>
            
            <div class="col-12 col-md-8">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" 
                       value="<?php echo htmlspecialchars($material['name'] ?? ($_POST['name'] ?? '')); ?>" required>
            </div>
            
            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea class="form-control" name="description" rows="2"><?php echo htmlspecialchars($material['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Categoría</label>
                <select class="form-select" name="category">
                    <option value="">Seleccionar</option>
                    <option value="cementos" <?php echo ($material['category'] ?? '') === 'cementos' ? 'selected' : ''; ?>>Cementos</option>
                    <option value="aceros" <?php echo ($material['category'] ?? '') === 'aceros' ? 'selected' : ''; ?>>Aceros</option>
                    <option value="agregados" <?php echo ($material['category'] ?? '') === 'agregados' ? 'selected' : ''; ?>>Agregados</option>
                    <option value="herramientas_manuales" <?php echo ($material['category'] ?? '') === 'herramientas_manuales' ? 'selected' : ''; ?>>Herramientas Manuales</option>
                    <option value="seguridad" <?php echo ($material['category'] ?? '') === 'seguridad' ? 'selected' : ''; ?>>Seguridad</option>
                    <option value="otros" <?php echo ($material['category'] ?? '') === 'otros' ? 'selected' : ''; ?>>Otros</option>
                </select>
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Unidad de Medida</label>
                <select class="form-select" name="unit_of_measure">
                    <option value="unidad" <?php echo ($material['unit_of_measure'] ?? '') === 'unidad' ? 'selected' : ''; ?>>Unidad</option>
                    <option value="kg" <?php echo ($material['unit_of_measure'] ?? '') === 'kg' ? 'selected' : ''; ?>>Kilogramo (kg)</option>
                    <option value="metro" <?php echo ($material['unit_of_measure'] ?? '') === 'metro' ? 'selected' : ''; ?>>Metro</option>
                    <option value="litro" <?php echo ($material['unit_of_measure'] ?? '') === 'litro' ? 'selected' : ''; ?>>Litro</option>
                    <option value="caja" <?php echo ($material['unit_of_measure'] ?? '') === 'caja' ? 'selected' : ''; ?>>Caja</option>
                    <option value="saco" <?php echo ($material['unit_of_measure'] ?? '') === 'saco' ? 'selected' : ''; ?>>Saco</option>
                    <option value="barra" <?php echo ($material['unit_of_measure'] ?? '') === 'barra' ? 'selected' : ''; ?>>Barra</option>
                    <option value="m3" <?php echo ($material['unit_of_measure'] ?? '') === 'm3' ? 'selected' : ''; ?>>Metro cúbico (m³)</option>
                </select>
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Stock Actual</label>
                <input type="number" step="0.01" class="form-control" name="current_stock" 
                       value="<?php echo htmlspecialchars($material['current_stock'] ?? '0'); ?>">
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Stock Mínimo (Alerta)</label>
                <input type="number" step="0.01" class="form-control" name="min_stock" 
                       value="<?php echo htmlspecialchars($material['min_stock'] ?? '0'); ?>">
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label">Precio Unitario ($)</label>
                <input type="number" step="0.01" class="form-control" name="unit_price" 
                       value="<?php echo htmlspecialchars($material['unit_price'] ?? '0'); ?>">
            </div>
            
            <div class="col-12 col-md-6">
                <label class="form-label">Proveedor</label>
                <input type="text" class="form-control" name="supplier" 
                       value="<?php echo htmlspecialchars($material['supplier'] ?? ''); ?>">
            </div>
            
            <div class="col-12 col-md-6">
                <label class="form-label">Ubicación en Almacén</label>
                <input type="text" class="form-control" name="location" 
                       value="<?php echo htmlspecialchars($material['location'] ?? ''); ?>">
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
