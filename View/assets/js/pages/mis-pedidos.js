// View/assets/js/pages/mis-pedidos.js
(function () {
  const API = '/Controller/MisPedidosController.php';
  const base = window.PROJECT_BASE || '';

  function $(id) { return document.getElementById(id); }

  function showError(msg) {
    const el = $('mp-error');
    if (!el) return;
    el.textContent = msg;
    el.style.display = 'block';
  }

  function hideError() {
    const el = $('mp-error');
    if (!el) return;
    el.style.display = 'none';
    el.textContent = '';
  }

  function formatMoney(n) {
    const num = Number(n || 0);
    return '$' + num.toFixed(2);
  }

  function escapeHtml(s) {
    return String(s ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  async function apiGet(params) {
    const qs = new URLSearchParams(params).toString();
    const res = await fetch(`${API}?${qs}`, { credentials: 'include' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.ok) {
      throw new Error(data.error || data.detalle || 'Error al obtener datos');
    }
    return data;
  }

  // Solo un detalle abierto a la vez (UX)
  let __openPedidoId = null;

  function renderList(pedidos) {
    const cont = $('mp-contenido');
    if (!cont) return;

    if (!Array.isArray(pedidos) || pedidos.length === 0) {
      cont.innerHTML = '<div>\nNo tienes pedidos registrados todavía.\n</div>';
      return;
    }

    const rows = pedidos.map(p => {
      const id = Number(p.id_pedido);
      const fecha = escapeHtml(p.fecha_pedido || '');
      const estado = escapeHtml(p.estado || '');
      const tipo = escapeHtml(p.tipo_entrega || '');
      const total = formatMoney(p.total_pagar);

      return `
        <div style="border:1px solid #eee; border-radius:12px; margin-bottom:12px; overflow:hidden;">
          <div style="padding:12px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
            <div>
              <div style="font-weight:700;">Pedido #${id}</div>
              <div style="font-size:13px; color:#666; margin-top:2px;">${fecha}</div>
              <div style="margin-top:6px; display:flex; gap:10px; flex-wrap:wrap;">
                <span style="background:#f5f5f5; padding:4px 10px; border-radius:999px; font-size:13px;">Estado: <b>${estado}</b></span>
                <span style="background:#f5f5f5; padding:4px 10px; border-radius:999px; font-size:13px;">Entrega: <b>${tipo}</b></span>
              </div>
            </div>
            <div style="text-align:right;">
              <div style="font-weight:800; font-size:16px;">${total}</div>
              <button class="btn-cart-primary" data-ver="${id}" style="margin-top:8px; padding:8px 12px;">Ver detalle</button>
            </div>
          </div>

          <div id="mp-inline-${id}" style="display:none; padding:14px; border-top:1px solid #eee; background:#fff;"></div>
        </div>
      `;
    }).join('');

    cont.innerHTML = rows;

    cont.querySelectorAll('[data-ver]')?.forEach(btn => {
      btn.addEventListener('click', () => {
        const id = Number(btn.getAttribute('data-ver') || 0);
        if (id > 0) toggleDetalleInline(id, btn);
      });
    });
  }

  function renderEntrega(p) {
    const tipo = p.tipo_entrega;
    if (tipo === 'retiro_local') {
      const s = p.sucursal_retiro || {};
      const ok = s.nombre || s.direccion || s.ciudad;
      if (!ok) return '<div style="color:#888;">Sucursal de retiro: No registrada</div>';
      return `
        <div style="margin-top:10px;">
          <div style="font-weight:700; margin-bottom:6px;">Sucursal de retiro</div>
          <div>${escapeHtml(s.nombre || '')}</div>
          <div style="color:#666; font-size:13px;">${escapeHtml(s.direccion || '')} ${s.ciudad ? ' - ' + escapeHtml(s.ciudad) : ''}</div>
          ${s.telefono ? `<div style="color:#666; font-size:13px;">Tel: ${escapeHtml(s.telefono)}</div>` : ''}
          ${s.horario ? `<div style="color:#666; font-size:13px;">Horario: ${escapeHtml(s.horario)}</div>` : ''}
        </div>
      `;
    }

    // envio
    const d = p.direccion_envio || {};
    const ok = d.direccion || d.ciudad || d.provincia;
    if (!ok) return '<div style="color:#888;">Dirección de envío: No registrada</div>';

    return `
      <div style="margin-top:10px;">
        <div style="font-weight:700; margin-bottom:6px;">Dirección de envío</div>
        <div>${escapeHtml(d.direccion || '')}</div>
        <div style="color:#666; font-size:13px;">
          ${escapeHtml(d.ciudad || '')}${d.provincia ? ', ' + escapeHtml(d.provincia) : ''}
          ${d.codigo_postal ? ' - ' + escapeHtml(d.codigo_postal) : ''}
        </div>
        ${d.referencia ? `<div style="color:#666; font-size:13px;">Ref: ${escapeHtml(d.referencia)}</div>` : ''}
      </div>
    `;
  }

  function buildDetalleHtml(p) {
    const items = Array.isArray(p.items) ? p.items : [];
    const itemsHtml = items.length
      ? `
        <table class="cart-tabla" style="margin-top:10px;">
          <thead>
            <tr>
              <th>Producto</th>
              <th>Cant.</th>
              <th>Precio</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            ${items.map(it => `
              <tr>
                <td>${escapeHtml(it.nombre || '')}</td>
                <td>${Number(it.cantidad || 0)}</td>
                <td>${formatMoney(it.precio_unit)}</td>
                <td>${formatMoney(it.subtotal)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `
      : '<div style="color:#888; margin-top:10px;">Sin items.</div>';

    return `
      <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <span style="background:#f5f5f5; padding:6px 12px; border-radius:999px; font-size:13px;">Estado: <b>${escapeHtml(p.estado)}</b></span>
        <span style="background:#f5f5f5; padding:6px 12px; border-radius:999px; font-size:13px;">Entrega: <b>${escapeHtml(p.tipo_entrega)}</b></span>
        <span style="background:#f5f5f5; padding:6px 12px; border-radius:999px; font-size:13px;">Fecha: <b>${escapeHtml(p.fecha_pedido)}</b></span>
      </div>

      ${renderEntrega(p)}

      ${itemsHtml}

      <div style="margin-top:12px; text-align:right; font-size:16px; font-weight:800;">Total: ${formatMoney(p.total_pagar)}</div>
    `;
  }

  async function toggleDetalleInline(idPedido, btn) {
    hideError();

    // Cerrar el que esté abierto (si es otro)
    if (__openPedidoId && __openPedidoId !== idPedido) {
      const prevBox = document.getElementById(`mp-inline-${__openPedidoId}`);
      const prevBtn = document.querySelector(`[data-ver="${__openPedidoId}"]`);
      if (prevBox) {
        prevBox.style.display = 'none';
        prevBox.innerHTML = '';
      }
      if (prevBtn) prevBtn.textContent = 'Ver detalle';
      __openPedidoId = null;
    }

    const box = document.getElementById(`mp-inline-${idPedido}`);
    if (!box) return;

    // Si ya está abierto: cerrar
    if (box.style.display === 'block') {
      box.style.display = 'none';
      box.innerHTML = '';
      if (btn) btn.textContent = 'Ver detalle';
      __openPedidoId = null;
      return;
    }

    // Abrir
    box.style.display = 'block';
    box.innerHTML = 'Cargando detalle...';
    if (btn) btn.textContent = 'Cerrar';
    __openPedidoId = idPedido;

    try {
      const data = await apiGet({ accion: 'ver', id: String(idPedido) });
      const p = data.pedido;
      box.innerHTML = buildDetalleHtml(p);
    } catch (e) {
      showError(e.message || 'Error al cargar detalle');
      box.innerHTML = '';
      box.style.display = 'none';
      if (btn) btn.textContent = 'Ver detalle';
      __openPedidoId = null;
    }
  }

  async function cargarLista() {
    hideError();
    const cont = $('mp-contenido');
    if (cont) cont.textContent = 'Cargando...';

    try {
      const data = await apiGet({ accion: 'listar' });
      renderList(data.pedidos || []);
    } catch (e) {
      const msg = e.message || 'No autorizado';
      showError(msg);
      // Si no está logueado o no es cliente, manda a login
      if (/no autorizado/i.test(msg)) {
        setTimeout(() => { window.location.href = `${base}/login`; }, 800);
      }
      if (cont) cont.innerHTML = '';
    }
  }

  window.initMisPedidosPage = function () {
    // Cualquier usuario logueado (UX). Seguridad real se hace en backend (sesión).
    const u = JSON.parse(localStorage.getItem('usuarioMega') || 'null');
    if (!u) {
      window.location.href = `${base}/login`;
      return;
    }

    cargarLista();

    // Si viene con ?id=123 en la URL, abrir detalle inline cuando cargue
    const url = new URL(window.location.href);
    const id = Number(url.searchParams.get('id') || 0);
    if (id > 0) {
      // esperar a que se renderice la lista
      setTimeout(() => {
        const btn = document.querySelector(`[data-ver="${id}"]`);
        if (btn) toggleDetalleInline(id, btn);
      }, 500);
    }
  };
})();
