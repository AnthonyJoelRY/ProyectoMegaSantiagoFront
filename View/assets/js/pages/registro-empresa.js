// View/assets/js/pages/registro-empresa.js
(function () {
  function togglePasswordButtons() {
    document.querySelectorAll(".btn-eye").forEach((btn) => {
      btn.addEventListener("click", () => {
        const targetId = btn.getAttribute("data-target");
        const input = document.getElementById(targetId);
        if (!input) return;
        input.type = input.type === "password" ? "text" : "password";
      });
    });
  }

  function initForm() {
    const form = document.getElementById("formRegistroEmpresa");
    if (!form) return;

    form.addEventListener("submit", (e) => {
      e.preventDefault();

      // ✅ Demo (igual que tu versión actual)
      // Aquí luego conectas a tu Controller si vas a guardar en BD
      alert("Registro de empresa (demo) realizado. Aquí iría el guardado real en base de datos.");
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    togglePasswordButtons();
    initForm();
  });
})();
