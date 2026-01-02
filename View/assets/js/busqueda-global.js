// View/assets/js/busqueda-global.js
function realizarBusquedaGlobal() {
  const input = document.getElementById("buscador");
  if (!input) return;

  const termino = input.value.trim();
  if (!termino) return;

  const base = window.PROJECT_BASE || "";
  window.location.href =
    `${base}/View/pages/busqueda.html?q=` + encodeURIComponent(termino);
}
