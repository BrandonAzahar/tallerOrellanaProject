<?php
/**
 * Activar/Desactivar Usuario
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$conn = getDbConnection();
requireAdmin();

$id = isset($_GET['id']) ? decryptId($_GET['id']) : 0;

if ($id <= 0) {
    setFlash('error', 'Usuario no válido');
    header('Location: index.php');
    exit();
}

// No permitir desactivarse a sí mismo
if ($id == getCurrentUserId()) {
    setFlash('error', 'No puedes desactivar tu propio usuario');
    header('Location: index.php');
    exit();
}

// Obtener estado actual
$sql = "SELECT status FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'Usuario no encontrado');
    header('Location: index.php');
    exit();
}

// Cambiar estado
$newStatus = $user['status'] === 'active' ? 'inactive' : 'active';

try {
    $sql = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$newStatus, $id]);
    
    $action = $newStatus === 'active' ? 'activado' : 'desactivado';
    setFlash('success', 'Usuario ' . $action . ' exitosamente');
} catch (PDOException $e) {
    setFlash('error', 'Error al cambiar el estado del usuario');
}

header('Location: index.php');
exit();
