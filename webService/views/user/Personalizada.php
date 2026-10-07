<?php
/** Pantalla para diseñar una libreta. La pinta PersonalizadaController::index(). Los campos se llaman como la propiedad de Personalizada. */
$title   = '¡Personaliza tu libreta! - BookArt';
$extraJs = ['userMenu.js', 'funcionModal.js', 'personalizada.js'];
?>
<!DOCTYPE html>
<html lang="es">
<head><?php require __DIR__ . '/../partials/head.php'; ?></head>
<body>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="personalized-hero">
    <h1>¡Diseña tu Libreta Soñada!</h1>
    <p>En BookArt, te damos la libertad total de diseñar tu libreta perfecta. ¡Todo es posible cuando se trata de tu creatividad!</p>
</section>

<div class="section-divider"></div>

<section class="personalized-form-section">
    <div class="personalized-form-container">
        <h2 class="form-section-title">Comencemos a Crear</h2>
        <p class="form-section-subtitle">Selecciona las opciones que mejor se adapten a tu estilo y necesidades</p>

        <dialog id="warning">
            <p id="mensaje"></p>
            <div class="btnModal"><button id="btnAcept">Aceptar</button></div>
        </dialog>

        <form id="formPersonalizada" enctype="multipart/form-data">
            <div class="options-section">
                <h3 class="options-section-title">Tipo de Encuadernación</h3>
                <p style="text-align:center;color:var(--marron-texto);margin-bottom:2rem;">Elige el estilo que más te guste</p>
                <input type="hidden" id="opcFinal" name="TipoEncuadernacion" value="">
                <div class="binding-options-grid">
                    <label class="binding-option">
                        <input type="radio" id="opcClasica" name="opcion" value="encuadernacionClasica">
                        <div class="binding-content">
                            <div class="binding-image"><img src="/webService/wwwroot/img/libretaEncuadernada.jpg" alt="Encuadernación Clásica"></div>
                            <h4 class="binding-title">Encuadernación Clásica</h4>
                            <p class="binding-description">Técnica tradicional con costura expuesta que da un toque artesanal elegante</p>
                        </div>
                    </label>
                    <label class="binding-option">
                        <input type="radio" id="opcPiel" name="opcion" value="diseñoPiel">
                        <div class="binding-content">
                            <div class="binding-image"><img src="/webService/wwwroot/img/libretaPiel.jpg" alt="Diseño en Piel"></div>
                            <h4 class="binding-title">Diseño en Piel</h4>
                            <p class="binding-description">Cubierta en piel auténtica con acabados de lujo y personalización única</p>
                        </div>
                    </label>
                    <label class="binding-option">
                        <input type="radio" id="opcEngargolado" name="opcion" value="diseñoEngargolado">
                        <div class="binding-content">
                            <div class="binding-image"><img src="/webService/wwwroot/img/libretaEngargolada.jpg" alt="Diseño Engargolado"></div>
                            <h4 class="binding-title">Diseño Engargolado</h4>
                            <p class="binding-description">Práctico y funcional, perfecto para escritura diaria con apertura de 360°</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="customization-form">
                <div class="form-row">
                    <div class="form-field">
                        <label for="tam">Tamaño de tu libreta</label>
                        <select name="Tamano" id="tam" required>
                            <option value="Chica 11cm x 12cm">Chica (11cm x 12cm)</option>
                            <option value="Mediana 14cm x 23cm" selected>Mediana (14cm x 23cm)</option>
                            <option value="Grande 18cm x 23cm">Grande (18cm x 23cm)</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="tipoPap">Tipo de papel</label>
                        <select name="TipoPapel" id="tipoPap" required>
                            <option value="Ahuesado">Ahuesado</option>
                            <option value="Capuchino">Capuchino (Reciclado)</option>
                            <option value="Blanco" selected>Blanco</option>
                        </select>
                    </div>
                </div>
                <div class="form-field" style="margin-top:2rem;">
                    <label for="color">Color de detalles</label>
                    <p style="color:var(--marron-texto);margin-bottom:1rem;font-size:.9rem;">Escoge el tono para resaltar el lomo y otros detalles clave</p>
                    <div class="color-picker-wrapper">
                        <input type="color" name="Color" id="color" class="color-picker-preview" value="#1E9332">
                        <input type="text" class="color-picker-input" value="#1E9332" readonly>
                    </div>
                </div>
                <div class="form-field" style="margin-top:2rem;">
                    <label for="portada">Portada personalizada (opcional)</label>
                    <div class="file-upload-wrapper">
                        <label for="portada" class="file-upload-label">
                            <span class="file-upload-icon">📁</span>
                            <span class="file-upload-text">Haz clic para subir tu diseño</span>
                            <span class="file-upload-hint">Formatos: JPG, PNG o WEBP (Máx. 5MB)</span>
                        </label>
                        <input type="file" name="Portada" id="portada" class="file-upload-input" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div id="imagePreview" class="image-preview" style="display:none;">
                        <img id="previewImg" src="" alt="Vista previa">
                        <button type="button" class="remove-preview" onclick="removePreview()">×</button>
                    </div>
                </div>
                <div class="form-field" style="margin-top:2rem;">
                    <label for="desc">Describe tu portada ideal</label>
                    <textarea name="Descripcion" id="desc" maxlength="150" placeholder="Cuéntanos cómo imaginas tu portada perfecta..."></textarea>
                </div>
                <div class="form-note">
                    <p class="form-note-text">
                        <strong>📝 Nota importante:</strong> Si escogiste libreta de piel, describe el color deseado y cualquier especificación adicional en el campo de descripción.
                    </p>
                </div>
                <div class="submit-section">
                    <button type="submit" class="btn-submit-custom">
                        <span class="material-symbols-outlined">send</span>
                        Enviar mi Diseño
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
