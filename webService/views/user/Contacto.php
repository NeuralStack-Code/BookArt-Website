<?php
/** Pantalla de contacto. La pinta ContactoController::index(). Los campos se llaman como el parámetro que reciben. */
$title   = 'Contacto - BookArt';
$extraJs = ['funcionModal.js', 'userMenu.js'];
?>
<!DOCTYPE html>
<html lang="es">
<head><?php require __DIR__ . '/../partials/head.php'; ?></head>
<body>
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="contact-hero">
    <h1>¡Contáctanos!</h1>
    <p>Estamos aquí para responder tus preguntas y hacer realidad tus ideas</p>
</section>

<section class="contact-main">
    <dialog id="warning">
        <p id="mensaje"></p>
        <div class="btnModal">
            <button id="btnAcept" style="padding:.8rem 2rem;background:var(--verde-bookart);color:white;border:3px solid var(--marron-texto);cursor:pointer;font-family:var(--font-body);font-weight:700;box-shadow:3px 3px 0px var(--marron-texto);">Aceptar</button>
        </div>
    </dialog>

    <div class="contact-container">
        <aside class="contact-info-side">
            <h2 class="contact-info-title">Información de Contacto</h2>
            <p class="contact-info-text">
                Estamos encantados de escucharte. Ya sea que tengas una pregunta,
                quieras hacer un pedido personalizado o simplemente quieras saber más
                sobre nuestro trabajo artesanal, ¡contáctanos!
            </p>
            <div class="info-cards">
                <div class="info-card">
                    <div class="info-card-icon"><span class="material-symbols-outlined">mail</span></div>
                    <div class="info-card-content"><h3>Correo Electrónico</h3><p>bookart@encuadernaciones.com</p></div>
                </div>
                <div class="info-card">
                    <div class="info-card-icon"><span class="material-symbols-outlined">phone</span></div>
                    <div class="info-card-content"><h3>Teléfono</h3><p>+52 (55) 1234-5678</p></div>
                </div>
                <div class="info-card">
                    <div class="info-card-icon"><span class="material-symbols-outlined">location_on</span></div>
                    <div class="info-card-content"><h3>Ubicación</h3><p>Ciudad de México, México</p></div>
                </div>
                <div class="info-card">
                    <div class="info-card-icon"><span class="material-symbols-outlined">schedule</span></div>
                    <div class="info-card-content"><h3>Horario</h3><p>Lun - Vie: 9:00 AM - 6:00 PM<br>Sáb: 10:00 AM - 2:00 PM</p></div>
                </div>
            </div>
            <div class="social-section">
                <h3 class="social-title">Síguenos en Redes</h3>
                <div class="social-links-grid">
                    <a href="https://www.facebook.com/profile.php?id=100090511421324" class="social-link" target="_blank">
                        <img src="<?= e($base) ?>/webService/wwwroot/img/facebook-icon.png" alt="Facebook"><span>Facebook</span>
                    </a>
                    <a href="https://www.instagram.com/bookart_encuadernaciones/" class="social-link" target="_blank">
                        <img src="<?= e($base) ?>/webService/wwwroot/img/Instagram-Icon.png" alt="Instagram"><span>Instagram</span>
                    </a>
                </div>
            </div>
        </aside>

        <div class="contact-form-side">
            <h2 class="contact-form-title">Envíanos un Mensaje</h2>
            <p class="contact-form-subtitle">* Utilizamos tu información sólo para contactarnos contigo</p>

            <form class="contact-form" id="contactForm">
                <div class="form-group-contact">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="Nombre" maxlength="100" required placeholder="Juan Pérez García">
                </div>
                <div class="form-group-contact">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="Correo" required placeholder="tu@email.com">
                </div>
                <div class="form-group-contact">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="Telefono" maxlength="30" required placeholder="(55) 1234-5678">
                </div>
                <div class="form-group-contact">
                    <label for="comentario">Tu Mensaje</label>
                    <textarea id="comentario" name="Mensaje" maxlength="3000" required placeholder="Cuéntanos en qué podemos ayudarte..."></textarea>
                </div>
                <button type="submit" class="btn-submit-contact">
                    <span class="material-symbols-outlined">send</span>
                    Enviar Mensaje
                </button>
                <div class="success-message" id="successMessage">
                    <span class="material-symbols-outlined">check_circle</span>
                    ¡Mensaje enviado con éxito! Te contactaremos pronto.
                </div>
            </form>
        </div>
    </div>

    <div class="contact-decoration">
        <h3 class="contact-decoration-title">¿Listo para crear algo único?</h3>
        <p class="contact-decoration-text">
            Ya sea que busques una libreta del catálogo o quieras diseñar una completamente personalizada,
            estamos aquí para ayudarte a hacer realidad tu visión artesanal.
        </p>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>

<script>
const form = document.getElementById('contactForm');
if (form) {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = form.querySelector('.btn-submit-contact');
        btn.disabled = true;
        btn.textContent = 'Enviando...';
        try {
            const response = await fetch(api('/contacto/enviar'), { method: 'POST', body: new FormData(form) });
            const result   = await response.json();
            if (result.success) {
                form.reset();
                document.getElementById('successMessage').style.display = 'flex';
            } else {
                mostrarDialog('❌ ' + result.message, 'error');
            }
        } catch {
            mostrarDialog('❌ Error de conexión. Intenta de nuevo.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined">send</span> Enviar Mensaje';
        }
    });
}
</script>
</body>
</html>
