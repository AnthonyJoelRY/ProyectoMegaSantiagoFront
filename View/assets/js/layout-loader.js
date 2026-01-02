// View/js/layout-loader.js
// Carga layouts (header/nav/footer) de forma robusta tanto en local como en hosting (InfinityFree),
// sin depender de una carpeta fija (ej: /MegaSantiagoFront).

(function () {
  function detectBasePath() {
    const p = (window.location.pathname || "/");
    const lower = p.toLowerCase();

    // Si estás en /<base>/View/... -> base = /<base>
    const iView = lower.indexOf("/view/");
    if (iView !== -1) return p.substring(0, iView);

    // Si estás en /<base>/admin/... -> base = /<base>
    const iAdmin = lower.indexOf("/admin/");
    if (iAdmin !== -1) return p.substring(0, iAdmin);

    // Si estás en /<base>/panel/... -> base = /<base>
    const iPanel = lower.indexOf("/panel/");
    if (iPanel !== -1) return p.substring(0, iPanel);

    // Si estás en /<base>/dashboard -> base = /<base>
    const iDash = lower.indexOf("/dashboard");
    if (iDash !== -1) return p.substring(0, iDash);

    // Si estás en /<base>/index.html -> base = /<base>
    const lastSlash = p.lastIndexOf("/");
    if (lastSlash > 0) return p.substring(0, lastSlash);

    // Si estás en raíz -> ""
    return "";
  }

  // Exponer base global para el resto del proyecto
  window.PROJECT_BASE = window.PROJECT_BASE ?? detectBasePath();

  const LAYOUTS_BASE = `${window.PROJECT_BASE}/View/layouts`;

  async function loadInto(id, url) {
    const el = document.getElementById(id);
    if (!el) return;

    const resp = await fetch(url, { cache: "no-cache" });
    if (!resp.ok) throw new Error(`No se pudo cargar ${url}`);

    let html = await resp.text();

    // Reemplaza placeholders {{BASE}} dentro de los layouts (header/nav/footer)
    // para que los links funcionen sin hardcodear /MegaSantiagoFront
    html = html.replace(/\{\{BASE\}\}/g, window.PROJECT_BASE || "");

    el.innerHTML = html;
  }

  function ensureScript(src) {
    if (document.querySelector(`script[src="${src}"]`)) return;
    const s = document.createElement("script");
    s.src = src;
    s.defer = true;
    document.body.appendChild(s);
  }

  async function loadLayout() {
    await Promise.all([
      loadInto("header", `${LAYOUTS_BASE}/header.php`),
      loadInto("nav", `${LAYOUTS_BASE}/nav.php`),
      loadInto("footer", `${LAYOUTS_BASE}/footer.php`),
    ]);

    // Scripts globales (sí se ejecutan)
    // UI del carrito (badge + comportamiento de botones)
    ensureScript(`${window.PROJECT_BASE}/View/assets/js/carrito-ui.js`);
    ensureScript(`${window.PROJECT_BASE}/View/assets/js/busqueda-global.js`);
    ensureScript(`${window.PROJECT_BASE}/View/assets/js/sesion-usuario.js`);

    // Marca activo el menú según URL actual (opcional)
    try {
      const path = window.location.pathname.toLowerCase();
      document.querySelectorAll(".menu a[data-route]").forEach((a) => {
        const route = a.getAttribute("data-route");
        if (route && path.includes(route.toLowerCase())) {
          const li = a.closest("li");
          if (li) li.classList.add("active");
        }
      });
    } catch (e) {}
  }

  // Exponer función global (se usa en los HTML)
  window.loadLayout = loadLayout;
})();
