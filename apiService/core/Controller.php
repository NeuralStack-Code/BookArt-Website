<?php
/**
 * Base de los controllers: UN controller por entidad. La URL es /<controller>/<método> y cada método
 * público es un endpoint (index es la pantalla). El controller es el backend: valida, decide y responde.
 * NO toca la base: le pide todo al business por el server api ($this->api, ver core/ServerApi.php),
 * revisa el status y carga en models la tabla que recibe.
 *
 *     class ClienteController extends Controller {
 *         public function eliminar(int $IdCliente): void {
 *             $this->api->command('Ventas', 'Cliente', 'Delete');          // módulo, entidad, operación
 *             $this->api->addParameter('IdCliente', 'I', $IdCliente);      // nombre, tipo, valor
 *             $result = $this->api->execute();
 *
 *             if (!$result->status()) {                                    // status() TRUE = hubo error
 *                 $this->ensure($result->affected() > 0, 'Cliente no encontrado.', 404);
 *                 $this->success('Cliente eliminado.');
 *             } else {
 *                 $this->failure($result);
 *             }
 *         }
 *     }
 *
 * Lo que se repetiría en cada método vive aquí una sola vez (authorize): la sesión y el verbo HTTP.
 * Los errores inesperados los atrapa el router (log + "Error interno"); nunca llegan al navegador.
 */
abstract class Controller
{
    /** Base de datos que le da el router a este controller: 'principal' o 'ninguna'. */
    public const CONNECTION = 'principal';

    /** Métodos que cualquiera puede llamar SIN sesión (una página pública, 'entrar'…). */
    protected array $publicActions = [];
    /** Métodos que solo LEEN (aceptan GET). Todo lo demás escribe y exige POST. */
    protected array $readActions = ['index', 'listar'];

    /** Por aquí se le habla al business. */
    protected ServerApi $api;
    protected ?mysqli $db;
    protected string $method;

    public function __construct(?mysqli $connection = null)
    {
        $this->db     = $connection;
        $this->api    = new ServerApi($connection);
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /* ── Guardias ── */

    /**
     * Guardia ÚNICO de todos los métodos: el router lo llama antes de ejecutar cualquiera.
     *   1. Sesión, salvo en los métodos de $publicActions.
     *   2. POST, salvo en los métodos de $readActions.
     * Lo que pida algo más (ser administrador, un permiso propio) lo agrega su método o sobrescribe authorize().
     */
    public function authorize(string $action): void
    {
        if (!in_array($action, $this->publicActions, true)) $this->requireSession();
        if (!in_array($action, $this->readActions, true))   $this->requirePost();
    }

    /** Usuario en sesión (el que guardó tu login en $_SESSION['usuario']), o null. */
    protected function user(): mixed
    {
        return $_SESSION['usuario'] ?? null;
    }

    /** Id de la cuenta en sesión (0 si no hay). */
    protected function accountId(): int
    {
        return $this->user() === null ? 0 : (int) ($_SESSION['id_cuenta'] ?? 0);
    }

    protected function requireSession(): void
    {
        if ($this->user() === null) response(401, false, 'No autenticado.');
    }

    protected function requirePost(): void
    {
        if ($this->method !== 'POST') response(405, false, 'Método no permitido.');
    }

    /** ¿Quien está en sesión es administrador? (por convención, $_SESSION['permiso'] = 1) */
    protected function isAdmin(): bool
    {
        return (int) ($_SESSION['permiso'] ?? 0) === 1;
    }

    protected function requireAdmin(): void
    {
        $this->requireSession();
        if (!$this->isAdmin()) response(403, false, 'No tienes permisos de administrador.');
    }

    /** Lo que es del cliente (carrito, pedidos): pide una cuenta en sesión que no sea de administrador. */
    protected function requireCustomer(): void
    {
        $this->requireSession();
        if ($this->isAdmin()) response(403, false, 'Los administradores no pueden realizar pedidos.');
        if ($this->accountId() <= 0) response(401, false, 'Sesión no válida. Inicia sesión nuevamente.');
    }

    /** Guardia de las PANTALLAS del cliente: sin sesión se va a entrar; el administrador, a su panel. */
    protected function customerScreen(): void
    {
        if ($this->user() === null) $this->redirect('/auth/entrar');
        if ($this->isAdmin()) $this->redirect('/administrador');
        if ($this->accountId() <= 0) $this->redirect('/auth/entrar');      // sesión sin cuenta: que vuelva a entrar
    }

    /** Corta con 422 (o el estatus que se indique) si la condición no se cumple. */
    protected function ensure(bool $condition, string $message, int $http = 422): void
    {
        if (!$condition) response($http, false, $message);
    }

    /* ── Respuestas ── */

    /** Respuesta de éxito (JSON): mensaje y, si hace falta, datos. */
    protected function success(string $message, array $data = [], int $http = 200): void
    {
        response($http, true, $message, $data);
    }

    /** Respuesta de error con lo que contestó el business: su mensaje y el estatus HTTP que le corresponde. */
    protected function failure(Result $result): void
    {
        response($result->http(), false, $result->message());
    }

    /** Manda al navegador a otra pantalla del sitio. */
    protected function redirect(string $path): never
    {
        header('Location: ' . BASE_URL . $path, true, 302);
        exit;
    }

    /**
     * Pinta una vista (webService/views/<archivo>.php) con sus datos ya preparados.
     * La vista solo imprime: recibe cada dato como variable y usa e() para escapar.
     */
    protected function view(string $file, array $data = []): void
    {
        header('Content-Type: text/html; charset=utf-8');
        $base = BASE_URL;
        extract($data, EXTR_SKIP);
        require dirname(__DIR__, 2) . '/webService/views/' . $file . '.php';
    }
}

/**
 * Rutas por convención:  /<controller>/<método>   →   <Controller>Controller::<método>()
 *     /cliente/index      → ClienteController::index()       (la pantalla; /cliente a secas también llega aquí)
 *     /cliente/guardar    → ClienteController::guardar()
 * Regresa la clase del controller de ese primer tramo de la URL, o null si no hay. No hay mapa de rutas.
 */
function routeController(string $segment): ?string
{
    static $classes = null;
    if ($classes === null) {
        $classes = [];
        foreach (glob(dirname(__DIR__, 2) . '/webService/controllers/*Controller.php') as $file) {
            $class = basename($file, '.php');
            $classes[strtolower(substr($class, 0, -10))] = $class;      // 'cliente' => 'ClienteController'
        }
    }
    $class = $classes[strtolower($segment)] ?? null;
    return $class !== null && is_subclass_of($class, Controller::class) ? $class : null;
}

/**
 * Lo que el navegador le manda a un método (formulario, JSON o URL) llega como sus PARÁMETROS, por nombre:
 *
 *     public function eliminar(int $IdCliente): void            // un dato: se convierte a su tipo
 *     public function guardar(Cliente $cliente): void           // un model: se carga con los campos del formulario
 *
 * - Con tipo simple (int, float, bool, string): se toma el dato del mismo nombre y se convierte.
 *   Si no vino, vale su valor por defecto; sin valor por defecto, su vacío (0, '', false) o null si lo admite.
 * - Con tipo de un model: se arma el model con los campos que coincidan con sus propiedades.
 * - Sin tipo: llega el dato tal cual, o null si no vino.
 * - Un id obligatorio (int $IdAlgo, sin valor por defecto) que no venga o no sea mayor a 0 se rechaza aquí
 *   mismo con "ID inválido.": el método ya no tiene que revisarlo.
 */
function actionArguments(object $controller, string $action): array
{
    $data = $_POST + $_GET;
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $json = json_decode((string) file_get_contents('php://input'), true);
        if (is_array($json)) $data = $json + $data;
    }

