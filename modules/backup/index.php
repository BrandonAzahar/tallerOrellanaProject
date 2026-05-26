<?php
/**
 * Sistema de Respaldo y Exportación
 * Estructuras y Remodelaciones Orellana
 * Solo accesible para administradores
 */

$pageTitle = 'Respaldo de Datos - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

// Solo admin
requireAdmin();

$conn = getDbConnection();
$successMsg = '';
$errorMsg = '';

// ============================================
// GENERAR RESPALDO SQL
// ============================================
if (isset($_POST['backup_sql'])) {
    try {
        // Obtener todas las tablas
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sqlDump = "-- ============================================\n";
        $sqlDump .= "-- RESPALDO DE BASE DE DATOS - ORELLANA\n";
        $sqlDump .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
        $sqlDump .= "-- ============================================\n\n";
        $sqlDump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sqlDump .= "SET time_zone = \"+00:00\";\n";
        $sqlDump .= "START TRANSACTION;\n\n";

        foreach ($tables as $table) {
            // Estructura de la tabla
            $sqlDump .= "--\n-- Estructura de tabla: {$table}\n--\n\n";
            $result = $conn->query("SHOW CREATE TABLE `{$table}`");
            $row = $result->fetch(PDO::FETCH_NUM);
            $sqlDump .= $row[1] . ";\n\n";

            // Datos de la tabla
            $result = $conn->query("SELECT * FROM `{$table}`");
            $rowCount = $result->rowCount();

            if ($rowCount > 0) {
                $sqlDump .= "--\n-- Datos de tabla: {$table} ({$rowCount} registros)\n--\n\n";
                $sqlDump .= "INSERT INTO `{$table}` VALUES\n";

                $rows = [];
                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $values = array_map(function($val) {
                        if ($val === null) return 'NULL';
                        return "'" . addslashes($val) . "'";
                    }, $row);
                    $rows[] = "(" . implode(', ', $values) . ")";
                }

                $sqlDump .= implode(",\n", $rows) . ";\n\n";
            }
        }

        $sqlDump .= "COMMIT;\n";

        // Guardar archivo
        $filename = 'orellana_backup_' . date('Y-m-d_His') . '.sql';
        $backupDir = BASE_PATH . '/backups/';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        file_put_contents($backupDir . $filename, $sqlDump);

        $fileSize = round(filesize($backupDir . $filename) / 1024, 2);
        $successMsg = "Respaldo generado exitosamente: <strong>{$filename}</strong> ({$fileSize} KB)";

    } catch (Exception $e) {
        $errorMsg = "Error al generar respaldo: " . $e->getMessage();
    }
}

// ============================================
// ELIMINAR RESPALDO
// ============================================
if (isset($_POST['delete_backup'])) {
    $file = basename($_POST['delete_backup']);
    $backupDir = BASE_PATH . '/backups/';
    $filePath = $backupDir . $file;

    if (file_exists($filePath) && strpos($file, 'orellana_backup_') === 0) {
        unlink($filePath);
        $successMsg = "Respaldo eliminado: <strong>{$file}</strong>";
    } else {
        $errorMsg = "Archivo no válido.";
    }
}

// ============================================
// DESCARGAR RESPALDO
// ============================================
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $backupDir = BASE_PATH . '/backups/';
    $filePath = $backupDir . $file;

    if (file_exists($filePath) && strpos($file, 'orellana_backup_') === 0) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit();
    }
}

// ============================================
// OBTENER LISTA DE RESPALDOS
// ============================================
$backupDir = BASE_PATH . '/backups/';
$backups = [];

