// View/assets/js/pages/carrito.js
(function () {
  const CART_KEY = "carritoMega";
  const PAYPAL_API = "/Controller/paypalController.php";
  const CHECKOUT_API = "/Controller/CheckoutController.php";
  let paypalSDKLoaded = false;
  let __paypalSupportsServerSide = true; // viene de PAYPAL_API?accion=config

  // Estado de checkout resuelto para enviar al servidor al capturar pago
  let __checkoutResolved = {
    tipo_entrega: "envio",            // 'retiro_local' | 'envio'
    id_sucursal_retiro: null,          // si retiro_local
    id_sucursal_origen: null,          // si envio (la sucursal que tiene stock)
    id_direccion_envio: null,          // si el usuario selecciona una dirección guardada
    direccion_nueva: null              // si el usuario escribe una dirección nueva
  };

  let __direccionesCache = [];
  let __sucursalesCache = [];

  // ✅ Restricción de entrega: solo provincia Loja
  const PROVINCIA_PERMITIDA = "Loja";
  const CIUDAD_PERMITIDA = "Loja";


  function normTxt(s) {
    return String(s || "").trim().toLowerCase();
  }

  function esProvinciaLoja(value) {
    return normTxt(value) === normTxt(PROVINCIA_PERMITIDA);
  }

  function esCiudadLoja(value) {
    return normTxt(value) === normTxt(CIUDAD_PERMITIDA);
  }

  function forzarProvinciaLojaEnUI() {
    const iProv = document.getElementById("dir_provincia");
    if (iProv) {
      // Evita que el usuario ingrese otra provincia
      iProv.value = PROVINCIA_PERMITIDA;
      iProv.readOnly = true;
    }
  }

  function forzarCiudadLojaEnUI() {
    const iCiudad = document.getElementById("dir_ciudad");
    if (iCiudad) {
      iCiudad.value = CIUDAD_PERMITIDA;
      iCiudad.readOnly = true;
      // Por si el navegador/autofill intenta cambiarlo
      iCiudad.addEventListener("input", () => {
        iCiudad.value = CIUDAD_PERMITIDA;
      });
    }
  }

  function validarDireccionSoloLojaEnFront() {
    // Solo aplica si es envío
    const tipo = document.querySelector("input[name='tipo_entrega']:checked")?.value || "envio";
    if (tipo !== "envio") return true;

    // Si eligió dirección guardada, validamos la provincia de esa dirección
    const selDir = document.getElementById("selectDireccionEnvio");
    const idDir = selDir && selDir.value ? Number(selDir.value) : 0;
    if (idDir) {
      const d = __direccionesCache.find(x => Number(x.id_direccion) === idDir);
      const prov = d ? (d.provincia || "") : "";
      if (prov && !esProvinciaLoja(prov)) {
        setStatus(`Solo se permiten direcciones dentro de ${PROVINCIA_PERMITIDA}.`, true);
        return false;
      }

      const ciudad = d ? (d.ciudad || "") : "";
      if (ciudad && !esCiudadLoja(ciudad)) {
        setStatus(`Solo se permiten direcciones dentro de ${CIUDAD_PERMITIDA}.`, true);
        return false;
      }
      return true;
    }

    // Dirección nueva escrita: validar provincia
    const prov = (document.getElementById("dir_provincia")?.value || "").trim();
    if (prov && !esProvinciaLoja(prov)) {
      setStatus(`Solo se permiten direcciones dentro de ${PROVINCIA_PERMITIDA}.`, true);
      return false;
    }
    const ciudad = (document.getElementById("dir_ciudad")?.value || "").trim();
    if (ciudad && !esCiudadLoja(ciudad)) {
      setStatus(`Solo se permiten direcciones dentro de ${CIUDAD_PERMITIDA}.`, true);
      return false;
    }
    return true;
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

  function formatearDinero(valor) {
    return "$" + Number(valor).toFixed(2);
  }

  // ---------- Checkout UI (retiro / envío) ----------
  function bindCheckoutRadios() {
    const radios = document.querySelectorAll("input[name='tipo_entrega']");
    const divRetiro = document.getElementById("checkout-retiro");
    const divEnvio = document.getElementById("checkout-envio");

    function aplicar(tipo) {
      const t = tipo || (document.querySelector("input[name='tipo_entrega']:checked")?.value || "envio");
      __checkoutResolved.tipo_entrega = t;

      if (divRetiro) divRetiro.style.display = (t === "retiro_local") ? "block" : "none";
      if (divEnvio) divEnvio.style.display = (t === "envio") ? "block" : "none";
    }

    radios.forEach(r => r.addEventListener("change", (e) => aplicar(e.target.value)));
    aplicar();

    // ✅ En envío, fijamos provincia Loja en el input
    forzarProvinciaLojaEnUI();
    forzarCiudadLojaEnUI();

    // Dirección guardada -> autocompletar inputs
    const selDir = document.getElementById("selectDireccionEnvio");
    if (selDir) {
      selDir.addEventListener("change", () => {
        const id = selDir.value ? Number(selDir.value) : 0;
        const d = __direccionesCache.find(x => Number(x.id_direccion) === id);
        if (!d) return;

        const iDir = document.getElementById("dir_direccion");
        const iCiudad = document.getElementById("dir_ciudad");
        const iProv = document.getElementById("dir_provincia");
        const iCod = document.getElementById("dir_codigo");
        const iRef = document.getElementById("dir_referencia");

        if (iDir) iDir.value = d.direccion || "";
        if (iCiudad) iCiudad.value = d.ciudad || "";
        if (iProv) iProv.value = d.provincia || "";
        if (iCod) iCod.value = d.codigo_postal || "";
        if (iRef) iRef.value = d.referencia || "";
      });
    }

    // ✅ Si el usuario intenta editar provincia (por autofill), lo forzamos a Loja
    const iProv = document.getElementById("dir_provincia");
    if (iProv) {
      iProv.addEventListener("input", () => {
        if (!esProvinciaLoja(iProv.value)) iProv.value = PROVINCIA_PERMITIDA;
      });
    }
  }

  // ✅ helper: setear mensaje en el select de sucursal
  function setSelectSucursalMensaje(msg) {
    const selSuc = document.getElementById("selectSucursalRetiro");
    if (!selSuc) return;
    selSuc.innerHTML = "";
    selSuc.insertAdjacentHTML("beforeend", `<option value="">${msg}</option>`);
  }

  async function cargarCheckoutData(idUsuario) {
    // ✅ mostrar "cargando" aunque no haya usuario
    setSelectSucursalMensaje("Cargando sucursales...");

    try {
      const id = Number(idUsuario) || 0;

      // 1) Intento principal: POST JSON
      let resp = await fetch(CHECKOUT_API + "?accion=data", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id_usuario: id })
      });

      let data = await resp.json().catch(() => null);

      // 2) Fallback: algunos hostings bloquean POST JSON -> probamos GET
      if (!resp.ok || !data || data.ok !== true) {
        resp = await fetch(CHECKOUT_API + "?accion=data&id_usuario=" + encodeURIComponent(id), {
          method: "GET",
          headers: { "Accept": "application/json" }
        });
        data = await resp.json().catch(() => null);
      }

      if (!resp.ok || !data || data.ok !== true) {
        console.warn("[checkout:data] error", { status: resp.status, data });
        setSelectSucursalMensaje("No se pudieron cargar sucursales");
        return;
      }

      __sucursalesCache = Array.isArray(data.sucursales) ? data.sucursales : [];
      // ✅ Solo direcciones dentro de Loja (provincia)
      __direccionesCache = Array.isArray(data.direcciones) ? data.direcciones : [];
      __direccionesCache = __direccionesCache.filter(d => {
        const prov = (d && (d.provincia ?? "")) ? String(d.provincia) : "";
        // Si no trae provincia, no bloqueamos (compatibilidad), pero preferimos Loja.
        return !prov ? true : esProvinciaLoja(prov);
      });

      // Sucursales (retiro)
      const selSuc = document.getElementById("selectSucursalRetiro");
      if (selSuc) {
        selSuc.innerHTML = "";
        selSuc.insertAdjacentHTML("beforeend", `<option value="">Selecciona una sucursal</option>`);

        if (__sucursalesCache.length === 0) {
          selSuc.insertAdjacentHTML("beforeend", `<option value="">No hay sucursales activas</option>`);
        } else {
          __sucursalesCache.forEach(s => {
            selSuc.insertAdjacentHTML(
              "beforeend",
              `<option value="${s.id_sucursal}">${s.nombre} - ${s.ciudad}</option>`
            );
          });
        }
      }

      // Direcciones guardadas (envío)
      const selDir = document.getElementById("selectDireccionEnvio");
      if (selDir) {
        selDir.innerHTML = "";
        selDir.insertAdjacentHTML("beforeend", `<option value="">(Opcional) Selecciona una dirección guardada</option>`);
        __direccionesCache.forEach(d => {
          const label = `${d.direccion || ""}${d.ciudad ? " - " + d.ciudad : ""}`;
          selDir.insertAdjacentHTML("beforeend", `<option value="${d.id_direccion}">${label}</option>`);
        });
      }

      // ✅ Asegurar provincia Loja en UI
      forzarProvinciaLojaEnUI();
    forzarCiudadLojaEnUI();
    } catch (e) {
      console.warn("No se pudo cargar data de checkout", e);
      setSelectSucursalMensaje("No se pudieron cargar sucursales");
    }
  }

  function leerDireccionNueva() {
    const dir = (document.getElementById("dir_direccion")?.value || "").trim();
    const ciudad = (document.getElementById("dir_ciudad")?.value || "").trim();
    const provincia = (document.getElementById("dir_provincia")?.value || "").trim();
    const codigo = (document.getElementById("dir_codigo")?.value || "").trim();
    const ref = (document.getElementById("dir_referencia")?.value || "").trim();

    if (!dir && !ciudad && !provincia && !codigo && !ref) return null;
    return { direccion: dir, ciudad, provincia, codigo_postal: codigo, referencia: ref };
  }

  async function validarStockAntesDePagar(idUsuario) {
    __checkoutResolved.id_sucursal_retiro = null;
    __checkoutResolved.id_sucursal_origen = null;
    __checkoutResolved.id_direccion_envio = null;
    __checkoutResolved.direccion_nueva = null;

    const tipo = document.querySelector("input[name='tipo_entrega']:checked")?.value || "envio";
    __checkoutResolved.tipo_entrega = tipo;

    // ✅ Entrega solo Loja (bloquea antes de pedir orden PayPal)
    if (!validarDireccionSoloLojaEnFront()) {
      return false;
    }

    const payload = { id_usuario: idUsuario, cart: cartParaServidor(), tipo_entrega: tipo };

    if (tipo === "retiro_local") {
      const selSuc = document.getElementById("selectSucursalRetiro");
      const idSuc = selSuc && selSuc.value ? Number(selSuc.value) : 0;
      if (!idSuc) {
        setStatus("Selecciona una sucursal para retiro.", true);
        return false;
      }
      payload.id_sucursal_retiro = idSuc;
    }

    if (tipo === "envio") {
      // ✅ Bloqueo inmediato si intenta poner provincia distinta a Loja
      if (!validarDireccionSoloLojaEnFront()) return false;

      const selDir = document.getElementById("selectDireccionEnvio");
      const idDir = selDir && selDir.value ? Number(selDir.value) : 0;
      const dirNueva = leerDireccionNueva();

      if (idDir) __checkoutResolved.id_direccion_envio = idDir;
      if (!idDir && dirNueva) __checkoutResolved.direccion_nueva = dirNueva;
    }

    try {
      const resp = await fetch(CHECKOUT_API + "?accion=validar-stock", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await resp.json().catch(() => null);
      if (!resp.ok || !data || data.ok !== true) {
        setStatus((data && data.error) ? data.error : "Stock insuficiente.", true);
        return false;
      }

      if (tipo === "retiro_local") {
        __checkoutResolved.id_sucursal_retiro = data.id_sucursal_retiro || payload.id_sucursal_retiro;
      } else {
        __checkoutResolved.id_sucursal_origen = data.id_sucursal_origen || null;
      }

      return true;
    } catch (e) {
      setStatus("No se pudo validar stock. Intenta nuevamente.", true);
      return false;
    }
  }

  function setStatus(msg, isError = false) {
    const el = document.getElementById("paypal-status");
    if (!el) return;
    el.textContent = msg || "";
    el.style.color = isError ? "crimson" : "inherit";
    // Si ya mostramos un error específico (stock, validaciones, servidor, etc.),
    // evitamos que el callback genérico onError de PayPal lo sobre-escriba.
    if (isError) el.dataset.preserve = "1";
    else delete el.dataset.preserve;
  }

  function mostrarAvisoDescuentoEmpresa() {
    const el = document.getElementById("empresa-descuento-msg");
    if (!el) return;

    let user = null;
    try { user = JSON.parse(localStorage.getItem("usuarioMega") || "null"); } catch (e) {}
    const rol = user?.rol ?? user?.id_rol ?? null;

    if (Number(rol) === 2) el.style.display = "block";
    else el.style.display = "none";
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

      // Si el carrito está vacío, limpiamos UI de PayPal/estado para que
      // no quede mostrando "Fallo de inicialización" de intentos previos.
      setStatus("", false);
      const pp = document.getElementById("paypal-buttons");
      if (pp) pp.innerHTML = "";

      mostrarAvisoDescuentoEmpresa();
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
    mostrarAvisoDescuentoEmpresa();
  }

  function bindEventosCarrito() {
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

    document.addEventListener("click", (e) => {
      if (!e.target.classList.contains("btn-cart-delete")) return;

      const id = e.target.getAttribute("data-id");
      let carrito = obtenerCarrito();
      carrito = carrito.filter(p => String(p.id) !== String(id));

      guardarCarrito(carrito);
      renderCarrito();
      cargarPayPalSDK();
    });

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

  async function cargarPayPalSDK() {
    
    // Limpia cualquier mensaje inicial (HTML trae "Fallo de inicialización")
    setStatus("", false);
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
    const cfg = await res.json().catch(() => ({}));
    __paypalSupportsServerSide = (cfg.supportsServerSide !== false);

    if (!cfg.clientId || cfg.clientId === "PAYPAL_SANDBOX_CLIENT_ID") {
      setStatus("⚠️ Falta configurar PayPal Sandbox (clientId/secret) en Model/Config/paypal_credentials.php", true);
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
    // ✅ robustez: el carrito puede guardar el id con distintos nombres (id, id_producto, idProducto...)
    // El backend recalcula el total desde BD usando estos IDs.
    return carrito
      .map(it => {
        const id = Number(
          it.id ?? it.id_producto ?? it.idProducto ?? it.idProductoFK ?? it.id_producto_fk ?? 0
        );
        const cantidad = Number(it.cantidad ?? it.qty ?? it.quantity ?? 0);
        return { id, cantidad };
      })
      .filter(it => it.id > 0 && it.cantidad > 0);
  }

  function totalCarritoPayPal() {
    const carrito = obtenerCarrito();
    const total = carrito.reduce((acc, it) => acc + (Number(it.precio) * Number(it.cantidad)), 0);
    // PayPal espera string con 2 decimales
    return Number(total || 0).toFixed(2);
  }

  function getUsuarioLocal() {
    try { return JSON.parse(localStorage.getItem("usuarioMega") || "null"); }
    catch { return null; }
  }

  function renderPayPalButtons() {
    
    // Limpia el estado antes de renderizar botones (evita que quede texto rojo aunque funcione)
    setStatus("", false);
if (!window.paypal) {
      setStatus("PayPal SDK no disponible.", true);
      return;
    }

    const cont = document.getElementById("paypal-button-container");
    if (cont) cont.innerHTML = "";

    paypal.Buttons({
      createOrder: async function (data, actions) {
        setStatus("Validando stock y entrega...");

        const __user = getUsuarioLocal();
        const __idUsuario =
          (__user && (__user.id_usuario || __user.id)) ? (__user.id_usuario || __user.id) : 0;

        if (!__idUsuario) {
          setStatus("Debes iniciar sesión para continuar con la compra.", true);
          throw new Error("login required");
        }

        const okStock = await validarStockAntesDePagar(__idUsuario);
        if (!okStock) throw new Error("stock validation failed");

        setStatus("Creando orden en PayPal...");

        // ✅ Modo CLIENT (sin cURL): crear orden en el navegador
        if (!__paypalSupportsServerSide) {
          const amount = totalCarritoPayPal();
          const orderId = await actions.order.create({
            purchase_units: [{
              amount: { currency_code: "USD", value: String(amount) }
            }]
          });
          setStatus("");
          return orderId;
        }

        // ✅ Modo SERVER (con cURL): crear orden en backend
        const resp = await fetch(PAYPAL_API + "?accion=create-order", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          // ✅ IMPORTANTE (InfinityFree): a veces el hosting devuelve php://input vacío en
          // capture-order, entonces el backend puede registrar el pedido pero perder los datos
          // de entrega (sucursal/dirección). Por eso también enviamos aquí la info de checkout
          // para que el backend la “recuerde” en sesión y la use como fallback en capture-order.
          body: JSON.stringify({
            cart: cartParaServidor(),
            amount: totalCarritoPayPal(),
            tipo_entrega: __checkoutResolved.tipo_entrega,
            id_sucursal_retiro: __checkoutResolved.id_sucursal_retiro,
            id_sucursal_origen: __checkoutResolved.id_sucursal_origen,
            id_direccion_envio: __checkoutResolved.id_direccion_envio,
            direccion_nueva: __checkoutResolved.direccion_nueva,
          }),
        });

        let json = null;
        try { json = await resp.json(); } catch (e) {
          const raw = await resp.text().catch(() => "");
          setStatus("Respuesta inválida del servidor al crear la orden. " + (raw ? raw.slice(0, 180) : ""), true);
          throw new Error("create-order invalid response");
        }
        if (!resp.ok) {
          setStatus(json.error || "Error al crear la orden.", true);
          throw new Error(json.error || "create-order failed");
        }

        setStatus("");
        return json.id;
      },

      onApprove: async function (data, actions) {
        setStatus("Capturando pago...");

        const __user = getUsuarioLocal();
        const __idUsuario =
          (__user && (__user.id_usuario || __user.id)) ? (__user.id_usuario || __user.id) : 0;

        const __email = (__user && __user.email) ? String(__user.email) : "";

        // ✅ Modo CLIENT (sin cURL): capturar con el SDK y luego registrar en backend
        if (!__paypalSupportsServerSide) {
          let paypalDetails = null;
          try {
            paypalDetails = await actions.order.capture();
          } catch (e) {
            setStatus("No se pudo capturar el pago en PayPal.", true);
            return;
          }

          const resp = await fetch(PAYPAL_API + "?accion=registrar-pedido", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              orderId: data.orderID,
              paypal: paypalDetails,
              cart: obtenerCarrito(),
              id_usuario: __idUsuario,
              email: __email,
              tipo_entrega: __checkoutResolved.tipo_entrega,
              id_sucursal_retiro: __checkoutResolved.id_sucursal_retiro,
              id_sucursal_origen: __checkoutResolved.id_sucursal_origen,
              id_direccion_envio: __checkoutResolved.id_direccion_envio,
              direccion_nueva: __checkoutResolved.direccion_nueva,
            }),
          });

          let details = null;
          try { details = await resp.json(); } catch (e) {
            const raw = await resp.text().catch(() => "");
            setStatus("Respuesta inválida del servidor al registrar el pedido. " + (raw ? raw.slice(0, 180) : ""), true);
            return;
          }
          if (!resp.ok) {
            setStatus(details.error || "No se pudo registrar el pedido.", true);
            return;
          }

          const status = (details && (details.status || details.paypal_status)) || "";
          const ok = !!(details && (details.ok === true || status === "COMPLETED"));

          if (ok) {
            if (details.email_sent) setStatus("Pago completado. Factura enviada al correo ✅");
            else {
              setStatus(
                "Pago completado ✅ (pero no se pudo enviar el correo: " +
                  (details.email_error || "sin detalle") +
                ")",
                true
              );
            }

            localStorage.removeItem(CART_KEY);
            try { window.dispatchEvent(new Event("carrito_actualizado")); } catch (e) {}
            if (typeof window.actualizarContadorCarrito === "function") window.actualizarContadorCarrito();

            renderCarrito();
            cargarPayPalSDK();
          } else {
            setStatus("Pago procesado con estado: " + (status || "desconocido"));
          }
          return;
        }

        // ✅ Modo SERVER (con cURL): capturar en backend
        // Nota: algunos hostings (InfinityFree) pueden devolver php://input vacío.
        // En ese caso reintentamos con x-www-form-urlencoded.
        const capturePayload = {
          orderId: data.orderID,
          cart: obtenerCarrito(),
          id_usuario: __idUsuario,
          email: __email,
          tipo_entrega: __checkoutResolved.tipo_entrega,
          id_sucursal_retiro: __checkoutResolved.id_sucursal_retiro,
          id_sucursal_origen: __checkoutResolved.id_sucursal_origen,
          id_direccion_envio: __checkoutResolved.id_direccion_envio,
          direccion_nueva: __checkoutResolved.direccion_nueva,
        };

        // ✅ Importante:
        // A veces el backend puede PROCESAR la orden (registrar pedido)
        // pero devolver 500 por un warning/notice del hosting (InfinityFree).
        // Para no dejar el carrito "atascado", hacemos un retry suave:
        // 1) POST JSON
        // 2) fallback x-www-form-urlencoded (si php://input llega vacío)
        // 3) si aún falla (500), reintento GET para disparar la ruta idempotente.

        const captureUrl = PAYPAL_API + "?accion=capture-order&orderId=" + encodeURIComponent(data.orderID || "");

        let resp = await fetch(captureUrl, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(capturePayload),
        });

        // Fallback: si el backend responde "Carrito vacío"/"Usuario inválido" (y sí hay items), reintenta como form
        if (!resp.ok) {
          let tmp = null;
          try { tmp = await resp.clone().json(); } catch (e) { tmp = null; }
          const hasItems = Array.isArray(capturePayload.cart) && capturePayload.cart.length > 0;
          if (hasItems && tmp && (tmp.error === "Carrito vacío" || tmp.error === "Usuario inválido")) {
            const params = new URLSearchParams();
            for (const [k, v] of Object.entries(capturePayload)) {
              if (v === undefined || v === null) continue;
              if (typeof v === "object") params.set(k, JSON.stringify(v));
              else params.set(k, String(v));
            }
            resp = await fetch(captureUrl, {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
              body: params.toString(),
            });
          }
        }

        // ✅ Retry idempotente: si el servidor devolvió 500 pero la orden quedó registrada,
        // el 2do hit (mismo orderId) debe responder ok:true con note idempotente.
        if (!resp.ok && resp.status >= 500) {
          try {
            resp = await fetch(
              captureUrl + "&id_usuario=" + encodeURIComponent(String(__idUsuario || 0)),
              { method: "GET", headers: { "Accept": "application/json" } }
            );
          } catch (e) {
            // si el retry falla, seguimos con la respuesta original
          }
        }

        let details = null;
        try { details = await resp.json(); } catch (e) {
          const raw = await resp.text().catch(() => "");
          setStatus("Respuesta inválida del servidor al capturar el pago. " + (raw ? raw.slice(0, 180) : ""), true);
          return;
        }
        if (!resp.ok) {
          setStatus(details.error || "Error al capturar el pago.", true);
          return;
        }

        const status = (details && (details.status || details.paypal_status)) || "";
        const ok = !!(details && (details.ok === true || status === "COMPLETED"));

        if (ok) {
          if (details.email_sent) setStatus("Pago completado. Factura enviada al correo ✅");
          else {
            setStatus(
              "Pago completado ✅ (pero no se pudo enviar el correo: " +
                (details.email_error || "sin detalle") +
              ")",
              true
            );
          }

          localStorage.removeItem(CART_KEY);
          try { window.dispatchEvent(new Event("carrito_actualizado")); } catch (e) {}
          if (typeof window.actualizarContadorCarrito === "function") window.actualizarContadorCarrito();

          renderCarrito();
          cargarPayPalSDK();
        } else {
          setStatus("Pago procesado con estado: " + (status || "desconocido"));
        }
      },

      onCancel: function () { setStatus("Pago cancelado."); },

      onError: function (err) {
        console.error(err);
        const el = document.getElementById("paypal-status");
        // Si ya se mostró un error más específico (p.ej. stock/datos), no lo pisamos.
        if (el && el.dataset && el.dataset.preserve === "1" && el.textContent) return;

        const msg = (err && err.message) ? String(err.message) : "Ocurrió un error con PayPal.";
        setStatus(msg, true);
      },
    }).render("#paypal-button-container");
  }

  window.initCarritoPage = function () {
    bindEventosCarrito();
    bindCheckoutRadios();

    // ✅ siempre cargar sucursales (id_usuario=0 si no hay login)
    try {
      const __u = getUsuarioLocal();
      const __id = (__u && (__u.id_usuario || __u.id)) ? (__u.id_usuario || __u.id) : 0;
      cargarCheckoutData(__id || 0);
    } catch (e) {
      cargarCheckoutData(0);
    }

    renderCarrito();
    cargarPayPalSDK();
    mostrarAvisoDescuentoEmpresa();
  };

})();
