function toggleMenu() {
    const nav = document.getElementById('mainNav');
    const toggle = document.getElementById('menuToggle');
    
    if (!nav || !toggle) return;
    
    nav.classList.toggle('active');
    
    const icon = toggle.querySelector('.material-symbols-outlined');
    if (icon) {
        icon.textContent = nav.classList.contains('active') ? 'close' : 'menu';
    }
    
    document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
}

// Función auxiliar para mostrar diálogo personalizado
function mostrarDialog(mensaje, tipo = 'info') {
    window.BookArtNotification.show(mensaje, tipo);
}

// Event listener para cerrar diálogos
document.addEventListener('DOMContentLoaded', function() {
    const btnAcept = document.getElementById('btnAcept');
    if (btnAcept) {
        btnAcept.addEventListener('click', function() {
            const dialog = document.getElementById('warning');
            if (dialog) {
                dialog.close();
            }
        });
    }
});

// --- Variables globales ---
let itemToRemove = null;

// --- FUNCIÓN PARA ABRIR MODAL DE ELIMINACIÓN ---
function removeItem(id) {
    itemToRemove = { id };
    const dialog = document.getElementById('deleteDialog');
    if (dialog) {
        dialog.showModal();
    }
}

// --- FUNCIÓN PARA CANCELAR ELIMINACIÓN ---
function cancelarEliminacion() {
    const dialog = document.getElementById('deleteDialog');
    if (dialog) {
        dialog.close();
    }
    itemToRemove = null;
}

// --- FUNCIÓN PARA CONFIRMAR ELIMINACIÓN ---
function confirmarEliminacion() {
    if (!itemToRemove) return;

    const { id } = itemToRemove;

    const dialog = document.getElementById('deleteDialog');
    if (dialog) dialog.close();

    const formData = new FormData();
    formData.append('IdPedido', id);

    fetch(api('/carrito/quitar'), {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        window.notifyResponse(data);
        if (data.success) setTimeout(() => location.reload(), 1200);
    })
    .catch(() => window.notify('error', 'Error de conexión al eliminar.'))
    .finally(() => { itemToRemove = null; });
}

// --- FUNCIÓN PARA REALIZAR PEDIDO ---
function realizarPedido() {
    const dialog = document.getElementById('confirmDialog');
    if (dialog) {
        dialog.showModal();
    }
}

// --- FUNCIÓN PARA CONFIRMAR PEDIDO ---
function confirmarPedido() {
    const dialog = document.getElementById('confirmDialog');
    if (dialog) dialog.close();

    fetch(api('/carrito/confirmar'), { method: 'POST' })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            window.location.href = api(result.data.redirect || '/pedidos');
        } else {
            window.notifyResponse(result);
        }
    })
    .catch(() => window.notify('error', 'Error de conexión. Por favor intenta nuevamente.'));
}