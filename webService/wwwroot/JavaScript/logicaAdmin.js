// ===== VARIABLES GLOBALES (DECLARADAS AL INICIO) =====
let productoEliminarId = null;
let modoEdicion = false;
let pedidosData = [];
let filtroActualEstatus = 'pendiente';

// FUNCIÓN DE ALERTA PERSONALIZADA PARA ADMIN
// Agregar al INICIO de logicaAdmin.js (después de las variables globales)

function mostrarAlerta(mensaje, tipo = 'info') {
    window.BookArtNotification.show(mensaje, tipo);
}

// ===== INICIALIZACIÓN AL CARGAR LA PÁGINA =====
document.addEventListener('DOMContentLoaded', function() {
    // Cargar principal por defecto
    principal();
    
    // Cargar estadísticas iniciales
    cargarEstadisticas();
    
    // Event listeners para formularios
    setupFormularios();
});

// ===== SETUP DE FORMULARIOS =====
function setupFormularios() {
    // Formulario de producto
    const form = document.getElementById('formProducto');
    if(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);   // con IdProducto es un cambio; sin él, un alta

            if(!modoEdicion && !formData.get('Imagen').size) {
                mostrarAlerta('Debes seleccionar una imagen', 'warning');
                return;
            }

            fetch(api('/catalogo/guardar'), {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    mostrarAlerta(data.message, 'success');
                    cerrarModal();
                    cargarCatalogo();
                } else {
                    mostrarAlerta(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al guardar producto', 'error');
            });
        });
    }
    
    // Formulario de cambio de estatus
    const formEstatus = document.getElementById('formCambiarEstatus');
    if (formEstatus) {
        formEstatus.addEventListener('submit', function(e) {
            e.preventDefault();
            const params = new URLSearchParams({
                IdPedido: document.getElementById('pedidoIdEstatus').value,
                Estatus:  document.getElementById('nuevoEstatus').value,
                Mensaje:  document.getElementById('mensajeAdmin').value
            });
            fetch(api('/pedidos/estatus'), {
                method: 'POST',
                body: params
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    mostrarAlerta(data.message, 'success');
                    cerrarModalEstatus();
                    cargarPedidos();
                } else {
                    mostrarAlerta(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al actualizar estatus', 'error');
            });
        });
    }
    
    // Botón de aceptar en modal de alerta
    const btnAcept = document.getElementById('btnAcept');
    if(btnAcept) {
        btnAcept.addEventListener('click', function() {
            document.getElementById('warning').close();
        });
    }

    // Formulario de asignar precio
const formPrecio = document.getElementById('formAsignarPrecio');
if (formPrecio) {
    formPrecio.addEventListener('submit', function(e) {
        e.preventDefault();
        const params = new URLSearchParams({
            IdPedido: document.getElementById('pedidoIdPrecio').value,
            Precio:   document.getElementById('precioPedido').value
        });
        fetch(api('/pedidos/precio'), {
            method: 'POST',
            body: params
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                mostrarAlerta(data.message, 'success');
                cerrarModalPrecio();
                cargarPedidos();
            } else {
                mostrarAlerta(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al asignar precio', 'error');
        });
    });
}
}

// Actualizar fecha y hora
function updateDateTime() {
    const now = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const dateElement = document.getElementById('currentDate');
    if (dateElement) {
        dateElement.textContent = now.toLocaleDateString('es-ES', options);
    }
}
updateDateTime();
setInterval(updateDateTime, 60000);

// ===== NAVEGACIÓN ENTRE SECCIONES =====
function principal() {
    hideAllSections();
    document.getElementById('principal').classList.add('active');
    updateActiveNav('nav-principal');
    document.getElementById('page-title').textContent = 'Panel de Administración';
    
    // Cargar estadísticas
    cargarEstadisticas();
}

function cargarEstadisticas() {
    // 1. Cargar total de productos
    fetch(api('/catalogo/listar'))
        .then(response => response.json())
        .then(result => {
            if(result.success) {
                const totalElement = document.getElementById('totalProductos');
                if (totalElement) {
                    totalElement.textContent = result.data.productos.length;
                }
            }
        })
        .catch(error => console.error('Error cargando productos:', error));
    
    // 2. Cargar estadísticas de pedidos
    fetch(api('/pedidos/estadisticas'))
        .then(response => response.json())
        .then(result => {
            if(result.success) {
                const numeros = result.data.estadisticas;
                const statCards = document.querySelectorAll('.stat-card');
                statCards.forEach(card => {
                    const text = card.textContent;
                    if (text.includes('Pedidos Activos')) {
                        card.querySelector('.stat-info h3').textContent = numeros.PedidosActivos || 0;
                    } else if (text.includes('Clientes')) {
                        card.querySelector('.stat-info h3').textContent = numeros.TotalClientes || 0;
                    } else if (text.includes('Ventas del Mes')) {
                        card.querySelector('.stat-info h3').textContent =
                            '$' + (numeros.VentasMes ? parseFloat(numeros.VentasMes).toFixed(2) : '0.00');
                    }
                });
            }
        })
        .catch(error => console.error('Error cargando estadísticas:', error));
}

function catalogo() {
    hideAllSections();
    document.getElementById('catalogo').classList.add('active');
    updateActiveNav('nav-catalogo');
    document.getElementById('page-title').textContent = 'Gestión de Catálogo';
    cargarCatalogo();
}

function pedidos() {
    hideAllSections();
    document.getElementById('pedidos').classList.add('active');
    updateActiveNav('nav-pedidos');
    document.getElementById('page-title').textContent = 'Gestión de Pedidos';
    
    // Establecer filtro en "todos" al cargar la sección
    const filtroSelect = document.getElementById('filtroEstatus');
    if (filtroSelect) {
        filtroSelect.value = 'todos';
    }
    filtroActualEstatus = 'todos';
    
    cargarPedidos();
}

function hideAllSections() {
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
}

function updateActiveNav(activeId) {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    const activeNav = document.getElementById(activeId);
    if (activeNav) {
        activeNav.classList.add('active');
    }
}

// ===== GESTIÓN DE CATÁLOGO =====
function cargarCatalogo() {
    const tbody = document.getElementById('tablaBody');
    tbody.innerHTML = '<tr><td colspan="6" class="loading"><div class="spinner"></div>Cargando productos...</td></tr>';
    
    fetch(api('/catalogo/listar'))
        .then(response => response.json())
        .then(result => {
            if(result.success) {
                mostrarProductos(result.data.productos);
                actualizarContadorProductos(result.data.productos.length);
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="error">Error al cargar productos: ' + result.message + '</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="error">Error de conexión</td></tr>';
        });
}

function mostrarProductos(productos) {
    const tbody = document.getElementById('tablaBody');
    
    if(productos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty">No hay productos en el catálogo</td></tr>';
        return;
    }
    
    tbody.innerHTML = productos.map(producto => `
        <tr data-id="${producto.IdProducto}">
            <td>${producto.IdProducto}</td>
            <td>
                <img src="${api(producto.Imagen)}" alt="${producto.Nombre}" class="producto-img"
                     onerror="this.onerror=null; this.src=api('/webService/wwwroot/catalogo/imgNoEncontrada.png')">
            </td>
            <td class="nombre-col">${producto.Nombre}</td>
            <td class="desc-col">${producto.Descripcion}</td>
            <td class="precio-col">$${parseFloat(producto.Precio).toFixed(2)}</td>
            <td class="acciones-col">
                <button class="btn-icon btn-editar" onclick="editarProducto(${producto.IdProducto})" title="Editar">
                    <span class="material-symbols-outlined">edit</span>
                </button>
                <button class="btn-icon btn-eliminar" onclick="eliminarProducto(${producto.IdProducto}, '${producto.Nombre.replace(/'/g, "\\'")}' )" title="Eliminar">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </td>
        </tr>
    `).join('');
}

function actualizarContadorProductos(total) {
    const contador = document.getElementById('totalProductos');
    if(contador) {
        contador.textContent = total;
    }
}

function filtrarTabla() {
    const filtro = document.getElementById('buscarProducto').value.toLowerCase();
    const filas = document.querySelectorAll('#tablaBody tr');
    
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(filtro) ? '' : 'none';
    });
}

