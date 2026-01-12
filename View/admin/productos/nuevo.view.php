<?php $seccionActiva = "productos"; ?>


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

            <?php include __DIR__ . "/../partials/sidebar.php"; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 py-4">

                <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm">
                    <h2 class="fw-bold text-primary mb-0">➕ Nuevo Producto</h2>
                    <a href="<?= PROJECT_BASE ?>/panel/productos" class="btn btn-outline-secondary">Volver</a>
                </div>

                <form action="<?= PROJECT_BASE ?>/panel/productos/acciones" method="POST" class="card shadow-sm p-4 rounded-4">
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
                            <input type="number" step="0.01" name="precio" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Descuento (%)</label>
                            <input type="number" step="0.01" name="precio_oferta" class="form-control">
                        </div>
                    </div>
                    
                    
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Stock inicial</label>
        <input
            type="number"
            name="stock"
            class="form-control"
            min="1"
            value="1"
            required
        >
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Stock mínimo (Alerta)</label>
        <input
            type="number"
            name="stock_minimo"
            class="form-control"
            min="1"
            value="1"
            required
        >
    </div>
</div>



                    <div class="mb-3">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Imagen del producto</label>

                        <!-- Seleccionar archivo y subir a Firebase Storage -->
                        <input type="file" id="fileImagen" accept="image/*" class="form-control" required>

                        <!-- URL final de Firebase -->
                        <input type="hidden" name="imagen" id="imagenUrl" required>

                        <small class="text-muted d-block mt-2" id="estadoUpload">
                            Selecciona una imagen para subirla a Firebase.
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="aplica_iva" checked>
                        <label class="form-check-label">Aplica IVA</label>
                    </div>

                    <button type="submit" id="btnGuardar" class="btn btn-primary">
    Guardar producto
</button>


                </form>

            </main>
        </div>
    </div>

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
            apiKey: "AIzaSyDkCYDW61i0q186DDEJ-bxAvjp0iAW4-Xc",
            authDomain: "megasantiago.firebaseapp.com",
            projectId: "megasantiago",
            storageBucket: "megasantiago.firebasestorage.app",
            messagingSenderId: "68954725464",
            appId: "1:68954725464:web:7e93567bcc98b4b3d83e2d",
            measurementId: "G-8VB7YCPJW1"
        };

        const app = initializeApp(firebaseConfig);
        const storage = getStorage(app);

        const fileInput = document.getElementById("fileImagen");
        const estado = document.getElementById("estadoUpload");
        const imagenUrl = document.getElementById("imagenUrl");
        const form = document.querySelector("form");
        const btnGuardar = document.getElementById("btnGuardar");

form.addEventListener("submit", (e) => {
  // Evitar enviar si la imagen no está lista
  if (!imagenUrl.value) {
    e.preventDefault();
    alert("Primero selecciona una imagen y espera a que se suba a Firebase.");
    return;
  }

  // Bloquear doble envío
  if (btnGuardar) {
    btnGuardar.disabled = true;
    btnGuardar.innerText = "Guardando...";
  }
});


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
            if (!imagenUrl.value) {
                e.preventDefault();
                alert("Primero selecciona una imagen y espera a que se suba a Firebase.");
            }
        });
    </script>

</body>

</html>