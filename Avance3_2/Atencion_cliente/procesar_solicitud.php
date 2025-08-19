<?php
session_start();
require_once('../include/conexion.php');

if (!isset($_SESSION['id_usuario'])) {
    echo "Debe iniciar sesión";
    exit;
}

// Recibir datos del POST
$id_usuario = $_POST['id_usuario'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';

if (empty($id_usuario) || empty($tipo) || empty($descripcion)) {
    echo "Todos los campos son obligatorios";
    exit;
}

// Preparar inserción
$stmt = $mysqli->prepare("INSERT INTO solicitudes (id_usuario, tipo, descripcion) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $id_usuario, $tipo, $descripcion);

if ($stmt->execute()) {
    echo "Solicitud enviada correctamente ✅";
} else {
    echo "Error al enviar la solicitud: " . $stmt->error;
}

$stmt->close();
$mysqli->close();
?>
