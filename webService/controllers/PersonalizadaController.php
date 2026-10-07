<?php
/**
 * Libretas personalizadas: el cliente diseña la suya y la manda al carrito; ya pedida, puede cambiarla
 * mientras siga pendiente o vista.
 *   /personalizada/index    → pantalla para diseñar una libreta
 *   /personalizada/editar   → pantalla para cambiar el diseño de un pedido (?IdPedido=)
 *   /personalizada/guardar  → guarda el diseño: nuevo (va al carrito) o el de un pedido (con IdPedido)
 * La URL anterior /editar-pedido?id= redirige a /personalizada/editar (index.php raíz).
 */
class PersonalizadaController extends Controller
{
    protected array $readActions = ['index', 'editar'];

    public function authorize(string $action): void
    {
        if (in_array($action, ['index', 'editar'], true)) {
            $this->customerScreen();
            return;
        }
        parent::authorize($action);
        $this->requireCustomer();
    }

    public function index(): void
    {
        $this->view('user/Personalizada');
    }

    public function editar(int $IdPedido = 0): void
    {
        $this->api->command('Personalizada', 'Personalizada', 'Get');
        $this->api->addParameter('IdCuenta', 'I', $this->accountId());
        $this->api->addParameter('IdPedido', 'I', $IdPedido);
        $result = $this->api->execute();
        if ($result->status()) $this->failure($result);

        // No es suyo, no es una libreta personalizada o ya no se puede cambiar: de vuelta a sus pedidos.
        $libreta = $result->row() !== null ? Personalizada::fromRow($result->row()) : null;
        if ($libreta === null || !$libreta->isOpen()) $this->redirect('/pedidos');

        $this->view('user/EditarPedido', ['libreta' => $libreta]);
    }

    public function guardar(Personalizada $libreta): void
    {
        $isNew = !$libreta->IdPedido;
        $file  = $_FILES['Portada'] ?? null;

        $this->ensure($libreta->TipoEncuadernacion !== '' && $libreta->Tamano !== '' && $libreta->TipoPapel !== ''
                      && $libreta->Color !== '' && $libreta->Descripcion !== '', 'Todos los campos de la libreta son obligatorios.');
        $this->ensure(isset(Personalizada::BINDINGS[$libreta->TipoEncuadernacion]), 'Selecciona un tipo de encuadernación.');
        $this->ensure(isset(Personalizada::SIZES[$libreta->Tamano]), 'Selecciona un tamaño.');
        $this->ensure(isset(Personalizada::PAPERS[$libreta->TipoPapel]), 'Selecciona un tipo de papel.');
        $this->ensure((bool) preg_match('/^#[0-9a-fA-F]{6}$/', $libreta->Color), 'El color no es válido.');
        // El tamaño es el de la columna: lo que no cabe se rechaza aquí, no se recorta en la base.
        $this->ensure((bool) preg_match('/^.{1,150}$/us', $libreta->Descripcion), 'La descripción admite máximo 150 caracteres.');
        if (ImagenPortada::sent($file)) {
            $problem = ImagenPortada::problem($file);
            $this->ensure($problem === null, (string) $problem);
        }

        // En un cambio se necesita la portada que tiene hoy, para borrarla si llega una nueva.
        $previousCover = null;
        if (!$isNew) {
            $this->api->command('Personalizada', 'Personalizada', 'Get');
            $this->api->addParameter('IdCuenta', 'I', $this->accountId());
            $this->api->addParameter('IdPedido', 'I', $libreta->IdPedido);
            $result = $this->api->execute();
            if ($result->status()) $this->failure($result);
            $this->ensure($result->row() !== null, 'Pedido no encontrado.', 404);
            $current = Personalizada::fromRow($result->row());
            $this->ensure($current->isOpen(), 'Este pedido ya no puede editarse.', 409);
            $previousCover = $current->Portada;
        }

        $newCover = null;
        if (ImagenPortada::sent($file)) {
            $newCover = ImagenPortada::store($file);
            $this->ensure($newCover !== null, 'Error al guardar la portada.', 500);
        }

        if ($isNew) {
            $this->api->command('Personalizada', 'Personalizada', 'Insert');
            $this->api->addParameter('IdCuenta',           'I', $this->accountId());
        } else {
            $this->api->command('Personalizada', 'Personalizada', 'Update');
            $this->api->addParameter('IdCuenta',           'I', $this->accountId());
            $this->api->addParameter('IdPedido',           'I', $libreta->IdPedido);
        }
        $this->api->addParameter('TipoEncuadernacion', 'S', $libreta->TipoEncuadernacion);
        $this->api->addParameter('Tamano',             'S', $libreta->Tamano);
        $this->api->addParameter('TipoPapel',          'S', $libreta->TipoPapel);
        $this->api->addParameter('Color',              'S', $libreta->Color);
        $this->api->addParameter('Descripcion',        'S', $libreta->Descripcion);
        $this->api->addParameter('Portada',            'S', $newCover);            // en un cambio, vacío = conserva la que tiene
        $result = $this->api->execute();

        if (!$result->status()) {
            if ($newCover !== null) ImagenPortada::delete($previousCover);
            if ($isNew) $this->success('¡Diseño creado y agregado al carrito!', ['redirect' => '/carrito'], 201);
            $this->success('Pedido actualizado correctamente.', ['redirect' => '/pedidos']);
        } else {
            ImagenPortada::delete($newCover);                                       // no se guardó: la portada recién subida sobra
            $this->failure($result);
        }
    }
}
