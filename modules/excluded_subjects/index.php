<?php
/**
 * Listado de Sujetos Excluidos
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Sujetos Excluidos - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();

// ============================================
// FILTROS Y BÚSQUEDA
// ============================================
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$occupation = $_GET['occupation'] ?? 'all';

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(es.first_name LIKE ? OR es.last_name LIKE ? OR es.dui LIKE ? OR es.nit LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereClauses[] = "es.status = ?";
    $params[] = $status;
}

if ($occupation !== 'all') {
    $whereClauses[] = "es.occupation = ?";
    $params[] = $occupation;
}

$whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// ============================================
// PAGINACIÓN
// ============================================
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 10;
$offset = ($page - 1) * $pageSize;

// Contar total de registros
$sqlCount = "SELECT COUNT(*) as total FROM excluded_subjects es {$whereClause}";
$stmt = $conn->prepare($sqlCount);
$stmt->execute($params);
$totalRecords = $stmt->fetch()['total'];
$totalPages = ceil($totalRecords / $pageSize);

// ============================================
// OBTENER REGISTROS
// ============================================
$sql = "SELECT es.*,
               (SELECT COUNT(*) FROM excluded_payments ep 
                WHERE ep.subject_id = es.id AND ep.status != 'cancelled') as payment_count,
               (SELECT COALESCE(SUM(ep.net_amount), 0) FROM excluded_payments ep 
                WHERE ep.subject_id = es.id AND ep.status != 'cancelled') as total_paid
        FROM excluded_subjects es
        {$whereClause}
        ORDER BY es.last_name, es.first_name
        LIMIT {$pageSize} OFFSET {$offset}";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$subjects = $stmt->fetchAll();

// ============================================
// OBTENER OCUPACIONES ÚNICAS PARA FILTRO
// ============================================
$sql = "SELECT DISTINCT occupation FROM excluded_subjects WHERE occupation IS NOT NULL AND occupation != '' ORDER BY occupation";
$occupations = $conn->query($sql)->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people me-2"></i>Sujetos Excluidos</h2>
    <a href="create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nuevo Sujeto
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtros de Búsqueda</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Nombre, DUI o NIT" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-12 col-md-3">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Todos</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Activos</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactivos</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label for="occupation" class="form-label">Ocupación</label>
                <select class="form-select" id="occupation" name="occupation">
                    <option value="all" <?php echo $occupation === 'all' ? 'selected' : ''; ?>>Todas</option>
                    <?php foreach ($occupations as $occ): ?>
                        <option value="<?php echo htmlspecialchars($occ); ?>" 
                                <?php echo $occupation === $occ ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($occ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Sujetos -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list me-2"></i>Listado de Sujetos Excluidos</h5>
        <span class="badge bg-secondary"><?php echo $totalRecords; ?> registros</span>
    </div>
    <div class="card-body p-0">
        <?php if (count($subjects) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="subjectsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>DUI</th>
                            <th>NIT</th>
                            <th>Ocupación</th>
                            <th>Teléfono</th>
                            <th>Pagos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td><?php echo $subject['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($subject['first_name'] . ' ' . $subject['last_name']); ?></strong>
                                </td>
                                <td><?php echo $subject['dui'] ? htmlspecialchars($subject['dui']) : '<span class="text-muted">N/A</span>'; ?></td>
                                <td><?php echo $subject['nit'] ? htmlspecialchars($subject['nit']) : '<span class="text-muted">N/A</span>'; ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($subject['occupation'] ?? 'N/A'); ?></span>
                                </td>
                                <td><?php echo $subject['phone'] ? htmlspecialchars($subject['phone']) : '<span class="text-muted">N/A</span>'; ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo $subject['payment_count']; ?> pagos<br>
                                        <strong>$<?php echo number_format($subject['total_paid'], 2); ?></strong>
                                    </small>
                                </td>
                                <td>
                                    <?php 
                                    $statusInfo = formatStatus($subject['status']);
                                    ?>
                                    <span class="badge <?php echo $statusInfo['class']; ?>">
                                        <?php echo $statusInfo['label']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo encryptId($subject['id']); ?>" 
                                           class="btn btn-outline-primary" title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo encryptId($subject['id']); ?>" 
                                           class="btn btn-outline-warning" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo encryptId($subject['id']); ?>" 
                                           class="btn btn-outline-danger delete-btn" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Paginación">
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&occupation=<?php echo urlencode($occupation); ?>">
                                    <i class="bi bi-chevron-left"></i> Anterior
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&occupation=<?php echo urlencode($occupation); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php elseif (abs($i - $page) == 3): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&occupation=<?php echo urlencode($occupation); ?>">
                                    Siguiente <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #dee2e6;"></i>
                <h5 class="text-muted mt-3">No se encontraron sujetos excluidos</h5>
                <p class="text-muted">Intente ajustar los filtros o registre un nuevo sujeto</p>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Registrar Sujeto
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Función para encriptar ID (simple base64 para este ejemplo)
function encryptId(id) {
    return btoa(id);
}

// Confirmar eliminación
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('¿Está seguro de eliminar este sujeto excluido? Esta acción no se puede deshacer.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
