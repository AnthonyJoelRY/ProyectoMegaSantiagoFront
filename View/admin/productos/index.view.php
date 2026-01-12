<?php $seccionActiva = "productos"; ?>






<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Dashboard | MegaSantiago</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos personalizados muy sencillos para la barra lateral y asegurar que el contenido se vea */
        .sidebar {
            /* Asegura que el color de fondo de la barra lateral sea consistente */
            background-color: #212529 !important;
            /* Usando bg-dark */
        }

        .nav-link.active {
            /* Estilo para el enlace activo */
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--bs-info);
            /* Línea de color para indicar activo */
        }

        .nav-link {
            padding-left: 1.5rem;
            /* Pequeño ajuste para el padding de los enlaces */
        }

        .card-body h2 {
            font-size: 2.5rem;
            /* Ajuste para las métricas clave */
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid">
        <div class="row">

            <?php $seccionActiva = "productos"; require __DIR__ . "/../partials/sidebar.php"; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 py-4">

                <div class="d-flex justify-content-between align-items-center mb-5 bg-white p-3 rounded-4 shadow-sm border-bottom border-primary border-3">
                    <h1 class="h2 text-primary fw-bold mb-0">Gestión de Productos</h1>
                    <small class="text-muted">Administra el catálogo de la tienda</small>

                </div>


                <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm">

                    <form method="GET" class="d-flex w-50">
                        <input
                            type="text"
                            name="q"
                            class="form-control me-2"
                            placeholder="Buscar por nombre o SKU..."
                            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                        <button class="btn btn-outline-primary">🔍</button>
                    </form>



                    <a href="<?= PROJECT_BASE ?>/panel/productos/nuevo"
                        class="btn btn-primary fw-semibold">
                        + Agregar producto
                    </a>



                </div>

                <?php if (isset($_GET["ok"])): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                        ✅ Producto guardado correctamente.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET["estado"])): ?>
                    <div class="alert alert-info alert-dismissible fade show rounded-4 shadow-sm">
                        <?= $_GET["estado"] === "on"
                            ? "✅ Producto activado correctamente"
                            : "🚫 Producto desactivado correctamente" ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET["ok"])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        ✅ Producto creado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET["edit"])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        ✏️ Cambios guardados correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>




                <div class="card rounded-4 shadow-sm border-0 bg-white">
                    <div class="card-body">

                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Imagen</th>
                                    <th>Nombre</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Oferta</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (count($productos) === 0): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No hay productos registrados aún
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($productos as $p): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($p["url_imagen"])): ?>

                                                    <?php
$rutaImagen = $p["url_imagen"];

// ✅ Si es URL externa (Firebase, Cloudinary, etc)
if (preg_match('/^https?:\/\//', $rutaImagen)) {
    // se usa tal cual
} else {
    // ✅ imagen local
    $rutaImagen = PROJECT_BASE . "/Model/" . ltrim($rutaImagen, '/');
}
?>

<img src="<?= htmlspecialchars($rutaImagen) ?>" width="60" class="rounded">


                                                <?php else: ?>
                                                    <span class="text-muted">Sin imagen</span>
                                                <?php endif; ?>
                                            </td>


                                            <td><?= htmlspecialchars($p["nombre"]) ?></td>

                                            <td>$<?= number_format($p["precio"], 2) ?></td>

                                            <td><?= $p["stock"] ?></td>

                                            
<td>
    <?php if (!empty($p["precio_oferta"])): ?>
        <span class="badge bg-warning text-dark">
            <?= (int)$p["precio_oferta"] ?>% OFF
        </span>
    <?php else: ?>
        —
    <?php endif; ?>
</td>




                                            <td>
                                                <?php if ($p["activo"]): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="text-center">
                                                <a href="<?= PROJECT_BASE ?>/panel/productos/editar?id=<?= $p["id_producto"] ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    ✏️
                                                </a>

                                                <form action="<?= PROJECT_BASE ?>/panel/productos/acciones" method="POST" class="d-inline">
                                                    <input type="hidden" name="accion"
                                                        value="<?= $p["activo"] ? 'desactivar' : 'activar' ?>">
                                                    <input type="hidden" name="id_producto"
                                                        value="<?= $p["id_producto"] ?>">

                                                    <button class="btn btn-sm <?= $p["activo"] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                                        onclick="return confirm('¿Seguro?')">
                                                        <?= $p["activo"] ? '🚫' : '✅' ?>
                                                    </button>
                                                </form>

                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>

                        </table>

                    </div>
                </div>


            </main>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>