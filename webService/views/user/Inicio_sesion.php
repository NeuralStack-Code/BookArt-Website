<?php
if (isset($_SESSION['usuario'])) {
    header('Location: /'); exit;
}

$title    = 'Inicio Sesión - BookArt';
$extraCss = ['styleSesion.css'];
$extraJs  = ['funcionModal.js'];
?>
<!DOCTYPE html>
<html lang="es">
<head><?php require __DIR__ . '/../partials/head.php'; ?></head>
<body>
<?php require __DIR__ . '/../partials/notificador.php'; ?>
    <main>
        <dialog class="iniciarSesion" id="modal">
            <!-- PANTALLA INICIAL -->
            <div class="Sesion" id="sesion">
                <h1>Inicia Sesión o regístrate</h1>
                <p class="pLeyenda">Usa tu correo para acceder a tu cuenta BookArt</p>
                <button class="btnIS" id="btnis" onclick="crearCuenta()">Crear una cuenta</button>
                <button class="btnIS" id="btn" onclick="abrirSesion()">Iniciar sesión</button>
                <p class="condiciones">Al continuar, aceptas los Términos y condiciones de uso.<br>Consulta nuestra política de privacidad</p>
            </div>

            <!-- INICIO DE SESIÓN -->
            <div class="inicioSesion" id="is">
                <h1>Inicio de Sesión</h1>
                <form id="formLogin">
                    <div style="position:relative;">
                        <p class="ingresaUsuario">Ingresa tu usuario o correo electrónico</p>
                        <input type="text" name="sesionUsuario" required>
                    </div>
                    <div style="position:relative;">
                        <p class="ingresaContra">Ingresa tu contraseña</p>
                        <span id="imgVerContrasena" class="material-symbols-outlined">visibility</span>
                        <input id="pass" type="password" name="sesionContra" required>
                    </div>
                    <p id="loginError" style="display:none;color:#c0392b;font-family:var(--font-body);font-size:.85rem;margin:.5rem 0;padding:.6rem 1rem;background:#fde8e8;border:2px solid #c0392b;border-radius:4px;"></p>
                    <p class="olvidaContra" onclick="reestablecerC()">He olvidado mi contraseña</p>
                    <button class="ISbtn" type="submit">Iniciar sesión</button>
                    <p class="faltaCuenta">¿Aún no tienes cuenta?<span class="creaCuentaDesdeInicio" onclick="crearCuenta()">Crear cuenta</span></p>
                </form>
            </div>

            <!-- CREAR CUENTA -->
            <form id="cc" class="crearCuenta">
                <h1>Crear una cuenta</h1>
                <div class="cNombre">
                    <p>Nombre</p>
                    <input type="text" id="name" name="nombre" data-error-id="etiquetaNombre" required>
                    <p class="etiquetaNombre">Debe empezar con mayúscula y no se aceptan números</p>
                </div>
                <div class="cApellidoP">
                    <p>Apellido Paterno</p>
                    <input id="firstname" type="text" name="paterno" data-error-id="etiquetaPaterno" required>
                    <p class="etiquetaPaterno">Debe empezar con mayúscula y no se aceptan números</p>
                </div>
                <div class="cApellidoM">
                    <p>Apellido Materno</p>
                    <input id="lastname" type="text" name="materno" data-error-id="etiquetaMaterno" required>
                    <p class="etiquetaMaterno">Debe empezar con mayúscula y no se aceptan números</p>
                </div>
                <div class="cUsuario">
                    <p>Nombre de usuario</p>
                    <input id="usuario" type="text" name="usuario" data-error-id="etiquetaUsuario" required>
                    <p class="etiquetaUsuario">Verifica tu usuario</p>
                </div>
                <div class="cTelefono">
                    <p>Teléfono</p>
                    <input id="tel" type="tel" name="tel" data-error-id="etiquetaTel" required>
                    <p class="etiquetaTel">Verifica tu teléfono</p>
                </div>
                <p class="tituloFechaN">Fecha de nacimiento</p>
                <div class="cDia">
                    <p>Día</p>
                    <select name="Dia" id="dias" required>
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?= str_pad($d, 2, '0', STR_PAD_LEFT) ?>"><?= $d ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="cMes">
                    <p>Mes</p>
                    <select name="Mes" id="meses" required>
                        <?php $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                        foreach ($meses as $i => $mes): ?>
                            <option value="<?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?>"><?= $mes ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cAño">
                    <p>Año</p>
                    <input id="año" type="text" name="anio" required>
                    <p class="etiquetaAño">Verifica tu año de nacimiento</p>
                </div>
                <div class="cCorreo">
                    <p>Correo electrónico</p>
                    <input id="emailV" type="email" name="correo" placeholder="example@mail.com" data-error-id="etiquetaCorreo" required>
                    <p class="etiquetaCorreo">Verifica tu correo electrónico</p>
                </div>
                <div class="cContraseña">
                    <p>Contraseña</p>
                    <input id="pass1" type="password" name="contrasena" data-error-id="etiquetaContra" required>
                    <span id="imgVerContrasena1" class="material-symbols-outlined">visibility</span>
                    <p class="etiquetaContra">Debe contener 8 caracteres: números, símbolos y mayúsculas</p>
                    <p class="etiquetaCoincidenciaC">Las contraseñas no coinciden</p>
                </div>
                <div class="cCContraseña">
                    <p>Confirma tu contraseña</p>
                    <input id="pass2" type="password" name="ConfirmaContra" data-error-id="etiquetaContra2" required>
                    <span id="imgVerContrasena2" class="material-symbols-outlined">visibility</span>
                    <p class="etiquetaContra2">Debe contener 8 caracteres: números, símbolos y mayúsculas</p>
                    <p class="etiquetaCoincidenciaC">Las contraseñas no coinciden</p>
                </div>
                <input type="hidden" name="validado" id="validado" value="0">
                <div class="botonCrearCuenta">
                    <button id="buttonCC" type="submit" disabled>Crear cuenta</button>
                    <div class="verCuenta">
                        <p>¿Ya tienes una cuenta?</p>
                        <label class="cambiarInicio" onclick="cambiar()">Iniciar sesión</label>
                    </div>
                </div>
            </form>

            <!-- RECUPERACIÓN DE CONTRASEÑA -->
            <form class="recuperacion" id="reestablecerContra">
                <h1>Recuperación de contraseña</h1>
                <div class="contentR">
                    <p>Ingresa el correo de tu cuenta y te enviaremos un enlace para elegir una contraseña nueva.</p>
                    <input type="email" id="correoRecuperacion" name="correoRecupera" autocomplete="email" required>
                    <p class="etiquetaError">Revisa tu correo electrónico</p>
                </div>
                <div class="btnReestablecer">
                    <button type="submit" id="restabeceContra">Enviar enlace</button>
                </div>
            </form>

            <!-- IMAGEN LATERAL -->
            <div class="imgSesion">
                <span id="cerrarSesion" class="material-symbols-outlined" onclick="reset()">close</span>
                <img class="imgP" src="/webService/wwwroot/img/PantallaSesion.png" alt="BookArt">
            </div>
        </dialog>
    </main>

    <dialog id="warning">
        <p id="mensaje"></p>
        <div class="btnModal">
            <button id="btnAcept">Aceptar</button>
        </div>
    </dialog>
</body>
</html>
