<?php
session_start();

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== 1) {
    header("Location: /MegaSantiagoFront/index.html");
    exit;
}

require_once __DIR__ . "/../../Model/db.php";

$pdo = obtenerConexion();

$busqueda = trim($_GET["q"] ?? "");


$sql = "
    SELECT 
        p.id_producto,
        p.nombre,
        p.precio,
        p.precio_oferta,
        p.activo,
        IFNULL(i.stock_actual, 0) AS stock,
        img.url_imagen
    FROM productos p
    LEFT JOIN inventario i ON i.id_producto = p.id_producto
    LEFT JOIN producto_imagenes img 
        ON img.id_producto = p.id_producto AND img.es_principal = 1
";

$params = [];

if ($busqueda !== "") {
    $sql .= " WHERE p.nombre LIKE :q OR p.sku LIKE :q ";
    $params[":q"] = "%" . $busqueda . "%";
}

$sql .= " ORDER BY p.id_producto DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$productos = $stmt->fetchAll();

?>





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

            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse min-vh-100 p-0 shadow-lg">
                <div class="position-sticky pt-4">
                    <div class="d-flex align-items-center justify-content-center mb-4 pb-2 border-bottom border-light opacity-50 mx-3">
                        <h4 class="text-white fw-bolder my-0">
                            MegaSantiago
                        </h4>
                    </div>
                    <ul class="nav flex-column px-2">
                        <li class="nav-item">
                            <a class="nav-link text-white rounded-2"
                                href="/MegaSantiagoFront/admin/dashboard.php">
                                🏠 Dashboard
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link text-white active rounded-2"
                                href="/MegaSantiagoFront/admin/productos/">
                                📦 Productos
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link text-white rounded-2"
                                href="/MegaSantiagoFront/admin/usuarios/">
                                👥 Usuarios
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link text-white rounded-2"
                                href="/MegaSantiagoFront/admin/pedidos/">
                                🛒 Pedidos
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link text-white rounded-2"
                                href="/MegaSantiagoFront/admin/reportes/">
                                📈 Reportes
                            </a>
                        </li>
                    </ul>

                    <div class="px-3 mt-5">
                        <a class="nav-link text-white bg-danger bg-opacity-75 hover-bg-danger rounded-3 p-2 text-center fw-semibold" href="/MegaSantiagoFront/index.html">
                            <span class="me-2">↩️</span> Volver al sitio
                        </a>
                    </div>
                </div>
            </nav>

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



                    <a href="/MegaSantiagoFront/admin/productos/nuevo.php"
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

                                                    // Si la ruta NO empieza con "/", la hacemos absoluta
                                                    if (strpos($rutaImagen, '/') !== 0) {
                                                        $rutaImagen = "/MegaSantiagoFront/Model/" . $rutaImagen;
                                                    }
                                                    ?>

                                                    <img src="<?= $rutaImagen ?>" width="60" class="rounded">

                                                <?php else: ?>
                                                    <span class="text-muted">Sin imagen</span>
                                                <?php endif; ?>
                                            </td>


                                            <td><?= htmlspecialchars($p["nombre"]) ?></td>

                                            <td>$<?= number_format($p["precio"], 2) ?></td>

                                            <td><?= $p["stock"] ?></td>

                                            <td>
                                                <?php if ($p["precio_oferta"] ? $p["precio_oferta"] . "%" : "—"): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        $<?= number_format($p["precio_oferta"], 2) ?>
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
                                                <a href="editar.php?id=<?= $p["id_producto"] ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    ✏️
                                                </a>

                                                <form action="acciones.php" method="POST" class="d-inline">
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