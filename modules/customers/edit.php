<?php
/**
 * Editar Cliente
 * Estructuras y Remodelaciones Orellana
 */

$pageTitle = 'Editar Cliente - Orellana';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDbConnection();
$errors = [];

$id = isset($_GET['id']) ? decryptId($_GET['id']) : 0;
$sql = "SELECT * FROM customers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Cliente no encontrado');
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido';
    } else {
        $type = $_POST['type'] ?? 'individual';
        $nit = !empty($_POST['nit']) ? sanitizeDbInput($_POST['nit']) : null;
        $dui = !empty($_POST['dui']) ? sanitizeDbInput($_POST['dui']) : null;
        $firstName = sanitizeDbInput($_POST['first_name'] ?? '');
        $lastName = sanitizeDbInput($_POST['last_name'] ?? '');
        $companyName = sanitizeDbInput($_POST['company_name'] ?? '');
        $phone = sanitizeDbInput($_POST['phone'] ?? '');
        $email = sanitizeDbInput($_POST['email'] ?? '');
        $address = sanitizeDbInput($_POST['address'] ?? '');
        
        if ($type === 'company' && empty($companyName)) $errors[] = 'Nombre de empresa obligatorio';
        if ($type === 'individual' && (empty($firstName) || empty($lastName))) $errors[] = 'Nombre y apellido obligatorios';
        
        if (empty($errors)) {
            try {
                $sql = "UPDATE customers SET type=?, nit=?, dui=?, first_name=?, last_name=?, 
                        company_name=?, phone=?, email=?, address=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$type, $nit, $dui, $firstName, $lastName, $companyName, $phone, $email, $address, $id]);
                setFlash('success', 'Cliente actualizado');
                header('Location: index.php');
                exit();
            } catch (PDOException $e) {
                $errors[] = 'Error al actualizar';
            }
        }
    }
}

$csrf_token = generateCsrfToken();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil me-2"></i>Editar Cliente</h2>
    <a href="index.php" class="btn btn-outline-secondary">Volver</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php echo implode('<br>', $errors); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="col-12 col-md-4">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="type" id="typeSelector" onchange="toggleFields()">
                    <option value="individual" <?php echo $customer['type'] === 'individual' ? 'selected' : ''; ?>>Persona Natural</option>
                    <option value="company" <?php echo $customer['type'] === 'company' ? 'selected' : ''; ?>>Empresa</option>
                </select>
            </div>
            
            <div class="col-12 company-field" style="display: <?php echo $customer['type'] === 'company' ? 'block' : 'none'; ?>">
                <label class="form-label">Nombre de la Empresa</label>
                <input type="text" class="form-control" name="company_name" value="<?php echo htmlspecialchars($customer['company_name']); ?>">
            </div>
            
            <div class="col-12 col-md-6 individual-field" style="display: <?php echo $customer['type'] !== 'company' ? 'block' : 'none'; ?>">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>">
            </div>
            <div class="col-12 col-md-6 individual-field" style="display: <?php echo $customer['type'] !== 'company' ? 'block' : 'none'; ?>">
                <label class="form-label">Apellido</label>
                <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($customer['last_name']); ?>">
            </div>
            
            <div class="col-12 col-md-6">
                <label class="form-label">NIT</label>
                <input type="text" class="form-control nit-input" name="nit" value="<?php echo htmlspecialchars($customer['nit']); ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">DUI</label>
                <input type="text" class="form-control dui-input" name="dui" value="<?php echo htmlspecialchars($customer['dui']); ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Teléfono</label>
                <input type="text" class="form-control phone-input" name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Dirección</label>
                <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($customer['address']); ?></textarea>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Actualizar</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleFields() {
    const type = document.getElementById('typeSelector').value;
    document.querySelectorAll('.company-field').forEach(f => f.style.display = type === 'company' ? 'block' : 'none');
    document.querySelectorAll('.individual-field').forEach(f => f.style.display = type !== 'company' ? 'block' : 'none');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
