<?php

function renderHeader(): void
{
    echo '<header>
            <a href="index.php" class="logo">Soluna</a>
            <nav>
                <ul class="menu">
                <li><a href="Modulos/menu.php">Menú</a></li>
                <li><a href="historial_reservas.html">Reservas</a></li>
                <li><a href="pedidos/lista.php">Pedidos</a></li>
                <li>
                <a href="Modulos/carrito.php">Carrito
                    <span class="badge text-bg-danger ms-1 d-none" data-cart-badge>0</span>
                </a>
                </li>
                <li class="submenu-container">
                    <a href="#">Atención al Cliente</a>
                    <ul class="submenu">
                    <li><a href="RegistroSolicitud.html">Realizar Solicitud</a></li>
                    <li><a href="VerificarSolicitud.html">Verificar Solicitud</a></li>
                    </ul>
                </li>
                <li><a href="Modulos/login.php">Iniciar Sesión</a></li>
                </ul>
            </nav>
        </header>';
}

function renderFooter(): void
{
    $year = date('Y');
    echo '<footer class="mt-auto">
        <p>&copy; ' . $year . ' Restaurante Soluna. Todos los derechos reservados.</p>
        <p>Dirección: Calle 123, Ciudad | Teléfono: 555-1234</p>
    </footer>';
}
