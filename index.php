<?php
/**
 * Dashboard Principal
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Dashboard - Orellana';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$conn = getDbConnection();

// ============================================
// OBTENER ESTADÍSTICAS DEL DASHBOARD
// ============================================

// Total sujetos excluidos activos
$sql = "SELECT COUNT(*) as total FROM excluded_subjects WHERE status = 'active'";
$stmt = $conn->query($sql);
$totalSubjects = $stmt->fetch()['total'];

// Pagos realizados este mes
$sql = "SELECT COALESCE(SUM(net_amount), 0) as total 
        FROM excluded_payments 
        WHERE YEAR(service_date) = YEAR(CURRENT_DATE()) 
        AND MONTH(service_date) = MONTH(CURRENT_DATE())
        AND status = 'paid'";
$stmt = $conn->query($sql);
$monthPayments = $stmt->fetch()['total'];

// Herramientas prestadas actualmente
$sql = "SELECT COUNT(*) as total FROM tool_loans WHERE status = 'active'";
$stmt = $conn->query($sql);
$loanedTools = $stmt->fetch()['total'];

// Materiales con stock bajo
$sql = "SELECT COUNT(*) as total FROM materials 
        WHERE current_stock < min_stock AND status = 'active'";
$stmt = $conn->query($sql);
$lowStockMaterials = $stmt->fetch()['total'];

// Total pagos del año
$sql = "SELECT COALESCE(SUM(net_amount), 0) as total 
        FROM excluded_payments 
        WHERE YEAR(service_date) = YEAR(CURRENT_DATE())
        AND status = 'paid'";
$stmt = $conn->query($sql);
$yearPayments = $stmt->fetch()['total'];

// Total retenciones del año
$sql = "SELECT COALESCE(SUM(withholding_tax), 0) as total 
        FROM excluded_payments 
        WHERE YEAR(service_date) = YEAR(CURRENT_DATE())
        AND status = 'paid'";
$stmt = $conn->query($sql);
$yearWithholdings = $stmt->fetch()['total'];

// Últimos 5 pagos realizados
$sql = "SELECT ep.*, 
               CONCAT(es.first_name, ' ', es.last_name) as subject_name,
               es.occupation
        FROM excluded_payments ep
        JOIN excluded_subjects es ON ep.subject_id = es.id
        WHERE ep.status != 'cancelled'
        ORDER BY ep.service_date DESC, ep.created_at DESC
        LIMIT 5";
$stmt = $conn->query($sql);
$recentPayments = $stmt->fetchAll();

// Herramientas prestadas (próximas a vencer)
$sql = "SELECT tl.*, 
               t.name as tool_name,
               t.code as tool_code,
               CONCAT(es.first_name, ' ', es.last_name) as subject_name,
               DATEDIFF(tl.expected_return_date, CURDATE()) as days_remaining
        FROM tool_loans tl
        JOIN tools t ON tl.tool_id = t.id
        JOIN excluded_subjects es ON tl.subject_id = es.id
        WHERE tl.status = 'active'
        ORDER BY tl.expected_return_date ASC
        LIMIT 5";
$stmt = $conn->query($sql);
$activeLoans = $stmt->fetchAll();

// Materiales con stock crítico
$sql = "SELECT * FROM materials 
        WHERE current_stock < min_stock AND status = 'active'
        ORDER BY (current_stock / min_stock) ASC
        LIMIT 5";
$stmt = $conn->query($sql);
$criticalMaterials = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
            <span class="text-muted"><?php echo date('d/m/Y'); ?></span>
        </div>
    </div>
</div>

<!-- Tarjetas de Estadísticas -->
<div class="row g-4 mb-4">
    <!-- Sujetos Excluidos -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card dashboard-card card-primary-gradient h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="card-label mb-1">Sujetos Excluidos</p>
                        <h3 class="card-value mb-0"><?php echo number_format($totalSubjects); ?></h3>
                        <small class="opacity-75">Activos</small>
                    </div>
                    <i class="bi bi-people card-icon"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pb-0">
                <a href="modules/excluded_subjects/index.php" class="text-white text-decoration-none small">
                    Ver todos <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Pagos del Mes -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card dashboard-card card-success-gradient h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="card-label mb-1">Pagos del Mes</p>
                        <h3 class="card-value mb-0">$<?php echo number_format($monthPayments, 2); ?></h3>
                        <small class="opacity-75"><?php echo date('F Y'); ?></small>
                    </div>
                    <i class="bi bi-cash-coin card-icon"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pb-0">
                <a href="modules/excluded_payments/index.php" class="text-white text-decoration-none small">
                    Ver pagos <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Herramientas Prestadas -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card dashboard-card card-warning-gradient h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="card-label mb-1">Herramientas</p>
                        <h3 class="card-value mb-0"><?php echo number_format($loanedTools); ?></h3>
                        <small class="opacity-75">Prestadas</small>
                    </div>
                    <i class="bi bi-tools card-icon"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pb-0">
                <a href="modules/tool_loans/index.php" class="text-dark text-decoration-none small">
                    Ver préstamos <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Stock Bajo -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card dashboard-card card-danger-gradient h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="card-label mb-1">Stock Bajo</p>
                        <h3 class="card-value mb-0"><?php echo number_format($lowStockMaterials); ?></h3>
                        <small class="opacity-75">Materiales</small>
                    </div>
                    <i class="bi bi-exclamation-triangle card-icon"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pb-0">
                <a href="modules/materials/index.php" class="text-white text-decoration-none small">
                    Ver inventario <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Resumen Anual -->
<div class="row g-4 mb-4">
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Resumen Anual <?php echo date('Y'); ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Total Pagado</small>
                            <strong class="text-success fs-5">$<?php echo number_format($yearPayments, 2); ?></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Retenciones</small>
                            <strong class="text-warning fs-5">$<?php echo number_format($yearWithholdings, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Accesos Rápidos</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="modules/excluded_payments/create.php" class="btn btn-success">
                        <i class="bi bi-plus-circle me-2"></i>Registrar Nuevo Pago
                    </a>
                    <a href="modules/excluded_subjects/create.php" class="btn btn-primary">
                        <i class="bi bi-person-plus me-2"></i>Nuevo Sujeto Excluido
                    </a>
                    <a href="modules/tool_loans/create.php" class="btn btn-warning">
                        <i class="bi bi-hand-index-thumb me-2"></i>Registrar Préstamo
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tablas de Datos Recientes -->
<div class="row g-4">
    <!-- Últimos Pagos -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Últimos Pagos</h5>
                <a href="modules/excluded_payments/index.php" class="btn btn-sm btn-outline-light">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (count($recentPayments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Factura</th>
                                    <th>Sujeto</th>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPayments as $payment): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($payment['invoice_number']); ?></span>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($payment['subject_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($payment['occupation']); ?></small>
                                        </td>
                                        <td><?php echo formatDate($payment['service_date']); ?></td>
                                        <td class="text-success">
                                            <strong>$<?php echo number_format($payment['net_amount'], 2); ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">No hay pagos registrados</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Préstamos Activos -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Préstamos Activos</h5>
                <a href="modules/tool_loans/index.php" class="btn btn-sm btn-outline-light">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (count($activeLoans) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Herramienta</th>
                                    <th>Prestado a</th>
                                    <th>Vence</th>
                                    <th>Días</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeLoans as $loan): ?>
                                    <tr>
                                        <td>
                                            <div><?php echo htmlspecialchars($loan['tool_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($loan['tool_code']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($loan['subject_name']); ?></td>
                                        <td><?php echo formatDate($loan['expected_return_date']); ?></td>
                                        <td>
                                            <?php if ($loan['days_remaining'] < 0): ?>
                                                <span class="badge bg-danger">Vencido</span>
                                            <?php elseif ($loan['days_remaining'] <= 3): ?>
                                                <span class="badge bg-warning text-dark"><?php echo $loan['days_remaining']; ?> días</span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?php echo $loan['days_remaining']; ?> días</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle" style="font-size: 3rem;"></i>
                        <p class="mt-3">No hay préstamos activos</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Materiales con Stock Bajo -->
<?php if (count($criticalMaterials) > 0): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-exclamation-octagon me-2"></i>Alerta de Stock Bajo</h5>
                <a href="modules/materials/index.php" class="btn btn-sm btn-outline-light">Ver inventario</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Material</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                                <th>Faltante</th>
                                <th>Proveedor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($criticalMaterials as $material): ?>
                                <tr class="table-danger">
                                    <td><strong><?php echo htmlspecialchars($material['code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($material['name']); ?></td>
                                    <td class="text-danger"><strong><?php echo number_format($material['current_stock'], 2); ?></strong></td>
                                    <td><?php echo number_format($material['min_stock'], 2); ?></td>
                                    <td class="text-danger">-<?php echo number_format($material['min_stock'] - $material['current_stock'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($material['supplier']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
