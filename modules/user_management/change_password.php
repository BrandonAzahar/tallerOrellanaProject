<?php
/**
 * Cambiar Contraseña de Usuario
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$conn = getDbConnection();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token inválido');
        header('Location: index.php');
        exit();
    }
    
    $id = (int)($_POST['id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_new_password'] ?? '';
    
    if ($id <= 0) {
        setFlash('error', 'Usuario no válido');
        header('Location: index.php');
        exit();
    }
    
    if (empty($newPassword)) {
        setFlash('warning', 'La contraseña no puede estar vacía');
        header('Location: edit.php?id=' . encryptId($id));
        exit();
    }

    if (strlen($newPassword) < 6) {
        setFlash('error', 'La contraseña debe tener al menos 6 caracteres');
        header('Location: edit.php?id=' . encryptId($id));
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        setFlash('error', 'Las contraseñas no coinciden');
        header('Location: edit.php?id=' . encryptId($id));
        exit();
    }
    
    try {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT, ['cost' => PASSWORD_COST]);
        
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$hashedPassword, $id]);
        
        setFlash('success', 'Contraseña actualizada exitosamente');
    } catch (PDOException $e) {
        setFlash('error', 'Error al cambiar la contraseña');
    }
}

header('Location: index.php');
exit();
