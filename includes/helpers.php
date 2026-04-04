<?php
/**
 * Funciones Helper Utilitarias
 * Estructuras y Remodelaciones Orellana
 */

require_once __DIR__ . '/../config/constants.php';

/**
 * Formatear monto como moneda
 * 
 * @param float $amount Monto a formatear
 * @param string $symbol Símbolo de moneda
 * @return string Monto formateado ej: $1,234.56
 */
function formatMoney($amount, $symbol = CURRENCY_SYMBOL) {
    return $symbol . number_format((float)$amount, CURRENCY_DECIMALS, '.', ',');
}

/**
 * Formatear fecha para mostrar
 * 
 * @param string $date Fecha en formato Y-m-d
 * @param string $format Formato de salida
 * @return string Fecha formateada
 */
function formatDate($date, $format = DATE_FORMAT_DISPLAY) {
    if (empty($date)) {
        return '';
    }
    $dt = DateTime::createFromFormat(DATE_FORMAT, $date);
    if (!$dt) {
        $dt = new DateTime($date);
    }
    return $dt->format($format);
}

/**
 * Formatear fecha y hora para mostrar
 * 
 * @param string $datetime Fecha y hora en formato Y-m-d H:i:s
 * @return string Fecha y hora formateada
 */
function formatDateTime($datetime) {
    if (empty($datetime)) {
        return '';
    }
    $dt = new DateTime($datetime);
    return $dt->format('d/m/Y H:i');
}

/**
 * Validar formato de DUI (El Salvador)
 * Formato: XXXXXXXX-X (8 dígitos + guión + dígito verificador)
 * 
 * @param string $dui DUI a validar
 * @return bool True si es válido
 */
function validateDUI($dui) {
    if (empty($dui)) {
        return false;
    }
    
    // Remover guiones y espacios
    $dui = str_replace(['-', ' '], '', $dui);
    
    // Validar que sean 9 dígitos (8 + verificador)
    if (!preg_match('/^\d{9}$/', $dui)) {
        return false;
    }
    
    // Validar dígito verificador
    $body = substr($dui, 0, 8);
    $check = substr($dui, 8, 1);
    
    // Calcular dígito verificador
    $sum = 0;
    for ($i = 0; $i < 8; $i++) {
        $sum += (int)$body[$i] * (9 - $i);
    }
    $expectedCheck = (10 - ($sum % 10)) % 10;
    
    return (string)$expectedCheck === $check;
}

/**
 * Validar formato de NIT (El Salvador)
 * Formato: XXXX-XXXXXX-XXX-X
 *
 * @param string $nit NIT a validar
 * @return bool True si es válido
 */
function validateNIT($nit) {
    if (empty($nit)) {
        return false;
    }

    // Remover guiones y espacios
    $nit = str_replace(['-', ' '], '', $nit);

    // Validar que sean 14 dígitos
    if (!preg_match('/^\d{14}$/', $nit)) {
        return false;
    }

    return true;
}

/**
 * Validar formato de teléfono (El Salvador)
 * 8 dígitos, puede tener guión
 * 
 * @param string $phone Teléfono a validar
 * @return bool True si es válido
 */
function validatePhone($phone) {
    if (empty($phone)) {
        return false;
    }
    
    // Remover guiones, espacios y paréntesis
    $phone = preg_replace('/[\-\s\(\)]/', '', $phone);
    
    // Validar que sean 8 dígitos (teléfono local) o 12 con código de país
    return preg_match('/^[267]\d{7}$/', $phone) || preg_match('/^503\d{8}$/', $phone);
}

/**
 * Calcular retención de renta para sujetos excluidos
 * 
 * @param float $grossAmount Monto bruto
 * @return float Monto de retención (10% si aplica)
 */
function calculateWithholdingTax($grossAmount) {
    $amount = (float)$grossAmount;
    
    if ($amount > WITHHOLDING_THRESHOLD) {
        return round($amount * WITHHOLDING_RATE, 2);
    }
    
    return 0.00;
}

/**
 * Calcular monto neto después de retención
 * 
 * @param float $grossAmount Monto bruto
 * @return float Monto neto
 */
function calculateNetAmount($grossAmount) {
    $gross = (float)$grossAmount;
    $withholding = calculateWithholdingTax($gross);
    return round($gross - $withholding, 2);
}

/**
 * Convertir número a letras (en español)
 * Soporta hasta millones y centavos
 * 
 * @param float $number Número a convertir
 * @param string $currency Moneda (opcional)
 * @return string Número en letras
 */
function numberToWords($number, $currency = 'dólares') {
    $number = (float)$number;
    
    if ($number == 0) {
        return 'cero con 00/100';
    }
    
    // Separar parte entera y decimal
    $integerPart = floor($number);
    $decimalPart = round(($number - $integerPart) * 100);
    
    $words = convertIntegerToWords($integerPart);
    
    // Agregar centavos
    $words .= ' con ' . str_pad($decimalPart, 2, '0', STR_PAD_LEFT) . '/100';
    
    return strtolower($words);
}

/**
 * Convertir número entero a letras (función auxiliar)
 */
