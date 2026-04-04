<?php
/**
 * Inventario de Materiales
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Materiales - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();

// Filtros
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'all';

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(name LIKE ? OR code LIKE ? OR category LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($category !== 'all') {
    $whereClauses[] = "category = ?";
    $params[] = $category;
}

$whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Obtener materiales con alerta de stock bajo
$sql = "SELECT m.*,
               CASE WHEN current_stock < min_stock THEN 1 ELSE 0 END as is_low_stock
        FROM materials m
        {$whereClause}
        ORDER BY m.name";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$materials = $stmt->fetchAll();

// Contar stock bajo
$sqlCount = "SELECT COUNT(*) as count FROM materials WHERE current_stock < min_stock AND status = 'active'";
$lowStockCount = $conn->query($sqlCount)->fetch()['count'];

// Categorías únicas
$sql = "SELECT DISTINCT category FROM materials WHERE category IS NOT NULL AND category != '' ORDER BY category";
$categories = $conn->query($sql)->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-seam me-2"></i>Inventario de Materiales</h2>
    <a href="create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nuevo Material
    </a>
</div>

<!-- Alerta de stock bajo -->
<?php if ($lowStockCount > 0): ?>
<div class="alert alert-warning alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong><?php echo $lowStockCount; ?> material(es) con stock bajo</strong>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-12 col-md-6">
                <input type="text" class="form-control" name="search" placeholder="Buscar material" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-12 col-md-4">
                <select class="form-select" name="category">
                    <option value="all">Todas las categorías</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list me-2"></i>Materiales</h5>
        <span class="badge bg-secondary"><?php echo count($materials); ?> registros</span>
    </div>
    <div class="card-body p-0">
        <?php if (count($materials) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Stock</th>
                            <th>Mínimo</th>
                            <th>Precio Unit.</th>
                            <th>Proveedor</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $mat): ?>
                            <tr class="<?php echo $mat['is_low_stock'] ? 'table-danger' : ''; ?>">
                                <td><strong><?php echo htmlspecialchars($mat['code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($mat['name']); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($mat['category'] ?? 'N/A'); ?></span></td>
                                <td>
                                    <strong class="<?php echo $mat['is_low_stock'] ? 'text-danger' : ''; ?>">
                                        <?php echo number_format($mat['current_stock'], 2); ?> <?php echo htmlspecialchars($mat['unit_of_measure']); ?>
                                    </strong>
                                </td>
                                <td><?php echo number_format($mat['min_stock'], 2); ?></td>
                                <td>$<?php echo number_format($mat['unit_price'] ?? 0, 2); ?></td>
                                <td><?php echo htmlspecialchars($mat['supplier'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo base64_encode($mat['id']); ?>" class="btn btn-outline-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="movements.php?material=<?php echo base64_encode($mat['id']); ?>" class="btn btn-outline-info">
                                            <i class="bi bi-arrow-left-right"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-box-seam" style="font-size: 4rem;"></i>
                <p class="mt-3">No hay materiales registrados</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
