/* ===================================
   BOOKART - LÓGICA DE MODAL DE SESIÓN
   Manejo de pantallas y validaciones
   =================================== */

// Helper global: construye URLs de API respetando BASE_URL local/producción
window.api = (path) => (window.BASE_URL || '') + path;

document.addEventListener("DOMContentLoaded", function() {
    const modal = document.querySelector("#modal");
    const CerrarModal = document.querySelector("#cerrarSesion");
    const pass = document.getElementById("pass");
    const pass1 = document.getElementById("pass1");
    const pass2 = document.getElementById("pass2");
    const pass3 = document.getElementById("pass3");
    const pass4 = document.getElementById("pass4");
    const icon = document.getElementById("imgVerContrasena");
    const icon1 = document.getElementById("imgVerContrasena1");
    const icon2 = document.getElementById("imgVerContrasena2");
    const icon3 = document.getElementById("imgVerContrasena3");
    const icon4 = document.getElementById("imgVerContrasena4");
    
    // Obtiene el valor del parámetro de consulta "origen" de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const origen = urlParams.get("origen");

    if (modal) {
        modal.showModal();
    }

    // Agrega un event listener al botón de cerrar modal
    if (CerrarModal) {
        CerrarModal.addEventListener("click", () => {
            // Redirige al usuario a la página de origen
            if (origen) {
                window.location.href = '/' + origen;
            } else {
                window.location.href = '/';
            }
        });
    }

    // Toggle contraseñas con cambio de icono
    if (icon && pass) {
        icon.addEventListener("click", (e) => {
            if (pass.type === "password") {
                pass.type = "text";
                icon.textContent = "visibility_off";
            } else {
                pass.type = "password";
                icon.textContent = "visibility";
            }
        });
    }

    if (icon1 && pass1) {
        icon1.addEventListener("click", (e) => {
            if (pass1.type === "password") {
                pass1.type = "text";
                icon1.textContent = "visibility_off";
            } else {
                pass1.type = "password";
                icon1.textContent = "visibility";
            }
        });
    }

    if (icon2 && pass2) {
        icon2.addEventListener("click", (e) => {
            if (pass2.type === "password") {
                pass2.type = "text";
                icon2.textContent = "visibility_off";
            } else {
                pass2.type = "password";
                icon2.textContent = "visibility";
            }
        });
    }

    if (icon3 && pass3) {
        icon3.addEventListener("click", (e) => {
            if (pass3.type === "password") {
                pass3.type = "text";
                icon3.textContent = "visibility_off";
            } else {
                pass3.type = "password";
                icon3.textContent = "visibility";
            }
        });
    }

    if (icon4 && pass4) {
        icon4.addEventListener("click", (e) => {
            if (pass4.type === "password") {
                pass4.type = "text";
                icon4.textContent = "visibility_off";
            } else {
                pass4.type = "password";
                icon4.textContent = "visibility";
            }
        });
    }
});

// ===================================
// VALIDACIONES DE CREAR CUENTA
// ===================================

