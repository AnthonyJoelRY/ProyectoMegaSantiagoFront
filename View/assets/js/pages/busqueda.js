// View/assets/js/pages/busqueda.js
(function () {
  const API_URL = "/Controller/productosController.php";
  const CART_KEY = "carritoMega";

  function resolverImagen(img) {
    if (!img || String(img).trim() === "") return "/Model/imagenes/sin-imagen.png";
    const limpia = String(img).trim();
    if (limpia.startsWith("http://") || limpia.startsWith("https://")) return limpia;
    return "/Model/" + limpia;
  }

  function obtenerTerminoBusqueda() {
    const params = new URLSearchParams(window.location.search);
    return params.get("q") || "";
  }

  // Búsqueda dentro de esta página (Enter o botón local si algún día lo agregas)
  function realizarBusquedaLocal() {
    const input = document.getElementById("buscador");
    if (!input) return;

    const termino = input.value.trim();
    if (!termino) return;

    // ✅ En InfinityFree mejor absoluto
    window.location.href = "/View/pages/busqueda.html?q=" + encodeURIComponent(termino);
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

  function agregarAlCarrito(id, nombre, precio) {
    const carrito = obtenerCarrito();
    const existe = carrito.find(p => p.id === id);

    if (existe) existe.cantidad += 1;
    else carrito.push({ id, nombre, precio, cantidad: 1 });

    guardarCarrito(carrito);
  }

  function crearTarjetaProducto(prod) {
    const precioBase = parseFloat(prod.precio);
    const precioOferta = prod.precio_oferta ? parseFloat(prod.precio_oferta) : null;
    const precioMostrar = (precioOferta && precioOferta > 0) ? precioOferta : precioBase;

    const rutaImagen = resolverImagen(prod.imagen);

    const article = document.createElement("article");
    article.className = "product-card";
    article.dataset.id = prod.id;
    article.dataset.precio = precioMostrar.toFixed(2);
    article.style.cursor = "pointer";

    article.innerHTML = `
      <div class="product-image">
        <img src="${rutaImagen}" alt="${prod.nombre}">
      </div>
      <h3>${prod.nombre}</h3>
      <p class="product-price">$${precioMostrar.toFixed(2)} + IVA</p>
      <button class="btn-add-grid" type="button">Agregar al carrito</button>
    `;

    // ✅ Botón agregar
    const btn = article.querySelector(".btn-add-grid");
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      agregarAlCarrito(prod.id, prod.nombre, precioMostrar);
      alert("Producto agregado al carrito");
    });

    // ✅ Click card -> detalle (sin interferir con botón)
    article.addEventListener("click", () => {
      window.location.href = `/View/pages/producto.html?id=${encodeURIComponent(prod.id)}`;
    });

    return article;
  }

  async function cargarResultados() {
    const termino = obtenerTerminoBusqueda();
    const spanTexto = document.getElementById("texto-busqueda");
    const resumen = document.getElementById("resumen-resultados");
    const grid = document.getElementById("grid-resultados");

    if (!spanTexto || !resumen || !grid) return;

    spanTexto.textContent = termino ? "“" + termino + "”" : "";

    if (!termino) {
      resumen.textContent = "Escribe algo en el buscador para comenzar.";
      grid.innerHTML = "";
      return;
    }

    grid.innerHTML = "<p>Cargando resultados...</p>";

    try {
      const resp = await fetch(`${API_URL}?accion=buscar&q=${encodeURIComponent(termino)}`);
      const data = await resp.json();

      if (data.error) {
        resumen.textContent = "Ocurrió un error al buscar.";
        grid.innerHTML = `<p>Error: ${data.error}</p>`;
        return;
      }

      if (!Array.isArray(data) || data.length === 0) {
        resumen.textContent = "No se encontraron productos para este término.";
        grid.innerHTML = "<p>No hay productos disponibles.</p>";
        return;
      }

      resumen.textContent = `Mostrando ${data.length} resultado${data.length > 1 ? "s" : ""}`;
      grid.innerHTML = "";

      data.forEach((prod) => {
        grid.appendChild(crearTarjetaProducto(prod));
      });
    } catch (error) {
      console.error(error);
      resumen.textContent = "Ocurrió un error al buscar.";
      grid.innerHTML = "<p>Error al cargar los resultados.</p>";
    }
  }

  // ✅ init para ejecutar después de loadLayout()
  window.initBusquedaPage = function () {
    const input = document.getElementById("buscador");

    // Setear texto actual en el input
    const terminoActual = obtenerTerminoBusqueda();
    if (input && terminoActual) input.value = terminoActual;

    // Enter: búsqueda local (o puedes llamar realizarBusquedaGlobal si prefieres)
    if (input) {
      input.addEventListener("keyup", (e) => {
        if (e.key === "Enter") {
          // Si quieres usar SIEMPRE la global del header:
          // window.realizarBusquedaGlobal?.();
          realizarBusquedaLocal();
        }
      });
    }

    cargarResultados();
  };
})();
