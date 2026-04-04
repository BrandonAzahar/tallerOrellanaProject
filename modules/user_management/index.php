<?php
/**
 * Gestión de Usuarios
 * Estructuras y Remodelaciones Orellana
 * Solo administradores pueden gestionar usuarios
 */

$pageTitle = 'Usuarios - Orellana';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();

// Solo administradores
requireAdmin();

// Filtros
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$role = $_GET['role'] ?? 'all';

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereClauses[] = "status = ?";
    $params[] = $status;
}

if ($role !== 'all') {
    $whereClauses[] = "role = ?";
    $params[] = $role;
}

$whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Obtener usuarios
$sql = "SELECT * FROM users {$whereClause} ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Contar por rol
$sqlCounts = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
              SUM(CASE WHEN role = 'operator' THEN 1 ELSE 0 END) as operators,
              SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
              SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
              FROM users";
$counts = $conn->query($sqlCounts)->fetch();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people me-2"></i>Gestión de Usuarios</h2>
    <a href="create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nuevo Usuario
    </a>
</div>

<!-- Resumen -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center">
                <h6>Total Usuarios</h6>
                <h3><?php echo $counts['total'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <h6>Administradores</h6>
                <h3><?php echo $counts['admins'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center">
                <h6>Operadores</h6>
                <h3><?php echo $counts['operators'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-secondary text-white h-100">
            <div class="card-body text-center">
                <h6>Inactivos</h6>
                <h3><?php echo $counts['inactive'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-12 col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Buscar usuario" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-12 col-md-3">
                <select class="form-select" name="role">
                    <option value="all">Todos los roles</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    <option value="operator" <?php echo $role === 'operator' ? 'selected' : ''; ?>>Operador</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <select class="form-select" name="status">
                    <option value="all">Todos los estados</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Activos</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactivos</option>
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
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list me-2"></i>Listado de Usuarios</h5>
        <span class="badge bg-secondary"><?php echo count($users); ?> usuarios</span>
    </div>
    <div class="card-body p-0">
        <?php if (count($users) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Último Acceso</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge bg-success"><i class="bi bi-shield-check me-1"></i>Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-info"><i class="bi bi-person-badge me-1"></i>Operador</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['last_login']): ?>
                                        <small><?php echo formatDateTime($user['last_login']); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Nunca</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo base64_encode($user['id']); ?>" class="btn btn-outline-warning" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($user['id'] != getCurrentUserId()): ?>
                                            <a href="toggle_status.php?id=<?php echo base64_encode($user['id']); ?>" 
                                               class="btn btn-outline-<?php echo $user['status'] === 'active' ? 'secondary' : 'success'; ?>" 
                                               title="<?php echo $user['status'] === 'active' ? 'Desactivar' : 'Activar'; ?>">
                                                <i class="bi bi-<?php echo $user['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people" style="font-size: 4rem;"></i>
                <p class="mt-3">No hay usuarios registrados</p>
                <a href="create.php" class="btn btn-primary">Crear primer usuario</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
