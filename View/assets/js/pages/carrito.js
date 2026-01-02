// View/assets/js/pages/carrito.js
(function () {
  const CART_KEY = "carritoMega";
  const PAYPAL_API = "/Controller/paypalController.php";
  let paypalSDKLoaded = false;

  function obtenerCarrito() {
    try { return JSON.parse(localStorage.getItem(CART_KEY) || "[]"); }
    catch { return []; }
  }

  function guardarCarrito(carrito) {
    localStorage.setItem(CART_KEY, JSON.stringify(carrito));
    try { window.dispatchEvent(new Event("carrito_actualizado")); } catch (e) {}
    if (typeof window.actualizarContadorCarrito === "function") window.actualizarContadorCarrito();
  }

  function formatearDinero(valor) {
    return "$" + Number(valor).toFixed(2);
  }

  function setStatus(msg, isError = false) {
    const el = document.getElementById("paypal-status");
    if (!el) return;
    el.textContent = msg || "";
    el.style.color = isError ? "crimson" : "inherit";
  }

  function renderCarrito() {
    const tbody = document.getElementById("tbodyCarrito");
    const divVacio = document.getElementById("cart-vacio");
    const divContenido = document.getElementById("cart-contenido");
    const spanTotal = document.getElementById("totalCarrito");

    if (!tbody || !divVacio || !divContenido || !spanTotal) return;

    const carrito = obtenerCarrito();
    tbody.innerHTML = "";

    if (carrito.length === 0) {
      divVacio.style.display = "block";
      divContenido.style.display = "none";
      spanTotal.textContent = "$0.00";
      return;
    }

    divVacio.style.display = "none";
    divContenido.style.display = "block";

    let total = 0;

    carrito.forEach((item) => {
      const subtotal = item.precio * item.cantidad;
      total += subtotal;

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${item.nombre}</td>
        <td>${formatearDinero(item.precio)}</td>
        <td>
          <input type="number" min="1" value="${item.cantidad}"
                class="cart-cantidad" data-id="${item.id}">
        </td>
        <td>${formatearDinero(subtotal)}</td>
        <td>
          <button class="btn-cart-delete" data-id="${item.id}">✕</button>
        </td>
      `;
      tbody.appendChild(tr);
    });

    spanTotal.textContent = formatearDinero(total);
  }

  // ---------- Eventos UI (cantidad / eliminar / vaciar) ----------
  function bindEventosCarrito() {
    // Cambiar cantidad
    document.addEventListener("change", (e) => {
      if (!e.target.classList.contains("cart-cantidad")) return;

      const id = e.target.getAttribute("data-id");
      let nuevaCantidad = parseInt(e.target.value, 10);
      if (isNaN(nuevaCantidad) || nuevaCantidad < 1) nuevaCantidad = 1;
      e.target.value = nuevaCantidad;

      const carrito = obtenerCarrito();
      const item = carrito.find(p => String(p.id) === String(id));
      if (item) {
        item.cantidad = nuevaCantidad;
        guardarCarrito(carrito);
        renderCarrito();
        cargarPayPalSDK();
      }
    });

    // Eliminar producto
    document.addEventListener("click", (e) => {
      if (!e.target.classList.contains("btn-cart-delete")) return;

      const id = e.target.getAttribute("data-id");
      let carrito = obtenerCarrito();
      carrito = carrito.filter(p => String(p.id) !== String(id));

      guardarCarrito(carrito);
      renderCarrito();
      cargarPayPalSDK();
    });

    // Vaciar carrito
    const btnVaciar = document.getElementById("btnVaciar");
    if (btnVaciar) {
      btnVaciar.addEventListener("click", () => {
        if (!confirm("¿Seguro que quieres vaciar el carrito?")) return;

        localStorage.removeItem(CART_KEY);
        try { window.dispatchEvent(new Event("carrito_actualizado")); } catch (e) {}
        if (typeof window.actualizarContadorCarrito === "function") window.actualizarContadorCarrito();

        renderCarrito();
        cargarPayPalSDK();
      });
    }
  }

  // ---------- PayPal ----------
  async function cargarPayPalSDK() {
    const carrito = obtenerCarrito();
    if (!carrito.length) {
      const cont = document.getElementById("paypal-button-container");
      if (cont) cont.innerHTML = "";
      setStatus("");
      return;
    }

    if (paypalSDKLoaded && window.paypal) {
      renderPayPalButtons();
      return;
    }

    const res = await fetch(PAYPAL_API + "?accion=config");
    const cfg = await res.json();

    if (!cfg.clientId || cfg.clientId === "PAYPAL_SANDBOX_CLIENT_ID") {
      setStatus("⚠️ Falta configurar PayPal Sandbox (clientId/secret) en Model/paypal_credentials.php", true);
      return;
    }

    const s = document.createElement("script");
    s.src =
      "https://www.paypal.com/sdk/js?client-id=" +
      encodeURIComponent(cfg.clientId) +
      "&currency=" +
      encodeURIComponent(cfg.currency || "USD") +
      "&intent=capture";

    s.onload = () => {
      paypalSDKLoaded = true;
      renderPayPalButtons();
    };
    s.onerror = () => setStatus("No se pudo cargar el SDK de PayPal.", true);
    document.head.appendChild(s);
  }

  function cartParaServidor() {
    const carrito = obtenerCarrito();
    return carrito.map(it => ({ id: it.id, cantidad: it.cantidad }));
  }

  function renderPayPalButtons() {
    if (!window.paypal) {
      setStatus("PayPal SDK no disponible.", true);
      return;
    }

    const cont = document.getElementById("paypal-button-container");
    if (cont) cont.innerHTML = "";

    paypal.Buttons({
      createOrder: async function () {
        setStatus("Creando orden en PayPal...");

        const resp = await fetch(PAYPAL_API + "?accion=create-order", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ cart: cartParaServidor() }),
        });

        const data = await resp.json();
        if (!resp.ok) {
          setStatus(data.error || "Error al crear la orden.", true);
          throw new Error(data.error || "create-order failed");
        }

        setStatus("");
        return data.id;
      },

      onApprove: async function (data) {
        setStatus("Capturando pago...");

        const __user = JSON.parse(localStorage.getItem("usuarioMega") || "null");
        const __idUsuario =
          (__user && (__user.id_usuario || __user.id)) ? (__user.id_usuario || __user.id) : 0;

        if (!__idUsuario) {
          setStatus("Debes iniciar sesión para completar la compra.", true);
          return;
        }

        const resp = await fetch(PAYPAL_API + "?accion=capture-order", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            orderId: data.orderID,
            cart: obtenerCarrito(),
            id_usuario: __idUsuario
          }),
        });

        const details = await resp.json();
        if (!resp.ok) {
          setStatus(details.error || "Error al capturar el pago.", true);
          return;
        }

        const status = (details && (details.status || details.paypal_status)) || "";
        const ok = !!(details && (details.ok === true || status === "COMPLETED"));

        if (ok) {
          setStatus("Pago completado. Gracias por tu compra.");

          localStorage.removeItem(CART_KEY);
          try { window.dispatchEvent(new Event("carrito_actualizado")); } catch (e) {}
          if (typeof window.actualizarContadorCarrito === "function") window.actualizarContadorCarrito();

          renderCarrito();
          cargarPayPalSDK();
        } else {
          setStatus("Pago procesado con estado: " + (status || "desconocido"));
        }
      },

      onCancel: function () {
        setStatus("Pago cancelado.");
      },

      onError: function (err) {
        console.error(err);
        setStatus("Ocurrió un error con PayPal.", true);
      },
    }).render("#paypal-button-container");
  }

  // ✅ init para ejecutar tras loadLayout()
  window.initCarritoPage = function () {
    bindEventosCarrito();
    renderCarrito();
    cargarPayPalSDK();
  };
})();
