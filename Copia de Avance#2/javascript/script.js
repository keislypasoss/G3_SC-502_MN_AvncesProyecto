document.addEventListener("DOMContentLoaded", function () {
  document.getElementById("header").innerHTML = `
   <header>
    <h1 class="logo">Soluna</h1>
    <nav> 
  <ul class="menu">
    <li><a href="HomePage.html">Menú</a></li>
    <li><a href="historial_reservas.html">Reservas</a></li>
    <li><a href="#">Pedidos</a></li>

    <li class="submenu-container">
      <a href="#">Atención al Cliente</a>
      <ul class="submenu">
        <li><a href="RegistroSolicitud.html">Realizar Solicitud</a></li>
        <li><a href="VerificarSolicitud.html">Verificar Solicitud</a></li>
    
      </ul>
    </li>
  </ul>
</nav>

  </header>
  `;
  document.getElementById("footer").innerHTML = `
   <footer>
    <p>&copy; 2025 Restaurante Soluna. Todos los derechos reservados.</p>
    <p>Dirección: Calle 123, Ciudad | Teléfono: 555-1234</p>
  </footer>
  `;
});
