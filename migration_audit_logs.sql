-- ============================================
-- MIGRACIÓN: Logs de Auditoría
-- Ejecutar en base de datos existente
-- ============================================

-- Crear tabla de logs de auditoría
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL COMMENT 'create/update/delete/login/logout/view',
    module VARCHAR(50) NOT NULL COMMENT 'excluded_subjects/excluded_payments/tools/etc',
    record_id INT NULL COMMENT 'ID del registro afectado',
    table_name VARCHAR(50) NULL COMMENT 'Tabla donde se realizó la acción',
    old_values JSON NULL COMMENT 'Valores antes del cambio',
    new_values JSON NULL COMMENT 'Valores después del cambio',
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_module (module),
    INDEX idx_created (created_at),
    INDEX idx_record (table_name, record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
