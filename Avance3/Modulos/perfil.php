<?php
session_start();
if (empty($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/layout.php';
require_once $APP_ROOT . '/include/conexion.php';
require_once $APP_ROOT . '/models/usuario_model.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$model = new UsuarioModel($mysqli);
$idUsuario = (int)$_SESSION['usuario'];

$usuario = $model->getById($idUsuario);
$perfilCliente = [
    'nombre'    => $usuario['perfil']['nombre']    ?? '',
    'telefono'  => $usuario['perfil']['telefono']  ?? '',
    'direccion' => $usuario['perfil']['direccion'] ?? '',
];
$correoActual = $usuario['correo'] ?? '';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAjax) {
    header('Content-Type: application/json; charset=utf-8');

    $errors = [];
    $csrf = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
        echo json_encode(['ok' => false, 'errors' => ['Sesión inválida. Recarga la página.']]);
        exit;
    }

    $email             = trim($_POST['email'] ?? '');
    $nombre            = trim($_POST['nombre'] ?? '');
    $telefono          = trim($_POST['telefono_personal'] ?? '');
    $direccion         = trim($_POST['direccion_personal'] ?? '');
    $pwdActual         = (string)($_POST['password_actual'] ?? '');
    $pwdNueva          = (string)($_POST['password_nueva'] ?? '');
    $pwdConfirmar      = (string)($_POST['password_confirmar'] ?? '');

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no tiene un formato válido.';
    }

    if ($telefono !== '' && !preg_match('/^(?:\+?506\s?)?(?:\d{4}-\d{4}|\d{8})$/', $telefono)) {
        $errors[] = 'El teléfono personal debe tener el formato 8888-8888, 88888888 o +506 8888-8888.';
    }

    $changedPassword = false;
    $wantsPasswordChange = ($pwdActual !== '' || $pwdNueva !== '' || $pwdConfirmar !== '');
    if ($wantsPasswordChange) {
        if ($pwdActual === '' || $pwdNueva === '' || $pwdConfirmar === '') {
            $errors[] = 'Para cambiar la contraseña, complete todos los campos de contraseña.';
        } elseif ($pwdNueva !== $pwdConfirmar) {
            $errors[] = 'La nueva contraseña y su confirmación no coinciden.';
        } elseif (strlen($pwdNueva) < 8) {
            $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
        } else {
            $stmt = $mysqli->prepare("SELECT contrasena FROM Usuario WHERE id_usuario = ? LIMIT 1");
            $stmt->bind_param("i", $idUsuario);
            $stmt->execute();
            $hash = $stmt->get_result()->fetch_column();
            $stmt->close();

            if (!$hash || !password_verify($pwdActual, $hash)) {
                $errors[] = 'La contraseña actual no es correcta.';
            } else {
                $changedPassword = true;
            }
        }
    }

    if ($errors) {
        echo json_encode(['ok' => false, 'errors' => $errors]);
        exit;
    }

    $data = [
        'correo' => $email,
        'tipo'   => 'cliente',
        'cliente' => [
            'nombre'    => $nombre,
            'telefono'  => $telefono,
            'direccion' => $direccion
        ]
    ];
    if ($changedPassword) {
        $data['password'] = $pwdNueva;
    }

    try {
        $model->update($idUsuario, $data);

        $_SESSION['correo'] = $email;
        $_SESSION['nombre'] = $nombre;

        echo json_encode([
            'ok' => true,
            'message' => 'Cambios guardados correctamente.',
            'nombre' => $nombre,
            'changed_password' => $changedPassword
        ]);
        exit;
    } catch (Throwable $e) {
        if (method_exists($e, 'getCode') && (int)$e->getCode() === 1062) {
            echo json_encode(['ok' => false, 'errors' => ['Ese correo ya está registrado por otro usuario.']]);
        } else {
            echo json_encode(['ok' => false, 'errors' => ['Error del servidor. Intente más tarde.']]);
        }
        exit;
    }
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <base href="/G3_SC-502_MN_AvncesProyecto/Avance3/">
    <title>Soluna | Mi Perfil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="styles/styles.css">
</head>


