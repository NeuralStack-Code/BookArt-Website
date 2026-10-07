<?php
/**
 * Partial: <head>
 * Variables esperadas:
 *   $title       string  Título de la página (requerido)
 *   $extraCss    array   CSS adicionales específicos de la página (opcional)
 *   $extraJs     array   JS adicionales específicos de la página (opcional)
 */
$title    = $title    ?? 'BookArt Encuadernaciones';
$extraCss = $extraCss ?? [];
$extraJs  = $extraJs  ?? [];
if (!defined('BASE_URL')) require_once __DIR__ . '/../../../apiService/core/config.php';
$base = BASE_URL;
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?></title>

<!-- Base URL para JS -->
<script>
window.BASE_URL = '<?= $base ?>';
window.api = (path) => window.BASE_URL + path;   // URL de un método o archivo del sitio: api('/catalogo/listar')
</script>

<!-- Fuentes -->
<link href="https://fonts.googleapis.com/css2?family=Chewy&family=Martian+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

<!-- CSS base -->
<link rel="stylesheet" href="<?= $base ?>/webService/wwwroot/CSS/reset.css">
<link rel="stylesheet" href="<?= $base ?>/webService/wwwroot/CSS/style.css">

<!-- CSS extra por página -->
<?php foreach ($extraCss as $css): ?>
<link rel="stylesheet" href="<?= $base ?>/webService/wwwroot/CSS/<?= htmlspecialchars($css) ?>">
<?php endforeach; ?>

<!-- JS extra por página -->
<?php foreach ($extraJs as $js): ?>
<script src="<?= $base ?>/webService/wwwroot/JavaScript/<?= htmlspecialchars($js) ?>"></script>
<?php endforeach; ?>
