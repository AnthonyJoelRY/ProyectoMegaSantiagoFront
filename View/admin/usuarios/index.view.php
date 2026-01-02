<?php $seccionActiva = "usuarios"; ?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Usuarios | MegaSantiago</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">

            <?php include __DIR__ . "/../partials/sidebar.php"; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 py-4">

                <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm">
                    <h2 class="fw-bold text-primary mb-0">👥 Gestión de Usuarios</h2>
                </div>

                <!-- MENSAJES DE ESTADO -->
                <?php if (isset($_GET["rol"]) && $_GET["rol"] === "ok"): ?>
                    <div class="alert alert-success rounded-4 shadow-sm">
                        ✅ Rol actualizado correctamente.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET["toggle"])): ?>
                    <div class="alert alert-success rounded-4 shadow-sm">
                        ✅ Estado del usuario actualizado.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET["edit"])): ?>
                    <div class="alert alert-success rounded-4 shadow-sm">
                        ✅ Usuario editado correctamente.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET["error"]) && $_GET["error"] === "self_role"): ?>
                    <div class="alert alert-danger rounded-4 shadow-sm">
                        ❌ No puedes cambiar tu propio rol.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET["error"]) && $_GET["error"] === "self_toggle"): ?>
                    <div class="alert alert-danger rounded-4 shadow-sm">
                        ❌ No puedes desactivar tu propio usuario.
                    </div>
                <?php endif; ?>




                <div class="card rounded-4 shadow-sm border-0 bg-white">
                    <div class="card-body">

                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($u["nombre"] . " " . $u["apellido"]) ?></td>
                                        <td><?= htmlspecialchars($u["email"]) ?></td>

                                        <!-- ROL -->
                                        <td>
                                            <form action="<?= PROJECT_BASE ?>/panel/usuarios/acciones" method="POST" class="d-flex gap-2">
                                                <input type="hidden" name="accion" value="cambiar_rol">
                                                <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">

                                                <select name="id_rol" class="form-select form-select-sm">
                                                    <?php foreach ($roles as $r): ?>
                                                        <option value="<?= $r["id_rol"] ?>"
                                                            <?= $r["id_rol"] == $u["id_rol"] ? "selected" : "" ?>>
                                                            <?= $r["nombre"] ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <button class="btn btn-sm btn-outline-primary">✔</button>
                                            </form>
                                        </td>

                                        <!-- ESTADO -->
                                        <td>
                                            <?php if ($u["activo"]): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- ACCIONES -->
                                        <td class="text-center">

                                            <!-- EDITAR -->
                                            <a href="<?= PROJECT_BASE ?>/panel/usuarios/editar?id=<?= $u["id_usuario"] ?>"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Editar usuario">
                                                ✏️
                                            </a>

                                            <!-- ACTIVAR / DESACTIVAR -->
                                            <form action="<?= PROJECT_BASE ?>/panel/usuarios/acciones" method="POST" class="d-inline">
                                                <input type="hidden" name="accion" value="toggle">
                                                <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">

                                                <button class="btn btn-sm btn-outline-danger"
                                                    title="Activar / Desactivar"
                                                    onclick="return confirm('¿Cambiar estado del usuario?')">
                                                    ❌
                                                </button>
                                            </form>

                                        </td>


                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>

                    </div>
                </div>

            </main>
        </div>
    </div>
</body>

</html>