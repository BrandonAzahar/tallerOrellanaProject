-- ============================================
-- ESTRUCTURAS Y REMODELACIONES ORELLANA
-- Sistema de Gestión de Pagos a Sujetos Excluidos
-- ============================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS orellana_payments CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE orellana_payments;

-- ============================================
-- TABLA DE USUARIOS DEL SISTEMA
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'operator') DEFAULT 'operator',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario admin por defecto (password: admin123)
INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador General', 'admin@orellana.com', 'admin'),
('operador', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operador', 'operador@orellana.com', 'operator');

-- ============================================
-- TABLA DE SUJETOS EXCLUIDOS (TRABAJADORES)
-- ============================================
CREATE TABLE excluded_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dui VARCHAR(10) UNIQUE,
    nit VARCHAR(17),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    occupation VARCHAR(100) COMMENT 'soldador/electricista/ayudante/albañil/pintor/otro',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dui (dui),
    INDEX idx_nit (nit),
    INDEX idx_status (status),
    INDEX idx_last_name (last_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE CORRELATIVOS FISCALES
-- ============================================
CREATE TABLE correlations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_type VARCHAR(50) NOT NULL COMMENT 'excluded_payment/invoice',
    year YEAR NOT NULL,
    last_number INT DEFAULT 0,
    prefix VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_correlation (document_type, year),
    INDEX idx_year (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar correlativos iniciales para 2026
INSERT INTO correlations (document_type, year, last_number, prefix) VALUES
('excluded_payment', 2026, 0, 'EXCL'),
('excluded_payment', 2027, 0, 'EXCL'),
('invoice', 2026, 0, 'FACT'),
('invoice', 2027, 0, 'FACT');

-- ============================================
-- TABLA DE PAGOS A SUJETOS EXCLUIDOS
-- ============================================
CREATE TABLE excluded_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    invoice_number VARCHAR(20) UNIQUE NOT NULL COMMENT 'Formato: EXCL-0001',
    service_date DATE NOT NULL,
    service_description TEXT NOT NULL,
    period_description VARCHAR(100) COMMENT 'ej: Quincena 1-15 enero 2026',
    gross_amount DECIMAL(10,2) NOT NULL COMMENT 'Monto bruto',
    withholding_tax DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Retención 10% renta',
    net_amount DECIMAL(10,2) NOT NULL COMMENT 'Monto neto a pagar',
    payment_method ENUM('cash', 'transfer') DEFAULT 'cash',
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES excluded_subjects(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invoice (invoice_number),
    INDEX idx_service_date (service_date),
    INDEX idx_subject (subject_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE CLIENTES
-- ============================================
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('individual', 'company') NOT NULL DEFAULT 'individual',
    nit VARCHAR(17),
    dui VARCHAR(10),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    company_name VARCHAR(200),
    phone VARCHAR(15),
    email VARCHAR(100),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nit (nit),
    INDEX idx_dui (dui),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE FACTURAS A CLIENTES
-- ============================================
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20) UNIQUE NOT NULL COMMENT 'Formato: FACT-0001',
    customer_id INT,
    invoice_date DATE NOT NULL,
    due_date DATE,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'IVA 13%',
    total_amount DECIMAL(10,2) NOT NULL,
    items_description TEXT NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invoice (invoice_number),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE HERRAMIENTAS
-- ============================================
CREATE TABLE tools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Código único de herramienta',
    name VARCHAR(150) NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    serial_number VARCHAR(100),
    purchase_date DATE,
    purchase_price DECIMAL(10,2),
    current_status ENUM('available', 'loaned', 'maintenance', 'damaged', 'lost') DEFAULT 'available',
    condition_rating ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
    location VARCHAR(150) COMMENT 'Ubicación actual',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_status (current_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE PRÉSTAMOS DE HERRAMIENTAS
-- ============================================
CREATE TABLE tool_loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tool_id INT NOT NULL,
    subject_id INT NOT NULL,
    loan_date DATE NOT NULL,
    expected_return_date DATE,
    actual_return_date DATE NULL,
    condition_at_loan ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
    condition_at_return ENUM('excellent', 'good', 'fair', 'poor') NULL,
    status ENUM('active', 'returned', 'overdue', 'lost') DEFAULT 'active',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE RESTRICT,
    FOREIGN KEY (subject_id) REFERENCES excluded_subjects(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tool (tool_id),
    INDEX idx_subject (subject_id),
    INDEX idx_status (status),
    INDEX idx_loan_date (loan_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE MATERIALES
-- ============================================
CREATE TABLE materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL COMMENT 'SKU del material',
    name VARCHAR(150) NOT NULL,
    description TEXT,
    category VARCHAR(100) COMMENT 'cementos/aceros/herramientas_manuales/seguridad/otros',
    unit_of_measure VARCHAR(50) DEFAULT 'unidad' COMMENT 'unidad/kg/metro/litro/caja',
    current_stock DECIMAL(10,2) DEFAULT 0,
    min_stock DECIMAL(10,2) DEFAULT 0 COMMENT 'Stock mínimo para alerta',
    unit_price DECIMAL(10,2),
    supplier VARCHAR(200),
    location VARCHAR(100) COMMENT 'Ubicación en almacén',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_category (category),
    INDEX idx_stock (current_stock)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE MOVIMIENTOS DE MATERIALES
-- ============================================
CREATE TABLE material_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    movement_date DATETIME NOT NULL,
    reference_type VARCHAR(50) COMMENT 'purchase/project/return/adjustment',
    reference_id INT COMMENT 'ID de referencia según reference_type',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_material (material_id),
    INDEX idx_movement_date (movement_date),
    INDEX idx_type (movement_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE SESIONES DE USUARIOS
-- ============================================
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS DE EJEMPLO (OPCIONAL)
-- ============================================

-- Sujetos excluidos de ejemplo
INSERT INTO excluded_subjects (dui, nit, first_name, last_name, phone, address, occupation) VALUES
('01234567-8', '0123-456789-012-3', 'Juan Carlos', 'Martínez López', '7012-3456', 'Calle Principal #123, San Salvador', 'soldador'),
('09876543-2', '0987-654321-098-7', 'María Elena', 'Rodríguez García', '7098-7654', 'Avenida Central #456, Soyapango', 'electricista'),
('11223344-5', '1122-334455-112-2', 'Roberto Antonio', 'Pérez Hernández', '7111-2233', 'Calle Los Flores #789, Mejicanos', 'albañil');

-- Herramientas de ejemplo
INSERT INTO tools (code, name, brand, model, serial_number, purchase_date, purchase_price, current_status, condition_rating, location) VALUES
('HERR-SOL-001', 'Máquina de Soldar Inverter', 'Telwin', 'Technology 164', 'SN-2024-001', '2024-01-15', 350.00, 'available', 'excellent', 'Almacén Principal'),
('HERR-TAL-002', 'Taladro Percutor Profesional', 'Bosch', 'GBH 2-26', 'SN-2024-002', '2024-02-20', 180.00, 'available', 'good', 'Almacén Principal'),
('HERR-ESI-003', 'Esmeril Angular 4.5"', 'Makita', '9557PB', 'SN-2024-003', '2024-03-10', 85.00, 'available', 'good', 'Obra Centro');

-- Materiales de ejemplo
INSERT INTO materials (code, name, description, category, unit_of_measure, current_stock, min_stock, unit_price, supplier, location) VALUES
('MAT-CEM-001', 'Cemento Portland Tipo I', 'Cemento gris 42.5kg', 'cementos', 'saco', 50, 20, 9.50, 'Cemento de El Salvador', 'Almacén Principal'),
('MAT-ACE-002', 'Varilla Corrugada 3/8"', 'Acero grado 40', 'aceros', 'barra', 100, 50, 8.75, 'Siderúrgica Salvadoreña', 'Patio Exterior'),
('MAT-ARE-003', 'Arena Gruesa', 'Arena de río lavada', 'agregados', 'm3', 15, 5, 35.00, 'Cantera Local', 'Patio Exterior');

-- ============================================
-- VISTAS ÚTILES
-- ============================================

-- Vista: Pagos del mes actual
CREATE OR REPLACE VIEW v_current_month_payments AS
SELECT 
    ep.*,
    es.first_name,
    es.last_name,
    es.dui,
    es.occupation,
    CONCAT(es.first_name, ' ', es.last_name) AS full_name
FROM excluded_payments ep
JOIN excluded_subjects es ON ep.subject_id = es.id
WHERE YEAR(ep.service_date) = YEAR(CURRENT_DATE())
  AND MONTH(ep.service_date) = MONTH(CURRENT_DATE())
  AND ep.status != 'cancelled';

-- Vista: Herramientas prestadas
CREATE OR REPLACE VIEW v_loaned_tools AS
SELECT 
    tl.*,
    t.name AS tool_name,
    t.code AS tool_code,
    t.brand,
    CONCAT(es.first_name, ' ', es.last_name) AS subject_name,
    es.dui AS subject_dui,
    es.phone AS subject_phone
FROM tool_loans tl
JOIN tools t ON tl.tool_id = t.id
JOIN excluded_subjects es ON tl.subject_id = es.id
WHERE tl.status = 'active';

-- Vista: Materiales con stock bajo
CREATE OR REPLACE VIEW v_low_stock_materials AS
SELECT 
    m.*,
    (m.min_stock - m.current_stock) AS shortage
FROM materials m
WHERE m.current_stock < m.min_stock
  AND m.status = 'active';

-- Vista: Resumen de pagos por sujeto excluido
CREATE OR REPLACE VIEW v_payments_by_subject AS
SELECT 
    es.id AS subject_id,
    CONCAT(es.first_name, ' ', es.last_name) AS full_name,
    es.dui,
    es.nit,
    es.occupation,
    COUNT(ep.id) AS total_payments,
    SUM(ep.gross_amount) AS total_gross,
    SUM(ep.withholding_tax) AS total_withheld,
    SUM(ep.net_amount) AS total_net,
    MAX(ep.service_date) AS last_payment_date
FROM excluded_subjects es
LEFT JOIN excluded_payments ep ON es.id = ep.subject_id AND ep.status != 'cancelled'
WHERE es.status = 'active'
GROUP BY es.id;
