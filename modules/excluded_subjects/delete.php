<?php
/**
 * Eliminar Sujeto Excluido
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/audit_utils.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar que sea admin
requireAdmin();

$conn = getDbConnection();

// Obtener ID y desencriptar
$id = isset($_GET['id']) ? decryptId($_GET['id']) : 0;

if ($id === false || $id <= 0) {
    setFlash('error', 'Sujeto no válido');
    header('Location: index.php');
    exit();
}

// Obtener datos antes de eliminar (para el log)
$sql = "SELECT first_name, last_name, dui, occupation FROM excluded_subjects WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$oldData = $stmt->fetch();

// Verificar si tiene pagos asociados
$sql = "SELECT COUNT(*) as count FROM excluded_payments WHERE subject_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$result = $stmt->fetch();

if ($result['count'] > 0) {
    setFlash('error', 'No se puede eliminar: el sujeto tiene ' . $result['count'] . ' pago(s) registrado(s).');
    header('Location: index.php');
    exit();
}

// Eliminar sujeto
try {
    $sql = "DELETE FROM excluded_subjects WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    // Registrar en logs de auditoría
    logAudit($conn, 'delete', 'excluded_subjects', $id, 'excluded_subjects', $oldData, null);

    setFlash('success', 'Sujeto excluido eliminado exitosamente');
} catch (PDOException $e) {
    setFlash('error', 'Error al eliminar el sujeto');
}

header('Location: index.php');
exit();
