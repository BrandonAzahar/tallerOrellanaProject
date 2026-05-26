<?php
/**
 * Imprimir Factura de Sujeto Excluido
 * Estructuras y Remodelaciones Orellana
 * 
 * Formato exacto según especificación
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/audit_utils.php';

$conn = getDbConnection();

// Obtener ID y desencriptar
$id = isset($_GET['id']) ? decryptId($_GET['id']) : 0;

if ($id === false || $id <= 0) {
    die('Pago no válido');
}

// Obtener datos del pago
$sql = "SELECT ep.*, 
               es.first_name, es.last_name, es.dui, es.nit, es.phone, es.address, es.occupation,
               CONCAT(es.first_name, ' ', es.last_name) as full_name
        FROM excluded_payments ep
        JOIN excluded_subjects es ON ep.subject_id = es.id
        WHERE ep.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$payment = $stmt->fetch();

if (!$payment) {
    die('Pago no encontrado');
}

// Registrar vista en logs de auditoría
logAudit($conn, 'view', 'excluded_payments', $id, 'excluded_payments', null, [
    'invoice_number' => $payment['invoice_number']
]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura <?php echo htmlspecialchars($payment['invoice_number']); ?> - Orellana</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            background: #f0f0f0;
        }
        
        .invoice-container {
            max-width: 210mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        /* Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #1e3a5f;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .invoice-logo {
            max-width: 100px;
            max-height: 100px;
        }
        
        .company-info {
            text-align: center;
            flex: 1;
            padding: 0 20px;
        }
        
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 5px;
        }
        
        .company-nit {
            font-size: 9pt;
            color: #666;
        }
        
        .invoice-title {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            background: #1e3a5f;
            color: white;
            padding: 8px 20px;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        .invoice-number {
            text-align: right;
            font-size: 14pt;
            font-weight: bold;
            color: #1e3a5f;
        }
        
        .invoice-number span {
            font-size: 10pt;
            color: #666;
        }
        
        /* Subject Info */
        .subject-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .subject-title {
            font-size: 11pt;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        
        .subject-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        
        .info-row {
            display: flex;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 140px;
            color: #333;
        }
        
        .info-value {
            color: #000;
        }
        
        /* Services Table */
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .services-table th,
        .services-table td {
            border: 1px solid #000;
            padding: 10px;
            text-align: left;
        }
        
        .services-table th {
            background: #1e3a5f;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        
        .services-table td.text-center {
            text-align: center;
        }
        
        .services-table td.text-right {
            text-align: right;
        }
        
        /* Totals */
        .totals-section {
            margin: 20px 0;
        }
        
        .amount-in-words {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px 15px;
            margin-bottom: 15px;
            font-style: italic;
        }
        
        .amount-in-words strong {
            text-transform: uppercase;
        }
        
        .totals-table {
            width: 100%;
            max-width: 350px;
            margin-left: auto;
        }
        
        .totals-table tr td {
            padding: 8px 10px;
        }
        
        .totals-table .label {
            font-weight: bold;
            text-align: right;
        }
        
        .totals-table .value {
            text-align: right;
            padding-left: 20px;
        }
        
        .totals-table .total-row {
            background: #1e3a5f;
            color: white;
            font-size: 13pt;
            font-weight: bold;
        }
        
        /* Signature */
        .signature-section {
            margin-top: 50px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            width: 250px;
            margin: 0 auto 10px;
        }
        
        .signature-label {
            font-weight: bold;
            font-size: 10pt;
        }
        
        .signature-sublabel {
            font-size: 9pt;
            color: #666;
        }
        
        /* Footer */
        .invoice-footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
        
        /* Print Button */
        .no-print {
            text-align: center;
            padding: 20px;
            background: #1e3a5f;
            color: white;
        }
        
        .no-print button {
            padding: 10px 30px;
            font-size: 14pt;
            background: white;
            color: #1e3a5f;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 10px;
        }
        
        .no-print button:hover {
            background: #f0f0f0;
        }
        
        /* Print Styles (MODIFICACIÓN: Garantiza que toda la factura se imprima en una sola página Carta con colores idénticos a la pantalla) */
        @media print {
            body {
                background: white;
                font-size: 9.5pt !important;
                line-height: 1.3 !important;
                -webkit-print-color-adjust: exact !important; /* Fuerza a Chrome, Edge y Safari a imprimir fondos e imágenes de fondo */
                print-color-adjust: exact !important;         /* Fuerza a Firefox a imprimir fondos e imágenes de fondo */
            }
            
            /* MODIFICACIÓN CLAVE: Fuerza a todos los elementos hijos a conservar sus colores, bordes y gradientes originales al imprimir */
            body * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .invoice-container {
                margin: 0 !important;
                padding: 6mm 10mm !important;
                box-shadow: none !important;
                max-width: none !important;
                border: none !important;
            }

            .invoice-header {
                margin-bottom: 10px !important;
                padding-bottom: 8px !important;
            }

            .invoice-title {
                font-size: 14pt !important;
                padding: 6px 15px !important;
                margin: 10px 0 !important;
            }

            .subject-section {
                padding: 10px 12px !important;
                margin: 10px 0 !important;
            }

            .services-table {
                margin: 12px 0 !important;
            }
            
            .services-table th, .services-table td {
                padding: 6px 8px !important;
            }

            .amount-in-words {
                padding: 6px 12px !important;
                margin-bottom: 8px !important;
                font-size: 9pt !important;
            }

            .totals-section {
                margin: 10px 0 !important;
            }

            .totals-table tr td {
                padding: 4px 8px !important;
            }

            .signature-section {
                margin-top: 30px !important;
            }

            .invoice-footer {
                margin-top: 25px !important;
                padding-top: 8px !important;
            }
            
            .no-print {
                display: none !important;
            }
            
            @page {
                margin: 8mm 10mm !important;
                size: letter;
            }
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <!-- Print Button (MODIFICACIÓN: El botón Cerrar ahora regresa a la pantalla anterior o al listado general) -->
    <div class="no-print">
        <button onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimir Factura
        </button>
        <button onclick="goBackOrClose()">
            <i class="bi bi-x-circle"></i> Cerrar
        </button>
    </div>
    
    <!-- Invoice Container -->
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="logo-section">
                <?php if (file_exists(LOGO_PATH)): ?>
                    <img src="<?php echo BASE_URL; ?>imagenes/logo orellana.png" alt="Logo Orellana" class="invoice-logo">
                <?php else: ?>
                    <div style="width: 100px; height: 100px; background: #1e3a5f; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24pt; font-weight: bold;">
                        ORELLANA
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="company-info">
                <div class="company-name"><?php echo COMPANY_NAME; ?></div>
                <div class="company-nit">
                    NIT: <?php echo COMPANY_NIT; ?><br>
                    REGISTRO N° <?php echo COMPANY_REGISTRY; ?>
                </div>
            </div>
            
            <div class="invoice-number">
                <span>N°</span> <?php echo str_replace('EXCL-', '', $payment['invoice_number']); ?>
            </div>
        </div>
        
        <!-- Title -->
        <div class="invoice-title">
            FACTURA DE SUJETO EXCLUIDO
        </div>
        
        <!-- Issue Date -->
        <div style="text-align: right; margin-bottom: 15px;">
            <strong>FECHA DE EMISIÓN:</strong> <?php echo formatDate($payment['created_at'], 'd/m/Y'); ?>
        </div>
        
        <!-- Subject Info -->
        <div class="subject-section">
            <div class="subject-title">DATOS DEL SUJETO EXCLUIDO</div>
            <div class="subject-info">
                <div class="info-row">
                    <span class="info-label">NOMBRE DEL SUJETO EXCLUIDO:</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['full_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">DUI/NIT:</span>
                    <span class="info-value">
                        <?php echo $payment['dui'] ? htmlspecialchars($payment['dui']) : ($payment['nit'] ? htmlspecialchars($payment['nit']) : 'N/A'); ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">DIRECCIÓN:</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['address'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">TELÉFONO:</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['phone'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">OCUPACIÓN:</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['occupation'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Services Table -->
        <table class="services-table">
            <thead>
                <tr>
                    <th style="width: 10%;">CANTIDAD</th>
                    <th style="width: 55%;">DESCRIPCIÓN</th>
                    <th style="width: 15%;">PRECIO UNITARIO</th>
                    <th style="width: 20%;">TOTAL COMPRAS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td>
                        <strong><?php echo htmlspecialchars($payment['service_description']); ?></strong>
                        <?php if (!empty($payment['period_description'])): ?>
                            <br><small style="color: #666;"><?php echo htmlspecialchars($payment['period_description']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">$<?php echo number_format($payment['gross_amount'], 2); ?></td>
                    <td class="text-right"><strong>$<?php echo number_format($payment['gross_amount'], 2); ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <!-- Amount in Words -->
        <div class="amount-in-words">
            <strong>RECIBÍ LA CANTIDAD DE:</strong> 
            <?php echo ucfirst(numberToWords($payment['net_amount'])); ?> dólares de los Estados Unidos de América
        </div>
        
        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">SUMAS:</td>
                    <td class="value">$<?php echo number_format($payment['gross_amount'], 2); ?></td>
                </tr>
                <?php if ($payment['withholding_tax'] > 0): ?>
                <tr>
                    <td class="label">(-) RENTA RETENIDO (10%):</td>
                    <td class="value">$<?php echo number_format($payment['withholding_tax'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td class="label">TOTAL:</td>
                    <td class="value">$<?php echo number_format($payment['net_amount'], 2); ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Signature -->
        <div class="signature-section">
            <div class="signature-line"></div>
            <div class="signature-label">FIRMA DEL SUJETO EXCLUIDO</div>
            <div class="signature-sublabel">
                <?php echo htmlspecialchars($payment['full_name']); ?><br>
                DUI: <?php echo $payment['dui'] ?? 'N/A'; ?>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="invoice-footer">
            <p>
                <strong>IMPRESIÓN AUTORIZADA</strong><br>
                Documento válido como comprobante de pago a sujeto excluido según normativa fiscal vigente.<br>
                Esta factura se emise en cumplimiento de las obligaciones fiscales establecidas.
            </p>
            <p style="margin-top: 10px;">
                Impreso: <?php echo date('d/m/Y H:i:s'); ?> | 
                Factura: <?php echo htmlspecialchars($payment['invoice_number']); ?>
            </p>
        </div>
    </div>
    
    <script>
        // MODIFICACIÓN: Función robusta en JavaScript para el botón Cerrar
        // Si el usuario abrió la factura en una pestaña independiente, intenta cerrarla.
        // De lo contrario, regresa exactamente a la pantalla donde estaba el usuario antes de entrar (historial del sujeto, etc.).
        function goBackOrClose() {
            if (document.referrer === "" || document.referrer.includes("print.php")) {
                // Si no hay referrer (pestaña nueva) o venimos de sí misma, redirecciona al historial de pagos
                window.location.href = "index.php";
            } else {
                // Regresa al historial o pantalla previa desde donde se hizo click
                window.location.href = document.referrer;
            }
        }
    </script>
</body>
</html>
