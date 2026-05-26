<?php
/**
 * Inventario de Herramientas
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Herramientas - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();

// Filtros
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(name LIKE ? OR code LIKE ? OR brand LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereClauses[] = "current_status = ?";
    $params[] = $status;
}

$whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Obtener herramientas
$sql = "SELECT t.*,
               (SELECT COUNT(*) FROM tool_loans tl WHERE tl.tool_id = t.id AND tl.status = 'active') as active_loans
        FROM tools t
        {$whereClause}
        ORDER BY t.name";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$tools = $stmt->fetchAll();

// Contar por estado
$sqlCounts = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN current_status = 'available' THEN 1 ELSE 0 END) as available,
              SUM(CASE WHEN current_status = 'loaned' THEN 1 ELSE 0 END) as loaned,
              SUM(CASE WHEN current_status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
              SUM(CASE WHEN current_status IN ('damaged', 'lost') THEN 1 ELSE 0 END) as unavailable
              FROM tools";
$counts = $conn->query($sqlCounts)->fetch();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-tools me-2"></i>Inventario de Herramientas</h2>
    <a href="create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nueva Herramienta
    </a>
</div>

<!-- Resumen -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <h6>Disponibles</h6>
                <h3><?php echo $counts['available'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center">
                <h6>Prestadas</h6>
                <h3><?php echo $counts['loaned'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body text-center">
                <h6>Mantenimiento</h6>
                <h3><?php echo $counts['maintenance'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-danger text-white h-100">
            <div class="card-body text-center">
                <h6>No Usables</h6>
                <h3><?php echo $counts['unavailable'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-12 col-md-6">
                <input type="text" class="form-control" name="search" placeholder="Buscar por nombre, código o marca" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-12 col-md-4">
                <select class="form-select" name="status">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Todos los estados</option>
                    <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>Disponibles</option>
                    <option value="loaned" <?php echo $status === 'loaned' ? 'selected' : ''; ?>>Prestadas</option>
                    <option value="maintenance" <?php echo $status === 'maintenance' ? 'selected' : ''; ?>>Mantenimiento</option>
                    <option value="damaged" <?php echo $status === 'damaged' ? 'selected' : ''; ?>>Dañadas</option>
                    <option value="lost" <?php echo $status === 'lost' ? 'selected' : ''; ?>>Perdidas</option>
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
        <h5 class="mb-0"><i class="bi bi-list me-2"></i>Listado de Herramientas</h5>
        <span class="badge bg-secondary"><?php echo count($tools); ?> herramientas</span>
    </div>
    <div class="card-body p-0">
        <?php if (count($tools) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Marca/Modelo</th>
                            <th>Serial</th>
                            <th>Estado</th>
                            <th>Condición</th>
                            <th>Ubicación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tools as $tool): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($tool['code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($tool['name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($tool['brand'] ?? ''); ?>
                                    <?php if ($tool['model']): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($tool['model']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo htmlspecialchars($tool['serial_number'] ?? 'N/A'); ?></small></td>
                                <td>
                                    <?php $statusInfo = formatStatus($tool['current_status']); ?>
                                    <span class="badge <?php echo $statusInfo['class']; ?>"><?php echo $statusInfo['label']; ?></span>
                                    <?php if ($tool['active_loans'] > 0): ?>
                                        <br><small class="text-warning"><?php echo $tool['active_loans']; ?> préstamo(s)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $condInfo = formatStatus($tool['condition_rating']); ?>
                                    <span class="badge <?php echo $condInfo['class']; ?>"><?php echo $condInfo['label']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($tool['location'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo encryptId($tool['id']); ?>" class="btn btn-outline-warning" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($tool['current_status'] === 'available'): ?>
                                            <a href="../tool_loans/create.php?tool=<?php echo encryptId($tool['id']); ?>" class="btn btn-outline-info" title="Prestar">
                                                <i class="bi bi-hand-index-thumb"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-tools" style="font-size: 4rem;"></i>
                <p class="mt-3">No hay herramientas registradas</p>
                <a href="create.php" class="btn btn-primary">Registrar primera herramienta</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
