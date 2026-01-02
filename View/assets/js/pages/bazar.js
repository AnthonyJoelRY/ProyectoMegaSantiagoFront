// View/assets/js/pages/bazar.js
(function () {
  const API_URL = "/Controller/productosController.php";
  const CART_KEY = "carritoMega";

  function resolverImagen(img) {
    if (!img || String(img).trim() === "") return "/Model/imagenes/sin-imagen.png";
    const limpia = String(img).trim();
    if (limpia.startsWith("http://") || limpia.startsWith("https://")) return limpia;
    return "/Model/" + limpia;
  }

  function formatearDinero(valor) {
    return "$" + Number(valor).toFixed(2);
  }

  // Mapea “sección” a keywords (porque no siempre hay columna subcategoria)
  const SECCION_KEYWORDS = {
    globos: ["globo"],
    decoracion: ["decor", "fiesta", "cumple", "banner", "guirnalda"],
    organizadores: ["organizador", "caja", "contenedor", "bandeja"],
    velas: ["vela"],
    regalos: ["regalo", "envol", "moño", "bolsa"],
    detalles: ["detalle", "sorpresa", "tarjeta"],
  };

  function detectarSeccion(prod) {
    const texto = ((prod.nombre || "") + " " + (prod.descripcion || "")).toLowerCase();
    for (const [sec, keys] of Object.entries(SECCION_KEYWORDS)) {
      if (keys.some(k => texto.includes(k))) return sec;
    }
    return "otros";
  }

  function crearTarjetaBazar(prod) {
    const precioBase = parseFloat(prod.precio);
    const precioOferta = prod.precio_oferta ? parseFloat(prod.precio_oferta) : null;
    const precioMostrar = (precioOferta && precioOferta > 0) ? precioOferta : precioBase;

    const rutaImagen = resolverImagen(prod.imagen);
    const seccion = detectarSeccion(prod);

    return `
      <article class="product-card"
        data-id="${prod.id}"
        data-precio="${precioMostrar}"
        data-seccion="${seccion}">
        <div class="product-image">
          <img src="${rutaImagen}" alt="${prod.nombre}">
        </div>
        <h3>${prod.nombre}</h3>
        <p class="product-price">${formatearDinero(precioMostrar)} + IVA</p>
        <button class="btn-add-grid" type="button">Agregar al carrito</button>
      </article>
    `;
  }

  async function cargarProductosBazar() {
    const grid = document.getElementById("gridBazar");
    const texto = document.getElementById("textoResultadosBazar");
    if (!grid || !texto) return;

    grid.innerHTML = "<p>Cargando productos...</p>";

    try {
      const resp = await fetch(`${API_URL}?accion=listarPorCategoria&categoria=bazar`);
      const data = await resp.json();

      if (data.error) {
        grid.innerHTML = `<p>Error: ${data.error}</p>`;
        texto.textContent = "Mostrando 0 resultados";
        return;
      }

      if (!Array.isArray(data) || data.length === 0) {
        grid.innerHTML = "<p>No hay productos disponibles en Bazar.</p>";
        texto.textContent = "Mostrando 0 resultados";
        return;
      }

      grid.innerHTML = data.map(crearTarjetaBazar).join("");
      texto.textContent = `Mostrando ${data.length} resultado${data.length > 1 ? "s" : ""}`;

      inicializarOrden();
      inicializarFiltros();
      inicializarCarrito();
      aplicarFiltroDesdeHash();
    } catch (err) {
      console.error(err);
      grid.innerHTML = "<p>Error al cargar productos.</p>";
      texto.textContent = "Mostrando 0 resultados";
    }
  }

  function inicializarOrden() {
    const selectOrden = document.getElementById("ordenBazar");
    const grid = document.getElementById("gridBazar");
    if (!selectOrden || !grid) return;

    selectOrden.addEventListener("change", () => {
      const opcion = selectOrden.value;
      const cards = Array.from(grid.querySelectorAll(".product-card"));
      if (opcion === "default") return;

      cards.sort((a, b) => {
        const pa = parseFloat(a.dataset.precio);
        const pb = parseFloat(b.dataset.precio);
        return opcion === "precio-asc" ? pa - pb : pb - pa;
      });

      cards.forEach(c => grid.appendChild(c));
    });
  }

  function inicializarFiltros() {
    const grid = document.getElementById("gridBazar");
    const selectSeccion = document.querySelector(".sidebar-select-seccion");
    const rangePrecio = document.querySelector(".sidebar-range");
    const infoPrecio = document.querySelector(".sidebar-range-info");
    const btnFiltrar = document.querySelector(".btn-sidebar");

    if (!grid || !selectSeccion || !rangePrecio || !infoPrecio || !btnFiltrar) return;

    infoPrecio.textContent = "Precio máx: $" + rangePrecio.value;
    rangePrecio.addEventListener("input", () => {
      infoPrecio.textContent = "Precio máx: $" + rangePrecio.value;
    });

    btnFiltrar.addEventListener("click", () => {
      const maxPrecio = parseFloat(rangePrecio.value);
      const secSel = selectSeccion.value;

      Array.from(grid.querySelectorAll(".product-card")).forEach(card => {
        const precio = parseFloat(card.dataset.precio);
        const seccion = card.dataset.seccion;
        const ok = (secSel === "todos" || seccion === secSel) && precio <= maxPrecio;
        card.style.display = ok ? "" : "none";
      });
    });
  }

  function aplicarFiltroDesdeHash() {
    const hash = (window.location.hash || "").replace("#", "").trim();
    if (!hash) return;

    const selectSeccion = document.querySelector(".sidebar-select-seccion");
    const btnFiltrar = document.querySelector(".btn-sidebar");
    if (!selectSeccion || !btnFiltrar) return;

    // si hash coincide con las opciones, selecciona y aplica
    const opciones = Array.from(selectSeccion.options).map(o => o.value);
    if (opciones.includes(hash)) {
      selectSeccion.value = hash;
      btnFiltrar.click();
    }
  }

  function obtenerCarrito() {
    try { return JSON.parse(localStorage.getItem(CART_KEY) || "[]"); }
    catch { return []; }
  }

  function guardarCarrito(carrito) {
    localStorage.setItem(CART_KEY, JSON.stringify(carrito));
    try { window.dispatchEvent(new Event("carrito_actualizado")); } catch (e) {}
    if (typeof window.actualizarContadorCarrito === "function") window.actualizarContadorCarrito();
  }

  function inicializarCarrito() {
    const grid = document.getElementById("gridBazar");
    if (!grid) return;

    grid.addEventListener("click", (e) => {
      const card = e.target.closest(".product-card");
      if (!card) return;

      // Si no fue botón, ir al detalle
      if (!e.target.classList.contains("btn-add-grid")) {
        window.location.href = `/View/pages/producto.html?id=${encodeURIComponent(card.dataset.id)}`;
        return;
      }

      const id = card.dataset.id;
      const nombre = card.querySelector("h3")?.textContent || "";
      const precio = parseFloat(card.dataset.precio);

      const carrito = obtenerCarrito();
      const existe = carrito.find(p => p.id === id);

      if (existe) existe.cantidad++;
      else carrito.push({ id, nombre, precio, cantidad: 1 });

      guardarCarrito(carrito);
      alert("Producto agregado al carrito");
    });
  }

  // ✅ Por si el header usa onclick
  window.realizarBusquedaGlobal = function () {
    const input = document.getElementById("buscador");
    if (!input) return;
    const q = input.value.trim();
    if (!q) return;
    window.location.href = "/View/pages/busqueda.html?q=" + encodeURIComponent(q);
  };

  window.initBazarPage = function () {
    const buscador = document.getElementById("buscador");
    if (buscador) {
      buscador.addEventListener("keyup", (e) => {
        if (e.key === "Enter") window.realizarBusquedaGlobal();
      });
    }
    cargarProductosBazar();
  };
})();
