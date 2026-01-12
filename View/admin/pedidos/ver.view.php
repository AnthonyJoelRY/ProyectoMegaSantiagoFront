<?php $seccionActiva = "pedidos"; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Pedido #<?= (int)($pedido["id_pedido"] ?? 0) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container-fluid">
    <div class="row">

        <?php include __DIR__ . "/../partials/sidebar.php"; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 py-4">

            <h2 class="mb-4">📦 Pedido #<?= (int)($pedido["id_pedido"] ?? 0) ?></h2>

            <div class="card mb-4 p-3">
                <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido["cliente"] ?? "") ?></p>
                <p><strong>Fecha:</strong> <?= htmlspecialchars($pedido["fecha_pedido"] ?? "") ?></p>
                <p><strong>Estado:</strong> <?= ucfirst(htmlspecialchars($pedido["estado"] ?? "")) ?></p>

                <?php
                  // ----- Info de entrega (envío vs retiro) -----
                  $tipoEntrega = (string)($pedido['tipo_entrega'] ?? 'envio');
                ?>

                <?php if ($tipoEntrega === 'retiro_local'): ?>
                  <p class="mb-1"><strong>Entrega:</strong> Retiro en el local</p>
                  <div class="ms-3">
                    <p class="mb-1"><strong>Sucursal:</strong> <?= htmlspecialchars($pedido['sucursal_retiro_nombre'] ?? '') ?></p>
                    <?php if (!empty($pedido['sucursal_retiro_direccion']) || !empty($pedido['sucursal_retiro_ciudad'])): ?>
                      <p class="mb-1"><strong>Dirección:</strong> <?= htmlspecialchars(trim(($pedido['sucursal_retiro_direccion'] ?? '') . ' ' . ($pedido['sucursal_retiro_ciudad'] ?? ''))) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($pedido['sucursal_retiro_telefono'])): ?>
                      <p class="mb-1"><strong>Teléfono:</strong> <?= htmlspecialchars($pedido['sucursal_retiro_telefono']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($pedido['sucursal_retiro_horario'])): ?>
                      <p class="mb-0"><strong>Horario:</strong> <?= htmlspecialchars($pedido['sucursal_retiro_horario']) ?></p>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <p class="mb-1"><strong>Entrega:</strong> Envío a domicilio</p>
                  <div class="ms-3">
                    <?php if (!empty($pedido['direccion_envio_direccion'])): ?>
                      <p class="mb-1"><strong>Dirección:</strong> <?= htmlspecialchars($pedido['direccion_envio_direccion']) ?></p>
                      <?php
                        $ciudadProv = trim((string)($pedido['direccion_envio_ciudad'] ?? ''));
                        $prov = trim((string)($pedido['direccion_envio_provincia'] ?? ''));
                        $cp = trim((string)($pedido['direccion_envio_codigo_postal'] ?? ''));
                        $line2 = trim($ciudadProv . ($prov ? (', ' . $prov) : '') . ($cp ? (' - ' . $cp) : ''));
                      ?>
                      <?php if ($line2 !== ''): ?>
                        <p class="mb-1"><strong>Ciudad/Provincia:</strong> <?= htmlspecialchars($line2) ?></p>
                      <?php endif; ?>
                      <?php if (!empty($pedido['direccion_envio_referencia'])): ?>
                        <p class="mb-1"><strong>Referencia:</strong> <?= htmlspecialchars($pedido['direccion_envio_referencia']) ?></p>
                      <?php endif; ?>
                    <?php else: ?>
                      <p class="mb-1 text-muted"><strong>Dirección:</strong> No registrada</p>
                    <?php endif; ?>

                    <?php if (!empty($pedido['sucursal_origen_nombre'])): ?>
                      <p class="mb-0"><strong>Sucursal origen:</strong> <?= htmlspecialchars($pedido['sucursal_origen_nombre']) ?></p>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
        <?php
          if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
          $__rol = isset($_SESSION["rol"]) ? (int)$_SESSION["rol"] : 0;
          $__tipo = isset($pedido["tipo_entrega"]) ? (string)$pedido["tipo_entrega"] : "envio";
          $__estados = ($__tipo === "retiro_local")
            ? ["en_proceso" => "En proceso", "listo_para_entregar" => "Listo para entregar"]
            : ["en_proceso" => "En proceso", "en_camino" => "En camino", "entregado" => "Entregado"];
        ?>

        <?php if ($__rol === 4 || $__rol === 1): ?>
          <hr style="margin:14px 0;">
          <h5 style="margin:0 0 8px 0;">Cambiar estado (Empleado)</h5>
          <form method="POST" action="<?= PROJECT_BASE ?>/panel/pedidos/estado">
            <input type="hidden" name="id_pedido" value="<?= (int)($pedido["id_pedido"] ?? 0) ?>">
            <select name="estado" class="form-select" style="max-width:320px;">
              <?php foreach ($__estados as $k => $label): ?>
                <option value="<?= htmlspecialchars($k) ?>" <?= ((string)($pedido["estado"] ?? "") === $k) ? "selected" : "" ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary mt-2">Actualizar</button>
          </form>
        <?php endif; ?>

            </div>

            <div class="card shadow-sm">
                <div class="card-body">

                    <table class="table align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php if (empty($detalles)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No hay productos en este pedido.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($detalles as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d["nombre"] ?? "") ?></td>
                                    <td><?= (int)($d["cantidad"] ?? 0) ?></td>
                                    <td>$<?= number_format((float)($d["precio_unit"] ?? 0), 2) ?></td>
                                    <td>$<?= number_format((float)($d["subtotal"] ?? 0), 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>

                    <hr>

                    <div class="text-end">
                        <p>Total productos: <strong>$<?= number_format((float)($pedido["total_productos"] ?? 0), 2) ?></strong></p>
                        <p>IVA: <strong>$<?= number_format((float)($pedido["total_iva"] ?? 0), 2) ?></strong></p>
                        <h5>Total a pagar: <strong>$<?= number_format((float)($pedido["total_pagar"] ?? 0), 2) ?></strong></h5>
                    </div>

                </div>
            </div>

            <a href="<?= PROJECT_BASE ?>/panel/pedidos" class="btn btn-secondary mt-3">⬅ Volver</a>

        </main>
    </div>
</div>

</body>
</html>
