(function () {
  const API = "/Controller/ResetPasswordController.php";

  const form = document.getElementById("formNuevaClave");
  const msg = document.getElementById("msg");

  function setMsg(t) { msg.textContent = t; }

  function getToken() {
    const params = new URLSearchParams(window.location.search);
    return (params.get("token") || "").trim();
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const token = getToken();
    const password = document.getElementById("password").value;
    const confirm = document.getElementById("confirm").value;

    if (!token) return setMsg("Enlace inválido (sin token).");
    if (password.length < 6) return setMsg("Mínimo 6 caracteres.");
    if (password !== confirm) return setMsg("Las contraseñas no coinciden.");

    const resp = await fetch(API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ token, password, confirm })
    });

    const data = await resp.json().catch(() => null);
    if (!data) return setMsg("Respuesta inválida del servidor.");

    if (data.ok) {
      setMsg(data.msg || "Contraseña actualizada. Ya puedes iniciar sesión.");
      form.reset();
      // opcional: redirigir al login
      // window.location.href = "/View/pages/login.html";
    } else {
      setMsg(data.error || "No se pudo actualizar.");
    }
  });
})();
