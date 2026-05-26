<?php
/**
 * Reporte de Pagos Realizados
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Reporte de Pagos - Orellana';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/database.php';

$conn = getDbConnection();

// Filtros
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$subjectId = isset($_GET['subject']) ? decryptId($_GET['subject']) : 0;

$whereClauses = ["ep.service_date BETWEEN ? AND ?"];
$params = [$dateFrom, $dateTo];

if ($subjectId !== false && $subjectId > 0) {
    $whereClauses[] = "ep.subject_id = ?";
    $params[] = $subjectId;
}

$whereClause = implode(' AND ', $whereClauses);

// ============================================
// PAGINACIÓN
// ============================================
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 20;

// Contar total
$sqlCount = "SELECT COUNT(*) as total FROM excluded_payments ep JOIN excluded_subjects es ON ep.subject_id = es.id WHERE {$whereClause}";
$stmt = $conn->prepare($sqlCount);
$stmt->execute($params);
$totalRecords = $stmt->fetch()['total'];
$totalPages = max(1, ceil($totalRecords / $pageSize));
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $pageSize;

// Obtener pagos paginados
$sql = "SELECT ep.*,
               CONCAT(es.first_name, ' ', es.last_name) as subject_name,
               es.occupation, es.dui
        FROM excluded_payments ep
        JOIN excluded_subjects es ON ep.subject_id = es.id
        WHERE {$whereClause}
        ORDER BY ep.service_date DESC
        LIMIT {$pageSize} OFFSET {$offset}";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Totales (sin paginar - siempre muestra el total del período)
$sqlTotals = "SELECT
              COUNT(*) as count,
              COALESCE(SUM(ep.gross_amount), 0) as total_gross,
              COALESCE(SUM(ep.withholding_tax), 0) as total_withheld,
              COALESCE(SUM(ep.net_amount), 0) as total_net
              FROM excluded_payments ep
              JOIN excluded_subjects es ON ep.subject_id = es.id
              WHERE {$whereClause}";
$stmt = $conn->prepare($sqlTotals);
$stmt->execute($params);
$totals = $stmt->fetch();

// Sujetos para filtro
$sql = "SELECT id, CONCAT(first_name, ' ', last_name) as full_name FROM excluded_subjects WHERE status = 'active' ORDER BY last_name";
$subjects = $conn->query($sql)->fetchAll();

// Helper para construir URL de paginación
function buildReportUrl($page, $dateFrom, $dateTo, $subjectId) {
    $url = "?page={$page}&date_from=" . urlencode($dateFrom) . "&date_to=" . urlencode($dateTo);
    if ($subjectId > 0) {
        $url .= "&subject=" . encryptId($subjectId);
    }
    return $url;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-file-earmark-bar-graph me-2"></i>Reporte de Pagos</h2>
    <button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer me-2"></i>Imprimir
    </button>
</div>

<!-- Filtros -->
<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-12 col-md-3">
                <label class="form-label">Desde</label>
                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Hasta</label>
                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Sujeto</label>
                <select class="form-select" name="subject">
                    <option value="0">Todos</option>
                    <?php foreach ($subjects as $subj): ?>
                        <option value="<?php echo encryptId($subj['id']); ?>" <?php echo $subjectId == $subj['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subj['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Resumen -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Resumen del Período: <?php echo formatDate($dateFrom); ?> al <?php echo formatDate($dateTo); ?></h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <div class="p-3 bg-light rounded text-center">
                    <small class="text-muted d-block">Total Pagos</small>
                    <h4 class="mb-0 text-primary"><?php echo number_format($totals['count']); ?></h4>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="p-3 bg-light rounded text-center">
                    <small class="text-muted d-block">Total Bruto</small>
                    <h4 class="mb-0 text-success">$<?php echo number_format($totals['total_gross'], 2); ?></h4>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="p-3 bg-light rounded text-center">
                    <small class="text-muted d-block">Total Retenido</small>
                    <h4 class="mb-0 text-warning">$<?php echo number_format($totals['total_withheld'], 2); ?></h4>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="p-3 bg-light rounded text-center">
                    <small class="text-muted d-block">Total Neto</small>
                    <h4 class="mb-0 text-info">$<?php echo number_format($totals['total_net'], 2); ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detalle -->
<div class="card">
    <div class="card-body p-0">
        <?php if (count($payments) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Fecha</th>
                            <th>Sujeto</th>
                            <th>DUI</th>
                            <th>Ocupación</th>
                            <th>Bruto</th>
                            <th>Retención</th>
                            <th>Neto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['invoice_number']); ?></td>
                                <td><?php echo formatDate($p['service_date']); ?></td>
                                <td><?php echo htmlspecialchars($p['subject_name']); ?></td>
                                <td><small><?php echo htmlspecialchars($p['dui'] ?? 'N/A'); ?></small></td>
                                <td><?php echo htmlspecialchars($p['occupation'] ?? 'N/A'); ?></td>
                                <td>$<?php echo number_format($p['gross_amount'], 2); ?></td>
                                <td>$<?php echo number_format($p['withholding_tax'], 2); ?></td>
                                <td><strong>$<?php echo number_format($p['net_amount'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer no-print">
                    <nav aria-label="Paginación">
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo buildReportUrl($page - 1, $dateFrom, $dateTo, $subjectId !== false ? $subjectId : 0); ?>">
                                    <i class="bi bi-chevron-left"></i> Anterior
                                </a>
                            </li>
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildReportUrl($i, $dateFrom, $dateTo, $subjectId !== false ? $subjectId : 0); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo buildReportUrl($page + 1, $dateFrom, $dateTo, $subjectId !== false ? $subjectId : 0); ?>">
                                    Siguiente <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <small class="text-muted d-block text-center mt-2">Mostrando <?php echo count($payments); ?> de <?php echo number_format($totalRecords); ?> registros</small>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                <p class="mt-3">No hay pagos en el período seleccionado</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
@media print {
    .no-print, .navbar, footer, .btn { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
