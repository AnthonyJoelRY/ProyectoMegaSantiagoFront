// View/assets/js/pages/recuperar-clave.js
(function () {

  function initLayout() {
    if (typeof loadLayout === "function") {
      loadLayout();
    }
  }

  function initForm() {
    const form = document.getElementById("form-recuperar");
    if (!form) return;

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const email = document.getElementById("email").value.trim();
      if (!email) return;

      // ✅ Demo (no rompe nada, luego conectas backend)
      alert(
        "Si este correo existe, recibirás instrucciones para recuperar tu contraseña."
      );
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    initLayout();
    initForm();
  });

})();
