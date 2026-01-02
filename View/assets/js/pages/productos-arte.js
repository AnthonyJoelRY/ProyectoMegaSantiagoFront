// View/assets/js/pages/productos-arte.js
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

  function detectarTipoDesdeNombre(nombre) {
    const n = (nombre || "").toLowerCase();
    if (n.includes("acril")) return "acrilica";
    if (n.includes("espuma")) return "espuma";
    if (n.includes("marcador") || n.includes("marcadores")) return "marcadores";
    return "otros";
  }

  function crearTarjetaArte(prod) {
    const precioBase = parseFloat(prod.precio);
    const precioOferta = prod.precio_oferta ? parseFloat(prod.precio_oferta) : null;
    const precioMostrar = (precioOferta && precioOferta > 0) ? precioOferta : precioBase;

    const rutaImagen = resolverImagen(prod.imagen);
    const tipo = detectarTipoDesdeNombre(prod.nombre);

    return `
      <article class="product-card"
        data-id="${prod.id}"
        data-precio="${precioMostrar}"
        data-tipo="${tipo}">
        <div class="product-image">
          <img src="${rutaImagen}" alt="${prod.nombre}">
        </div>
        <h3>${prod.nombre}</h3>
        <p class="product-price">${formatearDinero(precioMostrar)} + IVA</p>
        <button class="btn-add-grid" type="button">Agregar al carrito</button>
      </article>
    `;
  }

  async function cargarProductosArte() {
    const grid = document.getElementById("gridArte");
    const texto = document.getElementById("textoResultadosArte");
    if (!grid || !texto) return;

    grid.innerHTML = "<p>Cargando productos...</p>";

    try {
      const resp = await fetch(`${API_URL}?accion=listarPorCategoria&categoria=arte`);
      const data = await resp.json();

      if (data.error) {
        grid.innerHTML = `<p>Error: ${data.error}</p>`;
        texto.textContent = "Mostrando 0 resultados";
        return;
      }

      if (!Array.isArray(data) || data.length === 0) {
        grid.innerHTML = "<p>No hay productos disponibles en Productos de Arte.</p>";
        texto.textContent = "Mostrando 0 resultados";
        return;
      }

      grid.innerHTML = data.map(crearTarjetaArte).join("");
      texto.textContent = `Mostrando ${data.length} resultado${data.length > 1 ? "s" : ""}`;

      inicializarOrdenArte();
      inicializarFiltrosArte();
      inicializarCarritoArte();
    } catch (err) {
      console.error(err);
      grid.innerHTML = "<p>Error al cargar productos.</p>";
      texto.textContent = "Mostrando 0 resultados";
    }
  }

  function inicializarOrdenArte() {
    const selectOrden = document.getElementById("ordenArte");
    const grid = document.getElementById("gridArte");
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

  function inicializarFiltrosArte() {
    const grid = document.getElementById("gridArte");
    const selectTipo = document.querySelector(".sidebar-select-tipo");
    const rangePrecio = document.querySelector(".sidebar-range");
    const infoPrecio = document.querySelector(".sidebar-range-info");
    const btnFiltrar = document.querySelector(".btn-sidebar");

    if (!grid || !selectTipo || !rangePrecio || !infoPrecio || !btnFiltrar) return;

    infoPrecio.textContent = "Precio máx: $" + rangePrecio.value;

    rangePrecio.addEventListener("input", () => {
      infoPrecio.textContent = "Precio máx: $" + rangePrecio.value;
    });

    btnFiltrar.addEventListener("click", () => {
      const tipo = selectTipo.value;
      const maxPrecio = parseFloat(rangePrecio.value);

      Array.from(grid.querySelectorAll(".product-card")).forEach(card => {
        const precio = parseFloat(card.dataset.precio);
        const t = card.dataset.tipo;

        const okTipo = (tipo === "todos" || t === tipo);
        const okPrecio = precio <= maxPrecio;

        card.style.display = (okTipo && okPrecio) ? "" : "none";
      });
    });
  }

  function obtenerCarrito() {
    try { return JSON.parse(localStorage.getItem(CART_KEY) || "[]"); }
    catch { return []; }
  }

  function guardarCarrito(c) {
    localStorage.setItem(CART_KEY, JSON.stringify(c));
    try { window.dispatchEvent(new Event("carrito_actualizado")); } catch (e) {}
    if (typeof window.actualizarContadorCarrito === "function") window.actualizarContadorCarrito();
  }

  function inicializarCarritoArte() {
    const grid = document.getElementById("gridArte");
    if (!grid) return;

    grid.addEventListener("click", (e) => {
      const card = e.target.closest(".product-card");
      if (!card) return;

      // ✅ Si NO fue el botón, ir al detalle
      if (!e.target.classList.contains("btn-add-grid")) {
        window.location.href = `/View/pages/producto.html?id=${encodeURIComponent(card.dataset.id)}`;
        return;
      }

      const id = card.dataset.id;
      const precio = parseFloat(card.dataset.precio);
      const nombre = card.querySelector("h3")?.textContent || "";

      const carrito = obtenerCarrito();
      const existe = carrito.find(p => p.id === id);

      if (existe) existe.cantidad++;
      else carrito.push({ id, nombre, precio, cantidad: 1 });

      guardarCarrito(carrito);
      alert("Producto agregado al carrito");
    });
  }

  // ✅ Por si el header usa onclick="realizarBusquedaGlobal()"
  window.realizarBusquedaGlobal = function () {
    const input = document.getElementById("buscador");
    if (!input) return;
    const q = input.value.trim();
    if (!q) return;
    window.location.href = "/View/pages/busqueda.html?q=" + encodeURIComponent(q);
  };

  // ✅ init para ejecutar tras loadLayout()
  window.initArtePage = function () {
    const buscador = document.getElementById("buscador");
    if (buscador) {
      buscador.addEventListener("keyup", (e) => {
        if (e.key === "Enter") window.realizarBusquedaGlobal();
      });
    }
    cargarProductosArte();
  };
})();
