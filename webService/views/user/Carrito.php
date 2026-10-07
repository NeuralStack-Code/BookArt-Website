<?php
/**
 * Pantalla del carrito. La pinta CarritoController::index().
 * Recibe $items (lista de CarritoItem), $catalogTotal (suma de lo que sí tiene precio), $hasCustom y $base.
 */
$title    = 'Mi Carrito - BookArt';
$extraCss = ['styleCarrito.css'];
$extraJs  = ['funcionModal.js', 'carrito.js', 'userMenu.js'];
?>
<!DOCTYPE html>
<html lang="es">
<head><?php require __DIR__ . '/../partials/head.php'; ?></head>
<body>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="cart-section">
    <div class="cart-container">
        <div class="cart-header">
            <h1>🛒 Mi Carrito de Compras</h1>
            <p>Revisa tus productos antes de realizar tu pedido</p>
        </div>

        <?php if ($items): ?>
            <div class="cart-items">
                <?php foreach ($items as $item): ?>
                    <div class="cart-item" data-id="<?= $item->IdPedido ?>">
                        <div class="cart-item-image">
                            <img src="<?= e($base . $item->image()) ?>" alt="<?= e($item->Nombre) ?>">
                        </div>
                        <div class="cart-item-info">
                            <span class="cart-item-type"><?= $item->isCatalog() ? '📚 Catálogo' : '🎨 Personalizada' ?></span>
                            <h3><?= e($item->Nombre) ?></h3>
                            <?php if (!$item->isCatalog()): ?>
                                <p style="color:var(--marron-texto);font-size:.9rem;margin-top:.5rem;">
                                    <strong>Nota:</strong> El precio se definirá al aprobar tu diseño
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="cart-item-price">
                            <?= e($item->priceLabel()) ?>
                        </div>
                        <div class="cart-item-actions">
                            <button class="btn-remove" onclick="removeItem(<?= (int) $item->IdPedido ?>)">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h2>Resumen del Pedido</h2>
                <div class="summary-line">
                    <span>Productos del catálogo:</span>
                    <span><strong><?= money($catalogTotal) ?></strong></span>
                </div>
                <div class="summary-line">
                    <span>Productos personalizados:</span>
                    <span><strong><?= $hasCustom ? 'A cotizar' : '$0.00' ?></strong></span>
                </div>
                <div class="summary-line" style="border-bottom:none;padding-top:1.5rem;">
                    <span><strong>Total estimado:</strong></span>
                    <span class="summary-total"><?= money($catalogTotal) ?><?= $hasCustom ? '+' : '' ?></span>
                </div>
                <p style="margin-top:1rem;color:var(--marron-texto);font-size:.9rem;text-align:center;">
                    * Los productos personalizados se cotizarán individualmente
                </p>
                <div class="cart-actions">
                    <a href="<?= e($base) ?>/productos" class="btn-continue">Seguir Comprando</a>
                    <button class="btn-checkout" onclick="realizarPedido()">Realizar Pedido</button>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h2>Tu carrito está vacío</h2>
                <p style="color:var(--marron-texto);margin:1rem 0 2rem;">¡Explora nuestros productos y agrega tus favoritos!</p>
                <a href="<?= e($base) ?>/productos" class="btn-checkout" style="display:inline-block;text-decoration:none;">Ver Productos</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>

<dialog id="confirmDialog" style="max-width:500px;padding:0;border:5px solid var(--marron-texto);border-radius:15px;">
    <div style="background:var(--amarillo-bookart);padding:1.5rem;border-bottom:3px solid var(--marron-texto);">
        <h2 style="font-family:var(--font-display);font-size:1.8rem;color:var(--marron-texto);margin:0;">¿Realizar pedido?</h2>
    </div>
    <div style="padding:2rem;">
        <p style="margin-bottom:2rem;color:var(--marron-texto);line-height:1.6;">Tu pedido será enviado y nos pondremos en contacto contigo para confirmar detalles.</p>
        <div style="display:flex;gap:1rem;justify-content:flex-end;">
            <button onclick="document.getElementById('confirmDialog').close()" style="flex:1;padding:1rem;background:var(--marron-texto);color:white;border:none;cursor:pointer;font-family:var(--font-body);font-weight:700;border-radius:8px;">Cancelar</button>
            <button onclick="confirmarPedido()" style="flex:1;padding:1rem;background:var(--verde-bookart);color:white;border:none;cursor:pointer;font-family:var(--font-body);font-weight:700;border-radius:8px;">Confirmar</button>
        </div>
    </div>
</dialog>

<dialog id="deleteDialog" style="max-width:450px;padding:0;border:5px solid var(--marron-texto);border-radius:15px;">
    <div style="background:var(--amarillo-bookart);padding:1.5rem;border-bottom:3px solid var(--marron-texto);">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;color:var(--marron-texto);margin:0;">Eliminar producto</h2>
    </div>
    <div style="padding:2rem;">
        <p id="deleteMessage" style="margin-bottom:2rem;color:var(--marron-texto);line-height:1.6;">¿Estás seguro de que deseas eliminar este producto del carrito?</p>
        <div style="display:flex;gap:1rem;justify-content:flex-end;">
            <button onclick="cancelarEliminacion()" type="button" style="flex:1;padding:1rem;background:var(--marron-texto);color:white;border:none;cursor:pointer;font-family:var(--font-body);font-weight:700;border-radius:8px;">Cancelar</button>
            <button onclick="confirmarEliminacion()" type="button" style="flex:1;padding:1rem;background:var(--rojo-bookart,#c0392b);color:white;border:none;cursor:pointer;font-family:var(--font-body);font-weight:700;border-radius:8px;">Eliminar</button>
        </div>
    </div>
</dialog>

<dialog id="warning" style="max-width:500px;padding:0;border:5px solid var(--marron-texto);border-radius:15px;">
    <div style="padding:2rem;text-align:center;">
        <p id="mensaje" style="font-size:1.1rem;color:var(--marron-texto);margin-bottom:2rem;line-height:1.6;"></p>
        <div class="btnModal">
            <button id="btnAcept" style="padding:.8rem 2rem;background:var(--verde-bookart);color:white;border:3px solid var(--marron-texto);cursor:pointer;font-family:var(--font-body);font-weight:700;border-radius:8px;box-shadow:3px 3px 0px var(--marron-texto);">Aceptar</button>
        </div>
    </div>
</dialog>
</body>
</html>
