<?php
/**
 * Listado de Pagos a Sujetos Excluidos
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Pagos a Sujetos Excluidos - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();

// ============================================
// FILTROS Y BÚSQUEDA
// ============================================
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$subject = isset($_GET['subject']) ? (int)base64_decode($_GET['subject']) : 0;
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(ep.invoice_number LIKE ? OR es.first_name LIKE ? OR es.last_name LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereClauses[] = "ep.status = ?";
    $params[] = $status;
}

if ($subject > 0) {
    $whereClauses[] = "ep.subject_id = ?";
    $params[] = $subject;
}

if (!empty($dateFrom)) {
    $whereClauses[] = "ep.service_date >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereClauses[] = "ep.service_date <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// ============================================
// PAGINACIÓN
// ============================================
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 10;
$offset = ($page - 1) * $pageSize;

// Contar total
$sqlCount = "SELECT COUNT(*) as total 
             FROM excluded_payments ep
             JOIN excluded_subjects es ON ep.subject_id = es.id
             {$whereClause}";
$stmt = $conn->prepare($sqlCount);
$stmt->execute($params);
$totalRecords = $stmt->fetch()['total'];
$totalPages = ceil($totalRecords / $pageSize);

// ============================================
// OBTENER REGISTROS
// ============================================
$sql = "SELECT ep.*, 
               CONCAT(es.first_name, ' ', es.last_name) as subject_name,
               es.occupation,
               u.full_name as created_by_name
        FROM excluded_payments ep
        JOIN excluded_subjects es ON ep.subject_id = es.id
        LEFT JOIN users u ON ep.created_by = u.id
        {$whereClause}
        ORDER BY ep.service_date DESC, ep.created_at DESC
        LIMIT {$pageSize} OFFSET {$offset}";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// ============================================
// TOTALES
// ============================================
$sqlTotals = "SELECT 
              COUNT(*) as count,
              COALESCE(SUM(ep.gross_amount), 0) as total_gross,
              COALESCE(SUM(ep.withholding_tax), 0) as total_withheld,
              COALESCE(SUM(ep.net_amount), 0) as total_net
              FROM excluded_payments ep
              JOIN excluded_subjects es ON ep.subject_id = es.id
              {$whereClause}";
$stmt = $conn->prepare($sqlTotals);
$stmt->execute($params);
$totals = $stmt->fetch();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cash-coin me-2"></i>Pagos a Sujetos Excluidos</h2>
    <a href="create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nuevo Pago
    </a>
</div>

<!-- Resumen de Totales -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center">
                <h6 class="card-title">Total Pagos</h6>
                <h3 class="mb-0"><?php echo number_format($totals['count']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <h6 class="card-title">Total Bruto</h6>
                <h3 class="mb-0">$<?php echo number_format($totals['total_gross'], 2); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center">
                <h6 class="card-title">Total Neto</h6>
                <h3 class="mb-0">$<?php echo number_format($totals['total_net'], 2); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtros de Búsqueda</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-12 col-md-3">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Factura o nombre" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-12 col-md-2">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Todos</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Pagado</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label for="date_from" class="form-label">Desde</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            <div class="col-12 col-md-2">
                <label for="date_to" class="form-label">Hasta</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Pagos -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list me-2"></i>Listado de Pagos</h5>
        <span class="badge bg-secondary"><?php echo $totalRecords; ?> registros</span>
    </div>
    <div class="card-body p-0">
        <?php if (count($payments) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Fecha</th>
                            <th>Sujeto</th>
                            <th>Descripción</th>
                            <th>Bruto</th>
                            <th>Retención</th>
                            <th>Neto</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($payment['invoice_number']); ?></span>
                                </td>
                                <td><?php echo formatDate($payment['service_date']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($payment['subject_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($payment['occupation']); ?></small>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars(substr($payment['service_description'], 0, 30)); ?>...</small>
                                </td>
                                <td class="text-muted">$<?php echo number_format($payment['gross_amount'], 2); ?></td>
                                <td class="text-warning">$<?php echo number_format($payment['withholding_tax'], 2); ?></td>
                                <td class="text-success"><strong>$<?php echo number_format($payment['net_amount'], 2); ?></strong></td>
                                <td>
                                    <?php 
                                    $statusInfo = formatStatus($payment['status']);
                                    ?>
                                    <span class="badge <?php echo $statusInfo['class']; ?>">
                                        <?php echo $statusInfo['label']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="print.php?id=<?php echo base64_encode($payment['id']); ?>" 
                                           class="btn btn-outline-primary" target="_blank" title="Imprimir">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo base64_encode($payment['id']); ?>" 
                                           class="btn btn-outline-warning" title="Editar">
                                            <i class="bi bi-pencil"></i>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">
                                    <i class="bi bi-chevron-left"></i> Anterior
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php elseif (abs($i - $page) == 3): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">
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
                <h5 class="text-muted mt-3">No se encontraron pagos</h5>
                <p class="text-muted">Intente ajustar los filtros o registre un nuevo pago</p>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Registrar Pago
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
