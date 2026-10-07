<?php
/**
 * Carrito de compras del cliente (los administradores no compran).
 *   /carrito/index      → pantalla del carrito
 *   /carrito/contar     → cuántos artículos tiene (la insignia del menú)
 *   /carrito/agregar    → agrega un producto del catálogo
 *   /carrito/quitar     → quita un artículo
 *   /carrito/confirmar  → realiza el pedido
 * Las libretas personalizadas entran al carrito desde PersonalizadaController::guardar().
 */
class CarritoController extends Controller
{
    protected array $readActions = ['index', 'contar'];

    public function authorize(string $action): void
    {
        if ($action === 'index') {
            $this->customerScreen();
            return;
        }
        parent::authorize($action);
        $this->requireCustomer();
    }

    public function index(): void
    {
        $this->api->command('Carrito', 'Carrito', 'List');
        $this->api->addParameter('IdCuenta', 'I', $this->accountId());
        $result = $this->api->execute();
        if ($result->status()) $this->failure($result);

        $items = CarritoItem::fromTable($result->table());
        $catalogTotal = 0.0;
        $hasCustom    = false;
        foreach ($items as $item) {
            if ($item->isCatalog()) $catalogTotal += $item->Precio;
            else $hasCustom = true;                 // las personalizadas se cotizan después
        }

        $this->view('user/Carrito', ['items' => $items, 'catalogTotal' => $catalogTotal, 'hasCustom' => $hasCustom]);
    }

    public function contar(): void
    {
        $this->api->command('Carrito', 'Carrito', 'Count');
        $this->api->addParameter('IdCuenta', 'I', $this->accountId());
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->success('OK', ['count' => (int) ($result->row()['Total'] ?? 0)]);
        } else {
            $this->failure($result);
        }
    }

    public function agregar(int $IdProducto): void
    {
        $this->api->command('Carrito', 'Carrito', 'Add');
        $this->api->addParameter('IdCuenta',   'I', $this->accountId());
        $this->api->addParameter('IdProducto', 'I', $IdProducto);
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->success('Producto agregado al carrito.', [], 201);
        } else {
            $this->failure($result);
        }
    }

    public function quitar(int $IdPedido): void
    {
        $this->api->command('Carrito', 'Carrito', 'Remove');
        $this->api->addParameter('IdCuenta', 'I', $this->accountId());
        $this->api->addParameter('IdPedido', 'I', $IdPedido);
        $result = $this->api->execute();

        if (!$result->status()) {
            ImagenPortada::delete($result->row()['Portada'] ?? null);   // si era una libreta personalizada con portada
            $this->success('Producto eliminado del carrito.');
        } else {
            $this->failure($result);
        }
    }

    public function confirmar(): void
    {
        $this->api->command('Carrito', 'Carrito', 'Confirm');
        $this->api->addParameter('IdCuenta', 'I', $this->accountId());
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->ensure($result->affected() > 0, 'Tu carrito está vacío.');
            // "Mis pedidos" lo muestra al llegar (webService/views/partials/notificador.php).
            $_SESSION['flash'] = ['type' => 'success', 'message' => '¡Pedido realizado! Nos pondremos en contacto contigo pronto.'];
            $this->success('¡Pedido realizado exitosamente!', ['redirect' => '/pedidos']);
        } else {
            $this->failure($result);
        }
    }
}
