<?php
session_start();
// Aquí asumimos que ya tienes el id_usuario en sesión
// Ejemplo: $_SESSION['id_usuario'] = 1;
if (!isset($_SESSION['id_usuario'])) {
    echo "<div class='alert alert-danger'>Debe iniciar sesión para realizar una solicitud.</div>";
    exit;
}
$id_usuario = $_SESSION['id_usuario'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Realizar Solicitud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="solicitudes.js"></script>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h3 class="mb-3 text-center">Realizar Solicitud</h3>
        <form id="formSolicitud">
            <!-- Guardamos id_usuario en hidden -->
            <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">

            <div class="mb-3">
                <label for="tipo" class="form-label">Tipo de solicitud</label>
                <select class="form-select" name="tipo" id="tipo" required>
                    <option value="">Seleccione...</option>
                    <option value="Reserva">Reserva</option>
                    <option value="Consulta">Consulta</option>
                    <option value="Queja">Queja</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" name="descripcion" id="descripcion" rows="4" required></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">Enviar Solicitud</button>
        </form>

        <!-- Mensajes -->
        <div id="mensaje" class="mt-3"></div>
    </div>
</div>

</body>
</html>
