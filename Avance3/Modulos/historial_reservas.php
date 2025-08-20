<?php
session_start();
if (empty($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario'];

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/layout.php';
require_once $APP_ROOT . '/include/conexion.php';


$sql = "SELECT * FROM Mesa WHERE disponible = 1";
$result = mysqli_query($mysqli, $sql);
$mesas = [];
while ($row = mysqli_fetch_assoc($result)) {
    $mesas[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reservas - CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <base href="/G3_SC-502_MN_AvncesProyecto/Avance3/">
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body class="bg-light">
<?php renderHeader(); ?>

<div class="container mt-5">
    <div class="card shadow p-4">
        <h3 class="mb-3 text-center">Mis Reservas</h3>
        <button class="btn btn-primary mb-3" id="btnAgregar">Agregar Reserva</button>
        <div id="mensaje"></div>

        <table class="table table-bordered" id="tablaReservas">
            <thead>
                <tr>
                    <th>Mesa</th>
                    <th>Fecha y Hora</th>
                    <th>Cantidad</th>
                    <th>Nota</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
               
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="reservaModal" tabindex="-1" aria-labelledby="reservaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formReserva">
        <div class="modal-header">
          <h5 class="modal-title" id="reservaModalLabel">Agregar Reserva</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id_reserva" id="id_reserva">
            <input type="hidden" name="id_estado" value="1">

            <div class="mb-3">
                <label for="id_mesa" class="form-label">Mesa</label>
                <select class="form-select" name="id_mesa" id="id_mesa" required>
                    <option value="">Seleccione una mesa...</option>
                    <?php foreach ($mesas as $mesa): ?>
                        <option value="<?= $mesa['id_mesa'] ?>" data-asientos="<?= $mesa['num_asientos'] ?>">
                            Mesa <?= $mesa['numero_mesa'] ?> - <?= $mesa['num_asientos'] ?> personas (<?= $mesa['ubicacion'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="fecha" class="form-label">Fecha y Hora</label>
                <input type="datetime-local" class="form-control" name="fecha" id="fecha" required>
            </div>

            <div class="mb-3">
                <label for="cantidad_personas" class="form-label">Cantidad de Personas</label>
                <input type="number" class="form-control" name="cantidad_personas" id="cantidad_personas" min="1" required>
            </div>

            <div class="mb-3">
                <label for="nota_cliente" class="form-label">Nota del Cliente</label>
                <textarea class="form-control" name="nota_cliente" id="nota_cliente" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success" id="btnSubmit">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    let mesaSeleccionada = null;

    function cargarTabla() {
        $.get('models/procesar_reserva.php', {accion: 'listar'}, function(data) {
            $('#tablaReservas tbody').html(data);
        });
    }

    cargarTabla();

    $('#btnAgregar').click(function() {
        $('#formReserva')[0].reset();
        $('#id_reserva').val('');
        $('#reservaModalLabel').text('Agregar Reserva');
        $('#btnSubmit').text('Guardar');
        mesaSeleccionada = null;
        $('#reservaModal').modal('show');
        $('#mensaje').html('');
    });

    $('#id_mesa').change(function() {
        mesaSeleccionada = $(this).find('option:selected');
        validarCantidad();
    });

    $('#cantidad_personas').on('input', function() {
        validarCantidad();
    });

    function validarCantidad() {
        if (!mesaSeleccionada) return true;
        const cantidadPersonas = parseInt($('#cantidad_personas').val());
        const asientosMesa = parseInt(mesaSeleccionada.data('asientos'));
        if (cantidadPersonas > asientosMesa) {
            $('#mensaje').html('<div class="alert alert-danger">La cantidad de personas no puede exceder los asientos de la mesa.</div>');
            return false;
        } else {
            $('#mensaje').html('');
            return true;
        }
    }

    $('#formReserva').submit(function(e) {
        e.preventDefault();
        if (!mesaSeleccionada) {
            $('#mensaje').html('<div class="alert alert-danger">Debe seleccionar una mesa.</div>');
            return;
        }
        if (!validarCantidad()) return;

        $.ajax({
            url: 'models/procesar_reserva.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#mensaje').html(response);
                cargarTabla();
                $('#reservaModal').modal('hide');
               location.reload(); 
            },
            error: function() {
                $('#mensaje').html('<div class="alert alert-danger">Ocurrió un error.</div>');
            }
        });
    });

   
    $(document).on('click', '.btn-editar', function() {
        const row = $(this).closest('tr');
        const id = row.data('id');
        const idMesa = row.data('id-mesa');

        $('#id_reserva').val(id);
        $('#id_mesa').val(idMesa).trigger('change'); 
        $('#fecha').val(row.find('td:eq(1)').text());
        $('#cantidad_personas').val(row.find('td:eq(2)').text());
        $('#nota_cliente').val(row.find('td:eq(3)').text());
        $('#reservaModalLabel').text('Editar Reserva');
        $('#btnSubmit').text('Actualizar');
        $('#reservaModal').modal('show');
       location.reload(); 
    });

    
    $(document).on('click', '.btn-eliminar', function() {
        if (!confirm('¿Desea eliminar esta reserva?')) return;
        const id = $(this).closest('tr').data('id');
        $.post('models/procesar_reserva.php', {id_reserva: id, accion: 'eliminar'}, function(response) {
            $('#mensaje').html(response);
           location.reload(); 
        });
    });
});
</script>
</body>
</html>
