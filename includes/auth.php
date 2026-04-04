<?php
/**
 * Sistema de Autenticación y Autorización
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true
    ]);
}

/**
 * Generar token CSRF
 * @return string Token CSRF
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 * @param string $token Token a verificar
 * @return bool True si es válido
 */
function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerar token CSRF
 */
function regenerateCsrfToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Verificar si el usuario está autenticado
 * @return bool True si está logueado
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        return false;
    }
    
    // Verificar timeout de sesión
    if (isset($_SESSION['last_activity'])) {
        if ((time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            logout();
            return false;
        }
    }
    
    // Actualizar última actividad
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Verificar si el usuario es administrador
 * @return bool True si es admin
 */
function isAdmin() {
    return isLoggedIn() && 
           isset($_SESSION['user_role']) && 
           $_SESSION['user_role'] === 'admin';
}

/**
 * Verificar si el usuario es operador
 * @return bool True si es operador
 */
function isOperator() {
    return isLoggedIn() && 
           isset($_SESSION['user_role']) && 
           $_SESSION['user_role'] === 'operator';
}

/**
 * Verificar si el usuario tiene permisos (admin o operator)
 * @return bool True si tiene permisos
 */
function hasPermission() {
    return isLoggedIn() && (isAdmin() || isOperator());
}

/**
 * Requerir autenticación - Redirigir al login si no está autenticado
 */
function requireAuth() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
    
    // Actualizar última actividad
    $_SESSION['last_activity'] = time();
}

/**
 * Requerir rol de administrador
 */
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        $_SESSION['flash_error'] = 'Acceso denegado. Se requieren permisos de administrador.';
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}

/**
 * Iniciar sesión de usuario
 * @param array $user Datos del usuario
 * @return bool True si el login fue exitoso
 */
function login($user) {
    // Regenerar ID de sesión para prevenir fijación de sesión
    session_regenerate_id(true);
    
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_full_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email'] = $user['email'] ?? '';
    $_SESSION['last_activity'] = time();
    
    // Generar nuevo token CSRF
    generateCsrfToken();
    
    // Actualizar último login en la base de datos
    try {
        $conn = getDbConnection();
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user['id']]);
    } catch (PDOException $e) {
        // No fallar el login si no se puede actualizar el último login
        if (DEBUG_MODE) {
            error_log("Error actualizando last_login: " . $e->getMessage());
        }
    }
    
    return true;
}

/**
 * Cerrar sesión
 */
function logout() {
    // Limpiar todas las variables de sesión
    $_SESSION = array();
    
    // Borrar la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
}

/**
 * Obtener ID del usuario actual
 * @return int|null ID del usuario o null si no está logueado
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtener nombre de usuario actual
 * @return string|null Nombre de usuario o null si no está logueado
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Obtener nombre completo del usuario actual
 * @return string|null Nombre completo o null si no está logueado
 */
function getCurrentUserFullName() {
    return $_SESSION['user_full_name'] ?? null;
}

/**
 * Obtener rol del usuario actual
 * @return string|null Rol del usuario o null si no está logueado
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Obtener datos completos del usuario actual
 * @return array|null Datos del usuario o null si no está logueado
 */
function getCurrentUserData() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $conn = getDbConnection();
        $sql = "SELECT id, username, full_name, email, role, status FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([getCurrentUserId()]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Establecer mensaje flash
 * @param string $type Tipo de mensaje (success, error, warning, info)
 * @param string $message Mensaje
 */
function setFlash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Obtener y limpiar mensaje flash
 * @param string $type Tipo de mensaje
 * @return string|null Mensaje flash o null
 */
function getFlash($type) {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    return null;
}

/**
 * Verificar si hay mensajes flash
 * @param string $type Tipo de mensaje
 * @return bool True si hay mensaje
 */
function hasFlash($type) {
    return isset($_SESSION['flash_' . $type]);
}

/**
 * Sanitizar entrada de usuario
 * @param string $data Datos a sanitizar
 * @return string Datos sanitizados
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, CHARSET);
    return $data;
}

/**
 * Validar que el usuario esté activo
 * @param int $userId ID del usuario
 * @return bool True si el usuario está activo
 */
function isUserActive($userId) {
    try {
        $conn = getDbConnection();
        $sql = "SELECT status FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result && $result['status'] === 'active';
    } catch (PDOException $e) {
        return false;
    }
}