// ===== MODALES DE CATÁLOGO =====
function abrirModalAgregar() {
    modoEdicion = false;
    document.getElementById('modalTitulo').textContent = 'Agregar Producto';
    document.getElementById('formProducto').reset();
    document.getElementById('productoId').value = '';
    document.getElementById('preview').innerHTML = '';
    document.getElementById('modalProducto').showModal();
}

function editarProducto(id) {
    modoEdicion = true;
    document.getElementById('modalTitulo').textContent = 'Editar Producto';
    
    fetch(api(`/catalogo/obtener?IdProducto=${id}`))
        .then(response => response.json())
        .then(result => {
            if(result.success) {
                const p = result.data.producto;
                document.getElementById('formProducto').reset();      // que no se quede la imagen elegida antes
                document.getElementById('productoId').value = p.IdProducto;
                document.getElementById('nombreProducto').value = p.Nombre;
                document.getElementById('descripcionProducto').value = p.Descripcion;
                document.getElementById('precioProducto').value = p.Precio;

                document.getElementById('preview').innerHTML = `
                    <img src="${api(p.Imagen)}" alt="Imagen actual" onerror="this.onerror=null; this.src=api('/webService/wwwroot/catalogo/imgNoEncontrada.png')">
                    <p class="preview-text">Imagen actual</p>
                `;
                
                document.getElementById('modalProducto').showModal();
            } else {
                mostrarAlerta('Error al cargar producto', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión', 'error');
        });
}

function eliminarProducto(id, nombre) {
    productoEliminarId = id;
    
    // Usar modal personalizado en lugar de alert
    const modalConfirmar = document.getElementById('modalConfirmar');
    const mensajeConfirmar = document.getElementById('mensajeConfirmar');
    
    if (modalConfirmar && mensajeConfirmar) {
        mensajeConfirmar.textContent = `¿Estás seguro de eliminar el producto "${nombre}"? Esta acción no se puede deshacer.`;
        modalConfirmar.showModal();
    }
}

function confirmarAccion() {
    if(!productoEliminarId) return;
    
    const formData = new FormData();
    formData.append('IdProducto', productoEliminarId);

    fetch(api('/catalogo/eliminar'), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            mostrarAlerta(data.message, 'success');
            cargarCatalogo();
        } else {
            mostrarAlerta(data.message, 'error');
        }
        cerrarModalConfirmar();
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarAlerta('Error al eliminar producto', 'error');
        cerrarModalConfirmar();
    });
}

