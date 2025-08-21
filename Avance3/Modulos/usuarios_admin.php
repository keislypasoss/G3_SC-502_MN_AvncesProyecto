<?php

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/layout.php';
require_once $APP_ROOT . '/include/conexion.php';
require_once $APP_ROOT . '/models/usuario_model.php';

$usuarioM = new UsuarioModel($mysqli);

/* ===== Seguridad: solo ADMIN ===== */
function esAdmin(): bool
{
    $rol = null;
    if (isset($_SESSION['usuario']['rol'])) $rol = $_SESSION['usuario']['rol'];
    elseif (isset($_SESSION['rol'])) $rol = $_SESSION['rol'];
    $rol = $rol ? strtolower($rol) : '';
    return in_array($rol, ['admin', 'administrador'], true);
}
if (!esAdmin()) {
    http_response_code(403);
    echo "<!DOCTYPE html><html><body><p>Acceso denegado. Requiere rol administrador.</p></body></html>";
    exit;
}

/* ===== Acciones (activar/desactivar) ===== */
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id     = (int)($_POST['id_usuario'] ?? 0);

    try {
        if ($accion === 'toggle' && $id > 0) {
            $activo = (int)($_POST['activo'] ?? 0);
            $ok = $usuarioM->update($id, ['activo' => $activo]);
            $flash = $ok
                ? ['type' => 'success', 'msg' => $activo ? 'Usuario reactivado.' : 'Usuario desactivado.']
                : ['type' => 'danger', 'msg' => 'No se pudo actualizar el estado.'];
        }
    } catch (Throwable $e) {
        $flash = ['type' => 'danger', 'msg' => 'Error: ' . $e->getMessage()];
    }
}

/* ===== Listado y filtros ===== */
$q       = trim($_GET['q'] ?? '');
$estado  = $_GET['estado'] ?? 'activos';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset  = ($page - 1) * $perPage;

if ($q !== '') {
    $rows = $usuarioM->searchByNombre($q, $perPage, $offset);
} else {
    $rows = $usuarioM->listAll($perPage, $offset, 'u.fecha_registro DESC');
}