    $arguments = [];
    foreach ((new ReflectionMethod($controller, $action))->getParameters() as $parameter) {
        $type  = $parameter->getType();
        $value = $data[$parameter->getName()] ?? null;
        if (is_string($value)) $value = trim($value);

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && is_subclass_of($type->getName(), Model::class)) {
            $arguments[] = $type->getName()::fromForm($data);
        } elseif ($value === null || ($value === '' && $type?->getName() !== 'string')) {
            $arguments[] = match (true) {
                $parameter->isDefaultValueAvailable() => $parameter->getDefaultValue(),
                $type === null || $type->allowsNull()  => null,
                default => ['int' => 0, 'float' => 0.0, 'bool' => false, 'string' => '', 'array' => []][$type->getName()] ?? null,
            };
        } else {
            $arguments[] = match ($type?->getName()) {
                'int'    => (int) $value,
                'float'  => (float) $value,
                'bool'   => !in_array($value, ['0', 'false', 'no', 0, false], true),
                'string' => is_scalar($value) ? (string) $value : '',
                'array'  => (array) $value,
                default  => $value,
            };
        }
        $isRequiredId = str_starts_with($parameter->getName(), 'Id') && !$parameter->isDefaultValueAvailable() && $type?->getName() === 'int';
        if ($isRequiredId && end($arguments) <= 0) response(422, false, 'ID inválido.');
    }
    return $arguments;
}

/* ── Ayudas para imprimir en las vistas ── */

/** Escapa un valor para imprimirlo en HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Un model (o lista) como JSON listo para un atributo HTML: data-record="<?= record($cliente) ?>". */
function record(mixed $value): string
{
    return e(json_encode($value, JSON_UNESCAPED_UNICODE));
}

/** Fecha de la base (2026-10-05 o 2026-10-05 13:20:00) → 05/10/2026. Vacía → '—'. */
function formatDate(?string $value): string
{
    $time = $value ? strtotime($value) : false;
    return $time ? date('d/m/Y', $time) : '—';
}

/** 1234.5 → $1,234.50 */
function money(float|int|string|null $amount): string
{
    return '$' . number_format((float) $amount, 2);
}

/** Recorta un texto a $length caracteres y le pone "…" si era más largo. */
function truncate(string $text, int $length): string
{
    return preg_match('/^.{' . $length . '}(?=.)/us', $text, $m) ? $m[0] . '…' : $text;
}
