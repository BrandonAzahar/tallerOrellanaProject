<?php
/**
 * Reporte de Inventario de Herramientas
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Reporte de Herramientas - Orellana';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$conn = getDbConnection();

// Obtener herramientas con estado
$sql = "SELECT t.*,
               (SELECT COUNT(*) FROM tool_loans tl WHERE tl.tool_id = t.id AND tl.status = 'active') as active_loans
        FROM tools t
        ORDER BY t.current_status, t.name";
$tools = $conn->query($sql)->fetchAll();

// Agrupar por estado
$groupedTools = [];
foreach ($tools as $tool) {
    $groupedTools[$tool['current_status']][] = $tool;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-tools me-2"></i>Reporte de Inventario de Herramientas</h2>
    <button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer me-2"></i>Imprimir
    </button>
</div>

<!-- Resumen -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <h6>Disponibles</h6>
                <h3><?php echo count($groupedTools['available'] ?? []); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center">
                <h6>Prestadas</h6>
                <h3><?php echo count($groupedTools['loaned'] ?? []); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body text-center">
                <h6>Mantenimiento</h6>
                <h3><?php echo count($groupedTools['maintenance'] ?? []); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-danger text-white h-100">
            <div class="card-body text-center">
                <h6>No Usables</h6>
                <h3><?php echo count($groupedTools['damaged'] ?? []) + count($groupedTools['lost'] ?? []); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Listado por estado -->
<?php foreach ($groupedTools as $status => $items): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <?php 
            $statusLabels = [
                'available' => 'Disponibles',
                'loaned' => 'Prestadas',
                'maintenance' => 'En Mantenimiento',
                'damaged' => 'Dañadas',
                'lost' => 'Perdidas'
            ];
            echo $statusLabels[$status] ?? ucfirst($status);
            ?> (<?php echo count($items); ?>)
        </h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Marca/Modelo</th>
                    <th>Condición</th>
                    <th>Ubicación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $tool): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($tool['code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($tool['name']); ?></td>
                        <td><?php echo htmlspecialchars($tool['brand'] ?? ''); ?> <?php echo htmlspecialchars($tool['model'] ?? ''); ?></td>
                        <td><?php echo ucfirst($tool['condition_rating']); ?></td>
                        <td><?php echo htmlspecialchars($tool['location'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<style>
@media print {
    .no-print, .navbar, footer, .btn { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; page-break-inside: avoid; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
