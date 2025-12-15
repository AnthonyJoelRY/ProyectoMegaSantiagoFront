<?php
require_once __DIR__ . "/../../Model/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$accion = $_POST["accion"] ?? "";

$pdo = obtenerConexion();

switch ($accion) {

    case "crear":
        /* ======================
   LEER DATOS
====================== */
        $nombre            = trim($_POST["nombre"]);
        $idCategoria       = $_POST["id_categoria"];
        $descripcionCorta  = trim($_POST["descripcion_corta"]);
        $descripcionLarga  = trim($_POST["descripcion_larga"]);
        $precio            = str_replace(',', '.', $_POST["precio"]);
        $precioOferta = trim($_POST["precio_oferta"] ?? "");
        $precioOferta = ($precioOferta === "") ? null : str_replace(',', '.', $precioOferta);
        $sku               = trim($_POST["sku"]);
        $aplicaIva         = isset($_POST["aplica_iva"]) ? 1 : 0;
        $imagen = trim($_POST["imagen"] ?? "");


        if ($precioOferta !== null) {
            if ($precioOferta < 1 || $precioOferta > 90) {
                die("❌ El descuento debe estar entre 1% y 90%");
            }
        }


        /* ======================
   VALIDAR
====================== */
        if (
            $nombre === "" ||
            $idCategoria === "" ||
            $descripcionCorta === "" ||
            !is_numeric($precio) ||
            $precio <= 0 ||
            $sku === "" ||
            $imagen === ""
        ) {
            die("❌ Datos inválidos");
        }

        /* ======================
   TRANSACTION
====================== */
        $pdo->beginTransaction();

        try {

            /* 1️⃣ PRODUCTO */
            $stmt = $pdo->prepare("
        INSERT INTO productos
        (id_categoria, nombre, descripcion_corta, descripcion_larga,
         precio, precio_oferta, sku, aplica_iva, activo)
        VALUES
        (:id_categoria, :nombre, :descripcion_corta, :descripcion_larga,
         :precio, :precio_oferta, :sku, :aplica_iva, 1)
    ");

            $stmt->execute([
                ":id_categoria"       => $idCategoria,
                ":nombre"             => $nombre,
                ":descripcion_corta"  => $descripcionCorta,
                ":descripcion_larga"  => $descripcionLarga,
                ":precio"             => $precio,
                ":precio_oferta"      => $precioOferta ?: null,
                ":sku"                => $sku,
                ":aplica_iva"         => $aplicaIva
            ]);

            $idProducto = $pdo->lastInsertId();

            // ===============================
            // PROMOCIÓN AUTOMÁTICA (PORCENTAJE)
            // ===============================
            if ($precioOferta !== null && $precioOferta > 0) {

                // 1️⃣ Crear promoción
                $stmtPromo = $pdo->prepare("
        INSERT INTO promociones
        (nombre, tipo_descuento, valor_descuento, activo, fecha_inicio)
        VALUES
        (:nombre, 'porcentaje', :valor, 1, NOW())
    ");
                $stmtPromo->execute([
                    ':nombre' => 'Oferta automática',
                    ':valor'  => $precioOferta
                ]);

                $idPromocion = $pdo->lastInsertId();

                // 2️⃣ Vincular producto con promoción
                $pdo->prepare("
        INSERT INTO promocion_productos (id_promocion, id_producto)
        VALUES (?, ?)
    ")->execute([$idPromocion, $idProducto]);
            }




            $pdo->prepare("
    INSERT INTO producto_imagenes (id_producto, url_imagen, es_principal)
    VALUES (?, ?, 1)
")->execute([
                $idProducto,
                $imagen
            ]);



            /* 3️⃣ INVENTARIO */
            $pdo->prepare("
    INSERT INTO inventario
    (id_producto, stock_actual, ubicacion_almacen, ultima_actualizacion)
    VALUES (?, 0, 'Bodega principal', NOW())
")->execute([$idProducto]);


            $pdo->commit();

            header("Location: index.php?ok=1");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die("❌ Error al guardar producto: " . $e->getMessage());
        }


        break;

    case "editar":

        /* ======================
       LEER DATOS
    ====================== */
        $idProducto        = (int)$_POST["id_producto"];
        $nombre            = trim($_POST["nombre"]);
        $idCategoria       = $_POST["id_categoria"];
        $descripcionCorta  = trim($_POST["descripcion_corta"]);
        $descripcionLarga  = trim($_POST["descripcion_larga"]);
        $precio            = str_replace(',', '.', $_POST["precio"]);
        $precioOferta = trim($_POST["precio_oferta"] ?? "");
        $precioOferta = ($precioOferta === "") ? null : str_replace(',', '.', $precioOferta);
        $sku               = trim($_POST["sku"]);
        $imagen = trim($_POST["imagen"] ?? "");
        $aplicaIva         = isset($_POST["aplica_iva"]) ? 1 : 0;

        if ($precioOferta !== null) {
            if ($precioOferta < 1 || $precioOferta > 90) {
                die("❌ El descuento debe estar entre 1% y 90%");
            }
        }


        /* ======================
       VALIDAR
    ====================== */
        if (
            $idProducto <= 0 ||
            $nombre === "" ||
            !is_numeric($precio) ||
            $precio <= 0
        ) {
            die("❌ Datos inválidos para editar");
        }


        /* ======================
       UPDATE PRODUCTO
    ====================== */
        $stmt = $pdo->prepare("
    UPDATE productos SET
        id_categoria = :id_categoria,
        nombre = :nombre,
        descripcion_corta = :descripcion_corta,
        descripcion_larga = :descripcion_larga,
        precio = :precio,
        precio_oferta = :precio_oferta,
        sku = :sku,
        aplica_iva = :aplica_iva,
        fecha_actualizacion = NOW()
    WHERE id_producto = :id_producto
");

        // ===============================
        // PROMOCIONES (EDITAR)
        // ===============================

        // 1️⃣ Desactivar promociones actuales del producto
        $pdo->prepare("
    UPDATE promociones pr
    JOIN promocion_productos pp ON pp.id_promocion = pr.id_promocion
    SET pr.activo = 0
    WHERE pp.id_producto = ?
")->execute([$idProducto]);

        // 2️⃣ Eliminar vínculos
        $pdo->prepare("
    DELETE FROM promocion_productos
    WHERE id_producto = ?
")->execute([$idProducto]);

        // 3️⃣ Crear nueva promoción SOLO si hay descuento
        if ($precioOferta !== null && is_numeric($precioOferta) && $precioOferta > 0) {

            $stmtPromo = $pdo->prepare("
        INSERT INTO promociones
        (nombre, tipo_descuento, valor_descuento, activo, fecha_inicio)
        VALUES
        ('Oferta automática', 'porcentaje', ?, 1, NOW())
    ");
            $stmtPromo->execute([$precioOferta]);

            $idPromocion = $pdo->lastInsertId();

            $pdo->prepare("
        INSERT INTO promocion_productos (id_promocion, id_producto)
        VALUES (?, ?)
    ")->execute([$idPromocion, $idProducto]);
        }





        /* ======================
   ACTUALIZAR IMAGEN (si se cambió)
====================== */
        if ($imagen !== "") {

            // Desmarcar imagen principal anterior
            $pdo->prepare("
        UPDATE producto_imagenes
        SET es_principal = 0
        WHERE id_producto = ?
    ")->execute([$idProducto]);

            // Insertar nueva imagen principal
            $pdo->prepare("
        INSERT INTO producto_imagenes (id_producto, url_imagen, es_principal)
        VALUES (?, ?, 1)
    ")->execute([$idProducto, $imagen]);
        }

        if ($precioOferta !== null && $precioOferta > 0) {
            // 1. Crear promoción si no existe
            $stmt = $pdo->prepare("
        INSERT INTO promociones (nombre, tipo_descuento, valor_descuento, activo)
        VALUES (:nombre, 'porcentaje', :valor, 1)
    ");
            $stmt->execute([
                ':nombre' => 'Oferta automática',
                ':valor'  => $precioOferta
            ]);

            $idPromocion = $pdo->lastInsertId();

            // 2. Vincular producto a promoción
            $pdo->prepare("
        INSERT INTO promocion_productos (id_promocion, id_producto)
        VALUES (?, ?)
    ")->execute([$idPromocion, $idProducto]);
        }




        $stmt->execute([
            ":id_categoria"      => $idCategoria,
            ":nombre"            => $nombre,
            ":descripcion_corta" => $descripcionCorta,
            ":descripcion_larga" => $descripcionLarga,
            ":precio"            => $precio,
            ":precio_oferta"     => $precioOferta ?: null,
            ":sku"               => $sku,
            ":aplica_iva"        => $aplicaIva,
            ":id_producto"       => $idProducto
        ]);


        header("Location: index.php?edit=1");
        exit;

        break;

    case "desactivar":

        $idProducto = (int)$_POST["id_producto"];

        if ($idProducto <= 0) {
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
        UPDATE productos
        SET activo = 0
        WHERE id_producto = ?
    ");
        $stmt->execute([$idProducto]);

        header("Location: index.php?estado=off");
        exit;


    case "activar":

        $idProducto = (int)$_POST["id_producto"];

        if ($idProducto <= 0) {
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
        UPDATE productos
        SET activo = 1
        WHERE id_producto = ?
    ");
        $stmt->execute([$idProducto]);

        header("Location: index.php?estado=on");
        exit;

    default:
        header("Location: index.php");
        exit;
}
