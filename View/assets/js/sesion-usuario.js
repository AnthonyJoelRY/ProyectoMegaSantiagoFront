// View/assets/js/sesion-usuario.js

function initSesionUsuario() {
  const contenedor = document.getElementById("header-usuario");
  if (!contenedor) return;

  const base = window.PROJECT_BASE || "";
  const usuario = JSON.parse(localStorage.getItem("usuarioMega") || "null");

  const carritoLink = (extraLinks="") => `
    <a href="${base}/View/pages/carrito.html" class="link-header carrito-link" style="position:relative; display:inline-flex; align-items:center; gap:6px;">
      🛒 <span>Carrito</span>
      <span id="carrito-count"
            style="display:none; position:absolute; top:-6px; right:-10px;
                   min-width:18px; height:18px; padding:0 5px;
                   border-radius:999px; font-size:12px; line-height:18px;
                   text-align:center; background:#e53935; color:#fff;">
        0
      </span>
    </a>
    ${extraLinks}
  `;

  // ❌ NO hay sesión
  if (!usuario) {
    contenedor.innerHTML = `
      <a href="${base}/View/pages/login.html" class="link-header">
        Acceder / Registrarse
      </a>
      ${carritoLink()}
    `;

    // ✅ importante: el badge existe recién después del innerHTML
    actualizarContadorCarrito();
    return;
  }

  // ✅ HAY sesión
  let htmlSesion = `
    <span class="user-name">Hola, ${usuario.email}</span>
  `;

  // 👉 ADMINISTRADOR o EMPLEADO
  // Admin (1): panel completo
  // Empleado (4): panel limitado
  if (usuario.rol === 1 || usuario.rol === 4) {
    htmlSesion += `
      <a href="${base}/dashboard" class="link-header">
        📊 Dashboard
      </a>
    `;
  }

  // 👉 VENDEDOR / EMPRESA
  if (usuario.rol === 2) {
    htmlSesion += `
      <a href="${base}/empresa/panel.html" class="link-header">
        🏢 Panel empresa
      </a>
    `;
  }


  // 👉 Mis pedidos (cualquier rol autenticado)
  if (usuario && usuario.email) {
    htmlSesion += `
      <a href="${base}/mis-pedidos" class="link-header">
        📦 Mis pedidos
      </a>
    `;
  }

  htmlSesion += `
    ${carritoLink(`
      <a href="#" id="logout" class="link-header">Salir</a>
    `)}
  `;

  contenedor.innerHTML = htmlSesion;

  // ✅ actualizar badge ya con el DOM creado
  actualizarContadorCarrito();

  const btnLogout = document.getElementById("logout");
  if (btnLogout) {
    btnLogout.addEventListener("click", (e) => {
      e.preventDefault();
      localStorage.removeItem("usuarioMega");
      window.location.href = `${base}/index.html`;
    });
  }
}

function actualizarContadorCarrito() {
  const badge = document.getElementById("carrito-count");
  if (!badge) return;

  const carrito = JSON.parse(localStorage.getItem("carritoMega") || "[]");
  const total = carrito.reduce((sum, item) => sum + (item.cantidad || 0), 0);

  if (total > 0) {
    badge.textContent = total;
    badge.style.display = "inline-block";
  } else {
    badge.style.display = "none";
  }
}

// ✅ Escuchar cambios del carrito sin recargar
window.addEventListener("carrito_actualizado", () => {
  actualizarContadorCarrito();
});

// ✅ Sincronizar si cambia en otra pestaña
window.addEventListener("storage", (e) => {
  if (e.key === "carritoMega") {
    actualizarContadorCarrito();
  }
});

// Init
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initSesionUsuario);
} else {
  initSesionUsuario();
}
