<?php
/**
 * Eliminar Sujeto Excluido
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar que sea admin
requireAdmin();

$conn = getDbConnection();

// Obtener ID y decodificar
$id = isset($_GET['id']) ? (int)base64_decode($_GET['id']) : 0;

if ($id <= 0) {
    setFlash('error', 'Sujeto no válido');
    header('Location: index.php');
    exit();
}

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
    
    setFlash('success', 'Sujeto excluido eliminado exitosamente');
} catch (PDOException $e) {
    setFlash('error', 'Error al eliminar el sujeto');
}

header('Location: index.php');
exit();
