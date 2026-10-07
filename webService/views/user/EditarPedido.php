<?php
/**
 * Pantalla para cambiar el diseño de una libreta ya pedida. La pinta PersonalizadaController::editar().
 * Recibe $libreta (Personalizada) y $base. Los campos se llaman como la propiedad de Personalizada.
 */
$title   = 'Editar Pedido - BookArt';
$extraJs = ['funcionModal.js'];
?>
<!DOCTYPE html>
<html lang="es">
<head><?php require __DIR__ . '/../partials/head.php'; ?></head>
<body>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="personalized-hero">
    <h1>✏️ Editar tu Pedido</h1>
    <p>Realiza los cambios necesarios a tu libreta personalizada</p>
</section>

<section class="personalized-form-section">
    <div class="personalized-form-container">
        <h2 class="form-section-title">Modificar Diseño</h2>
        <p class="form-section-subtitle">Pedido <?= e($libreta->number()) ?></p>

        <dialog id="warning">
            <p id="mensaje"></p>
            <div class="btnModal"><button id="btnAcept">Aceptar</button></div>
        </dialog>

        <form id="formPersonalizada" enctype="multipart/form-data">
            <input type="hidden" name="IdPedido" value="<?= (int) $libreta->IdPedido ?>">

            <div class="options-section">
                <h3 class="options-section-title">Tipo de Encuadernación</h3>
                <input type="hidden" id="opcFinal" name="TipoEncuadernacion" value="<?= e($libreta->TipoEncuadernacion) ?>">
                <div class="binding-options-grid">
                    <label class="binding-option">
                        <input type="radio" name="opcion" value="encuadernacionClasica"
                               <?= $libreta->TipoEncuadernacion === 'encuadernacionClasica' ? 'checked' : '' ?>>
                        <div class="binding-content">
                            <div class="binding-image"><img src="/webService/wwwroot/img/libretaEncuadernada.jpg" alt="Clásica"></div>
                            <h4 class="binding-title">Encuadernación Clásica</h4>
                        </div>
                    </label>
                    <label class="binding-option">
                        <input type="radio" name="opcion" value="diseñoPiel"
                               <?= $libreta->TipoEncuadernacion === 'diseñoPiel' ? 'checked' : '' ?>>
                        <div class="binding-content">
                            <div class="binding-image"><img src="/webService/wwwroot/img/libretaPiel.jpg" alt="Piel"></div>
                            <h4 class="binding-title">Diseño en Piel</h4>
                        </div>
                    </label>
                    <label class="binding-option">
                        <input type="radio" name="opcion" value="diseñoEngargolado"
                               <?= $libreta->TipoEncuadernacion === 'diseñoEngargolado' ? 'checked' : '' ?>>
                        <div class="binding-content">
                            <div class="binding-image"><img src="/webService/wwwroot/img/libretaEngargolada.jpg" alt="Engargolado"></div>
                            <h4 class="binding-title">Diseño Engargolado</h4>
                        </div>
                    </label>
                </div>
            </div>

            <div class="customization-form">
                <div class="form-row">
                    <div class="form-field">
                        <label for="tam">Tamaño de tu libreta</label>
                        <select name="Tamano" id="tam" required>
                            <?php foreach (Personalizada::SIZES as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $libreta->Tamano === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="tipoPap">Tipo de papel</label>
                        <select name="TipoPapel" id="tipoPap" required>
                            <?php foreach (Personalizada::PAPERS as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $libreta->TipoPapel === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-field" style="margin-top:2rem;">
                    <label for="color">Color de detalles</label>
                    <div class="color-picker-wrapper">
                        <input type="color" name="Color" id="color" class="color-picker-preview" value="<?= e($libreta->Color) ?>">
                        <input type="text" class="color-picker-input" value="<?= e($libreta->Color) ?>" readonly>
                    </div>
                </div>

                <?php if (!empty($libreta->Portada)): ?>
                <div class="form-field" style="margin-top:2rem;">
                    <label>Portada actual</label>
                    <div class="image-preview" style="display:block;">
                        <img src="<?= e($base . $libreta->Portada) ?>" alt="Portada actual">
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-field" style="margin-top:2rem;">
                    <label for="portada">Cambiar portada (opcional)</label>
                    <div class="file-upload-wrapper">
                        <label for="portada" class="file-upload-label">
                            <span class="file-upload-icon">📁</span>
                            <span class="file-upload-text">Subir nueva portada</span>
                        </label>
                        <input type="file" name="Portada" id="portada" class="file-upload-input" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>

                <div class="form-field" style="margin-top:2rem;">
                    <label for="desc">Descripción</label>
                    <textarea name="Descripcion" id="desc" maxlength="150" required><?= e($libreta->Descripcion) ?></textarea>
                </div>

                <div class="submit-section">
                    <button type="submit" class="btn-submit-custom">
                        <span class="material-symbols-outlined">save</span>
                        Guardar Cambios
                    </button>
                    <a href="<?= e($base) ?>/pedidos" class="btn-submit-custom" style="background:var(--marron-texto);text-decoration:none;display:inline-flex;">
                        <span class="material-symbols-outlined">cancel</span>
                        Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>

<script>
document.querySelectorAll('.binding-option input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('opcFinal').value = this.value;
    });
});

document.getElementById('color').addEventListener('input', function(e) {
    document.querySelector('.color-picker-input').value = e.target.value;
});

document.getElementById('portada').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let preview = document.querySelector('.image-preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.className = 'image-preview';
                document.getElementById('portada').parentElement.after(preview);
            }
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Nueva portada">';
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});

// Guardar: /personalizada/guardar (con IdPedido es un cambio)
document.getElementById('formPersonalizada')
    ?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;
        try {
            const response = await fetch(api('/personalizada/guardar'), { method: 'POST', body: new FormData(this) });
            const result   = await response.json();
            if (result.success) {
                window.location.href = api(result.data.redirect || '/pedidos');
            } else {
                const dialog = document.getElementById('warning');
                const msg    = document.getElementById('mensaje');
                if (dialog && msg) { msg.textContent = '❌ ' + result.message; dialog.showModal(); }
            }
        } catch {
            const dialog = document.getElementById('warning');
            const msg    = document.getElementById('mensaje');
            if (dialog && msg) { msg.textContent = '❌ Error de conexión.'; dialog.showModal(); }
        } finally {
            if (btn) btn.disabled = false;
        }
    });
</script>
</body>
</html>
