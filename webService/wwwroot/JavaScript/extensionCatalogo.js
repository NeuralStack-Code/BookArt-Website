// REEMPLAZAR TODO EL BLOQUE <script> al final de Extension_Catalogo.php

function toggleMenu() {
    const nav = document.getElementById('mainNav');
    const toggle = document.getElementById('menuToggle');
    nav.classList.toggle('active');
    
    const icon = toggle.querySelector('.material-symbols-outlined');
    icon.textContent = nav.classList.contains('active') ? 'close' : 'menu';
    
    if (nav.classList.contains('active')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

document.querySelectorAll('.nav-links a, .btn-session').forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            const nav = document.getElementById('mainNav');
            nav.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

document.addEventListener('click', function(e) {
    const nav = document.getElementById('mainNav');
    const toggle = document.getElementById('menuToggle');
    
    if (nav.classList.contains('active') && 
        !nav.contains(e.target) && 
        !toggle.contains(e.target)) {
        toggleMenu();
    }
});

// Función auxiliar para mostrar diálogo personalizado
function mostrarDialog(mensaje, tipo = 'info') {
    window.BookArtNotification.show(mensaje, tipo);
}

// Event listener para cerrar el diálogo
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

// FUNCIÓN PARA AGREGAR AL CARRITO
function agregarAlCarritoCatalogo(idProducto, event) {
    const btn = event.target.closest('button');

    const formData = new FormData();
    formData.append('IdProducto', idProducto);

    fetch(api('/carrito/agregar'), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {

            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span class="material-symbols-outlined">check_circle</span> ¡Agregado!';
            btn.style.background = 'var(--verde-bookart)';
            btn.disabled = true;

            const badge = document.querySelector('.user-menu-badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent) || 0;
                badge.textContent = currentCount + 1;
                badge.style.display = 'block';
            }
            
            mostrarDialog('Producto agregado correctamente.', 'success');

            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.style.background = '';
                btn.disabled = false;
            }, 2000);

        } else {
            mostrarDialog('⚠️ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarDialog('❌ Error al agregar al carrito. Por favor, intenta nuevamente.', 'error');
    });
}
