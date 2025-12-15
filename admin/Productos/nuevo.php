<?php
session_start();
$seccionActiva = 'productos';

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== 1) {
    header("Location: /MegaSantiagoFront/index.html");
    exit;
}

require_once __DIR__ . "/../../Model/db.php";
$pdo = obtenerConexion();

/* Categorías */
$categorias = $pdo->query("
    SELECT id_categoria, nombre
    FROM categorias
    WHERE activo = 1
    ORDER BY nombre
")->fetchAll();

/* Imágenes disponibles */
$carpeta = __DIR__ . "/../../Model/imagenes/";
$imagenes = glob($carpeta . "*.{jpg,jpeg,png,webp}", GLOB_BRACE);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nuevo Producto | MegaSantiago</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">

            <?php include __DIR__ . "/../PLANTILLAS/sidebar.php"; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 py-4">

                <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm">
                    <h2 class="fw-bold text-primary mb-0">➕ Nuevo Producto</h2>
                    <a href="index.php" class="btn btn-outline-secondary">Volver</a>
                </div>

                <form action="acciones.php" method="POST" class="card shadow-sm p-4 rounded-4">
                    <input type="hidden" name="accion" value="crear">

                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select name="id_categoria" class="form-select" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c["id_categoria"] ?>"><?= htmlspecialchars($c["nombre"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción corta</label>
                        <input type="text" name="descripcion_corta" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción larga</label>
                        <textarea name="descripcion_larga" class="form-control" rows="4"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Precio</label>
                            <input type="text" name="precio" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Descuento (%)</label>
                            <input type="text" name="precio_oferta" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Imagen del producto</label>
                        <select name="imagen" class="form-select" required>
                            <option value="">Seleccione una imagen</option>

                            <?php foreach ($imagenes as $img): ?>
                                <option value="imagenes/<?= basename($img) ?>">
                                    <?= basename($img) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>


                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="aplica_iva" checked>
                        <label class="form-check-label">Aplica IVA</label>
                    </div>

                    <button class="btn btn-primary fw-semibold">Guardar producto</button>
                </form>

            </main>
        </div>
    </div>
</body>

</html>