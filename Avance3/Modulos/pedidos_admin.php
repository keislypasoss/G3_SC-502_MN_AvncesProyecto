<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/layout.php';
require_once $APP_ROOT . '/include/conexion.php';
require_once $APP_ROOT . '/models/factura_model.php';

function esAdmin(): bool
{
    $rol = $_SESSION['usuario']['rol'] ?? ($_SESSION['rol'] ?? '');
    $rol = strtolower((string)$rol);
    return in_array($rol, ['admin', 'administrador'], true);
}
if (!esAdmin()) {
    http_response_code(403);
    echo "Acceso denegado.";
    exit;
}

$model = new FacturaModel($mysqli);

/* Filtros */
$id_factura = (int)($_GET['id'] ?? 0);
$desde      = trim($_GET['desde'] ?? '');
$hasta      = trim($_GET['hasta'] ?? '');

$filtros = [
    'id_factura' => $id_factura ?: null,
    'desde'      => $desde ?: null,
    'hasta'      => $hasta ?: null,
];

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$totalRows = $model->contarTodas($filtros);
$rows      = $model->listarTodas($filtros, $perPage, $offset, 'DESC');

$totalPages = max(1, (int)ceil($totalRows / $perPage));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <base href="/G3_SC-502_MN_AvncesProyecto/Avance3/">
    <title>Soluna | Admin | Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body class="bg-light">
    <main class="container-fluid d-flex flex-column min-vh-100" style="margin:0;padding:0;">
        <?php renderHeader(); ?>

        <section class="container py-4">
            <h1 class="h3 mb-3">Pedidos</h1>

            <form class="row g-2 align-items-end mb-3" method="get">
                <div class="col-sm-3 col-md-2">
                    <label class="form-label mb-1">ID Factura</label>
                    <input type="number" name="id" class="form-control" value="<?php echo $id_factura ?: ''; ?>">
                </div>
                <div class="col-sm-4 col-md-3">
                    <label class="form-label mb-1">Desde</label>
                    <input type="date" name="desde" class="form-control" value="<?php echo htmlspecialchars($desde); ?>">
                </div>
                <div class="col-sm-4 col-md-3">
                    <label class="form-label mb-1">Hasta</label>
                    <input type="date" name="hasta" class="form-control" value="<?php echo htmlspecialchars($hasta); ?>">
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button class="btn btn-primary">Filtrar</button>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <a href="Modulos/admin_facturas.php" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>

            <div class="mb-2 small text-muted">
                <?php echo $totalRows; ?> resultado(s)
                <?php if ($totalPages > 1): ?>
                    • página <?php echo $page; ?> de <?php echo $totalPages; ?>
                <?php endif; ?>
            </div>

            <?php if (!$rows): ?>
                <div class="text-center text-muted py-5">No hay facturas para los filtros seleccionados.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:100px;">Factura</th>
                                <th style="width:120px;">Fecha</th>
                                <th>Cliente</th>
                                <th>Correo</th>
                                <th style="width:120px;">Pago</th>
                                <th style="width:120px;" class="text-end">Total</th>
                                <th style="width:140px;">Pedido</th>
                                <th style="width:120px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td>#<?php echo (int)$r['id_factura']; ?></td>
                                    <td><?php echo htmlspecialchars($r['fecha']); ?></td>
                                    <td><?php echo htmlspecialchars($r['cliente_nombre'] ?? '(sin nombre)'); ?></td>
                                    <td><?php echo htmlspecialchars($r['correo_cliente'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($r['metodo_pago'] ?? ''); ?></td>
                                    <td class="text-end">₡<?php echo number_format((float)$r['total'], 2); ?></td>
                                    <td>#<?php echo (int)$r['id_pedido']; ?></td>
                                    <td>
                                        <button
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#modalFactura"
                                            data-id="<?php echo (int)$r['id_factura']; ?>">
                                            Ver detalle
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination">
                            <?php
                            $qsBase = http_build_query(array_filter([
                                'id' => $id_factura ?: null,
                                'desde' => $desde ?: null,
                                'hasta' => $hasta ?: null
                            ]));
                            $mk = function ($p) use ($qsBase) {
                                return 'Modulos/admin_facturas.php?' . $qsBase . ($qsBase ? '&' : '') . 'page=' . $p;
                            };
                            ?>
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $mk($page - 1); ?>">Anterior</a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $mk($p); ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $mk($page + 1); ?>">Siguiente</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <?php renderFooter(); ?>
    </main>

    <!-- Modal Factura-->
    <div class="modal fade" id="modalFactura" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Factura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="facturaBody">
                    <div class="text-center text-muted py-5">Cargando factura…</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const modalFactura = document.getElementById('modalFactura');
        modalFactura.addEventListener('show.bs.modal', async (ev) => {
            const id = ev.relatedTarget?.getAttribute('data-id');
            const body = document.getElementById('facturaBody');
            if (!id) {
                body.innerHTML = '<div class="text-danger">ID inválido</div>';
                return;
            }
            body.innerHTML = '<div class="text-center text-muted py-5">Cargando factura…</div>';

            try {
                const fd = new FormData();
                fd.append('id_factura', id);
                const resp = await fetch('Modulos/factura_api.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await resp.json();
                if (!resp.ok || !data.ok) throw new Error(data.msg || 'Error al cargar');

                const f = data.factura;
                const det = data.detalles || [];

                let rows = '';
                let total = 0;
                det.forEach(d => {
                    const cant = parseInt(d.cantidad, 10);
                    const precio = parseFloat(d.precio_unitario);
                    const sub = cant * precio;
                    total += sub;
                    rows += `
        <tr>
          <td>${esc(d.nombre_producto||'')}</td>
          <td class="text-end">₡${precio.toFixed(2)}</td>
          <td class="text-center">${cant}</td>
          <td class="text-end">₡${sub.toFixed(2)}</td>
        </tr>`;
                });

                body.innerHTML = `
      <div class="mb-3 d-flex justify-content-between">
        <div>
          <div class="fw-bold">Factura #${f.id_factura}</div>
          <div class="small text-muted">Pedido #${f.id_pedido}</div>
        </div>
        <div class="text-end small text-muted">
          Fecha: ${esc(f.fecha||'')}<br>
          Pago: ${esc(f.metodo_pago||'')}
        </div>
      </div>
      <div class="mb-2">
        <div class="fw-bold">Cliente</div>
        <div>${esc(f.cliente_nombre||'')}</div>
        <div class="small text-muted">
          ${esc(f.telefono||'')} ${f.direccion ? ' • '+esc(f.direccion) : ''}
        </div>
      </div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead class="table-light">
            <tr><th>Producto</th><th class="text-end">Precio</th><th class="text-center">Cant.</th><th class="text-end">Subtotal</th></tr>
          </thead>
          <tbody>${rows || '<tr><td colspan="4" class="text-center text-muted">Sin líneas</td></tr>'}</tbody>
          <tfoot>
            <tr><th colspan="3" class="text-end">Total</th><th class="text-end">₡${total.toFixed(2)}</th></tr>
          </tfoot>
        </table>
      </div>
    `;
            } catch (e) {
                body.innerHTML = `<div class="text-danger">No se pudo cargar la factura: ${esc(e.message||'')}</div>`;
            }
        });

        function esc(s) {
            return String(s).replace(/[&<>"']/g, m => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [m]));
        }
    </script>
</body>

</html>