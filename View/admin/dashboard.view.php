<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Dashboard | MegaSantiago</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background-color: #212529 !important;
        }

        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--bs-info);
        }

        .nav-link {
            padding-left: 1.5rem;
        }

        .card-body h2 {
            font-size: 2.5rem;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid">
        <div class="row">

            <?php $seccionActiva = "dashboard"; require __DIR__ . "/partials/sidebar.php"; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 py-4">

                <div class="d-flex justify-content-between align-items-center mb-5 bg-white p-3 rounded-4 shadow-sm border-bottom border-primary border-3">
                    <h1 class="h2 text-primary fw-bold mb-0">Panel de Administración</h1>
                    <small class="text-muted">Bienvenido, Admin</small>
                </div>

                <h4 class="mb-4 text-dark fw-bold border-bottom pb-2">Resumen General</h4>
                <div class="row g-4 mb-5">

                    <div class="col-md-2">
                        <div class="card shadow rounded-4 border-0 h-100 bg-white">
                            <div class="card-body text-center">
                                <small class="text-muted d-block mb-1 fw-semibold">Productos</small>
                                <h2 class="fw-bolder text-primary mt-2"><?= $totalProductos ?></h2>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="card shadow rounded-4 border-0 h-100 bg-white">
                            <div class="card-body text-center">
                                <small class="text-muted d-block mb-1 fw-semibold">Usuarios</small>
                                <h2 class="fw-bolder text-success mt-2"><?= $totalUsuarios ?></h2>
                            </div>
                        </div>
                    </div>

                   
                    <div class="col-md-3">
                        <div class="card shadow rounded-4 border-0 h-100 bg-white">
                            <div class="card-body text-center">
                                <small class="text-muted d-block mb-1 fw-semibold">Productos en Oferta</small>
                                <h2 class="fw-bolder text-info mt-2"><?= $productosOferta ?></h2>
                            </div>
                        </div>
                    </div>

                </div>

                <h4 class="mb-4 text-dark fw-bold border-bottom pb-2">Catálogo</h4>
                <div class="row g-4 mb-5">

                    <div class="col-md-4">
                        <div class="card shadow rounded-4 border-0 h-100 bg-white">
                            <div class="card-body text-center">
                                <small class="text-muted d-block mb-1 fw-semibold">Productos Activos</small>
                                <h2 class="fw-bolder text-success mt-2"><?= $productosActivos ?></h2>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow rounded-4 border-0 h-100 bg-white">
                            <div class="card-body text-center">
                                <small class="text-muted d-block mb-1 fw-semibold">Sin Stock</small>
                                <h2 class="fw-bolder text-danger mt-2"><?= $sinStock ?></h2>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow rounded-4 border-0 h-100 bg-white">
                            <div class="card-body text-center">
                                <small class="text-muted d-block mb-1 fw-semibold">Stock Bajo</small>
                                <h2 class="fw-bolder text-warning mt-2"><?= $stockBajo ?></h2>
                            </div>
                        </div>
                    </div>

                </div>

                <h4 class="mb-4 text-dark fw-bold border-bottom pb-2">Usuarios</h4>
                <div class="row g-4 mb-5">

                    <div class="col-md-6">
                        <div class="card shadow rounded-4 border-0 h-100 bg-white">
                            <div class="card-body text-center">
                                <small class="text-muted d-block mb-1 fw-semibold">Administradores</small>
                                <h2 class="fw-bolder text-primary mt-2"><?= $admins ?></h2>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card shadow rounded-4 border-0 h-100 bg-white">
                            <div class="card-body text-center">
                                <small class="text-muted d-block mb-1 fw-semibold">Clientes</small>
                                <h2 class="fw-bolder text-secondary mt-2"><?= $clientes ?></h2>
                            </div>
                        </div>
                    </div>

                </div>

                <h4 class="mb-4 text-dark fw-bold border-bottom pb-2">Alertas</h4>
                <div class="row g-4 mb-4">

                    <div class="col-md-6">
                        <div class="card shadow rounded-4 border border-info border-3 h-100 bg-white">
                            <div class="card-body text-center">
                                <small class="text-muted d-block mb-1 fw-semibold">Último Usuario Registrado</small>
                                <h5 class="fw-bolder mt-2 text-info"><?= $ultimoUsuario ?: 'N/A' ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card shadow rounded-4 border border-info border-3 h-100 bg-white">
                            <div class="card-body text-center">
                                <small class="text-muted d-block mb-1 fw-semibold">Último Producto Añadido</small>
                                <h5 class="fw-bolder mt-2 text-info"><?= $ultimoProducto ?: 'N/A' ?></h5>
                            </div>
                        </div>
                    </div>

                </div>

            </main>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>