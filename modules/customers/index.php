<?php
/**
 * Clientes
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Clientes - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();

// Filtros
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? 'all';

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(company_name LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR nit LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($type !== 'all') {
    $whereClauses[] = "type = ?";
    $params[] = $type;
}

$whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$sql = "SELECT * FROM customers {$whereClause} ORDER BY 
        CASE WHEN type = 'company' THEN company_name ELSE CONCAT(first_name, ' ', last_name) END";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-badge me-2"></i>Clientes</h2>
    <a href="create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nuevo Cliente
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-12 col-md-6">
                <input type="text" class="form-control" name="search" placeholder="Buscar cliente" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-12 col-md-4">
                <select class="form-select" name="type">
                    <option value="all">Todos</option>
                    <option value="individual" <?php echo $type === 'individual' ? 'selected' : ''; ?>>Persona Natural</option>
                    <option value="company" <?php echo $type === 'company' ? 'selected' : ''; ?>>Empresa</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-body p-0">
        <?php if (count($customers) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Nombre / Empresa</th>
                            <th>NIT/DUI</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $cust): ?>
                            <tr>
                                <td>
                                    <?php if ($cust['type'] === 'company'): ?>
                                        <span class="badge bg-primary">Empresa</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Natural</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $cust['type'] === 'company' ? htmlspecialchars($cust['company_name']) : htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($cust['nit'] ?? $cust['dui'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cust['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cust['email'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo base64_encode($cust['id']); ?>" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-person-badge" style="font-size: 4rem;"></i>
                <p class="mt-3">No hay clientes registrados</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
