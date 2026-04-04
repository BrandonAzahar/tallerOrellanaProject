/**
 * JavaScript Personalizado
 * Estructuras y Remodelaciones Orellana
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // CONFIRMACIÓN DE ELIMINACIÓN
    // ============================================
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('¿Está seguro de que desea eliminar este registro? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });
    
    // ============================================
    // VALIDACIÓN DE FORMULARIOS
    // ============================================
    const forms = document.querySelectorAll('form.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // ============================================
    // AUTO-CALCULO DE MONTOS
    // ============================================
    const grossAmountInput = document.getElementById('gross_amount');
    if (grossAmountInput) {
        grossAmountInput.addEventListener('blur', function() {
            calculateWithholding();
        });
    }
    
    function calculateWithholding() {
        const grossAmount = parseFloat(document.getElementById('gross_amount')?.value) || 0;
        const threshold = 462.00;
        const rate = 0.10;
        
        let withholding = 0;
        if (grossAmount > threshold) {
            withholding = grossAmount * rate;
        }
        
        const netAmount = grossAmount - withholding;
        
        const withholdingInput = document.getElementById('withholding_tax');
        const netAmountInput = document.getElementById('net_amount');
        
        if (withholdingInput) {
            withholdingInput.value = withholding.toFixed(2);
        }
        if (netAmountInput) {
            netAmountInput.value = netAmount.toFixed(2);
        }
        
        // Mostrar mensaje de retención
        const withholdingMessage = document.getElementById('withholding_message');
        if (withholdingMessage) {
            if (withholding > 0) {
                withholdingMessage.textContent = `Retención de renta (10%): $${withholding.toFixed(2)}`;
                withholdingMessage.className = 'text-warning small';
            } else {
                withholdingMessage.textContent = 'No aplica retención (monto ≤ $462)';
                withholdingMessage.className = 'text-success small';
            }
        }
    }
    
    // ============================================
    // FORMATO DE MONEDA EN INPUTS
    // ============================================
    const currencyInputs = document.querySelectorAll('.currency-input');
    currencyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = parseFloat(this.value) || 0;
            this.value = value.toFixed(2);
        });
    });
    
    // ============================================
    // FORMATO DE TELÉFONO
    // ============================================
    const phoneInputs = document.querySelectorAll('.phone-input');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 8) {
                value = value.substring(0, 8);
            }
            if (value.length > 4) {
                value = value.substring(0, 4) + '-' + value.substring(4);
            }
            this.value = value;
        });
    });
    
    // ============================================
    // FORMATO DE DUI
    // ============================================
    const duiInputs = document.querySelectorAll('.dui-input');
    duiInputs.forEach(input => {
        input.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value.length > 9) {
                value = value.substring(0, 9);
            }
            if (value.length > 8) {
                value = value.substring(0, 8) + '-' + value.substring(8);
            }
            this.value = value;
        });
    });
    
    // ============================================
    // FORMATO DE NIT
    // ============================================
    const nitInputs = document.querySelectorAll('.nit-input');
    nitInputs.forEach(input => {
        input.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value.length > 14) {
                value = value.substring(0, 14);
            }
            
            let formatted = '';
            if (value.length > 0) {
                formatted = value.substring(0, 4);
            }
            if (value.length > 4) {
                formatted += '-' + value.substring(4, 10);
            }
            if (value.length > 10) {
                formatted += '-' + value.substring(10, 13);
            }
            if (value.length > 13) {
                formatted += '-' + value.substring(13, 14);
            }
            
            this.value = formatted;
        });
    });
    
    // ============================================
    // BUSCADOR EN TABLAS
    // ============================================
    const tableSearchInputs = document.querySelectorAll('.table-search');
    tableSearchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    });
    
    // ============================================
    // IMPRIMIR FACTURA
    // ============================================
    const printButtons = document.querySelectorAll('.btn-print');
    printButtons.forEach(button => {
        button.addEventListener('click', function() {
            window.print();
        });
    });
    
    // ============================================
    // PREVISUALIZACIÓN DE IMAGEN
    // ============================================
    const imageInputs = document.querySelectorAll('.image-input');
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById(this.getAttribute('data-preview'));
            
            if (file && preview) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // ============================================
    // CONFIRMACIÓN DE CAMBIOS NO GUARDADOS
    // ============================================
    const unsavedForms = document.querySelectorAll('.unsaved-form');
    let formChanged = false;
    
    unsavedForms.forEach(form => {
        form.addEventListener('input', function() {
            formChanged = true;
        });
        
        form.addEventListener('submit', function() {
            formChanged = false;
        });
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
    
    // ============================================
    // TOOLTIPS Y POPOVERS
    // ============================================
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // ============================================
    // AUTO-OCULTAR ALERTAS
    // ============================================
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // ============================================
    // SELECCIONAR TODO EN CHECKBOX
    // ============================================
    const selectAllCheckbox = document.getElementById('select_all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.select-item');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // ============================================
    // CAMBIO DINÁMICO DE ESTADO
    // ============================================
    const statusSelects = document.querySelectorAll('.status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
    
    // ============================================
    // CONTADOR DE CARACTERES
    // ============================================
    const charCountInputs = document.querySelectorAll('.char-count');
    charCountInputs.forEach(input => {
        const maxLength = input.getAttribute('maxlength');
        const counter = document.getElementById(input.getAttribute('data-counter'));
        
        if (counter && maxLength) {
            const updateCounter = () => {
                const remaining = maxLength - input.value.length;
                counter.textContent = `${remaining} caracteres restantes`;
                
                if (remaining < 20) {
                    counter.className = 'text-danger small';
                } else if (remaining < 50) {
                    counter.className = 'text-warning small';
                } else {
                    counter.className = 'text-muted small';
                }
            };
            
            input.addEventListener('input', updateCounter);
            updateCounter();
        }
    });
    
    // ============================================
    // FILTRO POR FECHA
    // ============================================
    const dateFilters = document.querySelectorAll('.date-filter');
    dateFilters.forEach(filter => {
        filter.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
    
    console.log('Orellana System JS loaded successfully');
});

/**
 * Función para mostrar loading en botones
 */
function showLoading(button) {
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
    
    // Restaurar después de 30 segundos si no se completa
    setTimeout(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    }, 30000);
}

/**
 * Función para copiar al portapapeles
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copiado al portapapeles', 'success');
    }).catch(() => {
        showToast('Error al copiar', 'danger');
    });
}

/**
 * Función para mostrar toast notifications
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

/**
 * Crear contenedor de toasts si no existe
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'position-fixed top-0 end-0 p-3';
    container.style.zIndex = '11';
    document.body.appendChild(container);
    return container;
}

/**
 * Función para exportar tabla a CSV
 */
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => {
            let data = col.innerText.replace(/"/g, '""');
            rowData.push('"' + data + '"');
        });
        csv.push(rowData.join(','));
    });
    
    downloadCSV(csv.join('\n'), filename);
}

/**
 * Función para descargar CSV
 */
function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', filename);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}
