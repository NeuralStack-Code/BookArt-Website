<?php
/**
 * Autocarga del proyecto.
 * - Usa Composer si vendor/ existe (classmap optimizado).
 * - Fallback en runtime: resuelve las clases propias (controllers/business) SIN
 *   Composer. Así solo programas: no hay que correr comandos.
 * - Carga auth.php (funciones requireAuth/requireUser) para todos los controllers.
 *
 * Idempotente: se incluye desde el index.php raíz y desde apiService/index.php
 * (el .htaccess manda /api directo a apiService).
 */
if (defined('BA_AUTOLOAD_READY')) return;
define('BA_AUTOLOAD_READY', true);

$raiz   = dirname(__DIR__, 2);
$vendor = $raiz . '/vendor/autoload.php';
if (is_file($vendor)) require_once $vendor;   // opcional: solo si Composer generó vendor/

require_once __DIR__ . '/../middleware/auth.php';  // funciones de sesión (no autocargables)

spl_autoload_register(static function (string $clase) use ($raiz): void {
    if (strpos($clase, '\\') !== false) return;   // namespaced → Composer/lib

    static $mapa = null;
    if ($mapa === null) {
        $mapa = [];
        foreach (['webService/controllers', 'webService/business'] as $rel) {
            $dir = $raiz . '/' . $rel;
            if (!is_dir($dir)) continue;
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $f) {
                if ($f->isFile() && substr($f->getFilename(), -4) === '.php') {
                    $mapa[substr($f->getFilename(), 0, -4)] = $f->getPathname();
                }
            }
        }
    }
    if (isset($mapa[$clase])) require $mapa[$clase];
});
