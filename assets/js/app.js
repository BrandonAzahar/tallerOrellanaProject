/**
 * JavaScript Personalizado
 * Estructuras y Remodelaciones Orellana
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // SISTEMA DE ANIMACIONES Y MICRO-INTERACCIONES
    // ============================================
    
    // 1. Animación escalonada (Staggered) para Tarjetas de Dashboard
    // Explicación: Hacemos que cada tarjeta del dashboard aparezca de forma secuencial
    // aplicando la animación 'animate-scale-in' y multiplicando el índice por un retraso de 80ms (0.08s).
    const dashboardCards = document.querySelectorAll('.dashboard-card');
    dashboardCards.forEach((card, index) => {
        card.classList.add('animate-scale-in');
        card.style.animationDelay = `${index * 0.08}s`;
    });

    // 2. Animación escalonada para Grupos de Formularios
    // Explicación: Hacemos que las diferentes secciones y campos de los formularios
    // suban de manera escalonada (slide-up) al cargar la página para un despliegue suave.
    const formGroups = document.querySelectorAll('.needs-validation .row > div, .needs-validation .mb-3');
    formGroups.forEach((group, index) => {
        group.classList.add('animate-slide-up');
        group.style.animationDelay = `${index * 0.04}s`;
    });

    // 3. Animación escalonada fluida para las Filas de las Tablas
    // Explicación: Para evitar saltos bruscos, primero ocultamos las filas de la tabla
    // y luego con JS las mostramos de forma escalonada (una tras otra con 35ms de delay)
    // dándoles una animación de subida sumamente elástica y satisfactoria.
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(10px)';
        row.style.transition = 'opacity 0.4s ease, transform 0.4s ease, background-color 0.25s ease';
        
        setTimeout(() => {
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 100 + (index * 35));
    });

    // 4. Animación para tarjetas estándar (formularios, listados)
    // Explicación: Aplica un retraso escalonado a tarjetas generales de listas o formularios
    // para dar coherencia visual en todo el sistema.
    const standardCards = document.querySelectorAll('.card:not(.dashboard-card):not(.login-card)');
    standardCards.forEach((card, index) => {
        card.classList.add('animate-slide-up');
        card.style.animationDelay = `${index * 0.06}s`;
    });

    // 5. Efecto de micro-ripple (onda táctil/clic) en Botones
    // Explicación: Al presionar un botón, calculamos la coordenada exacta del cursor
    // en relación al botón, creamos un span circular absolute temporal, y disparamos
    // la animación de onda (btnRipple) en CSS. Luego eliminamos el elemento tras 500ms.
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
        btn.addEventListener('mousedown', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const ripple = document.createElement('span');
            ripple.style.position = 'absolute';
            ripple.style.width = '100px';
            ripple.style.height = '100px';
            ripple.style.background = 'rgba(255, 255, 255, 0.25)';
            ripple.style.borderRadius = '50%';
            ripple.style.transform = 'scale(0)';
            ripple.style.left = `${x - 50}px`;
            ripple.style.top = `${y - 50}px`;
            ripple.style.pointerEvents = 'none';
            ripple.style.animation = 'btnRipple 0.5s ease-out forwards';
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            
            // Si el botón es claro o de borde, usamos una onda oscura para mejor contraste
            if (this.classList.contains('btn-light') || this.classList.contains('btn-outline-primary') || this.classList.contains('btn-link')) {
                ripple.style.background = 'rgba(45, 90, 135, 0.15)';
            }
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 500);
        });
    });

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
    // VALIDACIÓN ROBUSTA DE FORMULARIOS
    // ============================================

    // Validar DUI en tiempo real
    function validateDUIRealTime(dui) {
        const cleaned = dui.replace(/[-\s]/g, '');
        if (!/^\d{9}$/.test(cleaned)) return false;
        const body = cleaned.substring(0, 8);
        const check = cleaned.substring(8);
        let sum = 0;
        for (let i = 0; i < 8; i++) {
            sum += parseInt(body[i]) * (9 - i);
        }
        const expected = (10 - (sum % 10)) % 10;
        return parseInt(check) === expected;
    }

    // Validar NIT en tiempo real
    function validateNITRealTime(nit) {
        const cleaned = nit.replace(/[-\s]/g, '');
        return /^\d{14}$/.test(cleaned);
    }

    // Validar teléfono
    function validatePhoneRealTime(phone) {
        const cleaned = phone.replace(/[-\s()]/g, '');
        return /^[267]\d{7}$/.test(cleaned) || /^503\d{8}$/.test(cleaned);
    }

    // Feedback visual en campos de DUI
    document.querySelectorAll('.dui-input').forEach(input => {
        input.addEventListener('blur', function() {
            const val = this.value.trim();
            if (val && !validateDUIRealTime(val)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                let feedback = this.parentElement.querySelector('.invalid-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback d-block';
                    feedback.textContent = 'DUI inválido. Verifique el dígito verificador.';
                    this.parentElement.appendChild(feedback);
                }
            } else if (val) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });

    // Feedback visual en campos de NIT
    document.querySelectorAll('.nit-input').forEach(input => {
        input.addEventListener('blur', function() {
            const val = this.value.trim();
            if (val && !validateNITRealTime(val)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                let feedback = this.parentElement.querySelector('.invalid-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback d-block';
                    feedback.textContent = 'NIT inválido. Debe tener 14 dígitos.';
                    this.parentElement.appendChild(feedback);
                }
            } else if (val) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });

    // Feedback visual en campos de teléfono
    document.querySelectorAll('.phone-input').forEach(input => {
        input.addEventListener('blur', function() {
            const val = this.value.trim();
            if (val && !validatePhoneRealTime(val)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (val) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });

    // Validar monto en formularios de pago
    document.querySelectorAll('#gross_amount').forEach(input => {
        input.addEventListener('input', function() {
            const val = parseFloat(this.value);
            if (val <= 0) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });

    // Prevenir envío de formularios con errores
    document.querySelectorAll('form.needs-validation').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Verificar campos inválidos
            const invalidFields = form.querySelectorAll('.is-invalid');
            if (invalidFields.length > 0) {
                e.preventDefault();
                e.stopPropagation();
                // Focus en el primer campo inválido
                invalidFields[0].focus();
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
