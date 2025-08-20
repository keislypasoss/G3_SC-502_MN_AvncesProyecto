<?php

session_start();

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/layout.php';
require_once $APP_ROOT . '/include/conexion.php';
require_once $APP_ROOT . '/models/producto_model.php';

$model = new ProductoModel($mysqli);

/* ====== FILTROS (GET) ====== */
$q            = trim($_GET['q'] ?? '');
$id_categoria = (int)($_GET['categoria'] ?? 0);

// Mostrar solo disponibles al público
$filtros = [
    'nombre'       => $q,
    'id_categoria' => $id_categoria ?: null,
    'disponible'   => 1
];

$categorias = $model->categorias();
$productos  = $model->listar($filtros, page: 1, perPage: 96, orderBy: 'p.nombre ASC');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <base href="/G3_SC-502_MN_AvncesProyecto/Avance3/">
    <title>Soluna | Catálogo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body class="bg-light">
    <main class="container-fluid d-flex flex-column min-vh-100" style="margin:0;padding:0;">
        <?php renderHeader(); ?>

        <section class="container py-4">
            <h1 class="h3 mb-3">Nuestro Menú</h1>

            <!-- Filtros -->
            <form class="row g-2 align-items-end mb-4" method="get">
                <div class="col-sm-6 col-md-7">
                    <label class="form-label mb-1">Buscar por nombre</label>
                    <input type="text" name="q" class="form-control" placeholder="Ej. Ensalada, pasta..."
                        value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-sm-6 col-md-3">
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
                <div class="col-12 col-md-2 d-grid">
                    <button class="btn btn-primary">Filtrar</button>
                </div>
            </form>

            <!-- Grid productos -->
            <?php if (!$productos): ?>
                <div class="text-center text-muted py-5">No hay productos que coincidan con el filtro.</div>
            <?php else: ?>
                <div class="row g-3 row-cols-1 row-cols-md-3">
                    <?php foreach ($productos as $p): ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <?php if (!empty($p['imagen'])): ?>
                                    <img src="<?php echo htmlspecialchars($p['imagen']); ?>"
                                        class="card-img-top"
                                        alt="<?php echo htmlspecialchars($p['nombre']); ?>"
                                        style="height:200px;object-fit:cover;">
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($p['nombre']); ?></h5>
                                    <div class="small text-muted mb-2">
                                        <?php echo htmlspecialchars($p['categoria_nombre'] ?? 'Sin categoría'); ?>
                                    </div>
                                    <p class="card-text text-truncate" title="<?php echo htmlspecialchars($p['descripcion'] ?? ''); ?>">
                                        <?php echo htmlspecialchars(mb_strimwidth($p['descripcion'] ?? '', 0, 120, '…', 'UTF-8')); ?>
                                    </p>
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">₡<?php echo number_format((float)$p['precio'], 2); ?></span>
                                        <button
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDetalles"
                                            data-id="<?php echo (int)$p['id_producto']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-descripcion="<?php echo htmlspecialchars($p['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-precio="<?php echo (float)$p['precio']; ?>"
                                            data-imagen="<?php echo htmlspecialchars($p['imagen'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-categoria="<?php echo htmlspecialchars($p['categoria_nombre'] ?? 'Sin categoría', ENT_QUOTES, 'UTF-8'); ?>">Ver detalles</button>
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

    <!-- Modal Detalles -->
    <div class="modal fade" id="modalDetalles" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="det_titulo">Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <img id="det_img" src="" alt="" class="img-fluid rounded"
                                onerror="this.src='img/placeholder.jpg';">
                        </div>
                        <div class="col-md-7">
                            <div class="mb-1 text-muted small" id="det_categoria"></div>
                            <h4 class="mb-2">₡<span id="det_precio">0.00</span></h4>
                            <p id="det_desc" class="mb-3"></p>

                            <div class="row g-2 align-items-center">
                                <div class="col-auto">
                                    <label for="det_cantidad" class="col-form-label">Cantidad</label>
                                </div>
                                <div class="col-auto">
                                    <input type="number" id="det_cantidad" class="form-control" value="1" min="1" style="width:90px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="det_id">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btnAgregarCarrito">Agregar al carrito</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast confirmación -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="toastOk" class="toast align-items-center text-bg-success border-0" role="status" aria-live="polite" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toastMsg">Agregado al carrito.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        </div>
    </div>

    <script>
        /* Llenar modal de detalles */
        const modalDetalles = document.getElementById('modalDetalles');
        modalDetalles.addEventListener('show.bs.modal', event => {
            const btn = event.relatedTarget;

            const id = btn.getAttribute('data-id');
            const nom = btn.getAttribute('data-nombre') || '';
            const desc = btn.getAttribute('data-descripcion') || '';
            const precio = btn.getAttribute('data-precio') || '0';
            const img = btn.getAttribute('data-imagen') || '';
            const cat = btn.getAttribute('data-categoria') || 'Sin categoría';

            modalDetalles.querySelector('#det_id').value = id;
            modalDetalles.querySelector('#det_titulo').textContent = nom;
            modalDetalles.querySelector('#det_desc').textContent = desc;
            modalDetalles.querySelector('#det_precio').textContent = parseFloat(precio).toFixed(2);
            modalDetalles.querySelector('#det_categoria').textContent = cat;
            modalDetalles.querySelector('#det_img').src = img;
            modalDetalles.querySelector('#det_img').alt = nom;
            modalDetalles.querySelector('#det_cantidad').value = 1;
        });

        /* Toast helpers */
        function showToastSuccess(msg) {
            const toastEl = document.getElementById('toastOk');
            document.getElementById('toastMsg').textContent = msg;
            const toast = new bootstrap.Toast(toastEl, {
                delay: 2000
            });
            toast.show();
        }

        function updateCartBadge(count) {
            const badge = document.querySelector('[data-cart-badge]');
            if (!badge) return;
            badge.textContent = count;
            // ocultar si es 0 (opcional)
            if (count <= 0) badge.classList.add('d-none');
            else badge.classList.remove('d-none');
        }

        /* Agregar al carrito vía AJAX */
        document.getElementById('btnAgregarCarrito').addEventListener('click', async () => {
            const id = document.getElementById('det_id').value;
            const qty = document.getElementById('det_cantidad').value || '1';
            const nom = document.getElementById('det_titulo').textContent || 'Producto';

            const fd = new FormData();
            fd.append('accion', 'agregar');
            fd.append('id_producto', id);
            fd.append('cantidad', qty);

            try {
                const resp = await fetch('Modulos/carrito_api.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await resp.json();
                if (!resp.ok || !data.ok) throw new Error(data.msg || 'Error al agregar');

                showToastSuccess(`Se agregó "${nom}" (x${qty}) al carrito.`);
                updateCartBadge(data.count);
            } catch (err) {
                alert(err.message || 'Error al agregar al carrito.');
            }
        });

        /* (Opcional) Al cargar, sincroniza el badge si existe */
        window.addEventListener('DOMContentLoaded', async () => {
            try {
                const fd = new FormData();
                fd.append('accion', 'contar');
                const resp = await fetch('Modulos/carrito_api.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await resp.json();
                if (resp.ok && data.ok) updateCartBadge(data.count);
            } catch {}
        });
    </script>

</body>

</html>