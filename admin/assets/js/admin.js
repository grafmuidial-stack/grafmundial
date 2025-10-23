/**
 * JavaScript do Painel Administrativo
 * Mundial Gráfica
 */

// Inicialização quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    initializeAdmin();
});

/**
 * Inicializa funcionalidades do admin
 */
function initializeAdmin() {
    // Auto-dismiss alerts após 5 segundos
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Confirmar exclusões
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const message = this.getAttribute('data-confirm-delete') || 'Tem certeza que deseja excluir este item?';
            if (confirm(message)) {
                window.location.href = this.href;
            }
        });
    });
    
    // File upload drag and drop
    initializeFileUpload();
    
    // Form validation
    initializeFormValidation();
    
    // Sidebar toggle para mobile
    initializeSidebarToggle();
}

/**
 * Inicializa upload de arquivos com drag and drop
 */
function initializeFileUpload() {
    const uploadAreas = document.querySelectorAll('.file-upload-area');
    
    uploadAreas.forEach(function(area) {
        const input = area.querySelector('input[type="file"]');
        
        // Drag and drop events
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            area.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', function(e) {
            e.preventDefault();
            area.classList.remove('dragover');
        });
        
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            area.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                handleFileSelect(input);
            }
        });
        
        // Click to select
        area.addEventListener('click', function() {
            input.click();
        });
        
        // File input change
        input.addEventListener('change', function() {
            handleFileSelect(this);
        });
    });
}

/**
 * Manipula seleção de arquivo
 */
function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    
    const uploadArea = input.closest('.file-upload-area');
    const preview = uploadArea.querySelector('.file-preview');
    
    // Mostra preview se for imagem
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (preview) {
                preview.innerHTML = `<img src="${e.target.result}" class="image-preview" alt="Preview">`;
            }
        };
        reader.readAsDataURL(file);
    }
    
    // Atualiza texto
    const text = uploadArea.querySelector('.upload-text');
    if (text) {
        text.textContent = `Arquivo selecionado: ${file.name}`;
    }
}

/**
 * Inicializa validação de formulários
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

/**
 * Inicializa toggle da sidebar para mobile
 */
function initializeSidebarToggle() {
    const toggleBtn = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Fecha sidebar ao clicar fora (mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
}

/**
 * Mostra loading em botão
 */
function showButtonLoading(button, text = 'Carregando...') {
    const originalText = button.innerHTML;
    button.innerHTML = `<span class="loading"></span> ${text}`;
    button.disabled = true;
    
    return function() {
        button.innerHTML = originalText;
        button.disabled = false;
    };
}

/**
 * Faz requisição AJAX
 */
function ajaxRequest(url, options = {}) {
    const defaults = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const config = Object.assign(defaults, options);
    
    return fetch(url, config)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
}

/**
 * Mostra notificação toast
 */
function showToast(message, type = 'success') {
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    
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
    
    // Remove toast após ser ocultado
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

/**
 * Cria container para toasts
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1055';
    document.body.appendChild(container);
    return container;
}

/**
 * Confirma ação com modal
 */
function confirmAction(title, message, callback) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">${title}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger confirm-btn">Confirmar</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    const bsModal = new bootstrap.Modal(modal);
    const confirmBtn = modal.querySelector('.confirm-btn');
    
    confirmBtn.addEventListener('click', function() {
        callback();
        bsModal.hide();
    });
    
    modal.addEventListener('hidden.bs.modal', function() {
        modal.remove();
    });
    
    bsModal.show();
}

/**
 * Formata data para exibição
 */
function formatDate(date) {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Busca em tempo real
 */
function initializeSearch(inputSelector, resultsSelector, searchUrl) {
    const input = document.querySelector(inputSelector);
    const results = document.querySelector(resultsSelector);
    
    if (!input || !results) return;
    
    const debouncedSearch = debounce(function(query) {
        if (query.length < 2) {
            results.innerHTML = '';
            return;
        }
        
        ajaxRequest(`${searchUrl}?q=${encodeURIComponent(query)}`)
            .then(data => {
                results.innerHTML = data.html || '';
            })
            .catch(error => {
                console.error('Erro na busca:', error);
                results.innerHTML = '<p class="text-danger">Erro ao buscar resultados</p>';
            });
    }, 300);
    
    input.addEventListener('input', function() {
        debouncedSearch(this.value);
    });
}

/**
 * Auto-save de formulário
 */
function initializeAutoSave(formSelector, saveUrl, interval = 30000) {
    const form = document.querySelector(formSelector);
    if (!form) return;
    
    let lastSave = Date.now();
    let hasChanges = false;
    
    // Detecta mudanças
    form.addEventListener('input', function() {
        hasChanges = true;
    });
    
    // Auto-save periódico
    setInterval(function() {
        if (hasChanges && Date.now() - lastSave > interval) {
            const formData = new FormData(form);
            
            ajaxRequest(saveUrl, {
                method: 'POST',
                body: formData
            })
            .then(data => {
                if (data.success) {
                    hasChanges = false;
                    lastSave = Date.now();
                    showToast('Rascunho salvo automaticamente', 'info');
                }
            })
            .catch(error => {
                console.error('Erro no auto-save:', error);
            });
        }
    }, 5000);
}

// Exporta funções para uso global
window.AdminJS = {
    showToast,
    confirmAction,
    showButtonLoading,
    ajaxRequest,
    formatDate,
    debounce,
    initializeSearch,
    initializeAutoSave
};