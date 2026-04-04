<?php
/**
 * Script para corregir las contraseñas de usuarios
 * Ejecutar una vez y luego eliminar por seguridad
 */

require_once __DIR__ . '/config/database.php';

try {
    $conn = getDbConnection();
    
    // Generar hash correcto para admin123
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Actualizar contraseñas de admin y operador
    $sql = "UPDATE users SET password = ? WHERE username IN ('admin', 'operador')";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$hash]);
    
    echo "✅ Contraseñas actualizadas correctamente\n\n";
    echo "Usuario: admin\n";
    echo "Contraseña: admin123\n\n";
    echo "Usuario: operador\n";
    echo "Contraseña: admin123\n\n";
    echo "⚠️  IMPORTANTE: Elimina este archivo después de usarlo por seguridad.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
