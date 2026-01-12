// View/assets/js/pages/login.js
(function () {
  const API = "/Controller/AuthController.php";

  function bindPasswordEyeButtons() {
    document.querySelectorAll(".btn-eye").forEach(btn => {
      btn.addEventListener("click", () => {
        const targetId = btn.getAttribute("data-target");
        const input = document.getElementById(targetId);
        if (!input) return;
        input.type = (input.type === "password") ? "text" : "password";
      });
    });
  }

  async function fetchJSON(url, payload) {
    const resp = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    const data = await resp.json().catch(() => null);
    if (!data) return { error: "Respuesta inválida del servidor." };
    return data;
  }

  function bindLoginForm() {
    const form = document.getElementById("formLogin");
    if (!form) return;

    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const email = document.getElementById("login-email")?.value.trim() || "";
      const clave = document.getElementById("login-clave")?.value.trim() || "";

      try {
        const data = await fetchJSON(`${API}?accion=login`, { email, clave });

        if (data.error) {
          alert(data.error);
          return;
        }

        if (!data.exito || !data.usuario) {
          alert("Respuesta inválida del servidor.");
          return;
        }

        localStorage.setItem("usuarioMega", JSON.stringify(data.usuario));
        window.location.href = "/index.html";
      } catch (err) {
        console.error(err);
        alert("No se pudo conectar con el servidor.");
      }
    });
  }

  function bindRegistroForm() {
    const form = document.getElementById("formRegistro");
    if (!form) return;

    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const nombre   = document.getElementById("reg-nombre")?.value.trim() || "";
      const apellido = document.getElementById("reg-apellido")?.value.trim() || "";
      const email    = document.getElementById("reg-correo")?.value.trim() || "";
      const clave    = document.getElementById("reg-clave")?.value.trim() || "";

      if (!nombre || !apellido || !email || !clave) {
        alert("Completa todos los campos obligatorios.");
        return;
      }

      try {
        // ✅ AHORA enviamos nombre + apellido también
        const data = await fetchJSON(`${API}?accion=registrar`, {
          nombre,
          apellido,
          email,
          clave
        });

        if (data.error) {
          alert(data.error);
          return;
        }

        if (!data.exito) {
          alert("No se pudo crear la cuenta.");
          return;
        }

        alert("Cuenta creada exitosamente, ahora puedes iniciar sesión.");

        const loginEmail = document.getElementById("login-email");
        const loginClave = document.getElementById("login-clave");
        if (loginEmail) loginEmail.value = email;
        if (loginClave) loginClave.value = "";

        // (Opcional) limpiar registro
        document.getElementById("reg-nombre").value = "";
        document.getElementById("reg-apellido").value = "";
        document.getElementById("reg-correo").value = "";
        document.getElementById("reg-clave").value = "";

        window.scrollTo({ top: 0, behavior: "smooth" });
      } catch (err) {
        console.error(err);
        alert("No se pudo conectar con el servidor.");
      }
    });
  }

  // ✅ init para ejecutar tras loadLayout()
  window.initLoginPage = function () {
    bindPasswordEyeButtons();
    bindLoginForm();
    bindRegistroForm();
  };
})();
