<?php
session_start();
require_once('../include/conexion.php');

if (!isset($_SESSION['id_usuario'])) {
    echo "<div class='alert alert-danger'>Debe iniciar sesión para ver sus solicitudes.</div>";
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// Consultar solicitudes del usuario
$stmt = $mysqli->prepare("SELECT id_solicitud, tipo, descripcion, estado, fecha_creacion 
                          FROM solicitudes 
                          WHERE id_usuario = ? 
                          ORDER BY fecha_creacion DESC");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$solicitudes = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar Solicitudes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h3 class="mb-4 text-center">Mis Solicitudes</h3>

        <?php if (empty($solicitudes)) : ?>
            <div class="alert alert-info">No has enviado ninguna solicitud todavía.</div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($solicitudes as $sol) : ?>
                        <tr id="sol-<?php echo $sol['id_solicitud']; ?>">
                            <td><?php echo htmlspecialchars($sol['id_solicitud']); ?></td>
                            <td><?php echo htmlspecialchars($sol['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($sol['descripcion']); ?></td>
                            <td class="estado"><?php echo htmlspecialchars($sol['estado']); ?></td>
                            <td><?php echo htmlspecialchars($sol['fecha_creacion']); ?></td>
                            <td>
                                <?php if($sol['estado'] === 'Pendiente'): ?>
                                    <button class="btn btn-success btn-sm marcar-hecho" data-id="<?php echo $sol['id_solicitud']; ?>">Marcar como Hecho</button>
                                <?php else: ?>
                                    <span class="text-success">✔ Hecho</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function(){
    $(".marcar-hecho").click(function(){
        let id_solicitud = $(this).data("id");
        let boton = $(this);

        $.ajax({
            url: "actualizar_estado.php",
            type: "POST",
            data: { id_solicitud: id_solicitud },
            success: function(response){
                // Cambiar estado en la tabla
                $("#sol-" + id_solicitud + " .estado").text("Hecho");
                boton.replaceWith('<span class="text-success">✔ Hecho</span>');
            },
            error: function(){
                alert("Error al actualizar el estado");
            }
        });
    });
});
</script>

</body>
</html>
