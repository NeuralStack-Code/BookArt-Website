<?php
/** Pantalla del catálogo. La pinta CatalogoController::index(). Recibe $productos (lista de Producto) y $base. */
$title   = 'Catálogo - BookArt Encuadernaciones';
$extraJs = ['userMenu.js'];
?>
<!DOCTYPE html>
<html lang="es">
<head><?php require __DIR__ . '/../partials/head.php'; ?></head>
<body>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="catalog-hero">
    <h1>Nuestro Catálogo Artesanal</h1>
    <p>Explora nuestra colección de libretas hechas a mano con amor y dedicación</p>
</section>

<section class="catalog-section">
    <div class="catalog-container">
        <?php if ($productos): ?>
            <div class="catalog-grid">
                <?php foreach ($productos as $producto): ?>
                    <a href="<?= e($base) ?>/catalogo/detalle?IdProducto=<?= $producto->IdProducto ?>" class="catalog-item">
                        <div class="catalog-item-image">
                            <img src="<?= e($base . $producto->Imagen) ?>" alt="<?= e($producto->Nombre) ?>">
                        </div>
                        <div class="catalog-item-info">
                            <h3 class="catalog-item-title"><?= e($producto->Nombre) ?></h3>
                            <p class="catalog-item-description">Libreta artesanal hecha a mano con materiales de la más alta calidad</p>
                            <div class="catalog-item-footer">
                                <span class="catalog-item-price"><?= money($producto->Precio) ?></span>
                                <span class="catalog-item-action">
                                    Ver más
                                    <span class="material-symbols-outlined">arrow_forward</span>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="catalog-empty">
                <div class="catalog-empty-icon">📚</div>
                <h3>No hay productos disponibles</h3>
                <p>Estamos trabajando en nuevos diseños increíbles. ¡Vuelve pronto!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
