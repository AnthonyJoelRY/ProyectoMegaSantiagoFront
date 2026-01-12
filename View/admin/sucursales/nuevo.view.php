<?php $seccionActiva = "sucursales"; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva sucursal | MegaSantiago</title>
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
                    <h1 class="h2 text-primary fw-bold mb-0">Nueva sucursal</h1>
                    <small class="text-muted">Registra una nueva sucursal</small>
                </div>
                <a href="<?= PROJECT_BASE ?>/panel/sucursales" class="btn btn-outline-secondary">⬅️ Volver</a>
            </div>

            <div class="bg-white p-4 rounded-4 shadow-sm">
                <form method="POST" action="<?= PROJECT_BASE ?>/panel/sucursales/acciones" class="row g-3">
                    <input type="hidden" name="accion" value="crear">

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nombre</label>
                        <input class="form-control" name="nombre" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Teléfono</label>
                        <input class="form-control" name="telefono">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Dirección</label>
                        <input class="form-control" name="direccion" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ciudad</label>
                        <input class="form-control" name="ciudad" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Horario</label>
                        <input class="form-control" name="horario" placeholder="Ej: Lun-Vie 08:00-18:00">
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary fw-semibold" type="submit">Guardar</button>
                        <a href="<?= PROJECT_BASE ?>/panel/sucursales" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

</body>
</html>
