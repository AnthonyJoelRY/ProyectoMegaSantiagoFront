<?php $seccionActiva = "pedidos"; ?>

<?php
// ✅ Asegurar que $pedidos siempre sea un array (evita warnings/500 en PHP 8)
$pedidos = is_array($pedidos ?? null) ? $pedidos : [];
?>

<?php
// ✅ Asegurar sesión para saber rol
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$__rol = isset($_SESSION["rol"]) ? (int)$_SESSION["rol"] : 0;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Pedidos | MegaSantiago</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container-fluid">
        <div class="row">

            <!-- SIDEBAR -->
            <?php include __DIR__ . "/../partials/sidebar.php"; ?>

            <!-- CONTENIDO -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 py-4">

                <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm">
                    <h2 class="fw-bold text-primary mb-0">🛒 Pedidos</h2>
                </div>

                <form method="GET" class="row g-2 mb-4">
                    <div class="col-md-4">
                        <input
                            type="text"
                            name="q"
                            class="form-control"
                            placeholder="Buscar por #pedido o correo"
                            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">
                            🔍 Buscar
                        </button>
                    </div>
                </form>

                <div class="card shadow-sm rounded-4 border-0 bg-white">
                    <div class="card-body">

                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($pedidos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No hay pedidos registrados
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pedidos as $p): ?>
                                        <tr>
                                            <td><?= $p["id_pedido"] ?></td>
                                            <td><?= htmlspecialchars($p["cliente"]) ?></td>
                                            <td><?= date("d/m/Y H:i", strtotime($p["fecha_pedido"])) ?></td>
                                            <td>$<?= number_format((float)$p["total_pagar"], 2) ?></td>
                                            <td>
                                                <?php
                                                $estado = (string)($p["estado"] ?? "");
                                                $tipoEntrega = (string)($p["tipo_entrega"] ?? "envio");

                                                $colorEstado = match ($estado) {
                                                    "pendiente"           => "warning",
                                                    "pagado"              => "success",
                                                    "en_proceso"          => "secondary",
                                                    "listo_para_entregar" => "info",
                                                    "en_camino"           => "info",
                                                    "entregado"           => "primary",
                                                    "cancelado"           => "danger",
                                                    default               => "secondary"
                                                };
                                                ?>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="badge bg-<?= $colorEstado ?>">
                                                        <?= htmlspecialchars($estado) ?>
                                                    </span>

                                                    <?php if ($__rol === 4 || $__rol === 1): ?>
                                                        <?php
                                                        $__estados = ($tipoEntrega === "retiro_local")
                                                            ? ["en_proceso" => "En proceso", "listo_para_entregar" => "Listo para entregar"]
                                                            : ["en_proceso" => "En proceso", "en_camino" => "En camino", "entregado" => "Entregado"];
                                                        ?>
                                                        <form method="POST"
                                                            action="<?= PROJECT_BASE ?>/panel/pedidos/estado"
                                                            class="d-flex gap-2 align-items-center">
                                                            <input type="hidden" name="id_pedido" value="<?= (int)$p["id_pedido"] ?>">
                                                            <input type="hidden" name="redirect_to" value="<?= PROJECT_BASE ?>/panel/pedidos">
                                                            <select name="estado" class="form-select form-select-sm" style="max-width:220px;">
                                                                <?php foreach ($__estados as $k => $label): ?>
                                                                    <option value="<?= htmlspecialchars($k) ?>" <?= ($estado === $k) ? "selected" : "" ?>>
                                                                        <?= htmlspecialchars($label) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <button class="btn btn-sm btn-outline-success" type="submit">Guardar</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= PROJECT_BASE ?>/panel/pedidos/ver?id=<?= (int)$p["id_pedido"] ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    👁️
                                                </a>
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
