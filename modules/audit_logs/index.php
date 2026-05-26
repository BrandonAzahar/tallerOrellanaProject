<?php
/**
 * Dashboard de Logs de Auditoría
 * Estructuras y Remodelaciones Orellana
 * Solo accesible para administradores
 */

$pageTitle = 'Logs de Auditoría - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/audit_utils.php';
require_once __DIR__ . '/../../config/database.php';

// Solo admin
requireAdmin();

$conn = getDbConnection();

// ============================================
// FILTROS
// ============================================
$search = $_GET['search'] ?? '';
$action = $_GET['action'] ?? 'all';
$module = $_GET['module'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$period = $_GET['period'] ?? 'month';

$filters = [];
if ($action !== 'all') $filters['action'] = $action;
if ($module !== 'all') $filters['module'] = $module;
if ($dateFrom) $filters['date_from'] = $dateFrom;
if ($dateTo) $filters['date_to'] = $dateTo;
if ($search) $filters['search'] = $search;

// ============================================
// PAGINACIÓN
// ============================================
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 20;

$result = getAuditLogs($conn, $filters, $page, $pageSize);
$logs = $result['logs'];
$total = $result['total'];
$totalPages = $result['totalPages'];
$page = $result['page'];

// ============================================
// ESTADÍSTICAS
// ============================================
$stats = getAuditStats($conn, $period);
$availableActions = getAuditActions($conn);
$availableModules = getAuditModules($conn);

// ============================================
// LIMPIEZA DE LOGS (solo si se solicita)
// ============================================
$cleanupMsg = '';
if (isset($_POST['cleanup']) && $_POST['cleanup'] === 'yes') {
    $days = (int)($_POST['days'] ?? 90);
    $deleted = cleanupOldAuditLogs($conn, $days);
    $cleanupMsg = "Se eliminaron {$deleted} registros anteriores a {$days} días.";
    setFlash('success', $cleanupMsg);
    header('Location: index.php');
    exit();
}

// Mapeo de acciones a labels
$actionLabels = [
    'create' => ['label' => 'Creación', 'class' => 'bg-success'],
    'update' => ['label' => 'Actualización', 'class' => 'bg-primary'],
    'delete' => ['label' => 'Eliminación', 'class' => 'bg-danger'],
    'login' => ['label' => 'Inicio de sesión', 'class' => 'bg-info text-dark'],
    'logout' => ['label' => 'Cierre de sesión', 'class' => 'bg-secondary'],
    'view' => ['label' => 'Visualización', 'class' => 'bg-light text-dark']
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-journal-text me-2"></i>Logs de Auditoría</h2>
    <div>
        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cleanupModal">
            <i class="bi bi-trash me-1"></i>Limpiar Logs
        </button>
    </div>
</div>

<!-- Estadísticas -->
<div class="row g-4 mb-4">
    <div class="col-12 col-md-3">
        <div class="card h-100 text-center">
            <div class="card-body">
                <h3 class="text-primary mb-0"><?php echo number_format($stats['total']); ?></h3>
                <small class="text-muted">Acciones (<?php echo $period === 'today' ? 'hoy' : ($period === 'week' ? 'esta semana' : 'este mes'); ?>)</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card h-100 text-center">
            <div class="card-body">
                <h3 class="text-success mb-0"><?php echo count($availableModules); ?></h3>
                <small class="text-muted">Módulos registrados</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card h-100 text-center">
            <div class="card-body">
                <h3 class="text-info mb-0"><?php echo count($availableActions); ?></h3>
                <small class="text-muted">Tipos de acción</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card h-100 text-center">
            <div class="card-body">
                <h3 class="text-warning mb-0"><?php echo number_format($total); ?></h3>
                <small class="text-muted">Resultados del filtro</small>
            </div>
        </div>
    </div>
</div>

<!-- Período de estadísticas -->
<div class="row g-4 mb-4">
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Acciones más frecuentes</h6>
                <div class="btn-group btn-group-sm">
                    <a href="?period=today&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action); ?>&module=<?php echo urlencode($module); ?>"
                       class="btn btn-outline-primary <?php echo $period === 'today' ? 'active' : ''; ?>">Hoy</a>
                    <a href="?period=week&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action); ?>&module=<?php echo urlencode($module); ?>"
                       class="btn btn-outline-primary <?php echo $period === 'week' ? 'active' : ''; ?>">Semana</a>
                    <a href="?period=month&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action); ?>&module=<?php echo urlencode($module); ?>"
                       class="btn btn-outline-primary <?php echo $period === 'month' ? 'active' : ''; ?>">Mes</a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($stats['by_action'])): ?>
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php foreach ($stats['by_action'] as $row): ?>
                                <?php $info = $actionLabels[$row['action']] ?? ['label' => $row['action'], 'class' => 'bg-secondary']; ?>
                                <tr>
                                    <td><span class="badge <?php echo $info['class']; ?>"><?php echo $info['label']; ?></span></td>
                                    <td class="text-end"><strong><?php echo number_format($row['count']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">Sin datos</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-people me-2"></i>Usuarios más activos</h6>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($stats['by_user'])): ?>
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php foreach ($stats['by_user'] as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td class="text-muted small">(<?php echo htmlspecialchars($row['username']); ?>)</td>
                                    <td class="text-end"><strong><?php echo number_format($row['count']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">Sin datos</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtros</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-12 col-md-3">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search"
                       placeholder="Usuario, módulo, acción"
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-12 col-md-2">
                <label for="action" class="form-label">Acción</label>
                <select class="form-select" id="action" name="action">
                    <option value="all" <?php echo $action === 'all' ? 'selected' : ''; ?>>Todas</option>
                    <?php foreach ($availableActions as $act): ?>
                        <?php $info = $actionLabels[$act] ?? ['label' => $act]; ?>
                        <option value="<?php echo htmlspecialchars($act); ?>"
                                <?php echo $action === $act ? 'selected' : ''; ?>>
                            <?php echo $info['label']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label for="module" class="form-label">Módulo</label>
                <select class="form-select" id="module" name="module">
                    <option value="all" <?php echo $module === 'all' ? 'selected' : ''; ?>>Todos</option>
                    <?php foreach ($availableModules as $mod): ?>
                        <option value="<?php echo htmlspecialchars($mod); ?>"
                                <?php echo $module === $mod ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($mod); ?>
                        </option>
                    <?php endforeach; ?>
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
            <div class="col-12 col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Logs -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list me-2"></i>Registro de Actividad</h5>
        <span class="badge bg-secondary"><?php echo number_format($total); ?> registros</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($logs)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Módulo</th>
                            <th>Detalle</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php $info = $actionLabels[$log['action']] ?? ['label' => $log['action'], 'class' => 'bg-secondary']; ?>
                            <tr>
                                <td><small><?php echo formatDateTime($log['created_at']); ?></small></td>
                                <td>
                                    <?php if ($log['user_full_name']): ?>
                                        <div><?php echo htmlspecialchars($log['user_full_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['username']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Sistema</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?php echo $info['class']; ?>"><?php echo $info['label']; ?></span></td>
                                <td><code><?php echo htmlspecialchars($log['module']); ?></code></td>
                                <td>
                                    <?php if ($log['record_id']): ?>
                                        <small>
                                            <?php
                                            $detail = $log['new_values'] ?? $log['old_values'];
                                            if ($detail) {
                                                $data = json_decode($detail, true);
                                                if (isset($data['first_name'], $data['last_name'])) {
                                                    echo htmlspecialchars($data['first_name'] . ' ' . $data['last_name']);
                                                } elseif (isset($data['invoice_number'])) {
                                                    echo htmlspecialchars($data['invoice_number']);
                                                } elseif (isset($data['username'])) {
                                                    echo htmlspecialchars($data['username']);
                                                } else {
                                                    echo 'ID: ' . $log['record_id'];
                                                }
                                            } else {
                                                echo 'ID: ' . $log['record_id'];
                                            }
                                            ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action); ?>&module=<?php echo urlencode($module); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&period=<?php echo urlencode($period); ?>">
                                    <i class="bi bi-chevron-left"></i> Anterior
                                </a>
                            </li>
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action); ?>&module=<?php echo urlencode($module); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&period=<?php echo urlencode($period); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action); ?>&module=<?php echo urlencode($module); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&period=<?php echo urlencode($period); ?>">
                                    Siguiente <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-journal-x" style="font-size: 3rem; color: #dee2e6;"></i>
                <h5 class="text-muted mt-3">No se encontraron registros</h5>
                <p class="text-muted">Intente ajustar los filtros</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de limpieza -->
<div class="modal fade" id="cleanupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Limpiar Logs Antiguos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <p>Esta acción eliminará los logs de auditoría antiguos. ¿Cuántos días desea mantener?</p>
                    <div class="mb-3">
                        <label for="days" class="form-label">Días a mantener</label>
                        <input type="number" class="form-control" id="days" name="days" value="90" min="7" max="365">
                    </div>
                    <input type="hidden" name="cleanup" value="yes">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar Logs</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
