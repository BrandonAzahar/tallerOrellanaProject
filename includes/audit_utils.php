<?php
/**
 * Utilitarios de Auditoría
 * Estructuras y Remodelaciones Orellana
 *
 * Registra todas las acciones CRUD del sistema
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Registrar una acción de auditoría
 *
 * @param PDO $conn Conexión a la base de datos
 * @param string $action Acción realizada (create/update/delete/login/logout/view)
 * @param string $module Módulo (excluded_subjects, excluded_payments, etc.)
 * @param int|null $recordId ID del registro afectado
 * @param string|null $tableName Tabla donde se realizó la acción
 * @param array|null $oldValues Valores antes del cambio
 * @param array|null $newValues Valores después del cambio
 * @return bool True si se registró correctamente
 */
function logAudit($conn, $action, $module, $recordId = null, $tableName = null, $oldValues = null, $newValues = null) {
    try {
        $userId = getCurrentUserId();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $sql = "INSERT INTO audit_logs
                (user_id, action, module, record_id, table_name, old_values, new_values, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $userId,
            $action,
            $module,
            $recordId,
            $tableName,
            $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            $ipAddress,
            $userAgent
        ]);

        return true;
    } catch (PDOException $e) {
        // No fallar la operación principal si falla el log
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Audit log error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Obtener logs de auditoría con filtros
 *
 * @param PDO $conn Conexión a la base de datos
 * @param array $filters Filtros opcionales (user_id, action, module, date_from, date_to)
 * @param int $page Página actual
 * @param int $pageSize Registros por página
 * @return array ['logs' => [], 'total' => int, 'totalPages' => int]
 */
function getAuditLogs($conn, $filters = [], $page = 1, $pageSize = 20) {
    $whereClauses = [];
    $params = [];

    if (!empty($filters['user_id'])) {
        $whereClauses[] = "al.user_id = ?";
        $params[] = $filters['user_id'];
    }

    if (!empty($filters['action'])) {
        $whereClauses[] = "al.action = ?";
        $params[] = $filters['action'];
    }

    if (!empty($filters['module'])) {
        $whereClauses[] = "al.module = ?";
        $params[] = $filters['module'];
    }

    if (!empty($filters['date_from'])) {
        $whereClauses[] = "al.created_at >= ?";
        $params[] = $filters['date_from'] . ' 00:00:00';
    }

    if (!empty($filters['date_to'])) {
        $whereClauses[] = "al.created_at <= ?";
        $params[] = $filters['date_to'] . ' 23:59:59';
    }

    if (!empty($filters['search'])) {
        $whereClauses[] = "(u.username LIKE ? OR u.full_name LIKE ? OR al.module LIKE ? OR al.action LIKE ?)";
        $search = "%{$filters['search']}%";
        $params = array_merge($params, [$search, $search, $search, $search]);
    }

    $whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    // Contar total
    $sqlCount = "SELECT COUNT(*) as total FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id {$whereClause}";
    $stmt = $conn->prepare($sqlCount);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    $totalPages = max(1, ceil($total / $pageSize));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $pageSize;

    // Obtener logs
    $sql = "SELECT al.*,
                   u.username,
                   u.full_name as user_full_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            {$whereClause}
            ORDER BY al.created_at DESC
            LIMIT {$pageSize} OFFSET {$offset}";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    return [
        'logs' => $logs,
        'total' => $total,
        'totalPages' => $totalPages,
        'page' => $page
    ];
}

/**
 * Obtener acciones disponibles para filtros
 *
 * @param PDO $conn Conexión a la base de datos
 * @return array Lista de acciones
 */
function getAuditActions($conn) {
    $sql = "SELECT DISTINCT action FROM audit_logs ORDER BY action";
    return $conn->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Obtener módulos disponibles para filtros
 *
 * @param PDO $conn Conexión a la base de datos
 * @return array Lista de módulos
 */
function getAuditModules($conn) {
    $sql = "SELECT DISTINCT module FROM audit_logs ORDER BY module";
    return $conn->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Limpiar logs antiguos (mantener últimos N días)
 *
 * @param PDO $conn Conexión a la base de datos
 * @param int $days Días a mantener (default: 90)
 * @return int Número de registros eliminados
 */
function cleanupOldAuditLogs($conn, $days = 90) {
    try {
        $sql = "DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Obtener estadísticas de auditoría
 *
 * @param PDO $conn Conexión a la base de datos
 * @param string $period Período (today, week, month)
 * @return array Estadísticas
 */
function getAuditStats($conn, $period = 'month') {
    switch ($period) {
        case 'today':
            $dateClause = "DATE(al.created_at) = CURDATE()";
            break;
        case 'week':
            $dateClause = "al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
        default:
            $dateClause = "al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }

    // Total de acciones
    $sql = "SELECT COUNT(*) as total FROM audit_logs al WHERE {$dateClause}";
    $total = $conn->query($sql)->fetch()['total'];

    // Por acción
    $sql = "SELECT al.action, COUNT(*) as count
            FROM audit_logs al
            WHERE {$dateClause}
            GROUP BY al.action
            ORDER BY count DESC";
    $byAction = $conn->query($sql)->fetchAll();

    // Por módulo
    $sql = "SELECT al.module, COUNT(*) as count
            FROM audit_logs al
            WHERE {$dateClause}
            GROUP BY al.module
            ORDER BY count DESC
            LIMIT 10";
    $byModule = $conn->query($sql)->fetchAll();

    // Por usuario
    $sql = "SELECT u.full_name, u.username, COUNT(*) as count
            FROM audit_logs al
            JOIN users u ON al.user_id = u.id
            WHERE {$dateClause}
            GROUP BY al.user_id, u.full_name, u.username
            ORDER BY count DESC
            LIMIT 10";
    $byUser = $conn->query($sql)->fetchAll();

    return [
        'total' => $total,
        'by_action' => $byAction,
        'by_module' => $byModule,
        'by_user' => $byUser
    ];
}