/* Filtro por estado (en PHP, rápido) */
if ($estado === 'activos') {
    $rows = array_values(array_filter($rows, fn($r) => (int)$r['activo'] === 1));
} elseif ($estado === 'inactivos') {
    $rows = array_values(array_filter($rows, fn($r) => (int)$r['activo'] === 0));
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <base href="/G3_SC-502_MN_AvncesProyecto/Avance3/">
    <title>Soluna | Admin | Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body class="bg-light">
    <main class="container-fluid d-flex flex-column min-vh-100" style="margin:0;padding:0;">
        <?php renderHeader(); ?>

        <section class="container py-4">
            <h1 class="h3 mb-3">Usuarios</h1>

            <form class="row g-2 align-items-end mb-3" method="get">
                <div class="col-sm-6 col-md-7">
                    <label class="form-label mb-1">Buscar por nombre</label>
                    <input type="text" name="q" class="form-control" placeholder="Ej. Ana, Juan..."
                        value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label mb-1">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="activos" <?php echo $estado === 'activos' ? 'selected' : ''; ?>>Activos</option>
                        <option value="inactivos" <?php echo $estado === 'inactivos' ? 'selected' : ''; ?>>Inactivos</option>
                        <option value="todos" <?php echo $estado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button class="btn btn-primary">Filtrar</button>
                </div>
            </form>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($flash['msg']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!$rows): ?>
                <div class="text-center text-muted py-5">No hay usuarios para mostrar.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:90px;">ID</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Tipo</th>
                                <th class="text-center" style="width:120px;">Estado</th>
                                <th style="width:220px;">Registro</th>
                                <th style="width:220px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $u): ?>
                                <tr>
                                    <td><?php echo (int)$u['id_usuario']; ?></td>
                                    <td><?php echo htmlspecialchars($u['nombre'] ?? '(sin nombre)'); ?></td>
                                    <td><?php echo htmlspecialchars($u['correo']); ?></td>
                                    <td><?php echo htmlspecialchars($u['rol'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($u['tipo'] ?? ''); ?></td>
                                    <td class="text-center">
                                        <?php if ((int)$u['activo'] === 1): ?>
                                            <span class="badge text-bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['fecha_registro']); ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button
                                                class="btn btn-outline-primary btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#modalDetalle"
                                                data-id="<?php echo (int)$u['id_usuario']; ?>">
                                                Ver datos
                                            </button>

                                            <?php if ((int)$u['activo'] === 1): ?>
                                                <form method="post" onsubmit="return confirm('¿Desactivar este usuario?');">
                                                    <input type="hidden" name="accion" value="toggle">
                                                    <input type="hidden" name="id_usuario" value="<?php echo (int)$u['id_usuario']; ?>">
                                                    <input type="hidden" name="activo" value="0">
                                                    <button class="btn btn-outline-danger btn-sm">Desactivar</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" onsubmit="return confirm('¿Reactivar este usuario?');">
                                                    <input type="hidden" name="accion" value="toggle">
                                                    <input type="hidden" name="id_usuario" value="<?php echo (int)$u['id_usuario']; ?>">
                                                    <input type="hidden" name="activo" value="1">
                                                    <button class="btn btn-outline-success btn-sm">Reactivar</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <?php renderFooter(); ?>
    </main>

    <!-- Modal Detalle Usuario -->
    <div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle del usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="detalleBody">
                    <div class="text-center text-muted py-5">Cargando datos…</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const modalDetalle = document.getElementById('modalDetalle');
        modalDetalle.addEventListener('show.bs.modal', async (ev) => {
            const btn = ev.relatedTarget;
            const id = btn?.getAttribute('data-id');
            const body = document.getElementById('detalleBody');
            if (!id) {
                body.innerHTML = '<div class="text-danger">ID inválido</div>';
                return;
            }
            body.innerHTML = '<div class="text-center text-muted py-5">Cargando datos…</div>';

            try {
                const fd = new FormData();
                fd.append('accion', 'get');
                fd.append('id_usuario', id);
                const resp = await fetch('Modulos/usuario_api.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await resp.json();
                if (!resp.ok || !data.ok) throw new Error(data.msg || 'Error al cargar');

                const u = data.usuario;

                let perfilHtml = '<div class="text-muted">(Sin perfil asociado)</div>';
                if (u.tipo === 'cliente' && u.perfil) {
                    perfilHtml = `
        <div class="row g-2">
          <div class="col-md-6"><strong>Nombre</strong><br>${esc(u.perfil.nombre || '')}</div>
          <div class="col-md-3"><strong>Teléfono</strong><br>${esc(u.perfil.telefono || '')}</div>
          <div class="col-md-12"><strong>Dirección</strong><br>${esc(u.perfil.direccion || '')}</div>
        </div>`;
                } else if (u.tipo === 'empleado' && u.perfil) {
                    perfilHtml = `
        <div class="row g-2">
          <div class="col-md-6"><strong>Nombre</strong><br>${esc(u.perfil.nombre || '')}</div>
          <div class="col-md-3"><strong>Puesto</strong><br>${esc(u.perfil.puesto || '')}</div>
        </div>`;
                }

                body.innerHTML = `
      <div class="row g-3">
        <div class="col-md-4"><strong>ID Usuario</strong><br>${u.id_usuario}</div>
        <div class="col-md-4"><strong>Correo</strong><br>${esc(u.correo)}</div>
        <div class="col-md-4"><strong>Rol</strong><br>${esc(u.rol || '')}</div>
        <div class="col-md-4"><strong>Estado</strong><br>${u.activo ? 'Activo' : 'Inactivo'}</div>
        <div class="col-md-8"><strong>Fecha registro</strong><br>${esc(u.fecha_registro || '')}</div>
        <div class="col-12"><hr></div>
        <div class="col-12"><strong>Perfil (${esc(u.tipo || 'N/A')})</strong></div>
        <div class="col-12">${perfilHtml}</div>
      </div>`;
            } catch (e) {
                body.innerHTML = `<div class="text-danger">No se pudo cargar el detalle: ${esc(e.message || '')}</div>`;
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