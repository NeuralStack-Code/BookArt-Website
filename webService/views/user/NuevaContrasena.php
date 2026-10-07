<?php
/**
 * Pantalla del enlace del correo: /auth/restablecer?Token=<64 hex>. La pinta AuthController::restablecer().
 * Recibe $token y $validToken (solo el formato: el token se valida de verdad al guardar).
 */
$tokenValido = $validToken;

$title    = 'Nueva contraseña - BookArt';
$extraCss = ['styleSesion.css'];
$extraJs  = ['funcionModal.js'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php require __DIR__ . '/../partials/head.php'; ?>
<meta name="referrer" content="no-referrer"><!-- el token va en la URL: no mandarlo a otros sitios -->
</head>
<body>
<?php require __DIR__ . '/../partials/notificador.php'; ?>
    <main>
        <dialog class="iniciarSesion" id="modal">
            <?php if ($tokenValido): ?>
            <form class="recuperacion" id="formNuevaContra" style="display:flex;">
                <h1>Elige tu nueva contraseña</h1>
                <input type="hidden" name="Token" value="<?= e($token) ?>">
                <div class="contentR">
                    <div style="position:relative;">
                        <p>Nueva contraseña</p>
                        <span id="imgVerContrasena3" class="material-symbols-outlined">visibility</span>
                        <input id="pass3" type="password" name="Contrasena" autocomplete="new-password" minlength="8" required>
                        <p class="etiquetaContra">Al menos 8 caracteres, con letras y números</p>
                    </div>
                    <div style="position:relative;margin-top:1.5rem;">
                        <p>Confirma la contraseña</p>
                        <span id="imgVerContrasena4" class="material-symbols-outlined">visibility</span>
                        <input id="pass4" type="password" name="ConfirmaContrasena" autocomplete="new-password" minlength="8" required>
                        <p class="etiquetaCoincidenciaC">Las contraseñas no coinciden</p>
                    </div>
                </div>
                <div class="btnReestablecer">
                    <button type="submit" id="btnGuardarContra">Guardar contraseña</button>
                </div>
            </form>
            <?php else: ?>
            <div class="recuperacion" style="display:flex;">
                <h1>Enlace no válido</h1>
                <div class="contentR">
                    <p>Este enlace está incompleto o ya no sirve. Pide uno nuevo desde "He olvidado mi contraseña".</p>
                </div>
                <div class="btnReestablecer">
                    <button type="button" onclick="location.href = (window.BASE_URL || '') + '/auth/entrar'">Ir a iniciar sesión</button>
                </div>
            </div>
            <?php endif; ?>

            <div class="imgSesion">
                <span id="cerrarSesion" class="material-symbols-outlined">close</span>
            </div>
        </dialog>
    </main>

<?php if ($tokenValido): ?>
<script>
document.getElementById('formNuevaContra').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarContra');
    const p1  = document.getElementById('pass3').value.trim();
    const p2  = document.getElementById('pass4').value.trim();
    if (p1 !== p2) { window.mostrarDialog('Las contraseñas no coinciden.', 'error'); return; }
    if (p1.length < 8 || !/[A-Za-z]/.test(p1) || !/[0-9]/.test(p1)) {
        window.mostrarDialog('La contraseña debe tener al menos 8 caracteres, con letras y números.', 'error'); return;
    }

    btn.disabled = true;
    try {
        const response = await fetch((window.BASE_URL || '') + '/auth/restablecer', { method: 'POST', body: new FormData(this) });
        const result   = await response.json();
        window.mostrarDialog(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            setTimeout(() => { location.href = (window.BASE_URL || '') + (result.data.redirect || '/auth/entrar'); }, 1800);
            return;   // el botón se queda deshabilitado: el enlace ya se usó
        }
    } catch {
        window.mostrarDialog('Error de conexión. Intenta de nuevo.', 'error');
    }
    btn.disabled = false;
});
</script>
<?php endif; ?>
</body>
</html>
