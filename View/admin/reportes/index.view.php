<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reportes | MegaSantiago</title>
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

                <h2 class="fw-bold text-primary mb-4">📊 Reportes - Resumen General</h2>

                <!-- =======================
                      KPIs
                ======================= -->
                <div class="row g-4 mb-5">

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 text-center">
                            <small class="text-muted">💰 Total ventas</small>
                            <h3 id="kpiVentas">$0.00</h3>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 text-center">
                            <small class="text-muted">📦 Pedidos pagados</small>
                            <h3 id="kpiPedidos">0</h3>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 text-center">
                            <small class="text-muted">👤 Clientes</small>
                            <h3 id="kpiClientes">0</h3>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 text-center">
                            <small class="text-muted">📊 Promedio por pedido</small>
                            <h3 id="kpiPromedio">$0.00</h3>
                        </div>
                    </div>
                </div>

                <!-- IVA -->
                <div class="col-md-3 mb-5">
                    <div class="card shadow-sm p-3 text-center">
                        <small class="text-muted">💸 IVA recaudado</small>
                        <h3 id="ventasIVA">$0.00</h3>
                    </div>
                </div>


                <!-- =======================
                      VENTAS POR DÍA
                ======================= -->
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">📅 Ventas por día</h5>

                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="tablaVentasDia">
                                <tr>
                                    <td colspan="2" class="text-muted text-center">Cargando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>


                <!-- =======================
                      VENTAS POR MES
                ======================= -->
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">📆 Ventas por mes</h5>

                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Mes</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="tablaVentasMes">
                                <tr>
                                    <td colspan="2" class="text-muted text-center">Cargando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>


                <!-- =======================
                      PRODUCTOS MÁS VENDIDOS
                ======================= -->
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">📦 Productos más vendidos (TOP 5)</h5>

                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad vendida</th>
                                </tr>
                            </thead>
                            <tbody id="tablaProductosVendidos">
                                <tr>
                                    <td colspan="2" class="text-muted text-center">Cargando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>


                <!-- =======================
                      PRODUCTOS MENOS VENDIDOS
                ======================= -->
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">📉 Productos menos vendidos (TOP 5)</h5>

                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>Unidades vendidas</th>
                                </tr>
                            </thead>
                            <tbody id="tablaProductosMenosVendidos">
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Cargando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>


                <!-- =======================
                      CLIENTES CON MÁS COMPRAS
                ======================= -->
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">👥 Clientes con más compras</h5>

                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Pedidos</th>
                                    <th>Total gastado</th>
                                </tr>
                            </thead>
                            <tbody id="tablaClientesTop">
                                <tr>
                                    <td colspan="3" class="text-muted text-center">Cargando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        const API = "<?= PROJECT_BASE ?>/Controller/reportesController.php";

        // KPIs
        fetch(API + "?accion=kpis")
            .then(r => r.json())
            .then(d => {
                document.getElementById("kpiVentas").textContent = "$" + Number(d.ventasTotales).toFixed(2);
                document.getElementById("kpiPedidos").textContent = d.totalPedidos;
                document.getElementById("kpiClientes").textContent = d.totalClientes;
                document.getElementById("kpiPromedio").textContent = "$" + Number(d.promedioPorPedido).toFixed(2);
                document.getElementById("ventasIVA").textContent = "$" + Number(d.totalIVA).toFixed(2);
            });

        // Ventas por día
        fetch(API + "?accion=ventasDia")
            .then(r => r.json())
            .then(rows => {
                const tbody = document.getElementById("tablaVentasDia");
                tbody.innerHTML = "";
                if (!rows.length) {
                    tbody.innerHTML = `<tr><td colspan="2" class="text-muted text-center">Sin datos</td></tr>`;
                    return;
                }
                rows.forEach(r => {
                    tbody.innerHTML += `<tr><td>${r.fecha}</td><td>$${Number(r.total).toFixed(2)}</td></tr>`;
                });
            });

        // Ventas por mes
        fetch(API + "?accion=ventasMes")
            .then(r => r.json())
            .then(rows => {
                const tbody = document.getElementById("tablaVentasMes");
                tbody.innerHTML = "";
                if (!rows.length) {
                    tbody.innerHTML = `<tr><td colspan="2" class="text-muted text-center">Sin datos</td></tr>`;
                    return;
                }
                rows.forEach(r => {
                    tbody.innerHTML += `<tr><td>${r.mes}</td><td>$${Number(r.total).toFixed(2)}</td></tr>`;
                });
            });

        // Productos más vendidos
        fetch(API + "?accion=productosMasVendidos")
            .then(r => r.json())
            .then(rows => {
                const tbody = document.getElementById("tablaProductosVendidos");
                tbody.innerHTML = "";
                if (!rows.length) {
                    tbody.innerHTML = `<tr><td colspan="2" class="text-muted text-center">Sin datos</td></tr>`;
                    return;
                }
                rows.forEach(p => {
                    tbody.innerHTML += `<tr><td>${p.nombre}</td><td>${p.total_vendido}</td></tr>`;
                });
            });

        // Productos menos vendidos
        fetch(API + "?accion=productosMenosVendidos")
            .then(r => r.json())
            .then(rows => {
                const tbody = document.getElementById("tablaProductosMenosVendidos");
                tbody.innerHTML = "";
                if (!rows.length) {
                    tbody.innerHTML = `<tr><td colspan="2" class="text-center text-muted">Sin datos</td></tr>`;
                    return;
                }
                rows.forEach(p => {
                    tbody.innerHTML += `<tr><td>${p.nombre}</td><td>${p.total_vendido}</td></tr>`;
                });
            });

        // Clientes top
        fetch(API + "?accion=clientesTop")
            .then(r => r.json())
            .then(rows => {
                const tbody = document.getElementById("tablaClientesTop");
                tbody.innerHTML = "";
                if (!rows.length) {
                    tbody.innerHTML = `<tr><td colspan="3" class="text-muted text-center">Sin datos</td></tr>`;
                    return;
                }
                rows.forEach(r => {
                    tbody.innerHTML += `<tr><td>${r.cliente}</td><td>${r.total_pedidos}</td><td>$${Number(r.total_gastado).toFixed(2)}</td></tr>`;
                });
            });
    </script>

</body>
</html>
