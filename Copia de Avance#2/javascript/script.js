document.addEventListener("DOMContentLoaded", function () {
  document.getElementById("header").innerHTML = `
   <header>
    <h1 class="logo">Soluna</h1>
    <nav> 
  <ul class="menu">
    <li><a href="HomePage.html">Menú</a></li>
    <li><a href="#">Reservas</a></li>
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
});
