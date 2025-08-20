<?php
session_start();
require_once('../include/conexion.php');

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
  $email = $_POST['usuario'] ?? '';
  $password = $_POST['contrasena'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $mensaje = "Correo inválido";
  } else {
    $stmt = $mysqli->prepare("SELECT correo, contrasena, id_usuario, rol FROM usuario WHERE correo = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
      $usuario = $resultado->fetch_assoc();

      if (password_verify($password, $usuario['contrasena'])) {
        $_SESSION['correo'] = $usuario['correo'];
        $_SESSION['usuario'] = $usuario['id_usuario'];
        $_SESSION['rol'] = $usuario['rol'];

        header("Location: ../index.php");
        exit();
      } else {
        $mensaje = "Contraseña incorrecta";
      }
    } else {
      $mensaje = "Correo no registrado";
    }

    $stmt->close();
    $mysqli->close();
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <base href="/G3_SC-502_MN_AvncesProyecto/Avance3/">
  <title>Soluna | Iniciar Sesión</title>
  <link rel="stylesheet" href="Styles/styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" />
</head>

<body>
  <div class="login-container">
    <div class="image-side">
      <img src="img/images.jpeg" alt="Imagen" />
    </div>

    <div class="form-side">
      <div class="login-box">
        <h1>Iniciar Sesión</h1>

        <form method="post" id="loginForm" novalidate>
          <div class="form-group">
            <label for="usuario">Correo del usuario:</label>
            <input type="text" id="usuario" name="usuario" class="form-control" required />
          </div>

          <div class="form-group">
            <label for="contrasena">Contraseña:</label>
            <input type="password" id="contrasena" name="contrasena" class="form-control" required />
          </div>

          <div class="text-center">
            <button type="submit" id="btnLogin" class="btn btn-primary btn-block">Iniciar sesión</button>
          </div>
        </form>

        <br />
        <div class="text-center mt-3">
          <span>¿No tienes cuenta? </span>
          <a href="Modulos/registro.php">Regístrate aquí</a>
        </div>

        <div id="error" class="mt-3 text-danger text-center" style="display: <?php echo $mensaje ? 'block' : 'none'; ?>;">
          <?php echo htmlspecialchars($mensaje); ?>
        </div>
      </div>
    </div>
  </div>
</body>

</html>