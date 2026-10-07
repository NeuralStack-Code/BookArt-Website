<?php
/**
 * Catálogo de productos.
 *   /catalogo/index     → pantalla del catálogo (pide sesión)
 *   /catalogo/detalle   → pantalla de un producto (?IdProducto=)
 *   /catalogo/listar    → los productos (panel del administrador)
 *   /catalogo/obtener   → un producto (panel del administrador)
 *   /catalogo/guardar   → alta o cambio, con su imagen (solo administrador)
 *   /catalogo/eliminar  → baja (solo administrador)
 * La URL anterior /extension-catalogo?id= redirige a /catalogo/detalle (index.php raíz).
 */
class CatalogoController extends Controller
{
    /** Ver un producto y leer el catálogo no piden sesión. */
    protected array $publicActions = ['detalle', 'listar', 'obtener'];
    protected array $readActions   = ['index', 'detalle', 'listar', 'obtener'];

    public function authorize(string $action): void
    {
        // La pantalla del catálogo pide sesión: sin ella se va a entrar (no a un 401 en JSON).
        if ($action === 'index' && $this->user() === null) $this->redirect('/auth/entrar');
        parent::authorize($action);
    }

    public function index(): void
    {
        if ($this->isAdmin()) $this->redirect('/administrador');     // el administrador trabaja en su panel

        $this->api->command('Catalogo', 'Catalogo', 'List');
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->view('user/Catalogo', ['productos' => Producto::fromTable($result->table())]);
        } else {
            $this->failure($result);
        }
    }

    public function detalle(int $IdProducto = 0): void
    {
        if ($this->isAdmin()) $this->redirect('/administrador');

        $this->api->command('Catalogo', 'Catalogo', 'Get');
        $this->api->addParameter('IdProducto', 'I', $IdProducto);
        $result = $this->api->execute();
        if ($result->status()) $this->failure($result);

        if ($result->row() === null) {
            http_response_code(404);
            $this->view('404');
            return;
        }
        $this->view('user/Extension_Catalogo', ['producto' => Producto::fromRow($result->row())]);
    }

    public function listar(): void
    {
        $this->api->command('Catalogo', 'Catalogo', 'List');
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->success('OK', ['productos' => Producto::fromTable($result->table())]);
        } else {
            $this->failure($result);
        }
    }

    public function obtener(int $IdProducto): void
    {
        $this->api->command('Catalogo', 'Catalogo', 'Get');
        $this->api->addParameter('IdProducto', 'I', $IdProducto);
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->ensure($result->row() !== null, 'Producto no encontrado.', 404);
            $this->success('OK', ['producto' => Producto::fromRow($result->row())]);
        } else {
            $this->failure($result);
        }
    }

    /** Sin IdProducto es un alta; con él, un cambio. $Precio llega aparte para revisar que sí sea un número. */
    public function guardar(Producto $producto, string $Precio = ''): void
    {
        $this->requireAdmin();
        $isNew = !$producto->IdProducto;
        $file  = $_FILES['Imagen'] ?? null;

        $this->ensure($producto->Nombre !== '' && $producto->Descripcion !== '' && $Precio !== '',
                      'Nombre, descripción y precio son obligatorios.');
        $this->ensure(is_numeric($Precio) && $producto->Precio >= 0, 'El precio no es válido.');
        // Los tamaños son los de las columnas: lo que no cabe se rechaza aquí, no se recorta en la base.
        $this->ensure((bool) preg_match('/^.{1,35}$/us', $producto->Nombre), 'El nombre admite máximo 35 caracteres.');
        $this->ensure((bool) preg_match('/^.{1,500}$/us', $producto->Descripcion), 'La descripción admite máximo 500 caracteres.');
        if (ImagenCatalogo::sent($file)) {
            $problem = ImagenCatalogo::problem($file);
            $this->ensure($problem === null, (string) $problem);
        }

        // En un cambio se necesita la imagen que tiene hoy, para borrarla si llega una nueva.
        $previousImage = null;
        if (!$isNew) {
            $this->api->command('Catalogo', 'Catalogo', 'Get');
            $this->api->addParameter('IdProducto', 'I', $producto->IdProducto);
            $result = $this->api->execute();
            if ($result->status()) $this->failure($result);
            $this->ensure($result->row() !== null, 'Producto no encontrado.', 404);
            $previousImage = Producto::fromRow($result->row())->Imagen;
        }

        $newImage = null;
        if (ImagenCatalogo::sent($file)) {
            $newImage = ImagenCatalogo::store($file);
            $this->ensure($newImage !== null, 'Error al guardar la imagen.', 500);
        }

        if ($isNew) {
            $this->api->command('Catalogo', 'Catalogo', 'Insert');
            $this->api->addParameter('Nombre',      'S', $producto->Nombre);
            $this->api->addParameter('Descripcion', 'S', $producto->Descripcion);
            $this->api->addParameter('Precio',      'N', $producto->Precio);
            $this->api->addParameter('Imagen',      'S', $newImage ?? ImagenCatalogo::DEFAULT);
        } else {
            $this->api->command('Catalogo', 'Catalogo', 'Update');
            $this->api->addParameter('IdProducto',  'I', $producto->IdProducto);
            $this->api->addParameter('Nombre',      'S', $producto->Nombre);
            $this->api->addParameter('Descripcion', 'S', $producto->Descripcion);
            $this->api->addParameter('Precio',      'N', $producto->Precio);
            $this->api->addParameter('Imagen',      'S', $newImage);          // vacío = conserva la que tiene
        }
        $result = $this->api->execute();

        if (!$result->status()) {
            if ($newImage !== null && $previousImage !== null) ImagenCatalogo::delete($previousImage);
            if ($isNew) $this->success('Producto agregado exitosamente.', ['IdProducto' => $result->id()], 201);
            $this->success('Producto actualizado exitosamente.');
        } else {
            if ($newImage !== null) ImagenCatalogo::delete($newImage);        // no se guardó: la imagen recién subida sobra
            $this->failure($result);
        }
    }

    public function eliminar(int $IdProducto): void
    {
        $this->requireAdmin();

        $this->api->command('Catalogo', 'Catalogo', 'Get');
        $this->api->addParameter('IdProducto', 'I', $IdProducto);
        $result = $this->api->execute();
        if ($result->status()) $this->failure($result);
        $this->ensure($result->row() !== null, 'Producto no encontrado.', 404);
        $producto = Producto::fromRow($result->row());

        $this->api->command('Catalogo', 'Catalogo', 'Delete');
        $this->api->addParameter('IdProducto', 'I', $IdProducto);
        $result = $this->api->execute();

        if (!$result->status()) {
            $this->ensure($result->affected() > 0, 'Producto no encontrado.', 404);
            ImagenCatalogo::delete($producto->Imagen);
            $this->success('Producto eliminado exitosamente.');
        } else {
            $this->failure($result);
        }
    }
}
