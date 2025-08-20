<?php
session_start();
if ($_SESSION['rol'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/layout.php';
require_once $APP_ROOT . '/include/conexion.php';
require_once $APP_ROOT . '/models/usuario_model.php';
require_once $APP_ROOT . '/models/reservas.php'; 


$reservas = obtenerReservas();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_reserva'])) {
    $id_reserva = (int)$_POST['id_reserva'];
    $nuevo_estado = (int)$_POST['estado'];

    if (actualizarEstadoReserva($id_reserva, $nuevo_estado)) {
        echo "<script>showAlert('El estado de la reserva se ha actualizado correctamente.');</script>";
    } else {
        echo "<script>showAlert('Error al actualizar el estado de la reserva.');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <base href="/G3_SC-502_MN_AvncesProyecto/Avance3/">
    <title>Soluna | Administración de Reservas</title>
    <link rel="stylesheet" href="styles/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>

  
      
</head>

<body class="bg-light">
    <main class="container-fluid d-flex flex-column min-vh-100" style="margin:0;padding:0;">
        <?php renderHeader(); ?>

        <section class="container py-4">
            <h1 class="h3 mb-3">Administrar Reservas</h1>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID Reserva</th>
                            <th>Cliente</th>
                            <th>Mesa</th>
                            <th>Fecha y Hora</th>
                            <th>Personas</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservas as $reserva): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reserva['id_reserva']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['cliente_nombre']); ?></td>
                                <td><?php echo "Mesa " . htmlspecialchars($reserva['numero_mesa']) . " (" . htmlspecialchars($reserva['ubicacion']) . ")"; ?></td>
                                <td><?php echo htmlspecialchars($reserva['fecha']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['cantidad_personas']); ?></td>
                                <?php

$estados = obtenerEstados();
?>

<td>
    <select class="form-select form-select-sm" id="estado_<?php echo $reserva['id_reserva']; ?>" onchange="actualizarEstadoReserva(<?php echo $reserva['id_reserva']; ?>, this.value)">
        <?php foreach ($estados as $estado): ?>
            <option value="<?php echo $estado['id_estado']; ?>" <?php echo $reserva['id_estado'] == $estado['id_estado'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($estado['nombre']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</td>


                                <td>
                                   
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php renderFooter(); ?>
    </main>
</body>

</html>
