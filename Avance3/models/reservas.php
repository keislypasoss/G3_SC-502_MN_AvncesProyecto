<?php
$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/layout.php';
require_once $APP_ROOT . '/include/conexion.php';

function obtenerReservas() {
    global $mysqli;
    $sql = "SELECT r.id_reserva, c.nombre AS cliente_nombre, m.numero_mesa, m.ubicacion, r.fecha, r.cantidad_personas, r.id_estado, e.nombre AS estado_nombre
            FROM Reserva r
            JOIN Mesa m ON r.id_mesa = m.id_mesa
            JOIN Cliente c ON r.id_cliente = c.id_cliente
            JOIN Estado e ON r.id_estado = e.id_estado
            ORDER BY r.fecha DESC";
    $result = mysqli_query($mysqli, $sql);

    $reservas = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $reservas[] = $row;
    }
    return $reservas;
}

function obtenerEstados() {
    global $mysqli;
    
    $sql = "SELECT id_estado, nombre FROM Estado WHERE tipo_estado = 'Reserva'";
    $result = mysqli_query($mysqli, $sql);

    $estados = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $estados[] = $row;
    }
    return $estados;
}


function actualizarEstadoReserva($id_reserva, $nuevo_estado) {
    global $mysqli;
    
       if ($nuevo_estado == 7) {
        
        $update_mesa_sql = "UPDATE Mesa SET disponible = 1 WHERE id_mesa = (SELECT id_mesa FROM Reserva WHERE id_reserva = ?)";
        $stmt_mesa = mysqli_prepare($mysqli, $update_mesa_sql);
        mysqli_stmt_bind_param($stmt_mesa, 'i', $id_reserva);
        mysqli_stmt_execute($stmt_mesa);
    }
    $update_sql = "UPDATE Reserva SET id_estado = ? WHERE id_reserva = ?";
    $stmt = mysqli_prepare($mysqli, $update_sql);
    mysqli_stmt_bind_param($stmt, 'ii', $nuevo_estado, $id_reserva);
    return mysqli_stmt_execute($stmt);
}

?>


<script>
   
    function showAlert(message) {
        var alertDiv = document.createElement('div');
        alertDiv.classList.add('alert', 'alert-success', 'alert-dismissible', 'fade', 'show', 'fixed-top', 'm-3');
        alertDiv.role = 'alert';
        alertDiv.innerHTML = '<strong>Éxito!</strong> ' + message + 
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        document.body.appendChild(alertDiv);
    }

   
    function actualizarEstadoReserva(id_reserva) {
        var estadoSelect = document.getElementById("estado_" + id_reserva);
        var nuevoEstado = estadoSelect.value;

        var formData = new FormData();
        formData.append("id_reserva", id_reserva);
        formData.append("estado", nuevoEstado);

        fetch("Modulos/reservas_admin.php", { 
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            showAlert('El estado de la reserva se ha actualizado correctamente.');
        })
        .catch(error => {
            showAlert('Error al actualizar el estado de la reserva.');
        });
    }
</script>
