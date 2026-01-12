// View/assets/js/pages/producto.js
(function () {
  const CART_KEY = "carritoMega";

  const API = () => "/Controller/productosController.php";

  function getParam(name) {
    const u = new URL(window.location.href);
    return u.searchParams.get(name);
  }

  function money(n) {
    const x = Number(n || 0);
    return `$${x.toFixed(2)}`;
  }

  function setMsg(text, ok = true) {
    const el = document.getElementById("msg");
    if (!el) return;
    el.textContent = text || "";
    el.className = `msg ${ok ? "ok" : "err"}`;
  }

  // ✅ Manejo de imágenes: URL externa (Firebase) o ruta relativa guardada en BD (imagenes/...)
  function resolverImagen(img) {
    if (!img || String(img).trim() === "") return "/Model/imagenes/sin-imagen.png";
    const limpia = String(img).trim();
    if (/^https?:\/\//i.test(limpia)) return limpia;

    // si viene "imagenes/xxx.jpg" o "imagenes/..."
    if (limpia.startsWith("imagenes/")) return "/Model/" + limpia;

    // si viene solo "xxx.jpg" asumimos /Model/imagenes/xxx.jpg
    return "/Model/imagenes/" + limpia;
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

  function agregarAlCarrito(item) {
    const carrito = obtenerCarrito();

    // si manejas color como variante, lo tomamos en cuenta
    const idx = carrito.findIndex(
      (x) => String(x.id) === String(item.id) && String(x.color || "") === String(item.color || "")
    );

    if (idx >= 0) {
      carrito[idx].cantidad = (carrito[idx].cantidad || 1) + item.cantidad;
    } else {
      carrito.push(item);
    }

    guardarCarrito(carrito);
  }

  function cardRelacionado(prod) {
    const precioBase = Number(prod.precio || 0);
    const precioOferta = prod.precio_oferta != null ? Number(prod.precio_oferta) : null;
    const precioMostrado =
      precioOferta && precioOferta > 0 && precioOferta < precioBase ? precioOferta : precioBase;

    const rutaImagen = resolverImagen(prod.imagen);

    return `
      <article class="card-producto" data-id="${prod.id}" style="cursor:pointer;">
        <div class="img-producto"><img src="${rutaImagen}" alt="${prod.nombre}"></div>
        <p class="nombre-producto">${prod.nombre}</p>
        <p class="precio-actual">${money(precioMostrado)} ${prod.aplica_iva ? "<small>+ IVA</small>" : ""}</p>
      </article>
    `;
  }

  function wireClickCards(container) {
    container.querySelectorAll(".card-producto").forEach((card) => {
      card.addEventListener("click", (e) => {
        if (e.target.closest("button") || e.target.closest("a")) return;
        const id = card.dataset.id;
        window.location.href = `/View/pages/producto.html?id=${encodeURIComponent(id)}`;
      });
    });
  }

  function prepararQty() {
    const inp = document.getElementById("inpQty");
    const btnMenos = document.getElementById("btnMenos");
    const btnMas = document.getElementById("btnMas");

    if (!inp || !btnMenos || !btnMas) return;

    btnMenos.onclick = () => {
      const v = Math.max(1, Number(inp.value || 1) - 1);
      inp.value = v;
    };
    btnMas.onclick = () => {
      const v = Math.max(1, Number(inp.value || 1) + 1);
      inp.value = v;
    };
    inp.addEventListener("change", () => {
      const v = Math.max(1, Number(inp.value || 1));
      inp.value = v;
    });
  }

  // ✅ Relacionados con fallback "Más vendidos"
  // - Si hay relacionados por categoría → título "Productos relacionados"
  // - Si no hay → muestra "Más vendidos" para que la sección no quede vacía
  async function cargarRelacionados(id) {
    const relWrap = document.getElementById("relWrap");
    const grid = document.getElementById("gridRel");
    const titulo = document.getElementById("relTitulo") || relWrap?.querySelector("h2");

    if (!relWrap || !grid) return;

    async function pintar(lista, textoTitulo) {
      const arr = (Array.isArray(lista) ? lista : []).filter(
        (p) => String(p?.id ?? "") !== String(id)
      );

      if (!arr.length) {
        relWrap.style.display = "none";
        return;
      }

      if (titulo) titulo.textContent = textoTitulo;
      relWrap.style.display = "block";
      grid.innerHTML = arr.map(cardRelacionado).join("");
      wireClickCards(grid);
    }

    // 1) Intentar relacionados
    const respRel = await fetch(`${API()}?accion=relacionados&id=${encodeURIComponent(id)}&limit=4`);
    const dataRel = await respRel.json().catch(() => null);

    if (respRel.ok && Array.isArray(dataRel) && dataRel.length) {
      await pintar(dataRel, "Productos relacionados");
      return;
    }

    // 2) Fallback: más vendidos
    const respMV = await fetch(`${API()}?accion=masVendidos&limit=4`);
    const dataMV = await respMV.json().catch(() => null);

    if (respMV.ok && Array.isArray(dataMV) && dataMV.length) {
      await pintar(dataMV, "Más vendidos");
      return;
    }

    relWrap.style.display = "none";
  }

  async function cargarDetalle() {
    const id = Number(getParam("id") || 0);
    const estado = document.getElementById("estado");

    if (!id) {
      if (estado) estado.innerHTML = "<p>Falta el id del producto.</p>";
      return;
    }

    if (estado) estado.innerHTML = "<p>Cargando producto...</p>";

    const resp = await fetch(`${API()}?accion=detalle&id=${encodeURIComponent(id)}`);
    const data = await resp.json().catch(() => null);

    if (!resp.ok || !data || data.error) {
      if (estado) estado.innerHTML = `<p>${(data && data.error) || "No se pudo cargar el producto."}</p>`;
      return;
    }

    const wrap = document.getElementById("productoWrap");
    if (wrap) wrap.style.display = "grid";
    if (estado) estado.innerHTML = "";

    // Info
    document.getElementById("pNombre").textContent = data.nombre || "Producto";
    document.getElementById("pSku").textContent =
      data.sku ? `SKU: ${data.sku} | Categoría: ${data.categoria_nombre}` : `Categoría: ${data.categoria_nombre}`;

    const precioBase = Number(data.precio || 0);
    const precioOferta = data.precio_oferta != null ? Number(data.precio_oferta) : null;
    const precioMostrado =
      precioOferta && precioOferta > 0 && precioOferta < precioBase ? precioOferta : precioBase;

    document.getElementById("pPrecio").innerHTML =
      `${money(precioMostrado)} ${data.aplica_iva ? "<small>+ IVA</small>" : ""}`;

    document.getElementById("pDescCorta").textContent = data.descripcion_corta || "";
    document.getElementById("pDescLarga").innerHTML = (data.descripcion_larga || "").replace(/\n/g, "<br>");

    // Galería
    const imgs = Array.isArray(data.imagenes) && data.imagenes.length
      ? data.imagenes
      : [{ url_imagen: data.imagen }];

    const principal = imgs.find((x) => Number(x.es_principal) === 1) || imgs[0];
    document.getElementById("imgPrincipal").src = resolverImagen(principal?.url_imagen || principal);

    const thumbs = document.getElementById("thumbs");
    thumbs.innerHTML = imgs
      .filter((x) => x && (x.url_imagen || x))
      .map((x, i) => {
        const u = resolverImagen(x.url_imagen || x);
        return `<button type="button" data-url="${u}" aria-label="Imagen ${i + 1}">
                  <img src="${u}" alt="thumb">
                </button>`;
      })
      .join("");

    thumbs.querySelectorAll("button").forEach((b) => {
      b.addEventListener("click", () => {
        document.getElementById("imgPrincipal").src = b.dataset.url;
      });
    });

    // Colores
    const colores = Array.isArray(data.colores) ? data.colores : [];
    const rowColor = document.getElementById("rowColor");
    if (colores.length && rowColor) {
      rowColor.style.display = "flex";
      const sel = document.getElementById("selColor");
      sel.innerHTML = colores.map((c) => `<option value="${c}">${c}</option>`).join("");
    } else if (rowColor) {
      rowColor.style.display = "none";
    }

    // Qty
    prepararQty();

    // Add
    const btnAdd = document.getElementById("btnAdd");
    btnAdd.onclick = () => {
      const inp = document.getElementById("inpQty");
      const qty = Math.max(1, Number(inp.value || 1));

      const colorSel =
        (rowColor && rowColor.style.display !== "none")
          ? document.getElementById("selColor").value
          : "";

      agregarAlCarrito({
        id: data.id,
        nombre: data.nombre,
        precio: precioMostrado,
        cantidad: qty,
        color: colorSel,
      });

      setMsg("Agregado al carrito.", true);
    };

    await cargarRelacionados(id);
  }

  // ✅ init para ejecutar después de loadLayout()
  window.initProductoPage = function () {
    cargarDetalle().catch((e) => {
      console.error(e);
      const estado = document.getElementById("estado");
      if (estado) estado.innerHTML = "<p>Error al cargar el producto.</p>";
    });
  };
})();
