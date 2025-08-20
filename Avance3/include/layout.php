<?php
function renderHeader(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    $isLogged   = !empty($_SESSION['usuario']);
    $correoSafe = $isLogged ? htmlspecialchars($_SESSION['correo'] ?? 'usuario') : '';
    $rolSafe    = $isLogged ? htmlspecialchars($_SESSION['rol'] ?? '') : '';

    echo '<header>
            <a href="index.php" class="logo">Soluna</a>';
    if (!$isLogged || $rolSafe === 'cliente')
        echo '<nav>
                <ul class="menu">
                    <li><a href="Modulos/menu.php">Menú</a></li>
                    <li>
                        <a href="Modulos/carrito.php">Carrito
                            <span class="badge text-bg-danger ms-1 d-none" data-cart-badge>0</span>
                        </a>
                    </li>
                    ';
    if ($isLogged && $rolSafe === 'cliente') {
                    <li class="submenu-container">
                        <a href="#">Atención al Cliente</a>
                        <ul class="submenu">
                            <li><a href="RegistroSolicitud.html">Realizar Solicitud</a></li>
                            <li><a href="VerificarSolicitud.html">Verificar Solicitud</a></li>
                        </ul>
                    </li>
                <li class="submenu-container">
                    <a href="#">' . $correoSafe . '</a>
                    <ul class="submenu">
                        <li><a href="Modulos/perfil.php">Mi perfil</a></li>
                        <li>
                            <form action="Modulos/logout.php" method="post" style="margin:0;">
                                <input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">
                                <a href="include/logout.php"  type="hidden">Cerrar sesión</a>
                            </form>
                        </li>
                    </ul>
                </li>';
    } elseif ($isLogged && $rolSafe === 'admin') {
        echo    '<li><a href="Modulos/productos_admin.php">Productos</a></li>
                <li><a href="Modulos/usuarios_admin.php">Usuarios</a></li>
                <li><a href="Modulos/pedidos_admin.php">Reportes</a></li>
                <li><a href="Modulos/reservas_admin.php">Reportes</a></li>    
                <li class="submenu-container">
                    <a href="#">' . $correoSafe . '</a>
                    <ul class="submenu">
                        <li><a href="Modulos/perfil_admin.php">Mi perfil</a></li>
                        <li>
                            <form action="Modulos/logout.php" method="post" style="margin:0;">
                                <input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">
                                <a href="include/logout.php"  type="hidden">Cerrar sesión</a>
                            </form>
                        </li>
                    </ul>
                </li>';
    } else {
        echo    '<li><a href="Modulos/login.php">Iniciar Sesión</a></li>';
    }
    echo        '</ul>
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
