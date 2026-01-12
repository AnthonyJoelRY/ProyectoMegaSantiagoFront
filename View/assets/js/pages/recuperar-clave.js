(function () {
  console.log("[recuperar-clave] cargado ✅");

  const form = document.getElementById("form-recuperar");
  const emailInput = document.getElementById("email");

  if (!form) {
    console.error("[recuperar-clave] NO encuentro #form-recuperar");
    return;
  }
  if (!emailInput) {
    console.error("[recuperar-clave] NO encuentro #email");
    return;
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    console.log("[recuperar-clave] submit ✅");

    const email = emailInput.value.trim();
    console.log("[recuperar-clave] email:", email);

    try {
      const resp = await fetch("/Controller/RecuperarClaveController.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email })
      });

      console.log("[recuperar-clave] status:", resp.status);

      const text = await resp.text();
      console.log("[recuperar-clave] raw:", text);

      let data = null;
      try { data = JSON.parse(text); } catch (_) {}

      if (data?.ok) {
        alert("Si el correo existe, te enviamos instrucciones.");
      } else {
        alert((data && data.error) ? data.error : "Error: respuesta inválida del servidor");
      }

    } catch (err) {
      console.error("[recuperar-clave] fetch error:", err);
      alert("No se pudo conectar al servidor.");
    }
  });
})();
