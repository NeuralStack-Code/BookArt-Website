<?php
/**
 * Ejecuta un stored procedure con parámetros POR NOMBRE y deja el resultado en un estado uniforme.
 * Es la conexión a la base que usan las entidades (ver core/Entity.php):
 *
 *     $this->db->command('sp_cliente_eliminar');
 *     $this->db->addParameter('IdCliente', $this->IdCliente);
 *     $rows = $this->db->execute();
 *     if ($this->db->error()) … $this->db->code() / $this->db->message()
 *
 * MySQL/MariaDB solo aceptan parámetros por posición en un CALL. Para que el nombre valga de verdad,
 * Command lee la firma del procedure (sp_sistema_parametros) y acomoda los valores en su orden; si falta
 * o sobra un parámetro truena de inmediato, en vez de guardar un valor en la columna equivocada.
 * El nombre es el del parámetro sin la "p" inicial: pIdCliente → 'IdCliente'.
 */
final class Command
{
    /** Códigos de negocio (SIGNAL 'codigo:mensaje') → estatus HTTP. Lo que no esté aquí es 500. */
    private const HTTP = ['dup' => 409, 'fk' => 409, 'en_uso' => 409, 'notfound' => 404, 'invalido' => 422, 'rol' => 422];

    /** Firma de cada procedure ya consultado en esta petición: nombre → ['IdCliente', 'Nombre', …]. */
    private static array $signatures = [];

    private mysqli $db;
    private string $procedure  = '';
    private array  $parameters = [];
    private array  $rows       = [];
    private bool   $error      = false;
    private string $code       = '';
    private string $message    = '';

    public function __construct(mysqli $db) { $this->db = $db; }

    /** Inicia un comando nuevo (limpia parámetros y estado del anterior). */
    public function command(string $procedure): static
    {
        $this->procedure  = $procedure;
        $this->parameters = $this->rows = [];
        $this->error      = false;
        $this->code       = $this->message = '';
        return $this;
    }

    public function addParameter(string $name, mixed $value): static
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /** Nombres de los parámetros del procedure, en su orden. */
    public function signature(): array
    {
        if (!isset(self::$signatures[$this->procedure])) {
            self::$signatures[$this->procedure] = array_map(
                fn(array $row) => preg_replace('/^p(?=[A-Z])/', '', $row['Nombre']),
                sp($this->db, 'sp_sistema_parametros', [$this->procedure]));
        }
        return self::$signatures[$this->procedure];
    }

    /** Ejecuta y regresa las filas. Un error NO lanza excepción: queda en error()/code()/message(). */
    public function execute(): array
    {
        $signature = $this->signature();
        // Un nombre mal escrito o un parámetro olvidado es un error de programación: que se note al instante.
        if ($extra = array_diff(array_keys($this->parameters), $signature)) {
            throw new LogicException("{$this->procedure} no tiene el parámetro: " . implode(', ', $extra));
        }
        if ($missing = array_diff($signature, array_keys($this->parameters))) {
            throw new LogicException("A {$this->procedure} le falta el parámetro: " . implode(', ', $missing));
        }

        try {
            $this->rows = sp($this->db, $this->procedure, array_map(fn(string $name) => $this->parameters[$name], $signature));
        } catch (mysqli_sql_exception $e) {
            if (spCodigo($e) !== null) {
                $this->fail(spCodigo($e), spMensaje($e));                    // regla de negocio que avisó el procedure
            } else {
                error_log("{$this->procedure}: " . $e->getMessage());        // el detalle va al log, nunca al usuario
                $this->fail('interno', 'Error interno del servidor.');
            }
        }
        return $this->rows;
    }

    /** Marca el comando como fallido. */
    public function fail(string $code, string $message): void
    {
        $this->error   = true;
        $this->code    = $code;
        $this->message = $message;
        $this->rows    = [];
    }

    public function error(): bool     { return $this->error; }
    public function code(): string    { return $this->code; }
    public function message(): string { return $this->message; }
    public function http(): int       { return $this->error ? (self::HTTP[$this->code] ?? 500) : 200; }
}
