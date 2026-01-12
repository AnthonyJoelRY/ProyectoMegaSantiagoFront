<?php $seccionActiva = "productos"; ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Producto | MegaSantiago</title>
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

                <!-- CABECERA -->
                <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm">
                    <h2 class="fw-bold text-primary mb-0">✏️ Editar Producto</h2>
                    <a href="<?= PROJECT_BASE ?>/panel/productos" class="btn btn-outline-secondary">Volver</a>
                </div>

                <!-- FORMULARIO -->
                <div class="card shadow-sm rounded-4 border-0 bg-white p-4">

                    <form action="<?= PROJECT_BASE ?>/panel/productos/acciones" method="POST">
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id_producto" value="<?= $producto["id_producto"] ?>">

                        <div class="row">

                            <!-- NOMBRE -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nombre</label>
                                <input type="text"
                                    name="nombre"
                                    class="form-control"
                                    value="<?= htmlspecialchars($producto["nombre"]) ?>"
                                    required>
                            </div>

                            <!-- SKU -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">SKU</label>
                                <input type="text"
                                    name="sku"
                                    class="form-control"
                                    value="<?= htmlspecialchars($producto["sku"]) ?>"
                                    required>
                            </div>

                            <!-- Imagen -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Imagen del producto</label>

                                <input type="file" id="fileImagen" accept="image/*" class="form-control">
                                <input type="hidden" name="imagen" id="imagenUrl">

                                <?php if (!empty($producto["url_imagen"])): ?>
                                    <small class="text-muted d-block mt-2">
                                        Imagen actual:
                                        <a href="<?= htmlspecialchars($producto["url_imagen"]) ?>" target="_blank">ver</a>
                                    </small>
                                <?php endif; ?>

                                <small class="text-muted d-block mt-1" id="estadoUpload">
                                    Si seleccionas una imagen nueva, se subirá a Firebase y reemplazará la actual.
                                </small>
                            </div>

                            <!-- CATEGORÍA -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Categoría</label>
                                <select name="id_categoria" class="form-select" required>
                                    <option value="">Seleccione una categoría</option>

                                    <?php foreach ($categorias as $c): ?>
                                        <option value="<?= $c["id_categoria"] ?>"
                                            <?= $c["id_categoria"] == $producto["id_categoria"] ? "selected" : "" ?>>
                                            <?= htmlspecialchars($c["nombre"]) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- PRECIO -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Precio</label>
                                <input type="number"
                                    step="0.01"
                                    name="precio"
                                    class="form-control"
                                    value="<?= $producto["precio"] ?>"
                                    required>
                            </div>

                            <!-- PRECIO OFERTA -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Descuento (%)</label>
                                <input type="number"
                                    step="0.01"
                                    name="precio_oferta"
                                    class="form-control"
                                    value="<?= $producto["precio_oferta"] ?>">
                            </div>

                            <!-- IVA -->
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="checkbox"
                                        name="aplica_iva"
                                        id="aplicaIva"
                                        <?= $producto["aplica_iva"] ? "checked" : "" ?>>
                                    <label class="form-check-label fw-semibold" for="aplicaIva">
                                        Aplica IVA
                                    </label>
                                </div>
                            </div>
                            
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Stock actual</label>
        <input
            type="number"
            name="stock"
            class="form-control"
            min="0"
            value="<?= (int)($producto["stock_actual"] ?? 0) ?>"
            required
        >
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Stock mínimo</label>
        <input
            type="number"
            name="stock_minimo"
            class="form-control"
            min="0"
            value="<?= (int)($producto["stock_minimo"] ?? 0) ?>"
            required
        >
    </div>
</div>



                            <!-- DESCRIPCIÓN CORTA -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold">Descripción corta</label>
                                <input type="text"
                                    name="descripcion_corta"
                                    class="form-control"
                                    value="<?= htmlspecialchars($producto["descripcion_corta"]) ?>"
                                    required>
                            </div>

                            <!-- DESCRIPCIÓN LARGA -->
                            <div class="col-md-12 mb-4">
                                <label class="form-label fw-semibold">Descripción larga</label>
                                <textarea name="descripcion_larga"
                                    class="form-control"
                                    rows="4"><?= htmlspecialchars($producto["descripcion_larga"]) ?></textarea>
                            </div>
                        </div>

                        <!-- BOTÓN -->
                        <button class="btn btn-primary fw-semibold">
                            Guardar cambios
                        </button>
                    </form>

                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script type="module">
        import {
            initializeApp
        } from "https://www.gstatic.com/firebasejs/10.14.1/firebase-app.js";
        import {
            getStorage,
            ref,
            uploadBytes,
            getDownloadURL
        } from "https://www.gstatic.com/firebasejs/10.14.1/firebase-storage.js";

        const firebaseConfig = {
            apiKey: "TU_API_KEY",
            authDomain: "TU_AUTH_DOMAIN",
            projectId: "TU_PROJECT_ID",
            storageBucket: "TU_STORAGE_BUCKET",
            messagingSenderId: "TU_SENDER_ID",
            appId: "TU_APP_ID"
        };

        const app = initializeApp(firebaseConfig);
        const storage = getStorage(app);

        const fileInput = document.getElementById("fileImagen");
        const estado = document.getElementById("estadoUpload");
        const imagenUrl = document.getElementById("imagenUrl");
        const form = document.querySelector("form");

        function nombreSeguro(nombre) {
            return nombre
                .toLowerCase()
                .replace(/\s+/g, "_")
                .replace(/[^a-z0-9._-]/g, "");
        }

        async function subirImagen(file) {
            const stamp = Date.now();
            const path = `productos/${stamp}_${nombreSeguro(file.name)}`;
            const storageRef = ref(storage, path);

            await uploadBytes(storageRef, file, {
                contentType: file.type
            });
            return await getDownloadURL(storageRef);
        }

        fileInput.addEventListener("change", async () => {
            const file = fileInput.files?.[0];
            if (!file) return;

            estado.textContent = "Subiendo imagen a Firebase...";
            imagenUrl.value = "";

            try {
                const url = await subirImagen(file);
                imagenUrl.value = url;
                estado.textContent = "✅ Imagen subida correctamente.";
            } catch (e) {
                console.error(e);
                estado.textContent = "❌ Error subiendo imagen. Revisa Rules de Storage y consola.";
            }
        });

        form.addEventListener("submit", (e) => {
            if (fileInput.files?.length && !imagenUrl.value) {
                e.preventDefault();
                alert("Espera a que la imagen termine de subirse a Firebase antes de guardar.");
            }
        });
    </script>

</body>

</html>