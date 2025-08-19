<?php

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/layout.php';
require_once $APP_ROOT . '/include/conexion.php';      
require_once $APP_ROOT . '/models/producto_model.php';  

$model = new ProductoModel($mysqli);

/* ====== ACCIONES (POST) ====== */
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'crear') {
            $model->crear([
                'nombre'       => $_POST['nombre'] ?? '',
                'descripcion'  => $_POST['descripcion'] ?? '',
                'precio'       => $_POST['precio'] ?? 0,
                'disponible'   => isset($_POST['disponible']) ? 1 : 0,
                'imagen'       => $_POST['imagen'] ?? '',
                'id_categoria' => (int)($_POST['id_categoria'] ?? 0),
            ]);
            $flash = ['type' => 'success', 'msg' => 'Producto creado correctamente.'];
        } elseif ($accion === 'actualizar') {
            $id = (int)($_POST['id_producto'] ?? 0);
            $ok = $model->actualizar($id, [
                'nombre'       => $_POST['nombre'] ?? '',
                'descripcion'  => $_POST['descripcion'] ?? '',
                'precio'       => $_POST['precio'] ?? 0,
                'disponible'   => isset($_POST['disponible']) ? 1 : 0,
                'imagen'       => $_POST['imagen'] ?? '',
                'id_categoria' => (int)($_POST['id_categoria'] ?? 0),
            ]);
            $flash = $ok
                ? ['type' => 'success', 'msg' => 'Producto actualizado.']
                : ['type' => 'danger', 'msg' => 'No se pudo actualizar.'];
        } elseif ($accion === 'eliminar') {
            $id = (int)($_POST['id_producto'] ?? 0);
            $ok = $model->eliminar($id); 
            $flash = $ok
                ? ['type' => 'warning', 'msg' => 'Producto desactivado.']
                : ['type' => 'danger', 'msg' => 'No se pudo desactivar.'];
        }
    } catch (Throwable $e) {
        $flash = ['type' => 'danger', 'msg' => ' Error: ' . $e->getMessage()];
    }
}

/* ====== FILTROS (GET) ====== */
$q            = trim($_GET['q'] ?? '');
$id_categoria = (int)($_GET['categoria'] ?? 0);
$mostrar_inactivos = isset($_GET['inactivos']) ? 1 : 0;

$filtros = [
    'nombre'       => $q,
    'id_categoria' => $id_categoria ?: null,
    'disponible'   => $mostrar_inactivos ? null : 1,
];

