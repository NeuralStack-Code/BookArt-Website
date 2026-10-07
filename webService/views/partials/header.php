<?php
if (!defined('BASE_URL')) require_once __DIR__ . '/../../../apiService/core/config.php';
$base = BASE_URL;

require_once __DIR__ . '/notificador.php';

if (isset($_SESSION['usuario'])) {
    $displayName = $_SESSION['usuario'];
    if (strpos($displayName, '@') !== false) {
        $displayName = explode('@', $displayName)[0];
    }
}
?>
<header>
    <div class="header-content">
        <div class="logo-container">
            <a href="<?= $base ?>/"><img class="logo" src="<?= $base ?>/webService/wwwroot/img/Logo/BookArt Positivo_B-N.png" alt="BookArt Logo"></a>
            <h1 class="site-title">BookArt</h1>
        </div>

        <nav class="main-nav" id="mainNav">
            <ul class="nav-links">
                <li><a href="<?= $base ?>/">Inicio</a></li>
                <li><a href="<?= $base ?>/productos">Productos</a></li>
                <li><a href="<?= $base ?>/contacto">Contacto</a></li>
            </ul>

            <?php if (isset($_SESSION['usuario'])): ?>
                <div class="user-menu-wrapper active">
                    <button class="user-menu-trigger" type="button">
                        <span class="material-symbols-outlined">account_circle</span>
                        <span class="user-name-display"><?= htmlspecialchars(substr($displayName, 0, 15)) ?></span>
                        <span class="material-symbols-outlined">expand_more</span>
                    </button>
                    <div class="user-dropdown">
                        <div class="user-dropdown-header">
                            <div class="user-dropdown-avatar"><span class="material-symbols-outlined">person</span></div>
                            <div class="user-dropdown-name"><?= htmlspecialchars($displayName) ?></div>
                            <div class="user-dropdown-email"><?= htmlspecialchars($_SESSION['usuario']) ?></div>
                        </div>
                        <div class="user-dropdown-menu">
                            <a href="<?= $base ?>/pedidos" class="user-dropdown-item">
                                <span class="material-symbols-outlined">receipt_long</span><span>Mis Pedidos</span>
                            </a>
                            <a href="<?= $base ?>/carrito" class="user-dropdown-item" style="position:relative;">
                                <span class="material-symbols-outlined">shopping_cart</span><span>Mi Carrito</span>
                                <span class="user-menu-badge" style="display:none;">0</span>
                            </a>
                            <button onclick="cerrarSesion()" class="user-dropdown-item logout" style="width:100%;background:none;border:none;cursor:pointer;text-align:left;">
                                <span class="material-symbols-outlined">logout</span><span>Cerrar Sesión</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= $base ?>/auth/entrar" class="btn-session">Iniciar sesión</a>
            <?php endif; ?>
        </nav>

        <button class="menu-toggle" id="menuToggle" onclick="toggleMenu()">
            <span class="material-symbols-outlined">menu</span>
        </button>
    </div>
</header>

<script>
async function cerrarSesion() {
    try {
        await fetch((window.BASE_URL || '') + '/auth/salir', { method: 'POST' });
    } catch {}
    window.location.href = (window.BASE_URL || '') + '/auth/entrar';
}

function toggleMenu() {
    const nav = document.getElementById('mainNav');
    const toggle = document.getElementById('menuToggle');
    nav.classList.toggle('active');
    const icon = toggle.querySelector('.material-symbols-outlined');
    icon.textContent = nav.classList.contains('active') ? 'close' : 'menu';
    document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
}

document.querySelectorAll('.nav-links a, .btn-session').forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            document.getElementById('mainNav').classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

document.addEventListener('click', function(e) {
    const nav = document.getElementById('mainNav');
    const toggle = document.getElementById('menuToggle');
    if (nav.classList.contains('active') && !nav.contains(e.target) && !toggle.contains(e.target)) {
        toggleMenu();
    }
});
</script>
