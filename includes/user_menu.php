<?php
/**
 * Menú de Usuario Responsive
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
?>

<div class="dropdown">
    <button class="btn btn-outline-light dropdown-toggle" type="button" 
            id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-person-circle me-1"></i>
        <span class="d-none d-md-inline"><?php echo htmlspecialchars(getCurrentUserFullName() ?? getCurrentUsername()); ?></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="userMenuDropdown">
        <li>
            <div class="dropdown-header">
                <i class="bi bi-person-badge me-2"></i>
                <?php echo htmlspecialchars(getCurrentUserFullName()); ?>
            </div>
        </li>
        <li>
            <div class="dropdown-item-text small text-muted">
                <i class="bi bi-shield me-1"></i>
                <?php echo ucfirst(getCurrentUserRole()); ?>
            </div>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">
                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
            </a>
        </li>
    </ul>
</div>
