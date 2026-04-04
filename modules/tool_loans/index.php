<?php
/**
 * Préstamos de Herramientas
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Préstamos - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();

// Filtros
$status = $_GET['status'] ?? 'active';

// Obtener préstamos
$sql = "SELECT tl.*, 
               t.name as tool_name, t.code as tool_code,
               CONCAT(es.first_name, ' ', es.last_name) as subject_name,
               es.dui as subject_dui,
               DATEDIFF(tl.expected_return_date, CURDATE()) as days_remaining
        FROM tool_loans tl
        JOIN tools t ON tl.tool_id = t.id
        JOIN excluded_subjects es ON tl.subject_id = es.id";

if ($status !== 'all') {
    $sql .= " WHERE tl.status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$status]);
} else {
    $stmt = $conn->query($sql);
}
$loans = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-hand-index-thumb me-2"></i>Préstamos de Herramientas</h2>
    <a href="create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nuevo Préstamo
    </a>
</div>

<!-- Pestañas de estado -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?php echo $status === 'active' ? 'active' : ''; ?>" 
           href="?status=active">Activos</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $status === 'returned' ? 'active' : ''; ?>" 
           href="?status=returned">Devueltos</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $status === 'overdue' ? 'active' : ''; ?>" 
           href="?status=overdue">Vencidos</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $status === 'all' ? 'active' : ''; ?>" 
           href="?status=all">Todos</a>
    </li>
</ul>

<div class="card">
    <div class="card-body p-0">
        <?php if (count($loans) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Herramienta</th>
                            <th>Prestado a</th>
                            <th>DUI</th>
                            <th>Fecha Préstamo</th>
                            <th>Fecha Devolución</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($loan['tool_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($loan['tool_code']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($loan['subject_name']); ?></td>
                                <td><small><?php echo htmlspecialchars($loan['subject_dui'] ?? 'N/A'); ?></small></td>
                                <td><?php echo formatDate($loan['loan_date']); ?></td>
                                <td>
                                    <?php echo formatDate($loan['expected_return_date']); ?>
                                    <?php if ($loan['status'] === 'active' && $loan['days_remaining'] < 0): ?>
                                        <br><span class="badge bg-danger">Vencido</span>
                                    <?php elseif ($loan['status'] === 'active' && $loan['days_remaining'] <= 3): ?>
                                        <br><span class="badge bg-warning text-dark"><?php echo $loan['days_remaining']; ?> días</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $statusInfo = formatStatus($loan['status']); ?>
                                    <span class="badge <?php echo $statusInfo['class']; ?>"><?php echo $statusInfo['label']; ?></span>
                                </td>
                                <td>
                                    <?php if ($loan['status'] === 'active'): ?>
                                        <a href="return.php?id=<?php echo base64_encode($loan['id']); ?>" 
                                           class="btn btn-sm btn-success">
                                            <i class="bi bi-check-circle"></i> Devolver
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-hand-index-thumb" style="font-size: 4rem;"></i>
                <p class="mt-3">No hay préstamos <?php echo $status !== 'all' ? "con estado '{$status}'" : ''; ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
