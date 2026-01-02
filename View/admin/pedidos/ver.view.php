<?php $seccionActiva = "pedidos"; ?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalle Pedido #<?= $pedido["id_pedido"] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container-fluid">
        <div class="row">

            <?php include __DIR__ . "/../partials/sidebar.php"; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 py-4">

                <h2 class="mb-4">📦 Pedido #<?= $pedido["id_pedido"] ?></h2>

                <div class="card mb-4 p-3">
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido["cliente"]) ?></p>
                    <p><strong>Fecha:</strong> <?= $pedido["fecha_pedido"] ?></p>
                    <p><strong>Estado:</strong> <?= ucfirst($pedido["estado"]) ?></p>
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
                                <?php foreach ($detalles as $d): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d["nombre"]) ?></td>
                                        <td><?= $d["cantidad"] ?></td>
                                        <td>$<?= number_format($d["precio_unit"], 2) ?></td>
                                        <td>$<?= number_format($d["subtotal"], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <hr>

                        <div class="text-end">
                            <p>Total productos: <strong>$<?= number_format($pedido["total_productos"], 2) ?></strong></p>
                            <p>IVA: <strong>$<?= number_format($pedido["total_iva"], 2) ?></strong></p>
                            <h5>Total a pagar: <strong>$<?= number_format($pedido["total_pagar"], 2) ?></strong></h5>
                        </div>

                    </div>
                </div>

                <a href="<?= PROJECT_BASE ?>/panel/pedidos" class="btn btn-secondary mt-3">⬅ Volver</a>

            </main>
        </div>
    </div>

</body>

</html>