<?php
require __DIR__ . '/include/layout.php';
require __DIR__ . '/include/conexion.php';
require __DIR__ . '/models/producto_model.php';

$model = new ProductoModel($mysqli);
$destacados = $model->listarAleatorios(9, 1); // 9 al azar, solo disponibles
$slides = array_chunk($destacados, 3);        // 3 por slide
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

        <section class="py-5 bg-warning-subtle">
            <div class="container">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <h1 class="display-5 fw-bold mb-2">Bienvenido a Soluna</h1>
                        <p class="lead mb-0">Pizza rústica, sabor casero y el calor de una cocina que no deja de soñar. 💫</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container py-5">
            <div class="row g-4">
                <div class="col-lg-10">
                    <h2 class="h3 mb-3">Nuestra historia</h2>
                    <p class="mb-3">
                        La historia atrás de este emprendimiento es de <strong>Luis Solano</strong>, quien con tan solo 25 años
                        decidió emprender por su cuenta. Con solo un horno y su original receta de una <em>pizza rústica</em> inició esta aventura.
                    </p>
                    <p class="mb-3">
                        Inicialmente en <strong>Atenas de Alajuela</strong> y, con el tiempo, extendió su negocio hasta
                        <strong>Palmares de Alajuela</strong>.
                    </p>
                    <p class="mb-0">
                        Al poco tiempo en Palmares, creció el menú y la clientela, lo que llevó a Luis a necesitar un sistema para
                        manejar mejor su negocio y ser visible para muchas más personas. ¡De ahí nace este sitio! 🍕
                    </p>
                </div>
            </div>
        </section>

        <section class="container pb-5">
            <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                <h2 class="h3 mb-3">Síguenos en Instagram</h2>
                <a class="btn"
                    href="https://www.instagram.com/soluna.palmares?utm_source=ig_web_button_share_sheet&igsh=MWhtejVtdHlnNG1kag=="
                    target="_blank" rel="noopener">
                    @soluna.palmares
                </a>
            </div>
        </section>

        <?php renderFooter(); ?>
    </main>
</body>

</html>