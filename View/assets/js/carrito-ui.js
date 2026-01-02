// View/assets/js/carrito-ui.js
// Helpers de UI para el carrito:
// - Si el producto ya está en el carrito, el botón cambia a "Ver en carrito".
// - Al hacer click:
//     * si ya está en el carrito -> redirige a carrito.html
//     * si no está -> lo agrega y actualiza botón + contador

(function () {
  const CART_KEY = "carritoMega";

  function getBase() {
    return window.PROJECT_BASE || "";
  }

  function getCart() {
    try {
      return JSON.parse(localStorage.getItem(CART_KEY) || "[]");
    } catch (e) {
      return [];
    }
  }

  function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    // notificar a header/otros módulos
    try {
      window.dispatchEvent(new Event("carrito_actualizado"));
    } catch (e) {}
    if (typeof window.actualizarContadorCarrito === "function") {
      try {
        window.actualizarContadorCarrito();
      } catch (e) {}
    }
  }

  function isInCart(productId) {
    const cart = getCart();
    return cart.some((it) => String(it.id) === String(productId));
  }

  function getProductIdFromButton(btn) {
    const card = btn.closest("[data-id]");
    if (card && card.dataset && card.dataset.id) return card.dataset.id;
    // fallback: data-producto-id
    if (btn.dataset && btn.dataset.id) return btn.dataset.id;
    if (btn.dataset && btn.dataset.productoId) return btn.dataset.productoId;

    // ✅ fallback para la página de detalle (producto.html?id=...)
    // En esa vista, a veces el botón no queda dentro de un contenedor con data-id.
    if (btn && btn.id === "btnAdd") {
      try {
        const sp = new URLSearchParams(window.location.search);
        const idFromUrl = sp.get("id");
        if (idFromUrl) return idFromUrl;
      } catch (e) {}
    }
    return null;
  }

  function getProductName(btn) {
    const card = btn.closest("[data-id]");
    if (!card) {
      // fallback detalle
      // producto.html usa estos ids
      if (btn && btn.id === "btnAdd") {
        const el = document.getElementById("pNombre");
        if (el && el.textContent) return el.textContent.trim();
      }
      const h1 = document.querySelector(".product-info h1, .product-info .prod-title, h1");
      if (h1 && h1.textContent) return h1.textContent.trim();
      return "Producto";
    }
    if (card.dataset && card.dataset.nombre) return String(card.dataset.nombre);
    const h3 = card.querySelector("h3");
    if (h3 && h3.textContent) return h3.textContent.trim();
    const n = card.querySelector(".nombre-producto");
    if (n && n.textContent) return n.textContent.trim();
    return "Producto";
  }

  function getProductPrice(btn) {
    const card = btn.closest("[data-id]");
    if (card && card.dataset && card.dataset.precio != null) {
      const p = parseFloat(card.dataset.precio);
      return Number.isFinite(p) ? p : 0;
    }
    if (btn.dataset && btn.dataset.precio != null) {
      const p = parseFloat(btn.dataset.precio);
      return Number.isFinite(p) ? p : 0;
    }

    // fallback detalle
    // producto.html usa #pPrecio (ej: "$1.03 + IVA")
    if (btn && btn.id === "btnAdd") {
      const el = document.getElementById("pPrecio");
      if (el && el.textContent) {
        const raw = el.textContent
          .replace(/[^0-9.,]/g, "")
          .replace(",", ".");
        const num = parseFloat(raw);
        return Number.isFinite(num) ? num : 0;
      }
    }
    const priceEl = document.querySelector(".product-info .prod-price, .product-info .precio, .product-info strong");
    if (priceEl && priceEl.textContent) {
      const num = parseFloat(priceEl.textContent.replace(/[^0-9.,]/g, "").replace(",", "."));
      return Number.isFinite(num) ? num : 0;
    }
    return 0;
  }

  // =========================
  // Contexto: página de detalle (producto.html)
  // - Cantidad: #inpQty
  // - Color (opcional): #selColor (si existe)
  // =========================
  function getQtyFromContext(btn) {
    if (btn && btn.id === "btnAdd") {
      const inp = document.getElementById("inpQty");
      const v = inp ? parseInt(inp.value, 10) : 1;
      return Number.isFinite(v) && v > 0 ? v : 1;
    }
    return 1;
  }

  function getColorFromContext(btn) {
    if (btn && btn.id === "btnAdd") {
      const sel = document.getElementById("selColor");
      if (!sel) return "";
      const val = String(sel.value || "").trim();
      // si hay selector pero está en "Elige una opción", lo tratamos como vacío
      if (!val || val.toLowerCase().includes("elige")) return "";
      return val;
    }
    return "";
  }

  function goToCart() {
    window.location.href = `${getBase()}/View/pages/carrito.html`;
  }

  function setButtonState(btn, inCart) {
    if (!btn) return;

    // Guardar texto original una sola vez
    if (!btn.dataset.originalText) {
      btn.dataset.originalText = (btn.textContent || "").trim();
    }

    const isPlusBtn = btn.classList.contains("btn-add"); // botón circular "+"
    if (inCart) {
      // Para el botón "+" usamos texto corto para no romper el layout
      btn.textContent = isPlusBtn ? "🛒" : "Ver en carrito";
      btn.setAttribute("aria-label", "Ver en carrito");
      btn.title = "Ver en carrito";
      btn.dataset.inCart = "1";
    } else {
      btn.textContent = btn.dataset.originalText || (isPlusBtn ? "+" : "Agregar al carrito");
      btn.removeAttribute("aria-label");
      btn.title = "";
      btn.dataset.inCart = "0";
    }
  }

  function syncAllButtons() {
    // Botones “Agregar al carrito” en grids/categorías
    document.querySelectorAll(".btn-best, .btn-add, .btn-add-grid, #btnAdd").forEach((btn) => {
      const id = getProductIdFromButton(btn);
      if (!id) return;
      setButtonState(btn, isInCart(id));
    });
  }

  function addToCartFromButton(btn) {
    const id = getProductIdFromButton(btn);
    if (!id) return;

    // Si ya está, ir al carrito (SIN confirmar)
    if (isInCart(id)) {
      goToCart();
      return;
    }

    const nombre = getProductName(btn);
    const precio = getProductPrice(btn);

    // Cantidad / opciones (solo aplica en producto.html)
    const qty = getQtyFromContext(btn);
    const color = getColorFromContext(btn);

    // Confirmación (OK = aceptar, Cancel = cancelar)
    const extra = color ? ` (Color: ${color})` : "";
    if (!confirm(`¿Estás seguro de añadir "${nombre}" x${qty}${extra} al carrito?`)) {
      return;
    }

    const cart = getCart();
    // Si el producto tiene opción (color), lo tratamos como ítem distinto por color
    const idx = cart.findIndex((x) => {
      const sameId = String(x.id) === String(id);
      const sameColor = String(x.color || "") === String(color || "");
      return sameId && sameColor;
    });
    if (idx >= 0) {
      cart[idx].cantidad = (cart[idx].cantidad || 1) + qty;
    } else {
      const item = { id, nombre, precio, cantidad: qty };
      if (color) item.color = color;
      cart.push(item);
    }
    saveCart(cart);

    // Cambiar el botón a “Ver en carrito” inmediatamente
    setButtonState(btn, true);

    // Si existe un mensaje en la página de detalle, mostrarlo
    if (typeof window.setMsg === "function") {
      try {
        window.setMsg("Producto agregado al carrito.");
      } catch (e) {}
    }
  }

  // =========================
  // Event delegation (captura) para no romper listeners existentes
  // =========================
  document.addEventListener(
    "click",
    (e) => {
      const btn = e.target.closest(".btn-best, .btn-add, .btn-add-grid, #btnAdd");
      if (!btn) return;

      // Evitar que se ejecute el handler previo de cada página
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      addToCartFromButton(btn);
    },
    true
  );

  // Re-sincronizar cuando:
  // - cambie el carrito (evento)
  // - se actualice en otra pestaña
  window.addEventListener("carrito_actualizado", syncAllButtons);
  window.addEventListener("storage", (e) => {
    if (e.key === CART_KEY) syncAllButtons();
  });

  // Observar cambios en el DOM (porque las cards se renderizan con fetch)
  let t = null;
  const obs = new MutationObserver(() => {
    clearTimeout(t);
    t = setTimeout(syncAllButtons, 50);
  });
  obs.observe(document.documentElement, { childList: true, subtree: true });

  // Inicial
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", syncAllButtons);
  } else {
    syncAllButtons();
  }
})();
