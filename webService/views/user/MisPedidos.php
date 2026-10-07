<?php
/** Pantalla "Mis pedidos". La pinta PedidosController::index(). Recibe $pedidos (lista de Pedido) y $base. */
$title    = 'Mis Pedidos - BookArt';
$extraCss = ['styleMisPedidos.css'];
$extraJs  = ['funcionModal.js', 'misPedidos.js', 'userMenu.js'];
?>
<!DOCTYPE html>
<html lang="es">
<head><?php require __DIR__ . '/../partials/head.php'; ?></head>
<body>
<?php require __DIR__ . '/../partials/header.php'; ?>

<dialog id="warning">
    <p id="mensaje"></p>
    <div class="btnModal"><button id="btnAcept">Aceptar</button></div>
</dialog>

<section class="pedidos-section">
    <div class="pedidos-container">
        <div class="pedidos-header">
            <h1>📦 Mis Pedidos</h1>
            <p>Aquí puedes ver el estado de todos tus pedidos</p>
        </div>

        <?php if ($pedidos): ?>
            <?php foreach ($pedidos as $pedido): ?>
            <div class="pedido-card">
                <div class="pedido-header">
                    <div class="pedido-id">Pedido <?= e($pedido->number()) ?></div>
                    <div class="pedido-fecha">
                        📅 <?= formatDate($pedido->Fecha) ?>
                        🕐 <?= e(substr($pedido->Hora, 0, 5)) ?>
                    </div>
                </div>
                <div class="pedido-body">
                    <div class="pedido-image">
                        <img src="<?= e($base . $pedido->image()) ?>" alt="<?= e($pedido->Nombre) ?>">
                    </div>
                    <div class="pedido-info">
                        <span class="pedido-tipo"><?= $pedido->isCatalog() ? '📚 Catálogo' : '🎨 Personalizada' ?></span>
                        <h3><?= e($pedido->Nombre) ?></h3>
                        <?php if (!empty($pedido->Descripcion)): ?>
                            <p class="pedido-descripcion"><?= e(truncate($pedido->Descripcion, 150)) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="pedido-precio">
                        <?= e($pedido->priceLabel()) ?>
                    </div>
                </div>
                <div class="pedido-status">
                    <span class="status-badge status-<?= e($pedido->status()) ?>"><?= e($pedido->statusLabel()) ?></span>

                    <?php if ($pedido->isOpen()): ?>
                    <div class="pedido-actions">
                        <?php if (!$pedido->isCatalog()): ?>
                            <button onclick="editarPedido(<?= (int) $pedido->IdPedido ?>)" class="btn-action btn-editar-pedido">
                                <span class="material-symbols-outlined">edit</span> Editar
                            </button>
                        <?php endif; ?>
                        <button onclick="cancelarPedido(<?= (int) $pedido->IdPedido ?>)" class="btn-action btn-cancelar-pedido">
                            <span class="material-symbols-outlined">cancel</span> Cancelar Pedido
                        </button>
                    </div>
                    <?php endif; ?>

                    <?php if ($pedido->showsMessage()): ?>
                    <div class="mensaje-admin">
                        <div class="mensaje-admin-header">
                            <span class="material-symbols-outlined">mail</span> Mensaje del administrador:
                        </div>
                        <p><?= nl2br(e($pedido->Mensaje)) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-pedidos">
                <div class="empty-pedidos-icon">📦</div>
                <h2>No tienes pedidos aún</h2>
                <p style="color:var(--marron-texto);margin:1rem 0 2rem;">¡Explora nuestros productos y realiza tu primer pedido!</p>
                <a href="<?= e($base) ?>/productos" class="btn-primary" style="display:inline-block;text-decoration:none;padding:1rem 2rem;">Ver Productos</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>

<dialog id="dialogConfirmCancel" style="max-width:500px;padding:0;">
    <div style="background:var(--amarillo-bookart);padding:1.5rem;border-bottom:3px solid var(--marron-texto);">
        <h2 style="font-family:var(--font-display);font-size:1.8rem;color:var(--marron-texto);margin:0;">⚠️ ¿Cancelar pedido?</h2>
    </div>
    <div style="padding:2rem;">
        <p id="mensajeConfirmCancel" style="color:var(--marron-texto);margin-bottom:2rem;line-height:1.6;">
            ¿Estás seguro de que deseas cancelar este pedido? Esta acción no se puede deshacer.
        </p>
        <div style="display:flex;gap:1rem;justify-content:flex-end;">
            <button onclick="cerrarDialogConfirmCancel()" style="padding:.8rem 1.5rem;background:var(--marron-texto);color:white;border:none;cursor:pointer;font-family:var(--font-body);font-weight:700;">No, mantener</button>
            <button id="btnConfirmCancel" onclick="confirmarCancelacion()" style="padding:.8rem 1.5rem;background:var(--rojo-bookart);color:white;border:none;cursor:pointer;font-family:var(--font-body);font-weight:700;">Sí, cancelar pedido</button>
        </div>
    </div>
</dialog>
</body>
</html>