if (is_dir($backupDir)) {
    $files = glob($backupDir . 'orellana_backup_*.sql');
    usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });

    foreach ($files as $file) {
        $backups[] = [
            'filename' => basename($file),
            'size' => round(filesize($file) / 1024, 2),
            'date' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
}

// ============================================
// EXPORTAR TABLA ESPECÍFICA A CSV
// ============================================
if (isset($_POST['export_csv'])) {
    $table = $_POST['table'] ?? '';
    $allowedTables = ['excluded_subjects', 'excluded_payments', 'tools', 'tool_loans', 'materials', 'customers', 'invoices', 'users'];

    if (in_array($table, $allowedTables)) {
        try {
            $result = $conn->query("SELECT * FROM `{$table}`");
            $columns = [];
            for ($i = 0; $i < $result->columnCount(); $i++) {
                $meta = $result->getColumnMeta($i);
                $columns[] = $meta['name'];
            }

            $filename = $table . '_export_' . date('Y-m-d_His') . '.csv';
            $exportDir = BASE_PATH . '/exports/';

            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0755, true);
            }

            $fp = fopen($exportDir . $filename, 'w');
            // BOM para UTF-8 (Excel lo necesita)
            fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados
            fputcsv($fp, $columns);

            // Datos
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                fputcsv($fp, $row);
            }

            fclose($fp);

            $fileSize = round(filesize($exportDir . $filename) / 1024, 2);
            $successMsg = "Exportación CSV generada: <strong>{$filename}</strong> ({$fileSize} KB)";

        } catch (Exception $e) {
            $errorMsg = "Error al exportar: " . $e->getMessage();
        }
    } else {
        $errorMsg = "Tabla no válida para exportación.";
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-hdd-stack me-2"></i>Respaldo y Exportación de Datos</h2>
</div>

<?php if ($successMsg): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?php echo $successMsg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $errorMsg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Respaldo SQL -->
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-download me-2"></i>Respaldo Completo (SQL)</h5>
            </div>
            <div class="card-body">
                <p>Genera un respaldo completo de toda la base de datos en formato SQL. Este archivo puede usarse para restaurar el sistema en caso de emergencia.</p>
                <form method="POST" action="">
                    <button type="submit" name="backup_sql" class="btn btn-primary w-100">
                        <i class="bi bi-hdd me-2"></i>Generar Respaldo SQL
                    </button>
                </form>

                <hr>

                <h6><i class="bi bi-folder me-2"></i>Respaldos Existentes</h6>
                <?php if (!empty($backups)): ?>
                    <div class="list-group list-group-flush mt-2">
                        <?php foreach (array_slice($backups, 0, 10) as $backup): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <small class="d-block"><?php echo htmlspecialchars($backup['filename']); ?></small>
                                    <small class="text-muted"><?php echo $backup['size']; ?> KB - <?php echo formatDate($backup['date']); ?></small>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="?download=<?php echo urlencode($backup['filename']); ?>"
                                       class="btn btn-outline-primary btn-sm" title="Descargar">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="delete_backup" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm delete-backup-btn" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mt-2">No hay respaldos generados.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Exportación CSV -->
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Exportar a CSV</h5>
            </div>
            <div class="card-body">
                <p>Exporta los datos de una tabla específica en formato CSV, compatible con Excel y Google Sheets.</p>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="table" class="form-label">Seleccionar tabla</label>
                        <select class="form-select" id="table" name="table" required>
                            <option value="">Seleccionar...</option>
                            <option value="excluded_subjects">Sujetos Excluidos</option>
                            <option value="excluded_payments">Pagos</option>
                            <option value="tools">Herramientas</option>
                            <option value="tool_loans">Préstamos de Herramientas</option>
                            <option value="materials">Materiales</option>
                            <option value="customers">Clientes</option>
                            <option value="invoices">Facturas</option>
                            <option value="users">Usuarios</option>
                        </select>
                    </div>
                    <button type="submit" name="export_csv" class="btn btn-success w-100">
                        <i class="bi bi-file-earmark-excel me-2"></i>Exportar CSV
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Confirmar eliminación de respaldo
document.querySelectorAll('.delete-backup-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('¿Está seguro de eliminar este respaldo? Esta acción no se puede deshacer.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
