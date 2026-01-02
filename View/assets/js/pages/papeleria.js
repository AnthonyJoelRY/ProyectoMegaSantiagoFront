// View/assets/js/pages/papeleria.js
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
    if (n.includes("cartulina")) return "cartulina";
    if (n.includes("corrugado")) return "corrugado";
    if (n.includes("papel")) return "papel";
    return "otros";
  }

  function crearTarjetaPapeleria(prod) {
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

  async function cargarProductosPapeleria() {
    const grid = document.getElementById("gridPapeleria");
    const texto = document.getElementById("textoResultadosPapeleria");
    if (!grid || !texto) return;

    grid.innerHTML = "<p>Cargando productos...</p>";

    try {
      const resp = await fetch(`${API_URL}?accion=listarPorCategoria&categoria=papeleria`);
      const data = await resp.json();

      if (data.error) {
        grid.innerHTML = `<p>Error: ${data.error}</p>`;
        texto.textContent = "Mostrando 0 resultados";
        return;
      }

      if (!Array.isArray(data) || data.length === 0) {
        grid.innerHTML = "<p>No hay productos disponibles en Papelería.</p>";
        texto.textContent = "Mostrando 0 resultados";
        return;
      }

      grid.innerHTML = data.map(crearTarjetaPapeleria).join("");
      texto.textContent = `Mostrando ${data.length} resultado${data.length > 1 ? "s" : ""}`;

      inicializarOrdenPapeleria();
      inicializarFiltrosPapeleria();
      inicializarCarritoPapeleria();
    } catch (err) {
      console.error(err);
      grid.innerHTML = "<p>Error al cargar productos.</p>";
      texto.textContent = "Mostrando 0 resultados";
    }
  }

  function inicializarOrdenPapeleria() {
    const selectOrden = document.getElementById("ordenPapeleria");
    const grid = document.getElementById("gridPapeleria");
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

  function inicializarFiltrosPapeleria() {
    const grid = document.getElementById("gridPapeleria");
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
      const maxPrecio = parseFloat(rangePrecio.value);
      const tipoSel = selectTipo.value;

      Array.from(grid.querySelectorAll(".product-card")).forEach(card => {
        const precio = parseFloat(card.dataset.precio);
        const tipo = card.dataset.tipo;
        const ok = (tipoSel === "todos" || tipo === tipoSel) && precio <= maxPrecio;
        card.style.display = ok ? "" : "none";
      });
    });
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

  function inicializarCarritoPapeleria() {
    const grid = document.getElementById("gridPapeleria");
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

  // ✅ init para ejecutar tras loadLayout()
  window.initPapeleriaPage = function () {
    const buscador = document.getElementById("buscador");
    if (buscador) {
      buscador.addEventListener("keyup", (e) => {
        if (e.key === "Enter") window.realizarBusquedaGlobal();
      });
    }
    cargarProductosPapeleria();
  };
})();
