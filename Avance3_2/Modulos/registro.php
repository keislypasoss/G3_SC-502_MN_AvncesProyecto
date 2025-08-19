<?php 
session_start();
require_once('../include/conexion.php');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];
    $telefono = $_POST['telefono'] ?? null;

    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='alert alert-danger text-center'>Correo inválido</div>";
    } elseif ($password !== $confirm) {
        echo "<div class='alert alert-danger text-center'>Las contraseñas no coinciden</div>";
    } else {
        $hash_pass = password_hash($password, PASSWORD_DEFAULT);
        $activo = 1;
        $fecha_registro = date("Y-m-d H:i:s");
        $rol = "cliente"; 

       
        $stmt = $mysqli->prepare("INSERT INTO Usuario (correo, contrasena, activo, fecha_registro, rol) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $email, $hash_pass, $activo, $fecha_registro, $rol);

        if ($stmt->execute()) {
            $id_usuario = $stmt->insert_id;
            echo "<div class='alert alert-success text-center'>Usuario creado correctamente</div>";
        } else {
            echo "<div class='alert alert-danger text-center'>Error al crear usuario: " . $stmt->error . "</div>";
        }

        $stmt->close();
        $mysqli->close();
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro Usuario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../javascript/script.js"></script>
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow-lg w-100" style="max-width: 600px">
            <div class="card-header">
                <h3 class="card-title">Registro Usuario</h3>
            </div>   
            <div class="card-body">
                <form id="registro" method="post">
                    <div class="mb-3">
                        <label class="form-label" for="email">Correo electrónico:</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="confirm">Confirmar contraseña:</label>
                        <input type="password" class="form-control" id="confirm" name="confirm" required>
                    </div>
                      
                    <button type="submit" class="btn btn-primary btn-block">Registrar</button>
                    <p class="text-center mt-3">
                        Si ya está registrado, <a href="login.php">Inicie sesión aquí</a>
                    </p>
                </form>
            </div>   
        </div>
    </div>    
</body>
</html>
