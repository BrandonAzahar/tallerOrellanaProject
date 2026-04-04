# Estructuras y Remodelaciones Orellana - Sistema de Gestión

Sistema web monolítico para la gestión de pagos a sujetos excluidos, inventario de herramientas, préstamos, materiales y clientes.

## 📋 Características Principales

- **Pagos a Sujetos Excluidos**: Registro de pagos con generación automática de facturas fiscales
- **Correlativos Fiscales**: Generación automática de números de factura (EXCL-0001, FACT-0001)
- **Retención de Renta**: Cálculo automático del 10% cuando el monto supera $462
- **Inventario de Herramientas**: Control completo de herramientas y su estado
- **Préstamos de Herramientas**: Seguimiento de herramientas prestadas a trabajadores
- **Inventario de Materiales**: Control de stock con alertas de reposición
- **Gestión de Clientes**: Registro de clientes naturales y empresas
- **Reportes**: Informes de pagos, inventarios y stock

## 🚀 Requisitos

- **PHP**: 7.4 o superior
- **MySQL**: 5.7 o superior
- **Apache**: Con mod_rewrite habilitado
- **Extensiones PHP**: PDO, pdo_mysql, openssl

## 📦 Instalación

### Paso 1: Configurar Base de Datos

1. Inicie XAMPP (Apache y MySQL)
2. Abra phpMyAdmin en http://localhost/phpmyadmin
3. Ejecute el archivo `create_database.sql` o:
   - Cree la base de datos `orellana_payments`
   - Importe el archivo `create_database.sql`

### Paso 2: Configurar Conexión

Edite `config/database.php` si necesita cambiar las credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'orellana_payments');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Paso 3: Copiar Logo

Coloque el logo de la empresa en:
```
imagenes/logo orellana.png
```

### Paso 4: Acceder al Sistema

1. Abra su navegador y vaya a: `http://localhost/TallerOrellana/`
2. Inicie sesión con las credenciales por defecto:

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| admin | admin123 | Administrador |
| operador | admin123 | Operador |

**⚠️ IMPORTANTE**: Cambie las contraseñas inmediatamente después del primer acceso.

## 📁 Estructura de Directorios

```
TallerOrellana/
├── config/
│   ├── database.php          # Configuración de BD
│   └── constants.php         # Constantes del sistema
├── includes/
│   ├── auth.php              # Autenticación y autorización
│   ├── correlation_utils.php # Generación de correlativos
│   ├── helpers.php           # Funciones utilitarias
│   ├── header.php            # Cabecera común
│   ├── footer.php            # Pie de página común
│   └── user_menu.php         # Menú de usuario
├── modules/
│   ├── excluded_subjects/    # Sujetos excluidos (CRUD)
│   ├── excluded_payments/    # Pagos y facturas
│   ├── tools/                # Inventario de herramientas
│   ├── tool_loans/           # Préstamos de herramientas
│   ├── materials/            # Inventario de materiales
│   └── customers/            # Gestión de clientes
├── reports/
│   ├── payments_report.php   # Reporte de pagos
│   ├── inventory_report.php  # Reporte de herramientas
│   └── materials_report.php  # Reporte de materiales
├── assets/
│   ├── css/style.css         # Estilos personalizados
│   ├── js/app.js             # JavaScript personalizado
│   └── images/               # Imágenes del sistema
├── imagenes/
│   └── logo orellana.png     # Logo de la empresa
├── index.php                 # Dashboard principal
├── login.php                 # Página de login
├── logout.php                # Cerrar sesión
└── create_database.sql       # Script de creación de BD
```

## 🔐 Seguridad

- **Contraseñas**: Hasheadas con bcrypt (password_hash)
- **CSRF**: Tokens en todos los formularios
- **SQL Injection**: Prepared statements en todas las consultas
- **XSS**: htmlspecialchars en todos los outputs
- **Sesiones**: Timeout de 30 minutos por inactividad
- **Roles**: Admin y Operator con permisos diferenciados
- **Gestión de Usuarios**: Solo administradores pueden crear/editar usuarios