function cerrarModal() {
    document.getElementById('modalProducto').close();
}

function cerrarModalConfirmar() {
    document.getElementById('modalConfirmar').close();
    productoEliminarId = null;
}

function previsualizarImagen(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('preview');
    
    if(file) {
        if(file.size > 3 * 1024 * 1024) {
            mostrarAlerta('La imagen no debe superar 3MB', 'warning');
            event.target.value = '';
            preview.innerHTML = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Vista previa">
                <p class="preview-text">Vista previa</p>
            `;
        };
        reader.readAsDataURL(file);
    }
}
// ===== SISTEMA DE ALERTAS =====
function mostrarAlerta(mensaje, tipo) {
    window.BookArtNotification.show(mensaje, tipo);
}

// ===== GESTIÓN DE PEDIDOS =====
function cargarPedidos() {
    const tbody = document.getElementById('tablaPedidosBody');
    if (!tbody) {
        console.error('❌ No se encontró tablaPedidosBody');
        return;
    }
    
    tbody.innerHTML = '<tr><td colspan="7" class="loading"><div class="spinner"></div>Cargando pedidos...</td></tr>';
    
    fetch(api('/pedidos/listar'))
        .then(response => response.json())
        .then(data => {
            console.log('📦 Respuesta del servidor:', data);

            if(data.success) {
                pedidosData = data.data.pedidos;
                console.log('✅ Pedidos cargados:', pedidosData.length);
                mostrarPedidos(pedidosData);
                actualizarContadorPedidos(pedidosData);
            } else {
                console.error('❌ Error en respuesta:', data.message);
                tbody.innerHTML = `<tr><td colspan="7" class="error">Error al cargar pedidos: ${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('❌ Error de conexión:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="error">Error de conexión: ' + error.message + '</td></tr>';
        });
}

function mostrarPedidos(pedidos) {
    const tbody = document.getElementById('tablaPedidosBody');
    if (!tbody) return;
    
    console.log('🔄 Mostrando pedidos, total:', pedidos.length);
    
    // Filtrar por estatus si hay filtro activo
    const filtroSelect = document.getElementById('filtroEstatus');
    const filtro = filtroSelect ? filtroSelect.value : 'todos';
    console.log('🔍 Filtro actual:', filtro);
    
    if (filtro !== 'todos') {
        pedidos = pedidos.filter(p => p.Estatus.toLowerCase() === filtro);
        console.log('📊 Pedidos después del filtro:', pedidos.length);
    }
    
    if(pedidos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty">No hay pedidos con este estatus</td></tr>';
        return;
    }
    
    tbody.innerHTML = pedidos.map(pedido => {
        const estatusClass = 'estatus-' + pedido.Estatus.toLowerCase();
        const isCatalogo = (pedido.IdTipoPedido == 1);
        const tipoClass  = isCatalogo ? 'pedido-tipo-catalogo' : 'pedido-tipo-personalizada';
        const tipoTexto  = isCatalogo ? '📚 Catálogo' : '🎨 Personalizada';
        
        let estatusIcon = '';
        switch(pedido.Estatus.toLowerCase()) {
            case 'pendiente': estatusIcon = '⏳'; break;
            case 'visto': estatusIcon = '👀'; break;
            case 'aprobado': estatusIcon = '✅'; break;
            case 'declinado': estatusIcon = '❌'; break;
            case 'proceso': estatusIcon = '🔨'; break;
            case 'terminado': estatusIcon = '🎉'; break;
            case 'entregado': estatusIcon = '📦'; break;
        }
        
        // Determinar el precio a mostrar
        const precioVal = parseFloat(pedido.Precio || 0);
        let precioHTML = '';
        if (isCatalogo) {
            precioHTML = `<span style="color: var(--primary); font-weight: bold; font-size: 1.2rem;">$${precioVal.toFixed(2)}</span>`;
        } else if (precioVal > 0) {
            precioHTML = `
                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                    <span style="color: var(--primary); font-weight: bold; font-size: 1.2rem;">$${precioVal.toFixed(2)}</span>
                    <button class="btn-icon" onclick="abrirModalAsignarPrecio('${pedido.IdPedido}')" title="Cambiar precio" style="background: #fff3cd; color: #856404;">
                        <span class="material-symbols-outlined">edit</span>
                    </button>
                </div>
            `;
        } else {
            precioHTML = `
                <button onclick="abrirModalAsignarPrecio('${pedido.IdPedido}')"
                        style="padding: 0.8rem 1.2rem; background: var(--amarillo-bookart); color: var(--marron-texto); border: 3px solid var(--marron-texto); cursor: pointer; font-family: var(--font-body); font-weight: 700; border-radius: 8px; transition: all 0.3s ease; box-shadow: 3px 3px 0px var(--marron-texto); display: flex; align-items: center; gap: 0.5rem;"
                        onmouseover="this.style.transform='translate(2px, 2px)'; this.style.boxShadow='1px 1px 0px var(--marron-texto)';"
                        onmouseout="this.style.transform=''; this.style.boxShadow='3px 3px 0px var(--marron-texto)';">
                    <span class="material-symbols-outlined">payments</span>
                    Asignar Precio
                </button>
            `;
        }
        
        return `
        <tr data-id="${pedido.IdPedido}">
            <td><strong>#${String(pedido.IdPedido).padStart(5, '0')}</strong></td>
            <td>
                <div class="cliente-info">
                    ${pedido.ClienteNombre || 'N/A'}
                    <span class="cliente-correo">${pedido.ClienteCorreo || 'N/A'}</span>
                </div>
            </td>
            <td>
                <span class="pedido-tipo-badge ${tipoClass}">
                    ${tipoTexto}
                </span>
            </td>
            <td class="nombre-col">${pedido.Nombre || 'N/A'}</td>
            <td>
                ${pedido.Fecha}<br>
                <small style="color: var(--text-secondary);">${pedido.Hora}</small>
            </td>
            <td style="text-align: center;">${precioHTML}</td>
            <td>
                <span class="pedido-estatus ${estatusClass}">
                    ${estatusIcon} ${pedido.Estatus}
                </span>
            </td>
            <td class="acciones-col">
                <button class="btn-icon btn-editar" onclick="verDetallePedido('${pedido.IdPedido}')" title="Ver detalle">
                    <span class="material-symbols-outlined">visibility</span>
                </button>
                <button class="btn-icon" onclick="cambiarEstatus('${pedido.IdPedido}', '${pedido.Estatus}')" 
                        title="Cambiar estatus"
                        style="background: #e3f2fd; color: #1976d2;">
                    <span class="material-symbols-outlined">sync_alt</span>
                </button>
            </td>
        </tr>
        `;
    }).join('');
    
    console.log('✅ Tabla actualizada');
}

// Función para abrir modal de asignar precio
function abrirModalAsignarPrecio(idPedido) {
    document.getElementById('pedidoIdPrecio').value = idPedido;
    document.getElementById('precioPedido').value = '';
    document.getElementById('modalAsignarPrecio').showModal();
    
    // Enfocar el input
    setTimeout(() => {
        document.getElementById('precioPedido').focus();
    }, 100);
}

function cerrarModalPrecio() {
    document.getElementById('modalAsignarPrecio').close();
}

function filtrarPedidos() {
    const filtroSelect = document.getElementById('filtroEstatus');
    if (filtroSelect) {
        filtroActualEstatus = filtroSelect.value;
        console.log('🔄 Filtrando por:', filtroActualEstatus);
        mostrarPedidos(pedidosData);
    }
}

function buscarEnPedidos() {
    const searchInput = document.getElementById('buscarPedido');
    if (!searchInput) return;
    
    const filtro = searchInput.value.toLowerCase();
    const filas = document.querySelectorAll('#tablaPedidosBody tr');
    
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(filtro) ? '' : 'none';
    });
}

