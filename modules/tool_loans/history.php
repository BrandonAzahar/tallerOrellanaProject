<?php
/**
 * Historial de Préstamos
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Historial - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();

// Obtener historial completo
$sql = "SELECT tl.*, 
               t.name as tool_name, t.code as tool_code,
               CONCAT(es.first_name, ' ', es.last_name) as subject_name
        FROM tool_loans tl
        JOIN tools t ON tl.tool_id = t.id
        JOIN excluded_subjects es ON tl.subject_id = es.id
        WHERE tl.status IN ('returned', 'lost')
        ORDER BY tl.actual_return_date DESC, tl.created_at DESC
        LIMIT 50";
$loans = $conn->query($sql)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clock-history me-2"></i>Historial de Préstamos</h2>
    <a href="index.php" class="btn btn-outline-secondary">Préstamos Activos</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (count($loans) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Herramienta</th>
                            <th>Prestado a</th>
                            <th>Fecha Préstamo</th>
                            <th>Fecha Devolución</th>
                            <th>Condición</th>
                            <th>Estado</th>
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
                                <td><?php echo formatDate($loan['loan_date']); ?></td>
                                <td><?php echo formatDate($loan['actual_return_date']); ?></td>
                                <td>
                                    <small>
                                        Prestó: <?php echo ucfirst($loan['condition_at_loan']); ?><br>
                                        Devolvió: <?php echo ucfirst($loan['condition_at_return'] ?? 'N/A'); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php $statusInfo = formatStatus($loan['status']); ?>
                                    <span class="badge <?php echo $statusInfo['class']; ?>"><?php echo $statusInfo['label']; ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-clock-history" style="font-size: 4rem;"></i>
                <p class="mt-3">No hay historial de préstamos</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
