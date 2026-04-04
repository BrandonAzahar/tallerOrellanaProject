<?php
/**
 * Constantes del Sistema
 * Estructuras y Remodelaciones Orellana
 */

// ============================================
// CONFIGURACIÓN GENERAL
// ============================================

// Modo debug (true para desarrollo, false para producción)
define('DEBUG_MODE', true);

// Zona horaria
define('TIMEZONE', 'America/El_Salvador');
date_default_timezone_set(TIMEZONE);

// Codificación de caracteres
define('CHARSET', 'UTF-8');

// ============================================
// CONFIGURACIÓN DE LA EMPRESA
// ============================================

define('COMPANY_NAME', 'ESTRUCTURAS Y REMODELACIONES ORELLANA');
define('COMPANY_NIT', '0617-240404-104-9');
define('COMPANY_REGISTRY', '359357-9');
define('COMPANY_ADDRESS', 'San Salvador, El Salvador');
define('COMPANY_PHONE', '+503 2222-0000');
define('COMPANY_EMAIL', 'info@orellana.com');

// ============================================
// CONFIGURACIÓN DE IMPUESTOS
// ============================================

// Monto mínimo para retención de renta (sujetos excluidos)
define('WITHHOLDING_THRESHOLD', 462.00);

// Porcentaje de retención de renta
define('WITHHOLDING_RATE', 0.10); // 10%

// Tasa de IVA
define('IVA_RATE', 0.13); // 13%

// ============================================
// RUTAS DEL SISTEMA
// ============================================

// Ruta base del sistema (ajustar según instalación)
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/TallerOrellana/');

// Rutas de directorios
define('UPLOADS_PATH', BASE_PATH . '/uploads/');
define('PDF_PATH', BASE_PATH . '/pdfs/');
define('LOGO_PATH', BASE_PATH . '/imagenes/logo orellana.png');

// ============================================
// CONFIGURACIÓN DE SESIÓN
// ============================================

// Tiempo de expiración de sesión en segundos (30 minutos)
define('SESSION_TIMEOUT', 1800);

// Nombre de la sesión
define('SESSION_NAME', 'ORELLANA_SESSION');

// ============================================
// CONFIGURACIÓN DE SEGURIDAD
// ============================================

// Costo del hash de contraseñas
define('PASSWORD_COST', 10);

// ============================================
// FORMATOS
// ============================================

// Formato de fecha
define('DATE_FORMAT', 'Y-m-d');
define('DATE_FORMAT_DISPLAY', 'd/m/Y');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

// Formato de moneda
define('CURRENCY_SYMBOL', '$');
define('CURRENCY_DECIMALS', 2);

// ============================================
// PAGINACIÓN
// ============================================

define('DEFAULT_PAGE_SIZE', 10);
define('MAX_PAGE_SIZE', 100);
