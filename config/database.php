<?php
/**
 * Configuración de Base de Datos
 * Estructuras y Remodelaciones Orellana
 */

// Configuración de conexión
define('DB_HOST', 'localhost');
define('DB_NAME', 'orellana_payments');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Obtener conexión PDO a la base de datos
 * @return PDO Conexión a la base de datos
 * @throws PDOException Si falla la conexión
 */
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ];
            
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En producción, no mostrar el error completo
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                throw $e;
            }
            die("Error de conexión a la base de datos. Verifique la configuración.");
        }
    }
    
    return $conn;
}

/**
 * Cerrar la conexión a la base de datos
 */
function closeDbConnection() {
    global $conn;
    $conn = null;
}
