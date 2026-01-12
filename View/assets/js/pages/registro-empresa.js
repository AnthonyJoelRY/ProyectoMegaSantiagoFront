document.addEventListener("DOMContentLoaded", () => {
  // ✅ verificación
  console.log("registro-empresa.js cargado");
  
  const form = document.getElementById("formRegistroEmpresa");
  if (!form) {
    alert("No se encontró el formulario formRegistroEmpresa");
    return;
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const payload = {
      nombre_legal: document.getElementById("empresa-nombre")?.value.trim() || "",
      ruc: document.getElementById("empresa-ruc")?.value.trim() || "",
      email_empresa: document.getElementById("empresa-correo")?.value.trim() || "",
      telefono: document.getElementById("empresa-telefono")?.value.trim() || "",
      direccion_fiscal: document.getElementById("empresa-direccion")?.value.trim() || "",
      ciudad: document.getElementById("empresa-ciudad")?.value.trim() || "",
      pais: document.getElementById("empresa-pais")?.value.trim() || "Ecuador",
      tipo_negocio: document.getElementById("empresa-tipo")?.value.trim() || "",
      clave: document.getElementById("empresa-clave")?.value.trim() || ""
    };

    // ✅ verificación rápida
    if (!payload.nombre_legal || !payload.ruc || !payload.email_empresa || !payload.telefono || !payload.direccion_fiscal || !payload.clave) {
      alert("Completa todos los campos obligatorios (*)");
      return;
    }

    // ✅ verificación: ver qué envías
    console.log("payload registro empresa:", payload);

    try {
      const resp = await fetch("../../Controller/AuthController.php?accion=registrarEmpresa", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      // Si el servidor devuelve HTML por error, esto lo captura igual
      const text = await resp.text();
      let data = null;
      try { data = JSON.parse(text); } catch (e) {}

      if (!data) {
        alert("Servidor no devolvió JSON. Mira consola (F12) para ver la respuesta.");
        console.log("Respuesta servidor (texto):", text);
        return;
      }

      if (data.error) {
        alert(data.error);
        return;
      }

      alert("Empresa registrada correctamente");
      window.location.href = "/View/pages/login.html";

    } catch (err) {
      alert("Error de red o ruta al servidor.");
      console.log(err);
    }
  });
});