<body class="bg-light">
    <main class="container-fluid d-flex flex-column min-vh-100" style="margin:0;padding:0;">
        <?php renderHeader(); ?>

        <div class="align-self-center col-11 col-md-6 col-lg-4 m-3 p-2 bg-secondary" style="border-radius: 5px">
            <p id="titulo-perfil" class="h3 text-center text-white">
                Mi Perfil - <?= e($_SESSION['nombre'] ?? ($perfilCliente['nombre'] ?: 'Cliente')) ?>
            </p>
        </div>

        <div class="container py-4">
            <div id="alertas"></div>

            <form id="form-perfil" method="post" class="row g-3" novalidate>
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

                <div class="col-md-6">
                    <label class="form-label">Nombre *</label>
                    <input name="nombre" id="nombre" type="text" class="form-control" value="<?= e($perfilCliente['nombre']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email *</label>
                    <input id="email" name="email" type="email" class="form-control" value="<?= e($correoActual) ?>" required>
                    <div class="form-text">Formato: ejemplo@gmail.com</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Teléfono personal</label>
                    <input id="telefono_personal" name="telefono_personal" type="tel" class="form-control" inputmode="tel" value="<?= e($perfilCliente['telefono']) ?>">
                    <div class="form-text">Formato: 8888-8888, 88888888 o +506 8888-8888</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Dirección personal</label>
                    <input name="direccion_personal" id="direccion_personal" type="text" class="form-control" value="<?= e($perfilCliente['direccion']) ?>">
                </div>

                <hr class="my-4">

                <h2 class="h5">Cambiar contraseña</h2>
                <div class="col-md-4">
                    <label class="form-label">Contraseña actual</label>
                    <input id="password_actual" name="password_actual" type="password" class="form-control" autocomplete="current-password">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nueva contraseña</label>
                    <input id="password_nueva" name="password_nueva" type="password" class="form-control" autocomplete="new-password">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Confirmar nueva contraseña</label>
                    <input id="password_confirmar" name="password_confirmar" type="password" class="form-control" autocomplete="new-password">
                </div>

                <div class="col-12 d-flex justify-content-center">
                    <button type="submit" class="btn btn-success">Guardar cambios</button>
                </div>
            </form>
        </div>

        <?php renderFooter(); ?>
    </main>

    <script>
        $(function() {
            const esc = (s) => $('<div>').text(s ?? '').html();

            function showAlert(type, htmlContent) {
                $('#alertas').html(
                    `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
               ${htmlContent}
               <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
             </div>`
                );
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i;
            const phoneRegex = /^(?:\+?506\s?)?(?:\d{4}-\d{4}|\d{8})$/;

            function validateFront() {
                const errors = [];
                $('#email,#telefono_personal,#nombre').removeClass('is-invalid');

                const nombre = $('#nombre').val().trim();
                const email = $('#email').val().trim();
                const tel = $('#telefono_personal').val().trim();

                if (!nombre) {
                    errors.push('El nombre es obligatorio.');
                    $('#nombre').addClass('is-invalid');
                }
                if (!email || !emailRegex.test(email)) {
                    errors.push('El email no tiene un formato válido.');
                    $('#email').addClass('is-invalid');
                }
                if (tel && !phoneRegex.test(tel)) {
                    errors.push('El teléfono personal debe tener el formato 8888-8888, 88888888 o +506 8888-8888.');
                    $('#telefono_personal').addClass('is-invalid');
                }

                const a = $('#password_actual').val();
                const n = $('#password_nueva').val();
                const c = $('#password_confirmar').val();
                if (a || n || c) {
                    if (!a || !n || !c) {
                        errors.push('Para cambiar la contraseña, complete todos los campos de contraseña.');
                        if (!a) $('#password_actual').addClass('is-invalid');
                        if (!n) $('#password_nueva').addClass('is-invalid');
                        if (!c) $('#password_confirmar').addClass('is-invalid');
                    } else if (n !== c) {
                        errors.push('La nueva contraseña y su confirmación no coinciden.');
                        $('#password_nueva,#password_confirmar').addClass('is-invalid');
                    } else if (n.length < 8) {
                        errors.push('La nueva contraseña debe tener al menos 8 caracteres.');
                        $('#password_nueva').addClass('is-invalid');
                    }
                }

                return errors;
            }

            $('#email,#telefono_personal,#nombre,#password_actual,#password_nueva,#password_confirmar').on('input', function() {
                $(this).removeClass('is-invalid');
            });

            $('#form-perfil').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $form.find('button[type="submit"]');
                const original = $btn.text();
                $('#alertas').empty();

                const errors = validateFront();
                if (errors.length) {
                    showAlert('danger', `<ul class="mb-0">${errors.map(esc).map(t => `<li>${t}</li>`).join('')}</ul>`);
                    return;
                }

                $btn.prop('disabled', true).text('Guardando...');
                $.ajax({
                    url: location.href,
                    method: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(data) {
                        if (data.ok) {
                            showAlert('success', esc(data.message || 'Cambios guardados.'));
                            if (data.nombre) {
                                $('#titulo-perfil').text('Mi Perfil - ' + data.nombre);
                                $('#navbar-usuario-nombre').text(data.nombre);
                            }
                            $('#password_actual,#password_nueva,#password_confirmar').val('').removeClass('is-invalid');
                        } else {
                            const items = (data.errors || []).map(x => `<li>${esc(x)}</li>`).join('');
                            showAlert('danger', `<ul class="mb-0">${items}</ul>`);
                        }
                    },
                    error: function() {
                        showAlert('danger', 'Error de red.');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text(original);
                    }
                });
            });
        });
    </script>
</body>

</html>