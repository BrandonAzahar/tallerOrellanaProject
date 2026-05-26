<?php
/**
 * Página de Login
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error_message = '';
$success_message = '';

// Manejar mensaje de logout
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success_message = 'Sesión cerrada exitosamente.';
}

// Manejar mensaje de sesión expirada
if (isset($_GET['expired']) && $_GET['expired'] === '1') {
    $error_message = 'Su sesión ha expirado por inactividad. Por favor, inicie sesión nuevamente.';
}

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Token de seguridad inválido. Intente nuevamente.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validaciones básicas
        if (empty($username) || empty($password)) {
            $error_message = 'Por favor ingrese usuario y contraseña.';
        } else {
            try {
                $conn = getDbConnection();
                
                // Buscar usuario por username
                $sql = "SELECT id, username, password, full_name, email, role, status 
                        FROM users 
                        WHERE username = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Verificar si el usuario está activo
                    if ($user['status'] !== 'active') {
                        $error_message = 'Su cuenta está desactivada. Contacte al administrador.';
                    } else {
                        // Login exitoso
                        login($user);
                        
                        // Redirigir a la página original o al dashboard
                        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                        unset($_SESSION['redirect_after_login']);
                        header('Location: ' . $redirect);
                        exit();
                    }
                } else {
                    $error_message = 'Usuario o contraseña incorrectos.';
                }
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    $error_message = 'Error de conexión: ' . $e->getMessage();
                } else {
                    $error_message = 'Error del sistema. Intente nuevamente.';
                }
                error_log("Login error: " . $e->getMessage());
            }
        }
    }
}

// Generar token CSRF
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Estructuras y Remodelaciones Orellana</title>
    
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Outfit', sans-serif;
            /* ANIMACIÓN: Fondo con gradiente dinámico en movimiento continuo (15 segundos) */
            background: linear-gradient(-45deg, #12223a, #1e3a5f, #2d5a87, #1a2f4c);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            opacity: 1 !important; /* Evita parpadeo durante la carga del login */
            overflow-x: hidden; /* MODIFICACIÓN RESPONSIVE: Evita scrollbars horizontales causados por animaciones en móviles */
        }
        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 1.5rem 4rem rgba(0,0,0,0.3);
            max-width: 420px;
            width: 100%;
            overflow: hidden;
            /* ANIMACIÓN: Entrada elástica (slide-up) desde abajo con curva cubic-bezier premium */
            opacity: 0;
            transform: translateY(30px);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .login-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }
        .login-logo {
            max-width: 120px;
            max-height: 120px;
            margin-bottom: 1rem;
            background: white;
            border-radius: 50%;
            padding: 0.5rem;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            /* ANIMACIÓN: Efecto flotante lento (float) en el eje vertical (6 segundos infinite) */
            animation: float 6s ease-in-out infinite;
        }
        .login-body {
            padding: 2.5rem 2rem;
        }
        .form-floating > label {
            color: #6c757d;
        }
        .btn-login {
            padding: 0.75rem;
            font-size: 1.1rem;
            font-weight: 500;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
            border: none;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #254670 0%, #366c9f 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(45, 90, 135, 0.3);
        }
        .btn-login:active {
            transform: scale(0.98);
        }
        .footer-login {
            background: #111;
            color: #888;
            padding: 1.25rem;
            text-align: center;
            font-size: 0.85rem;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8 col-xl-6 d-flex justify-content-center">
                    <div class="login-card">
                        <div class="login-header">
                            <?php if (file_exists(LOGO_PATH)): ?>
                                <img src="imagenes/logo orellana.png" alt="Logo Orellana" class="login-logo">
                            <?php else: ?>
                                <div class="login-logo d-flex align-items-center justify-content-center bg-white rounded-circle mx-auto" style="width: 120px; height: 120px;">
                                    <i class="bi bi-building text-primary" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <h4 class="mb-1 fw-bold">Estructuras y Remodelaciones Orellana</h4>
                            <p class="mb-0 opacity-75">Sistema de Gestión</p>
                        </div>
                        <div class="login-body">
                            <?php if ($success_message): ?>
                                <div class="alert alert-success alert-dismissible fade show animate-fade-in" role="alert">
                                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger alert-dismissible fade show animate-fade-in" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" autocomplete="off">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                
                                <div class="form-floating mb-3 animate-slide-up delay-1">
                                    <input type="text" 
                                           class="form-control <?php echo $error_message ? 'is-invalid' : ''; ?>" 
                                           id="username" 
                                           name="username" 
                                           placeholder="Usuario"
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                           required 
                                           autofocus>
                                    <label for="username"><i class="bi bi-person me-2"></i>Usuario</label>
                                    <?php if ($error_message): ?>
                                        <div class="invalid-feedback">Usuario o contraseña incorrectos</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-floating mb-4 animate-slide-up delay-2">
                                    <input type="password" 
                                           class="form-control <?php echo $error_message ? 'is-invalid' : ''; ?>" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Contraseña"
                                           required>
                                    <label for="password"><i class="bi bi-lock me-2"></i>Contraseña</label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-login w-100 animate-slide-up delay-3">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                                </button>
                            </form>
                            
                            <div class="text-center mt-4 animate-fade-in delay-4">
                                <small class="text-muted">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Sistema seguro y confidencial
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer-login">
        <div class="container">
            <p class="mb-0">
                © <?php echo date('Y'); ?> Estructuras y Remodelaciones Orellana<br>
                NIT: <?php echo COMPANY_NIT; ?> | Registro: <?php echo COMPANY_REGISTRY; ?>
            </p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
