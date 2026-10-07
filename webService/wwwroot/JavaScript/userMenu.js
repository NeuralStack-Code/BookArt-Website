/* ===================================
   MENÚ DESPLEGABLE DE USUARIO
   =================================== */

document.addEventListener('DOMContentLoaded', function() {
    const userMenuWrapper = document.querySelector('.user-menu-wrapper');
    const userMenuTrigger = document.querySelector('.user-menu-trigger');

    if (userMenuTrigger) {
        userMenuTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenuWrapper.classList.toggle('open');
        });

        document.addEventListener('click', function(e) {
            if (!userMenuWrapper.contains(e.target)) {
                userMenuWrapper.classList.remove('open');
            }
        });

        cargarContadorCarrito();
    }
});

function cargarContadorCarrito() {
    fetch(api('/carrito/contar'))
        .then(r => r.json())
        .then(result => {
            if (result.success && result.data.count > 0) {
                const badge = document.querySelector('.user-menu-badge');
                if (badge) {
                    badge.textContent = result.data.count;
                    badge.style.display = 'block';
                }
            }
        })
        .catch(err => console.error('Error al cargar contador:', err));
}

/* ===================================
   SISTEMA DE NOTIFICACIONES
   Delega a window.mostrarDialog (funcionModal.js → notificador.php)
   =================================== */

function mostrarDialog(mensaje, tipo = 'info') {
    if (typeof window.mostrarDialog === 'function' && window.mostrarDialog !== mostrarDialog) {
        window.mostrarDialog(mensaje, tipo);
    } else if (typeof window.notify === 'function') {
        window.notify(tipo, mensaje.replace(/^[✅❌⚠️ℹ️\s]+/, ''));
    }
}

function cerrarDialog() {
    const dialog = document.getElementById('warning');
    if (dialog) dialog.close();
}

document.addEventListener('DOMContentLoaded', function() {
    const btnAcept = document.getElementById('btnAcept');
    if (btnAcept) btnAcept.addEventListener('click', cerrarDialog);
});

/* ===================================
   AGREGAR AL CARRITO (CATÁLOGO)
   =================================== */

function agregarAlCarritoCatalogo(idProducto) {
    const formData = new FormData();
    formData.append('IdProducto', idProducto);

    fetch(api('/carrito/agregar'), { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                mostrarDialog('✅ ' + data.message, 'success');
                cargarContadorCarrito();
            } else {
                mostrarDialog('⚠️ ' + data.message, 'error');
            }
        })
        .catch(() => mostrarDialog('❌ Error al agregar al carrito', 'error'));
}

console.log('✅ BookArt - Sistema de menú de usuario cargado correctamente');
