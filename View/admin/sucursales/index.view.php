<?php $seccionActiva = "sucursales"; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sucursales | MegaSantiago</title>
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
                    <h1 class="h2 text-primary fw-bold mb-0">Sucursales</h1>
                    <small class="text-muted">Crea y administra sucursales</small>
                </div>
                <a href="<?= PROJECT_BASE ?>/panel/sucursales/nuevo" class="btn btn-primary fw-semibold">+ Nueva sucursal</a>
            </div>

            <div class="bg-white p-3 rounded-4 shadow-sm mb-3">
                <form method="GET" class="d-flex gap-2">
                    <input class="form-control" type="text" name="q" placeholder="Buscar por nombre, ciudad o dirección" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    <button class="btn btn-outline-primary">🔍</button>
                </form>
            </div>

            <div class="bg-white p-3 rounded-4 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th>Ciudad</th>
                            <th>Teléfono</th>
                            <th>Horario</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach (($sucursales ?? []) as $s): ?>
                            <tr>
                                <td><?= (int)$s['id_sucursal'] ?></td>
                                <td><?= htmlspecialchars($s['nombre'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['direccion'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['ciudad'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['telefono'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['horario'] ?? '') ?></td>
                                <td>
                                    <?php if ((int)($s['activo'] ?? 0) === 1): ?>
                                        <span class="badge text-bg-success">Activa</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= PROJECT_BASE ?>/panel/sucursales/editar?id=<?= (int)$s['id_sucursal'] ?>">✏️</a>

                                    <form method="POST" action="<?= PROJECT_BASE ?>/panel/sucursales/acciones" class="d-inline">
                                        <input type="hidden" name="id_sucursal" value="<?= (int)$s['id_sucursal'] ?>">
                                        <?php if ((int)($s['activo'] ?? 0) === 1): ?>
                                            <input type="hidden" name="accion" value="desactivar">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">⛔</button>
                                        <?php else: ?>
                                            <input type="hidden" name="accion" value="activar">
                                            <button class="btn btn-sm btn-outline-success" type="submit">✅</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($sucursales)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No hay sucursales registradas.</td>
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
