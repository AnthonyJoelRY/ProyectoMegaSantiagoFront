<?php $seccionActiva = "inventario_sucursal"; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario por sucursal | MegaSantiago</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid">
    <div class="row">
        <?php require __DIR__ . "/../partials/sidebar.php"; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm border-bottom border-primary border-3">
                <div>
                    <h1 class="h2 text-primary fw-bold mb-0">Inventario por sucursal</h1>
                    <small class="text-muted">Define stock por sucursal (se usa en checkout)</small>
                </div>
            </div>

            <div class="bg-white p-3 rounded-4 shadow-sm mb-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Sucursal</label>
                        <select class="form-select" name="id_sucursal" onchange="this.form.submit()">
                            <?php foreach (($sucursales ?? []) as $s): ?>
                                <option value="<?= (int)$s['id_sucursal'] ?>" <?= ((int)$s['id_sucursal'] === (int)($idSucursal ?? 0)) ? 'selected' : '' ?>>
                                    #<?= (int)$s['id_sucursal'] ?> - <?= htmlspecialchars($s['nombre'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Buscar producto</label>
                        <input class="form-control" type="text" name="q" placeholder="Nombre o id_producto" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-outline-primary">🔍</button>
                    </div>
                </form>

                <hr>

                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="<?= PROJECT_BASE ?>/panel/inventario-sucursal/acciones" class="d-flex flex-wrap gap-2 align-items-end">
                        <input type="hidden" name="accion" value="upsert">
                        <input type="hidden" name="id_sucursal" value="<?= (int)($idSucursal ?? 0) ?>">

                        <div>
                            <label class="form-label fw-semibold">Producto</label>
                            <select class="form-select" name="id_producto" required>
                                <option value="">Selecciona</option>
                                <?php foreach (($productos ?? []) as $p): ?>
                                    <option value="<?= (int)$p['id_producto'] ?>">#<?= (int)$p['id_producto'] ?> - <?= htmlspecialchars($p['nombre'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Stock actual</label>
                            <input class="form-control" type="number" min="0" name="stock_actual" value="0" required>
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Stock mínimo</label>
                            <input class="form-control" type="number" min="0" name="stock_minimo" value="0">
                        </div>
                        <div class="d-grid">
                            <button class="btn btn-primary fw-semibold" type="submit">Guardar</button>
                        </div>
                    </form>

                    <form method="POST" action="<?= PROJECT_BASE ?>/panel/inventario-sucursal/acciones" class="ms-auto">
                        <input type="hidden" name="accion" value="copiar_global">
                        <input type="hidden" name="id_sucursal" value="<?= (int)($idSucursal ?? 0) ?>">
                        <button class="btn btn-outline-secondary" type="submit" onclick="return confirm('Copiará el stock global (tabla inventario) hacia inventario_sucursal. ¿Continuar?')">
                            Copiar stock global → esta sucursal
                        </button>
                    </form>
                </div>

                <div class="text-muted mt-2" style="font-size:13px;">
                    Si no has ejecutado la migración SQL, este módulo mostrará vacío. El checkout hará fallback al stock global.
                </div>
            </div>

            <div class="bg-white p-3 rounded-4 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-end">Stock</th>
                            <th class="text-end">Mínimo</th>
                            <th>Actualización</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach (($inventario ?? []) as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['producto_nombre'] ?? '') ?> <span class="text-muted">(#<?= (int)$r['id_producto'] ?>)</span></td>
                                <td class="text-end fw-semibold"><?= (int)($r['stock_actual'] ?? 0) ?></td>
                                <td class="text-end"><?= (int)($r['stock_minimo'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($r['ultima_actualizacion'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($inventario)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No hay inventario registrado para esta sucursal.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

</body>
</html>
