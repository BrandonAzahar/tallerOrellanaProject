<?php
/**
 * Header Común del Sistema
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

// Verificar autenticación
requireAuth();

// Obtener datos del usuario
$userData = getCurrentUserData();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Orellana - Sistema de Gestión'; ?></title>
    
    <!-- 
      MODIFICACIÓN: Integración de la fuente premium Google Fonts 'Outfit'
      Esta tipografía eleva la estética del sistema aportando un estilo moderno y fluido.
    -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    
    <?php if (isset($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?php echo BASE_URL . $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navegación Superior -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>index.php">
                <?php if (file_exists(LOGO_PATH)): ?>
                    <img src="<?php echo BASE_URL; ?>imagenes/logo orellana.png" alt="Logo" 
                         style="height: 40px; margin-right: 10px;">
                <?php else: ?>
                    <i class="bi bi-building me-2"></i>
                <?php endif; ?>
                <span class="d-none d-lg-inline">Orellana</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#mainNavbar" aria-controls="mainNavbar" 
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>index.php">
                            <i class="bi bi-speedometer2 me-1"></i>
                            <span class="d-none d-lg-inline">Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Sujetos Excluidos -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'create.php', 'edit.php', 'view.php']) && strpos($_SERVER['PHP_SELF'], 'excluded_subjects') !== false ? 'active' : ''; ?>" 
                           href="#" id="subjectsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-people me-1"></i>
                            <span class="d-none d-lg-inline">Sujetos Excluidos</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="subjectsDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/excluded_subjects/index.php">
                                <i class="bi bi-list me-2"></i>Listado
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/excluded_subjects/create.php">
                                <i class="bi bi-plus-circle me-2"></i>Nuevo Sujeto
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Pagos -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'create.php', 'edit.php', 'print.php']) && strpos($_SERVER['PHP_SELF'], 'excluded_payments') !== false ? 'active' : ''; ?>" 
                           href="#" id="paymentsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-cash-coin me-1"></i>
                            <span class="d-none d-lg-inline">Pagos</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="paymentsDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/excluded_payments/index.php">
                                <i class="bi bi-list me-2"></i>Listado de Pagos
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/excluded_payments/create.php">
                                <i class="bi bi-plus-circle me-2"></i>Nuevo Pago
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Herramientas -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], 'tools') !== false ? 'active' : ''; ?>" 
                           href="#" id="toolsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-tools me-1"></i>
                            <span class="d-none d-lg-inline">Herramientas</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="toolsDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/tools/index.php">
                                <i class="bi bi-list me-2"></i>Inventario
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/tools/create.php">
                                <i class="bi bi-plus-circle me-2"></i>Nueva Herramienta
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Préstamos -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], 'tool_loans') !== false ? 'active' : ''; ?>" 
                           href="#" id="loansDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-hand-index-thumb me-1"></i>
                            <span class="d-none d-lg-inline">Préstamos</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="loansDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/tool_loans/index.php">
                                <i class="bi bi-list me-2"></i>Préstamos Activos
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/tool_loans/create.php">
                                <i class="bi bi-plus-circle me-2"></i>Nuevo Préstamo
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/tool_loans/history.php">
                                <i class="bi bi-clock-history me-2"></i>Historial
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Materiales -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], 'materials') !== false ? 'active' : ''; ?>" 
                           href="#" id="materialsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-box-seam me-1"></i>
                            <span class="d-none d-lg-inline">Materiales</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="materialsDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/materials/index.php">
                                <i class="bi bi-list me-2"></i>Inventario
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/materials/create.php">
                                <i class="bi bi-plus-circle me-2"></i>Nuevo Material
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/materials/movements.php">
                                <i class="bi bi-arrow-left-right me-2"></i>Movimientos
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Clientes -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], 'customers') !== false ? 'active' : ''; ?>" 
                           href="#" id="customersDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-badge me-1"></i>
                            <span class="d-none d-lg-inline">Clientes</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="customersDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/customers/index.php">
                                <i class="bi bi-list me-2"></i>Listado
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/customers/create.php">
                                <i class="bi bi-plus-circle me-2"></i>Nuevo Cliente
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Reportes -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], 'reports') !== false || strpos($_SERVER['PHP_SELF'], 'backup') !== false ? 'active' : ''; ?>"
                           href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-earmark-bar-graph me-1"></i>
                            <span class="d-none d-lg-inline">Reportes</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>reports/payments_report.php">
                                <i class="bi bi-cash me-2"></i>Pagos Realizados
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>reports/inventory_report.php">
                                <i class="bi bi-tools me-2"></i>Inventario Herramientas
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>reports/materials_report.php">
                                <i class="bi bi-box-seam me-2"></i>Stock Materiales
                            </a></li>
                            <?php if (isAdmin()): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/backup/index.php">
                                <i class="bi bi-hdd-stack me-2"></i>Respaldo y Exportación
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <!-- Usuarios (Solo Admin) -->
                    <?php if (isAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], 'user_management') !== false || strpos($_SERVER['PHP_SELF'], 'audit_logs') !== false ? 'active' : ''; ?>"
                           href="#" id="usersDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-gear me-1"></i>
                            <span class="d-none d-lg-inline">Admin</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="usersDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/user_management/index.php">
                                <i class="bi bi-list me-2"></i>Listado de Usuarios
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/user_management/create.php">
                                <i class="bi bi-person-plus me-2"></i>Nuevo Usuario
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/audit_logs/index.php">
                                <i class="bi bi-journal-text me-2"></i>Logs de Auditoría
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Menú de Usuario -->
                <?php include __DIR__ . '/user_menu.php'; ?>
            </div>
        </div>
    </nav>
    
    <!-- Contenido Principal -->
    <main class="container-fluid py-4">
        <!-- Mensajes Flash -->
        <?php if (hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo getFlash('success'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo getFlash('error'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (hasFlash('warning')): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?php echo getFlash('warning'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (hasFlash('info')): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i><?php echo getFlash('info'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Contenido de la página -->
