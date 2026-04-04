<?php
/**
 * Reporte de Stock de Materiales
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Reporte de Materiales - Orellana';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$conn = getDbConnection();

// Obtener materiales
$sql = "SELECT m.*,
               CASE WHEN current_stock < min_stock THEN 1 ELSE 0 END as is_low_stock,
               (min_stock - current_stock) as shortage
        FROM materials m
        WHERE m.status = 'active'
        ORDER BY m.category, m.name";
$materials = $conn->query($sql)->fetchAll();

// Agrupar por categoría
$groupedMaterials = [];
foreach ($materials as $mat) {
    $groupedMaterials[$mat['category'] ?? 'Otros'][] = $mat;
}

// Contar stock bajo
$lowStockCount = count(array_filter($materials, fn($m) => $m['is_low_stock']));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-seam me-2"></i>Reporte de Stock de Materiales</h2>
    <button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer me-2"></i>Imprimir
    </button>
</div>

<!-- Alerta -->
<?php if ($lowStockCount > 0): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong><?php echo $lowStockCount; ?> materiales con stock bajo</strong> - Se recomienda reordenar
</div>
<?php endif; ?>

<!-- Listado por categoría -->
<?php foreach ($groupedMaterials as $category => $items): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><?php echo ucfirst(str_replace('_', ' ', $category)); ?> (<?php echo count($items); ?>)</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Stock Actual</th>
                    <th>Stock Mínimo</th>
                    <th>Estado</th>
                    <th>Precio Unit.</th>
                    <th>Proveedor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $mat): ?>
                    <tr class="<?php echo $mat['is_low_stock'] ? 'table-danger' : ''; ?>">
                        <td><strong><?php echo htmlspecialchars($mat['code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($mat['name']); ?></td>
                        <td>
                            <strong class="<?php echo $mat['is_low_stock'] ? 'text-danger' : ''; ?>">
                                <?php echo number_format($mat['current_stock'], 2); ?> <?php echo htmlspecialchars($mat['unit_of_measure']); ?>
                            </strong>
                        </td>
                        <td><?php echo number_format($mat['min_stock'], 2); ?></td>
                        <td>
                            <?php if ($mat['is_low_stock']): ?>
                                <span class="badge bg-danger">Falta <?php echo number_format($mat['shortage'], 2); ?></span>
                            <?php else: ?>
                                <span class="badge bg-success">OK</span>
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo number_format($mat['unit_price'] ?? 0, 2); ?></td>
                        <td><?php echo htmlspecialchars($mat['supplier'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<style>
@media print {
    .no-print, .navbar, footer, .btn, .alert { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; page-break-inside: avoid; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
