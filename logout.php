<?php
/**
 * Cerrar Sesión
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/includes/auth.php';

// Cerrar sesión
logout();

// Redirigir al login con mensaje de éxito
header('Location: login.php?logout=success');
exit();