$categorias = $model->categorias();
$productos  = $model->listar($filtros, page: 1, perPage: 100, orderBy: 'p.nombre ASC');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <base href="/G3_SC-502_MN_AvncesProyecto/Avance3/">
    <title>Soluna | Admin | Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body class="bg-light">
    <main class="container-fluid d-flex flex-column min-vh-100" style="margin:0;padding:0;">
        <?php renderHeader(); ?>

        <section class="container py-4">

            <div class="d-flex flex-wrap align-items-end gap-2 mb-3">
                <form class="row g-2 flex-grow-1" method="get">
                    <div class="col-sm-5 col-md-6">
                        <label class="form-label mb-1">Buscar por nombre</label>
                        <input type="text" name="q" class="form-control" placeholder="Ej. Ensalada"
                            value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-sm-4 col-md-4">
                        <label class="form-label mb-1">Categoría</label>
                        <select name="categoria" class="form-select">
                            <option value="0">Todas</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?php echo (int)$c['id_categoria']; ?>"
                                    <?php echo $id_categoria == (int)$c['id_categoria'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-3 col-md-2">
                        <label class="form-label mb-1">&nbsp;</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="inactivos" name="inactivos"
                                <?php echo $mostrar_inactivos ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="inactivos">Ver inactivos</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-12 d-flex gap-2">
                        <button class="btn btn-primary">Filtrar</button>
                        <a href="Modulos/menu.php" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                </form>

                <button class="btn btn-success ms-auto" data-bs-toggle="modal" data-bs-target="#modalProducto"
                    data-mode="crear">
                    + Agregar producto
                </button>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($flash['msg']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!$productos): ?>
                <div class="text-center text-muted py-5">No hay productos que coincidan con el filtro.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($productos as $p): ?>
                        <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                            <div class="card h-100 shadow-sm">
                                <?php if (!empty($p['imagen'])): ?>
                                    <img src="<?php echo htmlspecialchars($p['imagen']); ?>" class="card-img-top"
                                        alt="<?php echo htmlspecialchars($p['nombre']); ?>"
                                        style="height:180px;object-fit:cover;">
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title mb-1">
                                        <?php echo htmlspecialchars($p['nombre']); ?>
                                    </h5>
                                    <div class="small text-muted mb-2">
                                        <?php echo htmlspecialchars($p['categoria_nombre'] ?? 'Sin categoría'); ?>
                                        <?php if (!(int)$p['disponible']): ?>
                                            <span class="badge text-bg-secondary ms-1">Inactivo</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="card-text flex-grow-1" style="min-height:3.5em;">
                                        <?php echo htmlspecialchars(mb_strimwidth($p['descripcion'] ?? '', 0, 120, '…', 'UTF-8')); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">₡<?php echo number_format((float)$p['precio'], 2); ?></span>
                                        <div class="btn-group">
                                            <button
                                                class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#modalProducto"
                                                data-mode="editar"
                                                data-id="<?php echo (int)$p['id_producto']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-descripcion="<?php echo htmlspecialchars($p['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-precio="<?php echo (float)$p['precio']; ?>"
                                                data-imagen="<?php echo htmlspecialchars($p['imagen'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-idcategoria="<?php echo (int)($p['id_categoria'] ?? 0); ?>"
                                                data-disponible="<?php echo (int)$p['disponible']; ?>">Editar</button>

                                            <button
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#modalEliminar"
                                                data-id="<?php echo (int)$p['id_producto']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?>">Eliminar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </section>

        <?php renderFooter(); ?>
    </main>

    <!-- ===== Modal Crear/Editar ===== -->
    <div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalProductoTitle">Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" id="accion" value="crear">
                    <input type="hidden" name="id_producto" id="id_producto" value="">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre" id="nombre" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Precio</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="precio" id="precio" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="id_categoria" id="id_categoria" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?php echo (int)$c['id_categoria']; ?>">
                                        <?php echo htmlspecialchars($c['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="descripcion" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">URL de imagen</label>
                            <input type="url" class="form-control" name="imagen" id="imagen" placeholder="https://...">
                            <div class="form-text">Puedes pegar una URL absoluta o una ruta relativa desde tu servidor.</div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="disponible" id="disponible" checked>
                                <label class="form-check-label" for="disponible">Disponible</label>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== Modal Eliminar ===== -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Desactivar producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_producto" id="del_id_producto">
                    <p class="mb-0">¿Seguro que deseas desactivar <strong id="del_nombre"></strong>?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Desactivar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modalProducto = document.getElementById('modalProducto');
        modalProducto.addEventListener('show.bs.modal', event => {
            const btn = event.relatedTarget;
            const mode = btn?.getAttribute('data-mode') || 'crear';

            const title = modalProducto.querySelector('#modalProductoTitle');
            const accion = modalProducto.querySelector('#accion');
            const idProd = modalProducto.querySelector('#id_producto');
            const nombre = modalProducto.querySelector('#nombre');
            const descripcion = modalProducto.querySelector('#descripcion');
            const precio = modalProducto.querySelector('#precio');
            const imagen = modalProducto.querySelector('#imagen');
            const idCategoria = modalProducto.querySelector('#id_categoria');
            const disponible = modalProducto.querySelector('#disponible');

            if (mode === 'editar') {
                title.textContent = 'Editar producto';
                accion.value = 'actualizar';
                idProd.value = btn.getAttribute('data-id') || '';
                nombre.value = btn.getAttribute('data-nombre') || '';
                descripcion.value = btn.getAttribute('data-descripcion') || '';
                precio.value = btn.getAttribute('data-precio') || '';
                imagen.value = btn.getAttribute('data-imagen') || '';
                idCategoria.value = btn.getAttribute('data-idcategoria') || '';
                disponible.checked = (btn.getAttribute('data-disponible') === '1');
            } else {
                title.textContent = 'Agregar producto';
                accion.value = 'crear';
                idProd.value = '';
                nombre.value = '';
                descripcion.value = '';
                precio.value = '';
                imagen.value = '';
                idCategoria.value = '';
                disponible.checked = true;
            }
        });

        const modalEliminar = document.getElementById('modalEliminar');
        modalEliminar.addEventListener('show.bs.modal', event => {
            const btn = event.relatedTarget;
            const id = btn.getAttribute('data-id');
            const nombre = btn.getAttribute('data-nombre');
            modalEliminar.querySelector('#del_id_producto').value = id;
            modalEliminar.querySelector('#del_nombre').textContent = nombre;
        });
    </script>
</body>

</html>