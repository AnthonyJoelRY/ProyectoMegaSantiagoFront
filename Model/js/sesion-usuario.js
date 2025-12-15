// Model/js/sesion-usuario.js

function initSesionUsuario() {
  const contenedor = document.getElementById("header-usuario");
  if (!contenedor) return;

  const usuario = JSON.parse(localStorage.getItem("usuarioMega"));

  // ❌ NO hay sesión
  if (!usuario) {
    contenedor.innerHTML = `
      <a href="/MegaSantiagoFront/View/pages/login.html" class="link-header">
        Acceder / Registrarse
      </a>
      <a href="/MegaSantiagoFront/View/pages/carrito.html" class="link-header">
        🛒 Carrito
      </a>
    `;
    return;
  }

  // ✅ HAY sesión
  let htmlSesion = `
    <span class="user-name">Hola, ${usuario.email}</span>
  `;

  // 👉 ADMINISTRADOR
  if (usuario.rol === 1) {
    htmlSesion += `
      <a href="/MegaSantiagoFront/admin/dashboard.php" class="link-header">
        📊 Dashboard
      </a>
    `;
  }

  // 👉 VENDEDOR / EMPRESA
  if (usuario.rol === 2) {
    htmlSesion += `
      <a href="/MegaSantiagoFront/empresa/panel.html" class="link-header">
        🏢 Panel empresa
      </a>
    `;
  }

  // 👉 Todos los usuarios logueados ven carrito + salir
  htmlSesion += `
    <a href="/MegaSantiagoFront/View/pages/carrito.html" class="link-header">
      🛒 Carrito
    </a>
    <a href="#" id="logout" class="link-header">
      Salir
    </a>
  `;

  contenedor.innerHTML = htmlSesion;

  const btnLogout = document.getElementById("logout");
  if (btnLogout) {
    btnLogout.addEventListener("click", (e) => {
      e.preventDefault();
      localStorage.removeItem("usuarioMega");
      window.location.href = "/MegaSantiagoFront/index.html";
    });
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initSesionUsuario);
} else {
  initSesionUsuario();
}
