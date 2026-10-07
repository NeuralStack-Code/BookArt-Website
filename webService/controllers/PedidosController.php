<?php
/**
 * Pedidos ya realizados.
 * Del cliente:
 *   /pedidos/index         → pantalla "Mis pedidos"
 *   /pedidos/cancelar      → cancela uno suyo (mientras esté pendiente o visto)
 * Del administrador (su panel):
 *   /pedidos/listar        → todos los pedidos
 *   /pedidos/detalle       → un pedido completo
 *   /pedidos/estatus       → cambia el estatus y deja un mensaje al cliente
 *   /pedidos/precio        → cotiza una libreta personalizada
 *   /pedidos/estadisticas  → los números del panel
 * El pedido se realiza en CarritoController::confirmar(); el diseño de una libreta se cambia en PersonalizadaController.
 * La URL anterior /mis-pedidos redirige a /pedidos (index.php raíz).
 */
class PedidosController extends Controller
{
    protected array $readActions = ['index', 'listar', 'detalle', 'estadisticas'];

    /** Lo que es del cliente; todo lo demás es del administrador. */
    private const CUSTOMER_ACTIONS = ['cancelar'];

    public function authorize(string $action): void
    {
        if ($action === 'index') {
            $this->customerScreen();
            return;
        }
        parent::authorize($action);
        if (in_array($action, self::CUSTOMER_ACTIONS, true)) $this->requireCustomer();
        else $this->requireAdmin();
    }

    /* ── Cliente ── */

    public function index(): void
    {
        $this->api->command('Pedidos', 'Pedidos', 'Mine');
        $this->api->addParameter('IdCuenta', 'I', $this->accountId());
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->view('user/MisPedidos', ['pedidos' => Pedido::fromTable($result->table())]);
        } else {
            $this->failure($result);
        }
    }

    public function cancelar(int $IdPedido): void
    {
        $this->api->command('Pedidos', 'Pedidos', 'Cancel');
        $this->api->addParameter('IdCuenta', 'I', $this->accountId());
        $this->api->addParameter('IdPedido', 'I', $IdPedido);
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->ensure($result->affected() > 0, 'Pedido no encontrado o no se puede cancelar.', 404);
            $this->success('Pedido cancelado correctamente.');
        } else {
            $this->failure($result);
        }
    }

    /* ── Administrador ── */

    public function listar(): void
    {
        $this->api->command('Pedidos', 'Pedidos', 'List');
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->success('OK', ['pedidos' => Pedido::fromTable($result->table())]);
        } else {
            $this->failure($result);
        }
    }

    public function detalle(int $IdPedido): void
    {
        $this->api->command('Pedidos', 'Pedidos', 'Get');
        $this->api->addParameter('IdPedido', 'I', $IdPedido);
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->ensure($result->row() !== null, 'Pedido no encontrado.', 404);
            $this->success('OK', ['pedido' => PedidoDetalle::fromRow($result->row())]);
        } else {
            $this->failure($result);
        }
    }

    public function estatus(int $IdPedido, string $Estatus = '', string $Mensaje = ''): void
    {
        $this->ensure(in_array($Estatus, Pedido::ADMIN_STATUSES, true), 'Estatus no válido.');
        $this->ensure((bool) preg_match('/^.{0,250}$/us', $Mensaje), 'El mensaje admite máximo 250 caracteres.');

        $this->api->command('Pedidos', 'Pedidos', 'SetStatus');
        $this->api->addParameter('IdPedido', 'I', $IdPedido);
        $this->api->addParameter('Estatus',  'S', $Estatus);
        $this->api->addParameter('Mensaje',  'S', $Mensaje);
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->success("Estatus actualizado a: $Estatus.");
        } else {
            $this->failure($result);
        }
    }

    /** $Precio llega como texto para revisar que sí sea un número. */
    public function precio(int $IdPedido, string $Precio = ''): void
    {
        $this->ensure(is_numeric($Precio) && $Precio >= 0 && $Precio < 100000000, 'Precio no válido.');

        $this->api->command('Pedidos', 'Pedidos', 'SetPrice');
        $this->api->addParameter('IdPedido', 'I', $IdPedido);
        $this->api->addParameter('Precio',   'N', $Precio);
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->success('Precio asignado: ' . money($Precio));
        } else {
            $this->failure($result);
        }
    }

    public function estadisticas(): void
    {
        $this->api->command('Pedidos', 'Pedidos', 'Statistics');
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->success('OK', ['estadisticas' => Estadisticas::fromRow($result->row() ?? [])]);
        } else {
            $this->failure($result);
        }
    }
}
