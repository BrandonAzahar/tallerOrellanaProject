<?php
/**
 * Utilitarios para Correlativos Fiscales
 * Estructuras y Remodelaciones Orellana
 * 
 * Genera correlativos autoincrementables únicos por año y tipo de documento
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

/**
 * Generar correlativo fiscal
 * 
 * Usa transacción con FOR UPDATE para prevenir condiciones de carrera
 * 
 * @param PDO $conn Conexión a la base de datos
 * @param string $documentType Tipo de documento ('excluded_payment', 'invoice')
 * @param int|null $year Año (null = año actual)
 * @return array ['correlation' => 'EXCL-0001', 'number' => 1, 'year' => 2026]
 * @throws Exception Si falla la generación
 */
function generateCorrelation($conn, $documentType, $year = null) {
    if ($year === null) {
        $year = date('Y');
    }

    $ownTransaction = false;

    try {
        // Iniciar transacción solo si no hay una activa
        if (!$conn->inTransaction()) {
            $conn->beginTransaction();
            $ownTransaction = true;
        }

        // Bloquear el registro para actualización (FOR UPDATE)
        $sql = "SELECT id, last_number, prefix
                FROM correlations
                WHERE document_type = ? AND year = ?
                FOR UPDATE";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$documentType, $year]);
        $correlation = $stmt->fetch();

        if (!$correlation) {
            // Si no existe, crear nuevo registro
            $prefix = strtoupper(substr($documentType, 0, 4));
            if ($documentType === 'excluded_payment') {
                $prefix = 'EXCL';
            } elseif ($documentType === 'invoice') {
                $prefix = 'FACT';
            }

            $sql = "INSERT INTO correlations (document_type, year, last_number, prefix)
                    VALUES (?, ?, 0, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$documentType, $year, $prefix]);

            $lastNumber = 0;
            $prefix = $prefix;
        } else {
            $lastNumber = $correlation['last_number'];
            $prefix = $correlation['prefix'];
        }

        // Incrementar el número
        $newNumber = $lastNumber + 1;

        // Actualizar el registro
        $sql = "UPDATE correlations
                SET last_number = ?
                WHERE document_type = ? AND year = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$newNumber, $documentType, $year]);

        // Confirmar transacción solo si es propia
        if ($ownTransaction) {
            $conn->commit();
        }

        // Formatear correlativo: EXCL-0001
        $formattedNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        $correlationCode = $prefix . '-' . $formattedNumber;

        return [
            'correlation' => $correlationCode,
            'number' => $newNumber,
            'year' => $year,
            'prefix' => $prefix,
            'formatted_number' => $formattedNumber
        ];

    } catch (Exception $e) {
        // Revertir transacción solo si es propia
        if ($ownTransaction && $conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }
}

/**
 * Obtener el último correlativo generado
 * 
 * @param PDO $conn Conexión a la base de datos
 * @param string $documentType Tipo de documento
 * @param int|null $year Año (null = año actual)
 * @return array|null Datos del correlativo o null si no existe
 */
function getLastCorrelation($conn, $documentType, $year = null) {
    if ($year === null) {
        $year = date('Y');
    }
    
    try {
        $sql = "SELECT id, document_type, year, last_number, prefix 
                FROM correlations 
                WHERE document_type = ? AND year = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$documentType, $year]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Obtener todos los correlativos de un año
 * 
 * @param PDO $conn Conexión a la base de datos
 * @param int|null $year Año (null = año actual)
 * @return array Lista de correlativos
 */
function getAllCorrelations($conn, $year = null) {
    if ($year === null) {
        $year = date('Y');
    }
    
    try {
        $sql = "SELECT id, document_type, year, last_number, prefix 
                FROM correlations 
                WHERE year = ? 
                ORDER BY document_type";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$year]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Verificar si un correlativo existe
 * 
 * @param PDO $conn Conexión a la base de datos
 * @param string $correlationCode Código de correlativo (ej: EXCL-0001)
 * @param string $documentType Tipo de documento
 * @return bool True si existe
 */
function correlationExists($conn, $correlationCode, $documentType = null) {
    try {
        if ($documentType) {
            $sql = "SELECT id FROM excluded_payments WHERE invoice_number = ? AND document_type = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$correlationCode, $documentType]);
        } else {
            // Buscar en excluded_payments
            $sql = "SELECT id FROM excluded_payments WHERE invoice_number = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$correlationCode]);
        }
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Reiniciar correlativos para un año específico
 * 
 * @param PDO $conn Conexión a la base de datos
 * @param string $documentType Tipo de documento
 * @param int $year Año
 * @return bool True si se reinició correctamente
 */
function resetCorrelation($conn, $documentType, $year) {
    try {
        $sql = "UPDATE correlations SET last_number = 0 WHERE document_type = ? AND year = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$documentType, $year]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
