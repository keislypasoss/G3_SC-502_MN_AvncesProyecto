<?php
session_start();

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/layout.php';
require_once $APP_ROOT . '/include/conexion.php';
require_once $APP_ROOT . '/models/usuario_model.php';
$id_usuario = $_SESSION['usuario'];

$accion = $_POST['accion'] ?? $_GET['accion'] ?? 'insertar';

function mensaje($tipo, $texto) {
    return "<div class='alert alert-$tipo'>$texto</div>";
}

switch ($accion) {

    case 'insertar':
    case 'actualizar':
        $id_reserva = $_POST['id_reserva'] ?? null;
        $id_mesa = $_POST['id_mesa'];
        $fecha = $_POST['fecha'];
        $cantidad_personas = $_POST['cantidad_personas'];
        $nota_cliente = $_POST['nota_cliente'];
        $id_estado = $_POST['id_estado'] ?? 1;

        if (empty($id_mesa) || empty($fecha) || empty($cantidad_personas)) {
            echo mensaje('danger', 'Todos los campos obligatorios deben completarse.');
            exit;
        }

       
        $mesaQuery = mysqli_query($mysqli, "SELECT num_asientos FROM Mesa WHERE id_mesa = $id_mesa");
        $mesa = mysqli_fetch_assoc($mesaQuery);
        if ($cantidad_personas > $mesa['num_asientos']) {
            echo mensaje('danger', 'La cantidad de personas excede los asientos disponibles en la mesa.');
            exit;
        }

        if ($accion == 'insertar') {
            
            $sql = "INSERT INTO Reserva (id_cliente, id_mesa, fecha, cantidad_personas, nota_cliente, id_estado)
                    VALUES ($id_usuario, $id_mesa, '$fecha', $cantidad_personas, '".mysqli_real_escape_string($mysqli, $nota_cliente)."', $id_estado)";
            if (mysqli_query($mysqli, $sql)) {
                
                mysqli_query($mysqli, "UPDATE Mesa SET disponible=0 WHERE id_mesa=$id_mesa");
                echo mensaje('success', 'Reserva realizada con éxito.');
            } else {
                echo mensaje('danger', 'Error al realizar la reserva: ' . mysqli_error($mysqli));
            }
        } else { 
            
            $resAnteriorQuery = mysqli_query($mysqli, "SELECT id_mesa FROM Reserva WHERE id_reserva=$id_reserva AND id_cliente=$id_usuario");
            $resAnterior = mysqli_fetch_assoc($resAnteriorQuery);
            $mesaAnterior = $resAnterior['id_mesa'];

            $sql = "UPDATE Reserva SET id_mesa=$id_mesa, fecha='$fecha', cantidad_personas=$cantidad_personas,
                    nota_cliente='".mysqli_real_escape_string($mysqli, $nota_cliente)."', id_estado=$id_estado
                    WHERE id_reserva=$id_reserva AND id_cliente=$id_usuario";
            if (mysqli_query($mysqli, $sql)) {
                
                if ($mesaAnterior != $id_mesa) {
                    mysqli_query($mysqli, "UPDATE Mesa SET disponible=1 WHERE id_mesa=$mesaAnterior");
                    mysqli_query($mysqli, "UPDATE Mesa SET disponible=0 WHERE id_mesa=$id_mesa");
                }
                echo mensaje('success', 'Reserva actualizada con éxito.');
            } else {
                echo mensaje('danger', 'Error al actualizar la reserva: ' . mysqli_error($mysqli));
            }
        }
        break;

    case 'eliminar':
        $id_reserva = $_POST['id_reserva'];
        if (empty($id_reserva)) {
            echo mensaje('danger', 'ID de reserva no proporcionado.');
            exit;
        }

       
        $mesaQuery = mysqli_query($mysqli, "SELECT id_mesa FROM Reserva WHERE id_reserva=$id_reserva AND id_cliente=$id_usuario");
        $mesaRes = mysqli_fetch_assoc($mesaQuery);
        $idMesaEliminar = $mesaRes['id_mesa'];

        $sql = "DELETE FROM Reserva WHERE id_reserva=$id_reserva AND id_cliente=$id_usuario";
        if (mysqli_query($mysqli, $sql)) {
          
            mysqli_query($mysqli, "UPDATE Mesa SET disponible=1 WHERE id_mesa=$idMesaEliminar");
            echo mensaje('success', 'Reserva eliminada con éxito.');
        } else {
            echo mensaje('danger', 'Error al eliminar la reserva: ' . mysqli_error($mysqli));
        }
        break;

    case 'listar':
        $sql = "SELECT r.*, m.numero_mesa, m.num_asientos, m.ubicacion 
                FROM Reserva r 
                INNER JOIN Mesa m ON r.id_mesa = m.id_mesa 
                WHERE r.id_cliente = $id_usuario";
        $result = mysqli_query($mysqli, $sql);

        while ($reserva = mysqli_fetch_assoc($result)) {
            echo "<tr data-id='{$reserva['id_reserva']}' data-id-mesa='{$reserva['id_mesa']}'>
        <td>Mesa {$reserva['numero_mesa']} ({$reserva['num_asientos']} personas)</td>
        <td>{$reserva['fecha']}</td>
        <td>{$reserva['cantidad_personas']}</td>
        <td>{$reserva['nota_cliente']}</td>
        <td>
            <button class='btn btn-sm btn-warning btn-editar'>Editar</button>
            <button class='btn btn-sm btn-danger btn-eliminar'>Eliminar</button>
        </td>
      </tr>";
        }
        break;

    default:
        echo mensaje('danger', 'Acción no válida.');
        break;
}
?>
