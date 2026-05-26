<?php
/**
 * Devolver Herramienta
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$conn = getDbConnection();
requireAuth();

$id = isset($_GET['id']) ? decryptId($_GET['id']) : 0;

if ($id <= 0) {
    setFlash('error', 'Préstamo no válido');
    header('Location: index.php');
    exit();
}

// Obtener préstamo
$sql = "SELECT tl.*, t.id as tool_id FROM tool_loans tl JOIN tools t ON tl.tool_id = t.id WHERE tl.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$loan = $stmt->fetch();

if (!$loan || $loan['status'] !== 'active') {
    setFlash('error', 'Préstamo no encontrado o no está activo');
    header('Location: index.php');
    exit();
}

// Procesar devolución
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token inválido');
    } else {
        $conditionAtReturn = $_POST['condition_at_return'] ?? 'good';
        $notes = sanitizeDbInput($_POST['notes'] ?? '');
        $newStatus = $_POST['status'] ?? 'returned';
        
        try {
            $conn->beginTransaction();
            
            // Actualizar préstamo
            $sql = "UPDATE tool_loans SET actual_return_date = CURDATE(), condition_at_return = ?, 
                    notes = CONCAT(IFNULL(notes, ''), ' | DEVUELTO: ', ?), status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$conditionAtReturn, date('d/m/Y') . ' ' . $notes, $newStatus, $id]);
            
            // Actualizar herramienta
            $toolStatus = ($conditionAtReturn === 'poor') ? 'damaged' : 'available';
            $sql = "UPDATE tools SET current_status = ?, condition_rating = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$toolStatus, $conditionAtReturn, $loan['tool_id']]);
            
            $conn->commit();
            setFlash('success', 'Herramienta devuelta');
        } catch (Exception $e) {
            $conn->rollBack();
            setFlash('error', 'Error al devolver');
        }
    }
}

header('Location: index.php');
exit();