function convertIntegerToWords($number) {
    $units = ['', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
    $teens = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];
    $tens = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
    $hundreds = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];
    
    if ($number == 0) {
        return 'cero';
    }
    
    if ($number == 100) {
        return 'cien';
    }
    
    $words = '';
    
    // Millones
    if ($number >= 1000000) {
        $millions = floor($number / 1000000);
        $number %= 1000000;
        
        if ($millions == 1) {
            $words .= 'un millón ';
        } else {
            $words .= convertIntegerToWords($millions) . ' millones ';
        }
    }
    
    // Miles
    if ($number >= 1000) {
        $thousands = floor($number / 1000);
        $number %= 1000;
        
        if ($thousands == 1) {
            $words .= 'mil ';
        } else {
            $words .= convertIntegerToWords($thousands) . ' mil ';
        }
    }
    
    // Cientos
    if ($number >= 100) {
        $hundredsDigit = floor($number / 100);
        $number %= 100;
        
        if ($hundredsDigit == 1 && $number == 0) {
            $words .= 'cien';
            return trim($words);
        }
        
        $words .= $hundreds[$hundredsDigit] . ' ';
    }
    
    // Decenas y unidades
    if ($number >= 10) {
        if ($number < 20) {
            $words .= $teens[$number - 10] . ' ';
        } else {
            $tensDigit = floor($number / 10);
            $unitsDigit = $number % 10;
            
            if ($unitsDigit == 0) {
                $words .= $tens[$tensDigit] . ' ';
            } elseif ($tensDigit == 2) {
                $words .= 'veinti' . $units[$unitsDigit] . ' ';
            } else {
                $words .= $tens[$tensDigit] . ' y ' . $units[$unitsDigit] . ' ';
            }
        }
    } elseif ($number > 0) {
        $words .= $units[$number] . ' ';
    }
    
    // Reemplazar "un" por "uno" al final
    $words = trim($words);
    if (substr($words, -2) === 'un' && strlen($words) > 2) {
        $words = substr($words, 0, -2) . 'uno';
    }
    
    return trim($words);
}

/**
 * Sanitizar entrada de texto
 * 
 * @param string $data Datos a sanitizar
 * @return string Datos sanitizados
 */
function sanitizeText($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, CHARSET);
    return $data;
}

/**
 * Sanitizar entrada para usar en base de datos (sin htmlspecialchars)
 * 
 * @param string $data Datos a sanitizar
 * @return string Datos sanitizados
 */
function sanitizeDbInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Redireccionar con mensaje flash
 * 
 * @param string $url URL de destino
 * @param string $type Tipo de mensaje (success, error, warning, info)
 * @param string $message Mensaje
 */
function redirectWithFlash($url, $type, $message) {
    $_SESSION['flash_' . $type] = $message;
    header('Location: ' . $url);
    exit();
}

/**
 * Obtener la URL actual
 * 
 * @return string URL actual
 */
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || 
                 (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) 
                 ? "https://" : "http://";
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Generar código único para herramientas/materiales
 * 
 * @param string $prefix Prefijo del código
 * @param int $length Longitud del código numérico
 * @return string Código único
 */
function generateUniqueCode($prefix, $length = 3) {
    return $prefix . '-' . strtoupper(bin2hex(random_bytes($length)));
}

/**
 * Calcular edad a partir de fecha de nacimiento
 * 
 * @param string $birthDate Fecha de nacimiento (Y-m-d)
 * @return int Edad en años
 */
function calculateAge($birthDate) {
    $birth = new DateTime($birthDate);
    $today = new DateTime();
    $diff = $today->diff($birth);
    return $diff->y;
}

/**
 * Obtener nombre del mes en español
 * 
 * @param int $month Número del mes (1-12)
 * @return string Nombre del mes
 */
function getMonthName($month) {
    $months = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $months[(int)$month] ?? '';
}

/**
 * Obtener diferencia entre dos fechas en días
 * 
 * @param string $date1 Fecha 1 (Y-m-d)
 * @param string $date2 Fecha 2 (Y-m-d)
 * @return int Diferencia en días
 */
function dateDiffInDays($date1, $date2) {
    $d1 = new DateTime($date1);
    $d2 = new DateTime($date2);
    $diff = $d1->diff($d2);
    return $diff->days;
}

/**
 * Verificar si una fecha es mayor a otra
 * 
 * @param string $date1 Fecha 1 (Y-m-d)
 * @param string $date2 Fecha 2 (Y-m-d)
 * @return bool True si date1 > date2
 */
function isDateGreater($date1, $date2) {
    return strtotime($date1) > strtotime($date2);
}

/**
 * Formatear estado para visualización
 * 
 * @param string $status Estado
 * @return array ['class' => 'badge-class', 'label' => 'Label']
 */
function formatStatus($status) {
    $statuses = [
        'active' => ['class' => 'bg-success', 'label' => 'Activo'],
        'inactive' => ['class' => 'bg-secondary', 'label' => 'Inactivo'],
        'pending' => ['class' => 'bg-warning text-dark', 'label' => 'Pendiente'],
        'paid' => ['class' => 'bg-success', 'label' => 'Pagado'],
        'cancelled' => ['class' => 'bg-danger', 'label' => 'Cancelado'],
        'available' => ['class' => 'bg-success', 'label' => 'Disponible'],
        'loaned' => ['class' => 'bg-info text-dark', 'label' => 'Prestado'],
        'maintenance' => ['class' => 'bg-warning text-dark', 'label' => 'Mantenimiento'],
        'damaged' => ['class' => 'bg-danger', 'label' => 'Dañado'],
        'lost' => ['class' => 'bg-dark', 'label' => 'Perdido'],
        'returned' => ['class' => 'bg-success', 'label' => 'Devuelto'],
        'overdue' => ['class' => 'bg-danger', 'label' => 'Vencido'],
        'excellent' => ['class' => 'bg-success', 'label' => 'Excelente'],
        'good' => ['class' => 'bg-primary', 'label' => 'Bueno'],
        'fair' => ['class' => 'bg-warning text-dark', 'label' => 'Regular'],
        'poor' => ['class' => 'bg-danger', 'label' => 'Malo']
    ];
    
    return $statuses[$status] ?? ['class' => 'bg-secondary', 'label' => ucfirst($status)];
}
