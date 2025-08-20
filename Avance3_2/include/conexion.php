<?php
// Control de conexion a base de datos
// Activar reporte de errores
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'localhost';
$usuario = 'root';
$contrasenia = '';
$base_datos = 'Restaurante_Soluna';
$puerto = 3307; // 👈 Puerto MySQL

$mysqli = new mysqli($host, $usuario, $contrasenia, $base_datos, $puerto);

if ($mysqli->connect_error) {
    echo "<div class='alert alert-danger'>Error en la conexion a base de datos</div>";
} else {
    $mysqli->set_charset('utf8mb4');
}
?>