document.addEventListener("DOMContentLoaded", function() {
    const passwordRegex = /^(?=.*[0-9])(?=.*[A-Za-z])[\w!@#$%^&*()_+=-]+$/;
    const emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}(?:\.[A-Za-z]+)?$/;
    const phoneRegex = /^(?:\d\s?){10}$/;
    const nameRegex = /^[A-ZÁÉÍÓÚÜÑ][a-záéíóúüñ\s'-]+$/;
    const yearRegex = /^(19[3-9]\d|20[0-2]\d)$/;

    const buttonCC = document.getElementById("buttonCC");
    let validado = false;

    const nameInput = document.getElementById("name");
    const paternoInput = document.getElementById("firstname");
    const maternoInput = document.getElementById("lastname");
    const emailInput = document.getElementById("emailV");
    const password1Input = document.getElementById("pass1");
    const password2Input = document.getElementById("pass2");
    const phoneInput = document.getElementById("tel");
    const yearInput = document.getElementById("año");

    if (nameInput) {
        nameInput.addEventListener("input", function() {
            const inputValue = nameInput.value;
            validado = nameRegex.test(inputValue);
            toggleVisibility(validado, "etiquetaNombre");
            enableDisableButton();
        });
    }

    if (paternoInput) {
        paternoInput.addEventListener("input", function() {
            const inputValue = paternoInput.value;
            validado = nameRegex.test(inputValue);
            toggleVisibility(validado, "etiquetaPaterno");
            enableDisableButton();
        });
    }

    if (maternoInput) {
        maternoInput.addEventListener("input", function() {
            const inputValue = maternoInput.value;
            validado = inputValue === '' || nameRegex.test(inputValue);   // opcional: vacío es válido
            toggleVisibility(validado, "etiquetaMaterno");
            enableDisableButton();
        });
    }

    if (emailInput) {
        emailInput.addEventListener("input", function() {
            const inputValue = emailInput.value;
            validado = emailRegex.test(inputValue);
            toggleVisibility(validado, "etiquetaCorreo");
            enableDisableButton();
        });
    }

    if (password1Input) {
        password1Input.addEventListener("input", function() {
            const inputValue = password1Input.value;
            validado = passwordRegex.test(inputValue);
            toggleVisibility(validado, "etiquetaContra");
            enableDisableButton();

            if (password2Input) {
                const pass1Value = password1Input.value;
                const pass2Value = password2Input.value;

                if (pass1Value !== pass2Value) {
                    toggleVisibility(false, "etiquetaCoincidenciaC");
                    validado = false;
                } else {
                    toggleVisibility(true, "etiquetaCoincidenciaC");
                    enableDisableButton();
                }
            }
        });
    }

    if (password2Input) {
        password2Input.addEventListener("input", function() {
            const inputValue = password2Input.value;
            validado = passwordRegex.test(inputValue);
            toggleVisibility(validado, "etiquetaContra2");
            enableDisableButton();

            if (password1Input) {
                const pass1Value = password1Input.value;
                const pass2Value = password2Input.value;

                if (pass1Value !== pass2Value) {
                    toggleVisibility(false, "etiquetaCoincidenciaC");
                    validado = false;
                } else {
                    toggleVisibility(true, "etiquetaCoincidenciaC");
                    enableDisableButton();
                }
            }
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener("input", function() {
            const inputValue = phoneInput.value;
            validado = phoneRegex.test(inputValue);
            toggleVisibility(validado, "etiquetaTel");
            enableDisableButton();
        });
    }

    if (yearInput) {
        yearInput.addEventListener("input", function() {
            const inputValue = yearInput.value;
            validado = yearRegex.test(inputValue);
            toggleVisibility(validado, "etiquetaAño");
            enableDisableButton();
        });
    }

    function toggleVisibility(isValid, className) {
        const element = document.getElementsByClassName(className)[0];
        if (element) {
            element.style.visibility = isValid ? 'hidden' : 'visible';
        }
    }

    function enableDisableButton() {
        if (buttonCC) {
            buttonCC.disabled = !validado;
        }
    }
});

// ===================================
// VALIDACIONES DE RECUPERACIÓN
// ===================================

document.addEventListener("DOMContentLoaded", function() {
    const passwordRegex = /^(?=.*[0-9])(?=.*[A-Za-z])[\w!@#$%^&*()_+=-]+$/;
    const emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}(?:\.[A-Za-z]+)?$/;

    const restablece = document.getElementById("restableceContra");
    const emailInput = document.getElementById("correoRecuperacion");
    const password3 = document.getElementById("pass3");
    const password4 = document.getElementById("pass4");

    let validado = false;

    if (emailInput) {
        emailInput.addEventListener("input", function() {
            const inputValue = emailInput.value;
            validado = emailRegex.test(inputValue);
            toggleVisibility(validado, "etiquetaError");
            enableDisableButton();
        });
    }

    if (password3) {
        password3.addEventListener("input", function() {
            const inputValue = password3.value;
            validado = passwordRegex.test(inputValue);
            toggleVisibility(validado, "etiquetaContra");
            enableDisableButton();

            if (password4) {
                const pass3Value = password3.value;
                const pass4Value = password4.value;

                if (pass3Value !== pass4Value) {
                    toggleVisibility(false, "etiquetaCoincidenciaC");
                    validado = false;
                } else {
                    toggleVisibility(true, "etiquetaCoincidenciaC");
                    enableDisableButton();
                }
            }
        });
    }

    if (password4) {
        password4.addEventListener("input", function() {
            const inputValue = password4.value;
            validado = passwordRegex.test(inputValue);
            toggleVisibility(validado, "etiquetaContra");
            enableDisableButton();

            if (password3) {
                const pass3Value = password3.value;
                const pass4Value = password4.value;

                if (pass3Value !== pass4Value) {
                    toggleVisibility(false, "etiquetaCoincidenciaC");
                    validado = false;
                } else {
                    toggleVisibility(true, "etiquetaCoincidenciaC");
                    enableDisableButton();
                }
            }
        });
    }

    function toggleVisibility(isValid, className) {
        const element = document.getElementsByClassName(className)[0];
        if (element) {
            element.style.visibility = isValid ? 'hidden' : 'visible';
        }
    }

    function enableDisableButton() {
        if (restablece) {
            restablece.disabled = !validado;
        }
    }
});

// ===================================
// NAVEGACIÓN ENTRE PANTALLAS
// ===================================

function abrirSesion() {
    ocultar();
    mostrar();
}

function crearCuenta() {
    ocultar();
    ocultarS();
    mostrarS();
}

function cambiar() {
    ocultarCC();
    mostrar();
    resetForm();
}

function reset() {
    mostrarInicio();
    ocultarCC();
    ocultarS();
    ocultarR();
}

function reestablecerC() {
    ocultarS();
    ocultar();
    mostrarR();
}

function reestablece() {
    ocultarR();
    mostrar();
}

function mostrarInicio() {
    const sesion = document.getElementById('sesion');
    if (sesion) {
        sesion.style.display = 'flex';
    }
}

function ocultar() {
    const sesion = document.querySelector("#sesion");
    if (sesion) {
        sesion.style.display = 'none';
    }
}

function ocultarS() {
    const is = document.querySelector("#is");
    if (is) {
        is.style.display = 'none';
    }
}

function mostrar() {
    const is = document.getElementById('is');
    if (is) {
        is.style.display = 'flex';
    }
}

function mostrarS() {
    const cc = document.getElementById('cc');
    if (cc) {
        cc.style.display = 'block';
    }
}

function ocultarCC() {
    const cc = document.getElementById('cc');
    if (cc) {
        cc.style.display = 'none';
    }
}

function mostrarR() {
    const recuperar = document.getElementById('reestablecerContra');
    if (recuperar) {
        recuperar.style.display = 'flex';
    }
}

function ocultarR() {
    const recuperar = document.getElementById('reestablecerContra');
    if (recuperar) {
        recuperar.style.display = 'none';
    }
}

function resetForm() {
    const fields = {
        "name": "",
        "firstname": "",
        "lastname": "",
        "usuario": "",
        "emailV": "",
        "pass1": "",
        "pass2": "",
        "tel": "",
        "dias": "01",
        "meses": "01",
        "año": ""
    };

    for (const [id, value] of Object.entries(fields)) {
        const field = document.getElementById(id);
        if (field) {
            field.value = value;
        }
    }
    
    // Ocultar todas las etiquetas de error
    const etiquetas = document.querySelectorAll('[class*="etiqueta"]');
    etiquetas.forEach(etiqueta => {
        etiqueta.style.visibility = 'hidden';
    });
}

const formLogin = document.querySelector('#is form');
const formCrear = document.getElementById('cc');
const formRecuperar = document.getElementById('reestablecerContra');
const telInput = document.getElementById('tel');

if (formLogin) formLogin.reset();
if (formCrear) formCrear.reset();
if (formRecuperar) formRecuperar.reset();

// Ocultar todas las etiquetas de error
document.querySelectorAll('[class*="etiqueta"]').forEach(etiqueta => {
    etiqueta.style.visibility = 'hidden';
});

// Formatear teléfono automáticamente
if (telInput) {
    telInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 10) value = value.slice(0, 10);
        e.target.value = value;
    });
}

