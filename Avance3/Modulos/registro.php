<?php
session_start();
require_once('../include/conexion.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm'] ?? '';
    $nombre    = trim($_POST['nombre'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alerta = "<div class='alert alert-danger text-center'>Correo inválido</div>";
    } elseif ($password !== $confirm) {
        $alerta = "<div class='alert alert-danger text-center'>Las contraseñas no coinciden</div>";
    } else {
        $hash_pass      = password_hash($password, PASSWORD_DEFAULT);
        $activo         = 1;
        $fecha_registro = date("Y-m-d H:i:s");
        $rol            = "cliente";

        try {
            $mysqli->begin_transaction();

            $stmt = $mysqli->prepare(
                "INSERT INTO Usuario (correo, contrasena, activo, fecha_registro, rol)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("ssiss", $email, $hash_pass, $activo, $fecha_registro, $rol);
            $stmt->execute();

            $id_usuario = $mysqli->insert_id;

            $nombre    = ($nombre !== '') ? $nombre : null;
            $telefono  = ($telefono !== '') ? $telefono : null;
            $direccion = ($direccion !== '') ? $direccion : null;

            $stmt2 = $mysqli->prepare(
                "INSERT INTO Cliente (id_usuario, nombre, telefono, direccion)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt2->bind_param("isss", $id_usuario, $nombre, $telefono, $direccion);
            $stmt2->execute();

            $mysqli->commit();

            $alerta = "<div class='alert alert-success text-center'>Cuenta creada correctamente</div>";
        } catch (mysqli_sql_exception $e) {
            $mysqli->rollback();
            if ($e->getCode() === 1062) {
                $alerta = "<div class='alert alert-danger text-center'>Ese correo ya está registrado.</div>";
            } else {
                $msg = htmlspecialchars($e->getMessage());
                $alerta = "<div class='alert alert-danger text-center'>Ocurrió un error: $msg</div>";
            }
        } finally {
            if (isset($stmt) && $stmt) {
                $stmt->close();
            }
            if (isset($stmt2) && $stmt2) {
                $stmt2->close();
            }
            $mysqli->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Soluna | Registro Usuario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../javascript/script.js"></script>
</head>

<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow-lg w-100" style="max-width: 600px">
            <div class="card-header">
                <h3 class="card-title text-center">Registro Usuario</h3>
            </div>
            <div class="card-body">

                <?= $alerta ?>

                <form id="registro" method="post" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="email">Correo electrónico</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="confirm">Confirmar contraseña</label>
                        <input type="password" class="form-control" id="confirm" name="confirm" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                            value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="telefono">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono"
                            value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="direccion">Dirección (opcional)</label>
                        <input type="text" class="form-control" id="direccion" name="direccion"
                            value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Registrar</button>
                    <p class="text-center mt-3">
                        Si ya está registrado, <a href="login.php">Inicie sesión aquí</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</body>

</html>