function verDetallePedido(id) {
    console.log('👁️ Viendo detalle del pedido:', id);

    fetch(api(`/pedidos/detalle?IdPedido=${id}`))
        .then(response => response.json())
        .then(data => {
            console.log('📋 Detalle recibido:', data);

            if(data.success) {
                mostrarDetallePedido(data.data.pedido);
            } else {
                mostrarAlerta('Error al cargar detalle del pedido: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('❌ Error:', error);
            mostrarAlerta('Error de conexión', 'error');
        });
}

function mostrarDetallePedido(p) {
    const modal   = document.getElementById('modalDetallePedido');
    const content = document.getElementById('detallePedidoContent');
    if (!modal || !content) return;

    const idStr      = String(p.IdPedido).padStart(5, '0');
    const isCatalogo = (p.IdTipoPedido == 1);

    document.getElementById('modalPedidoTitulo').textContent =
        `Pedido #${idStr} - ${isCatalogo ? 'Catálogo' : 'Personalizada'}`;

    let detalleHTML = `
        <div class="detalle-section">
            <h3>📋 Información del Pedido</h3>
            <div class="detalle-grid">
                <div class="detalle-item">
                    <span class="detalle-label">ID del Pedido</span>
                    <span class="detalle-value">#${idStr}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Fecha</span>
                    <span class="detalle-value">${p.Fecha} ${p.Hora}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Tipo</span>
                    <span class="detalle-value">${isCatalogo ? 'Catálogo' : 'Personalizada'}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Estatus</span>
                    <span class="detalle-value pedido-estatus estatus-${p.Estatus.toLowerCase()}">${p.Estatus}</span>
                </div>
            </div>
        </div>

        <div class="detalle-section">
            <h3>👤 Información del Cliente</h3>
            <div class="detalle-grid">
                <div class="detalle-item">
                    <span class="detalle-label">Nombre</span>
                    <span class="detalle-value">${p.ClienteNombre || ''}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Correo</span>
                    <span class="detalle-value">${p.ClienteCorreo || 'N/A'}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Teléfono</span>
                    <span class="detalle-value">${p.ClienteTelefono || 'N/A'}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Usuario</span>
                    <span class="detalle-value">${p.ClienteUsuario || 'N/A'}</span>
                </div>
            </div>
        </div>

        <div class="detalle-section">
            <h3>📦 Detalles del Producto</h3>
    `;

    if (isCatalogo) {
        detalleHTML += `
            ${p.Imagen ? `<div class="producto-preview"><img src="${api(p.Imagen)}" alt="${p.Nombre}"></div>` : ''}
            <div class="detalle-grid">
                <div class="detalle-item">
                    <span class="detalle-label">Nombre</span>
                    <span class="detalle-value">${p.Nombre || 'N/A'}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Precio</span>
                    <span class="detalle-value" style="color: var(--primary); font-weight: bold; font-size: 1.5rem;">
                        $${parseFloat(p.Precio || 0).toFixed(2)}
                    </span>
                </div>
            </div>
            ${p.Descripcion ? `
            <div class="detalle-item" style="margin-top: 1rem;">
                <span class="detalle-label">Descripción</span>
                <span class="detalle-value">${p.Descripcion}</span>
            </div>` : ''}
        `;
    } else {
        detalleHTML += `
            ${p.Imagen
                ? `<div class="producto-preview"><img src="${api(p.Imagen)}" alt="Diseño personalizado"></div>`
                : '<p style="text-align:center;color:var(--text-secondary);">Sin imagen de portada</p>'}
            <div class="detalle-grid">
                <div class="detalle-item">
                    <span class="detalle-label">Tamaño</span>
                    <span class="detalle-value">${p.Tamano || 'No especificado'}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Tipo de Encuadernación</span>
                    <span class="detalle-value">${p.TipoEncuadernacion || 'No especificado'}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Tipo de Papel</span>
                    <span class="detalle-value">${p.TipoPapel || 'No especificado'}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Color</span>
                    <span class="detalle-value">
                        ${p.Color ? `<span style="display:inline-block;width:30px;height:30px;background:${p.Color};border:2px solid var(--marron-texto);vertical-align:middle;margin-right:6px;"></span>` : ''}
                        ${p.Color || 'No especificado'}
                    </span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Precio</span>
                    <span class="detalle-value" style="color: var(--primary); font-weight: bold;">
                        ${parseFloat(p.Precio || 0) > 0 ? '$' + parseFloat(p.Precio).toFixed(2) : 'A cotizar'}
                    </span>
                </div>
            </div>
            ${p.Descripcion ? `
            <div class="detalle-item" style="margin-top: 1rem;">
                <span class="detalle-label">Descripción del cliente</span>
                <span class="detalle-value">${p.Descripcion}</span>
            </div>` : ''}
        `;
    }

    detalleHTML += '</div>';
    content.innerHTML = detalleHTML;
    modal.showModal();
}

function cerrarModalDetalle() {
    const modal = document.getElementById('modalDetallePedido');
    if (modal) {
        modal.close();
    }
}

function cambiarEstatus(id, estatusActual) {
    document.getElementById('pedidoIdEstatus').value = id;
    document.getElementById('nuevoEstatus').value = estatusActual.toLowerCase();
    document.getElementById('mensajeAdmin').value = '';
    
    document.getElementById('modalCambiarEstatus').showModal();
}

function cerrarModalEstatus() {
    const modal = document.getElementById('modalCambiarEstatus');
    if (modal) {
        modal.close();
    }
}

function actualizarContadorPedidos(pedidos) {
    const pendientes = pedidos.filter(p => p.Estatus.toLowerCase() === 'pendiente').length;
    
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        const text = card.textContent;
        if (text.includes('Pedidos Activos')) {
            const h3 = card.querySelector('.stat-info h3');
            if (h3) h3.textContent = pendientes;
        }
    });
}

// ===== CERRAR SESIÓN =====
async function closeSesion() {
    try {
        await fetch(api('/auth/salir'), { method: 'POST' });
    } catch {}
    window.location.href = api('/auth/entrar');
}

console.log('✅ logicaAdmin.js cargado correctamente');