## 👥 Gestión de Usuarios

El **administrador principal** (admin) puede:
- Crear nuevos usuarios
- Asignar roles (Admin u Operador)
- Activar/desactivar usuarios
- Cambiar contraseñas de cualquier usuario
- Ver historial de accesos

| Rol | Permisos |
|-----|----------|
| **Admin** | Acceso completo a todos los módulos, incluyendo gestión de usuarios |
| **Operator** | Acceso operativo (no puede gestionar usuarios) |

## 💰 Conceptos Fiscales

### Sujeto Excluido
Trabajador que presta servicios eventuales sin relación laboral formal. No está sujeto a ISSS/AFP.

### Retención de Renta
- **Monto ≤ $462**: No aplica retención
- **Monto > $462**: Se retiene el 10% del monto bruto

### Correlativos Fiscales
- **EXCL-0001**: Pagos a sujetos excluidos
- **FACT-0001**: Facturas a clientes
- Se reinician cada año calendario

## 📊 Módulos del Sistema

### 1. Sujetos Excluidos
- Registro de trabajadores con DUI, NIT, ocupación
- Historial de pagos por trabajador
- Estado activo/inactivo

### 2. Pagos
- Registro de pagos con descripción del servicio
- Generación automática de correlativo fiscal
- Cálculo automático de retención
- Impresión de factura formato fiscal

### 3. Herramientas
- Inventario con código único
- Estado: disponible, prestada, mantenimiento, dañada, perdida
- Condición: excelente, buena, regular, mala

### 4. Préstamos
- Registro de préstamos a sujetos excluidos
- Fecha esperada de devolución
- Control de condición al prestar/devolver
- Historial completo

### 5. Materiales
- Control de stock por SKU
- Alertas de stock bajo
- Movimientos (entradas/salidas/ajustes)
- Múltiples unidades de medida

### 6. Clientes
- Personas naturales y empresas
- NIT/DUI para facturación

## 🖨️ Formato de Factura

La factura de sujeto excluido incluye:
- Logo de la empresa
- NIT y Registro de la empresa
- Correlativo fiscal único
- Datos completos del sujeto excluido
- Detalle del servicio prestado
- Monto en letras
- Desglose: Sumas, Retención, Total
- Espacio para firma

## 🔧 Funciones Utilitarias

### Helpers Disponibles
- `formatMoney($amount)`: Formatea como moneda
- `formatDate($date)`: Formatea fecha
- `validateDUI($dui)`: Valida DUI de El Salvador
- `validateNIT($nit)`: Valida NIT de El Salvador
- `validatePhone($phone)`: Valida teléfono
- `calculateWithholdingTax($amount)`: Calcula retención 10%
- `numberToWords($number)`: Convierte número a letras

### Correlativos
- `generateCorrelation($conn, $type)`: Genera correlativo único
- `getLastCorrelation($conn, $type)`: Obtiene último correlativo

## 📱 Diseño Responsivo

El sistema utiliza Bootstrap 5.3 y es completamente responsivo:
- Funciona en desktop, tablet y móvil
- Menú colapsable en pantallas pequeñas
- Tablas con scroll horizontal en móvil

## 🐛 Solución de Problemas

### Error de conexión a la base de datos
- Verifique que MySQL esté ejecutándose en XAMPP
- Confirme las credenciales en `config/database.php`

### Las páginas no cargan
- Verifique que Apache esté ejecutándose
- Confirme que la URL sea correcta: `http://localhost/TallerOrellana/`

### Error al imprimir facturas
- Verifique que el logo exista en `imagenes/logo orellana.png`
- Permita ventanas emergentes en el navegador

### Sesión expira rápidamente
- El timeout está configurado en 30 minutos en `config/constants.php`
- Modifique `SESSION_TIMEOUT` si es necesario

## 📞 Soporte

Para soporte técnico o personalizaciones, contacte al administrador del sistema.

---

**© 2026 Estructuras y Remodelaciones Orellana**  
NIT: 0617-240404-104-9 | Registro: 359357-9
