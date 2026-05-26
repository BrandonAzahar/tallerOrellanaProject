<?php
/**
 * Ver Detalle de Sujeto Excluido
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Detalle del Sujeto - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/audit_utils.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();

// Obtener ID y desencriptar
$id = isset($_GET['id']) ? decryptId($_GET['id']) : 0;

if ($id === false || $id <= 0) {
    setFlash('error', 'Sujeto no válido');
    header('Location: index.php');
    exit();
}

// Obtener datos del sujeto
$sql = "SELECT es.*,
               (SELECT COUNT(*) FROM excluded_payments ep 
                WHERE ep.subject_id = es.id AND ep.status != 'cancelled') as payment_count,
               (SELECT COALESCE(SUM(ep.gross_amount), 0) FROM excluded_payments ep 
                WHERE ep.subject_id = es.id AND ep.status != 'cancelled') as total_gross,
               (SELECT COALESCE(SUM(ep.withholding_tax), 0) FROM excluded_payments ep 
                WHERE ep.subject_id = es.id AND ep.status != 'cancelled') as total_withheld,
               (SELECT COALESCE(SUM(ep.net_amount), 0) FROM excluded_payments ep 
                WHERE ep.subject_id = es.id AND ep.status != 'cancelled') as total_net
        FROM excluded_subjects es
        WHERE es.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$subject = $stmt->fetch();

if (!$subject) {
    setFlash('error', 'Sujeto no encontrado');
    header('Location: index.php');
    exit();
}

// Registrar vista en logs de auditoría
logAudit($conn, 'view', 'excluded_subjects', $id, 'excluded_subjects', null, [
    'first_name' => $subject['first_name'],
    'last_name' => $subject['last_name']
]);

// Obtener historial de pagos
$sql = "SELECT ep.*, u.full_name as created_by_name
        FROM excluded_payments ep
        LEFT JOIN users u ON ep.created_by = u.id
        WHERE ep.subject_id = ? AND ep.status != 'cancelled'
        ORDER BY ep.service_date DESC, ep.created_at DESC
        LIMIT 20";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$payments = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-badge me-2"></i>Detalle del Sujeto Excluido</h2>
    <div class="btn-group">
        <a href="edit.php?id=<?php echo encryptId($id); ?>" class="btn btn-warning">
            <i class="bi bi-pencil me-2"></i>Editar
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<!-- Datos del Sujeto -->
<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>Información Personal</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" 
                         style="width: 100px; height: 100px; font-size: 2.5rem; color: var(--primary-color);">
                        <i class="bi bi-person"></i>
                    </div>
                </div>
                <h4 class="text-center"><?php echo htmlspecialchars($subject['first_name'] . ' ' . $subject['last_name']); ?></h4>
                <p class="text-center text-muted"><?php echo htmlspecialchars($subject['occupation'] ?? 'N/A'); ?></p>
                
                <hr>
                
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="text-muted">DUI:</th>
                        <td><?php echo $subject['dui'] ? htmlspecialchars($subject['dui']) : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">NIT:</th>
                        <td><?php echo $subject['nit'] ? htmlspecialchars($subject['nit']) : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Teléfono:</th>
                        <td><?php echo $subject['phone'] ? htmlspecialchars($subject['phone']) : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Estado:</th>
                        <td>
                            <?php 
                            $statusInfo = formatStatus($subject['status']);
                            ?>
                            <span class="badge <?php echo $statusInfo['class']; ?>">
                                <?php echo $statusInfo['label']; ?>
                            </span>
                        </td>
                    </tr>
                </table>
                
                <?php if (!empty($subject['address'])): ?>
                    <hr>
                    <div>
                        <strong><i class="bi bi-geo-alt me-2"></i>Dirección:</strong>
                        <p class="mb-0"><?php echo htmlspecialchars($subject['address']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Resumen de Pagos -->
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Resumen de Pagos</h5>
                <a href="../excluded_payments/create.php?subject=<?php echo encryptId($id); ?>" class="btn btn-sm btn-light">
                    <i class="bi bi-plus-circle me-1"></i>Nuevo Pago
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-light rounded text-center">
                            <small class="text-muted d-block">Total Pagos</small>
                            <h4 class="mb-0 text-primary"><?php echo number_format($subject['payment_count']); ?></h4>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-light rounded text-center">
                            <small class="text-muted d-block">Total Bruto</small>
                            <h4 class="mb-0 text-success">$<?php echo number_format($subject['total_gross'], 2); ?></h4>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-light rounded text-center">
                            <small class="text-muted d-block">Total Neto</small>
                            <h4 class="mb-0 text-info">$<?php echo number_format($subject['total_net'], 2); ?></h4>
                        </div>
                    </div>
                </div>
                
                <h6 class="border-bottom pb-2"><i class="bi bi-clock-history me-2"></i>Últimos Pagos</h6>
                
                <?php if (count($payments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Factura</th>
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                    <th>Bruto</th>
                                    <th>Retención</th>
                                    <th>Neto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td>
                                            <a href="../excluded_payments/print.php?id=<?php echo encryptId($payment['id']); ?>" 
                                               target="_blank" class="text-decoration-none">
                                                <?php echo htmlspecialchars($payment['invoice_number']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo formatDate($payment['service_date']); ?></td>
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($subject['payment_count'] > 20): ?>
                        <div class="text-center">
                            <a href="../excluded_payments/index.php?subject=<?php echo encryptId($id); ?>" class="btn btn-sm btn-outline-primary">
                                Ver todos los pagos <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-2">No hay pagos registrados</p>
                        <a href="../excluded_payments/create.php?subject=<?php echo encryptId($id); ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Registrar Primer Pago
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
