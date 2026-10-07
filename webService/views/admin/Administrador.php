<?php
if (!isset($_SESSION['usuario']) || ($_SESSION['permiso'] ?? '') != 1) {
    header('Location: /'); exit;
}

$title    = 'Administrador - BookArt';
$extraCss = ['adminStyle.css'];
$extraJs  = ['funcionModal.js', 'logicaAdmin.js'];
?>
<!DOCTYPE html>
<html lang="es">
<head><?php require __DIR__ . '/../partials/head.php'; ?></head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="/webService/wwwroot/img/Logo/BookArt Negativo_B-N.png" alt="BookArt Logo" class="logo">
        </div>
        <div class="user-info">
            <div class="user-avatar"><span class="material-symbols-outlined">account_circle</span></div>
            <div class="user-details">
                <p class="user-name"><?= htmlspecialchars($_SESSION['usuario'] ?? 'Admin') ?></p>
                <span class="user-role">Administrador</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-item" onclick="principal()" id="nav-principal">
                <span class="material-symbols-outlined">dashboard</span><span>Principal</span>
            </a>
            <a href="#" class="nav-item" onclick="catalogo()" id="nav-catalogo">
                <span class="material-symbols-outlined">inventory_2</span><span>Catálogo</span>
            </a>
            <a href="#" class="nav-item" onclick="pedidos()" id="nav-pedidos">
                <span class="material-symbols-outlined">shopping_bag</span><span>Pedidos</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <button class="btn-icon-sidebar" title="Configuración">
                <span class="material-symbols-outlined">settings</span>
            </button>
            <button class="btn-icon-sidebar" onclick="closeSesion()" title="Cerrar sesión">
                <span class="material-symbols-outlined">logout</span>
            </button>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left"><h1 id="page-title">Panel de Administración</h1></div>
            <div class="header-right"><span class="date-time" id="currentDate"></span></div>
        </header>

        <!-- PRINCIPAL -->
        <section class="content-section active" id="principal">
            <div class="welcome-card">
                <div class="welcome-content">
                    <h2>¡Bienvenido de nuevo! 👋</h2>
                    <p>Gestiona tu tienda de forma eficiente desde este panel de control.</p>
                </div>
                <div class="welcome-illustration">
                    <span class="material-symbols-outlined">admin_panel_settings</span>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon productos"><span class="material-symbols-outlined">inventory</span></div>
                    <div class="stat-info"><h3 id="totalProductos">0</h3><p>Productos</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon pedidos"><span class="material-symbols-outlined">shopping_cart</span></div>
                    <div class="stat-info"><h3>0</h3><p>Pedidos Activos</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon usuarios"><span class="material-symbols-outlined">group</span></div>
                    <div class="stat-info"><h3>0</h3><p>Clientes</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon ventas"><span class="material-symbols-outlined">trending_up</span></div>
                    <div class="stat-info"><h3>$0.00</h3><p>Ventas del Mes</p></div>
                </div>
            </div>
            <div class="quick-actions">
                <h3>Acciones Rápidas</h3>
                <div class="actions-grid">
                    <button class="action-btn" onclick="catalogo(); setTimeout(abrirModalAgregar, 100)">
                        <span class="material-symbols-outlined">add_circle</span><span>Agregar Producto</span>
                    </button>
                    <button class="action-btn" onclick="pedidos()">
                        <span class="material-symbols-outlined">list_alt</span><span>Ver Pedidos</span>
                    </button>
                    <button class="action-btn" onclick="catalogo()">
                        <span class="material-symbols-outlined">visibility</span><span>Ver Catálogo</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- CATÁLOGO -->
        <section class="content-section" id="catalogo">
            <div class="section-header">
                <div class="section-title"><p>Administra los productos de tu tienda</p></div>
                <button class="btn-primary" onclick="abrirModalAgregar()">
                    <span class="material-symbols-outlined">add</span> Nuevo Producto
                </button>
            </div>
            <div class="tabla-card">
                <div class="tabla-header">
                    <div class="search-box">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" id="buscarProducto" placeholder="Buscar producto..." onkeyup="filtrarTabla()">
                    </div>
                </div>
                <div class="tabla-wrapper">
                    <table id="tablaCatalogo">
                        <thead><tr><th>ID</th><th>Imagen</th><th>Nombre</th><th>Descripción</th><th>Precio</th><th>Acciones</th></tr></thead>
                        <tbody id="tablaBody">
                            <tr><td colspan="6" class="loading"><div class="spinner"></div>Cargando productos...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- PEDIDOS -->
        <section class="content-section" id="pedidos">
            <div class="section-header">
                <div class="section-title"><p>Administra los pedidos de tus clientes</p></div>
                <div style="display:flex;gap:1rem;">
                    <select id="filtroEstatus" onchange="filtrarPedidos()" style="padding:.8rem;border:3px solid var(--marron-texto);font-family:var(--font-body);cursor:pointer;">
                        <option value="todos">Todos los pedidos</option>
                        <option value="pendiente" selected>Pendientes</option>
                        <option value="visto">Vistos</option>
                        <option value="aprobado">Aprobados</option>
                        <option value="declinado">Declinados</option>
                        <option value="proceso">En proceso</option>
                        <option value="terminado">Terminados</option>
                        <option value="entregado">Entregados</option>
                    </select>
                </div>
            </div>
            <div class="tabla-card">
                <div class="tabla-header">
                    <div class="search-box">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" id="buscarPedido" placeholder="Buscar por cliente o ID..." onkeyup="buscarEnPedidos()">
                    </div>
                </div>
                <div class="tabla-wrapper">
                    <table id="tablaPedidos">
                        <thead><tr><th>ID</th><th>Cliente</th><th>Tipo</th><th>Producto</th><th>Fecha</th><th>Precio</th><th>Estatus</th><th>Acciones</th></tr></thead>
                        <tbody id="tablaPedidosBody">
                            <tr><td colspan="7" class="loading"><div class="spinner"></div>Cargando pedidos...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <!-- MODALES -->
    <dialog id="modalProducto" class="modal-producto">
        <div class="modal-header">
            <h2 id="modalTitulo">Agregar Producto</h2>
            <button class="btn-cerrar" onclick="cerrarModal()"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="formProducto" enctype="multipart/form-data">
            <input type="hidden" id="productoId" name="IdProducto">
            <div class="form-group">
                <label for="nombreProducto"><span class="material-symbols-outlined">label</span> Nombre del producto</label>
                <input type="text" id="nombreProducto" name="Nombre" maxlength="35" required>
            </div>
            <div class="form-group">
                <label for="descripcionProducto"><span class="material-symbols-outlined">description</span> Descripción</label>
                <textarea id="descripcionProducto" name="Descripcion" rows="4" maxlength="500" required></textarea>
            </div>
            <div class="form-group">
                <label for="precioProducto"><span class="material-symbols-outlined">attach_money</span> Precio</label>
                <input type="number" id="precioProducto" name="Precio" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label for="imagenProducto"><span class="material-symbols-outlined">image</span> Imagen del producto</label>
                <div class="file-upload">
                    <input type="file" id="imagenProducto" name="Imagen" accept="image/jpeg,image/png,image/gif" onchange="previsualizarImagen(event)">
                    <div id="preview" class="preview-container"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn-guardar"><span class="material-symbols-outlined">save</span> Guardar</button>
            </div>
        </form>
    </dialog>

    <dialog id="modalConfirmar" class="modal-confirmar">
        <span class="material-symbols-outlined warning-icon">warning</span>
        <h2>¿Estás seguro?</h2>
        <p id="mensajeConfirmar">Esta acción no se puede deshacer.</p>
        <div class="modal-footer">
            <button class="btn-cancelar" onclick="cerrarModalConfirmar()">Cancelar</button>
            <button class="btn-eliminar" onclick="confirmarAccion()">Eliminar</button>
        </div>
    </dialog>

    <dialog id="warning">
        <p id="mensaje"></p>
        <div class="btnModal"><button id="btnAcept">Aceptar</button></div>
    </dialog>

    <dialog id="modalDetallePedido" class="modal-producto">
        <div class="modal-header">
            <h2 id="modalPedidoTitulo">Detalle del Pedido</h2>
            <button class="btn-cerrar" onclick="cerrarModalDetalle()"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div id="detallePedidoContent" style="padding:2rem;max-height:60vh;overflow-y:auto;"></div>
    </dialog>

    <dialog id="modalCambiarEstatus" class="modal-producto">
        <div class="modal-header">
            <h2>Cambiar Estatus del Pedido</h2>
            <button class="btn-cerrar" onclick="cerrarModalEstatus()"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="formCambiarEstatus" style="padding:2rem;">
            <input type="hidden" id="pedidoIdEstatus" name="pedidoId">
            <div class="form-group">
                <label for="nuevoEstatus"><span class="material-symbols-outlined">sync_alt</span> Nuevo Estatus</label>
                <select id="nuevoEstatus" name="estatus" required style="width:100%;padding:1rem;border:3px solid var(--marron-texto);font-family:var(--font-body);font-size:1rem;">
                    <option value="pendiente">⏳ Pendiente</option>
                    <option value="visto">👀 Visto</option>
                    <option value="aprobado">✅ Aprobado</option>
                    <option value="declinado">❌ Declinado</option>
                    <option value="proceso">🔨 En Proceso</option>
                    <option value="terminado">🎉 Terminado</option>
                    <option value="entregado">📦 Entregado</option>
                </select>
            </div>
            <div class="form-group" style="margin-top:1.5rem;">
                <label for="mensajeAdmin"><span class="material-symbols-outlined">message</span> Mensaje para el cliente (opcional)</label>
                <textarea id="mensajeAdmin" name="mensaje" rows="4" placeholder="Agrega un mensaje que el cliente verá..." style="width:100%;padding:1rem;border:3px solid var(--marron-texto);font-family:var(--font-body);"></textarea>
                <p style="text-align:right;color:var(--text-secondary);font-size:.85rem;margin-top:.3rem;"><span id="mensajeCounter">0</span>/250 caracteres</p>
            </div>
            <div class="modal-footer" style="margin-top:2rem;">
                <button type="button" class="btn-cancelar" onclick="cerrarModalEstatus()">Cancelar</button>
                <button type="submit" class="btn-guardar"><span class="material-symbols-outlined">save</span> Actualizar</button>
            </div>
        </form>
    </dialog>

    <dialog id="modalAsignarPrecio" class="modal-producto">
        <div class="modal-header">
            <h2>💰 Asignar Precio</h2>
            <button class="btn-cerrar" onclick="cerrarModalPrecio()"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="formAsignarPrecio" style="padding:2rem;">
            <input type="hidden" id="pedidoIdPrecio" name="pedidoId">
            <div class="form-group">
                <label for="precioPedido"><span class="material-symbols-outlined">payments</span> Precio de la Libreta Personalizada</label>
                <div style="position:relative;">
                    <span style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);font-size:1.5rem;color:var(--primary);font-weight:bold;">$</span>
                    <input type="number" id="precioPedido" name="precio" step="0.01" min="0" required placeholder="0.00"
                           style="width:100%;padding:1rem 1rem 1rem 2.5rem;font-size:1.5rem;text-align:center;font-weight:700;color:var(--primary);border:3px solid var(--primary);">
                </div>
            </div>
            <div class="modal-footer" style="margin-top:2rem;">
                <button type="button" class="btn-cancelar" onclick="cerrarModalPrecio()">Cancelar</button>
                <button type="submit" class="btn-guardar"><span class="material-symbols-outlined">save</span> Asignar Precio</button>
            </div>
        </form>
    </dialog>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mensajeTextarea = document.getElementById('mensajeAdmin');
        const counter = document.getElementById('mensajeCounter');
        if (mensajeTextarea && counter) {
            mensajeTextarea.addEventListener('input', function() {
                counter.textContent = this.value.length;
                counter.style.color = this.value.length > 230 ? 'var(--rojo-bookart)' : this.value.length > 200 ? 'var(--amarillo-bookart)' : 'var(--text-secondary)';
            });
        }
    });
    </script>
</body>
</html>
