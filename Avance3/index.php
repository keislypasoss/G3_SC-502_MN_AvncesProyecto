<?php
require __DIR__ . '/include/layout.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <base href="/G3_SC-502_MN_AvncesProyecto/Avance3/">
    <title>Soluna | Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body class="bg-light">
    <main class="container-fluid d-flex flex-column min-vh-100" style="margin:0;padding:0;">
        <?php renderHeader(); ?>

        <section class="container py-4">
            <h1>Bienvenido a Soluna</h1>
            <p>Contenido de ejemplo…</p>
        </section>

        <?php renderFooter(); ?>
    </main>
</body>

</html>