// Componente global de notificaciones — delega al notificador.php
// window.notify() es declarado por views/partials/notificador.php
window.BookArtNotification = {
    show: function(mensaje, tipo = 'info') {
        // Limpiar emojis que se agregaban antes (✅ ❌ ⚠️) — el notificador los pone
        const limpio = mensaje.replace(/^[✅❌⚠️ℹ️\s]+/, '');
        if (typeof window.notify === 'function') {
            window.notify(tipo, limpio);
        }
    },
    close: function() {
        // Ya no aplica — los toasts se cierran solos
    }
};

window.mostrarDialog = function(mensaje, tipo = 'info') {
    window.BookArtNotification.show(mensaje, tipo);
};

window.mostrarAlerta = function(mensaje, tipo = 'info') {
    window.BookArtNotification.show(mensaje, tipo);
};

// Event listener para cerrar el dialog
document.addEventListener('DOMContentLoaded', function() {
    const btnAcept = document.getElementById('btnAcept');
    if (btnAcept) {
        btnAcept.addEventListener('click', function() {
            window.BookArtNotification.close();
        });
    }
});

// ===================================
// SUBMIT VÍA API
// ===================================

document.addEventListener('DOMContentLoaded', function () {

    // — LOGIN —
    const formLogin = document.getElementById('formLogin');
    if (formLogin) {
        formLogin.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn        = formLogin.querySelector('[type="submit"]');
            const errorEl    = document.getElementById('loginError');
            btn.disabled     = true;
            if (errorEl) errorEl.style.display = 'none';
            try {
                const response = await fetch(BASE_URL + '/auth/entrar', { method: 'POST', body: new FormData(formLogin) });
                const result   = await response.json();
                if (result.success) {
                    window.location.href = BASE_URL + result.data.redirect;
                } else {
                    if (errorEl) {
                        errorEl.textContent = '⚠️ ' + result.message;
                        errorEl.style.display = 'block';
                    }
                }
            } catch {
                if (errorEl) {
                    errorEl.textContent = '⚠️ Error de conexión. Intenta de nuevo.';
                    errorEl.style.display = 'block';
                }
            } finally {
                btn.disabled = false;
            }
        });
    }

    // — CREAR CUENTA —
    const formCC = document.getElementById('cc');
    if (formCC) {
        formCC.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = formCC.querySelector('[type="submit"]');
            btn.disabled = true;
            try {
                const response = await fetch(BASE_URL + '/auth/registrar', { method: 'POST', body: new FormData(formCC) });
                const result   = await response.json();
                if (result.success) {
                    window.mostrarDialog('✅ ' + result.message, 'success');
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    window.mostrarDialog('❌ ' + result.message, 'error');
                }
            } catch {
                window.mostrarDialog('❌ Error de conexión.', 'error');
            } finally {
                btn.disabled = false;
            }
        });
    }

    // — RECUPERAR CONTRASEÑA —
    const formRecuperar = document.getElementById('reestablecerContra');
    if (formRecuperar) {
        formRecuperar.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = formRecuperar.querySelector('[type="submit"]');
            btn.disabled = true;
            try {
                const response = await fetch(BASE_URL + '/auth/recuperar', { method: 'POST', body: new FormData(formRecuperar) });
                const result   = await response.json();
                window.mostrarDialog((result.success ? '✅ ' : '❌ ') + result.message, result.success ? 'success' : 'error');
                if (result.success) formRecuperar.reset();
            } catch {
                window.mostrarDialog('❌ Error de conexión.', 'error');
            } finally {
                btn.disabled = false;
            }
        });
    }
});

console.log('✅ BookArt - Sistema de sesión cargado correctamente');