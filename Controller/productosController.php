<?php
// Controller/productosController.php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../Model/db.php";  // db.php está en Model y devuelve un PDO

$pdo    = obtenerConexion();
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

switch ($accion) {

    /* ======================================================
       LISTAR POR CATEGORÍA (bazar, papeleria, arte, oficina, utiles)
    ======================================================= */
    case 'listarPorCategoria':
        $categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

        if ($categoria === '') {
            echo json_encode(["error" => "Falta parámetro categoria"]);
            exit;
        }

        $sql = "
            SELECT 
                p.id_producto   AS id,
                p.nombre,
                p.descripcion_corta,
                p.precio,
                p.precio_oferta,
                c.slug          AS categoria,
                (
                    SELECT url_imagen
                    FROM producto_imagenes i
                    WHERE i.id_producto = p.id_producto
                    ORDER BY i.es_principal DESC, i.orden ASC, i.id_imagen ASC
                    LIMIT 1
                ) AS imagen
            FROM productos p
            JOIN categorias c ON c.id_categoria = p.id_categoria
            WHERE p.activo = 1
              AND c.slug = :slug
            ORDER BY p.fecha_creacion DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':slug' => $categoria]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($productos);
        break;

    /* ======================================================
       BÚSQUEDA + REGISTRO EN TABLA busquedas
    ======================================================= */
    case 'buscar':
        $termino   = isset($_GET['q']) ? trim($_GET['q']) : '';
        $idUsuario = isset($_GET['id_usuario']) ? intval($_GET['id_usuario']) : null;

        if ($termino === '') {
            echo json_encode(["error" => "Falta parámetro q"]);
            exit;
        }

        $sql = "
            SELECT 
                p.id_producto   AS id,
                p.nombre,
                p.descripcion_corta,
                p.precio,
                p.precio_oferta,
                c.slug          AS categoria,
                (
                    SELECT url_imagen
                    FROM producto_imagenes i
                    WHERE i.id_producto = p.id_producto
                    ORDER BY i.es_principal DESC, i.orden ASC, i.id_imagen ASC
                    LIMIT 1
                ) AS imagen
            FROM productos p
            JOIN categorias c ON c.id_categoria = p.id_categoria
            WHERE p.activo = 1
              AND p.nombre LIKE :busq
            ORDER BY p.nombre
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':busq' => "%$termino%"]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Guardar en tabla busquedas
        $sqlHist = "
            INSERT INTO busquedas (termino, id_usuario, resultados)
            VALUES (:termino, :id_usuario, :resultados)
        ";

        $stmtHist = $pdo->prepare($sqlHist);
        $stmtHist->execute([
            ':termino'    => $termino,
            ':id_usuario' => $idUsuario ?: null,
            ':resultados' => count($productos)
        ]);

        echo json_encode($productos);
        break;

    /* ======================================================
       MÁS VENDIDOS (INDEX) - usa productos activos
    ======================================================= */
    case 'masVendidos':
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 4;
        if ($limit <= 0) { $limit = 4; }

        $sql = "
SELECT 
    p.id_producto   AS id,
    p.nombre,
    p.descripcion_corta,
    p.precio,
    NULL AS precio_oferta,
    c.slug          AS categoria,
    (
        SELECT url_imagen
        FROM producto_imagenes i
        WHERE i.id_producto = p.id_producto
        ORDER BY i.es_principal DESC, i.orden ASC, i.id_imagen ASC
        LIMIT 1
    ) AS imagen
FROM productos p
JOIN categorias c ON c.id_categoria = p.id_categoria
WHERE p.activo = 1
  AND (p.precio_oferta IS NULL OR p.precio_oferta = 0)
ORDER BY p.id_producto ASC
LIMIT $limit

        ";

        $stmt = $pdo->query($sql);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($productos);
        break;

    /* ======================================================
       OFERTAS (INDEX) - usa promociones + promocion_productos
    ======================================================= */
    case 'ofertas':
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
        if ($limit <= 0) { $limit = 6; }

        $sql = "
            SELECT 
                p.id_producto   AS id,
                p.nombre,
                p.descripcion_corta,
                p.precio,
                -- precio_oferta calculado desde la promoción
                CASE pr.tipo_descuento
                    WHEN 'porcentaje' THEN ROUND(p.precio * (1 - pr.valor_descuento/100), 2)
                    WHEN 'monto'      THEN GREATEST(0, p.precio - pr.valor_descuento)
                    ELSE p.precio
                END AS precio_oferta,
                c.slug          AS categoria,
                (
                    SELECT url_imagen
                    FROM producto_imagenes i
                    WHERE i.id_producto = p.id_producto
                    ORDER BY i.es_principal DESC, i.orden ASC, i.id_imagen ASC
                    LIMIT 1
                ) AS imagen
            FROM productos p
            JOIN categorias c          ON c.id_categoria = p.id_categoria
            JOIN promocion_productos pp ON pp.id_producto = p.id_producto
            JOIN promociones pr         ON pr.id_promocion = pp.id_promocion
            WHERE p.activo = 1
              AND pr.activo = 1
            ORDER BY pr.fecha_inicio DESC, p.id_producto ASC
            LIMIT $limit
        ";

        $stmt = $pdo->query($sql);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($productos);
        break;

    /* ======================================================
       PROMOCIONES ESPECIALES (INDEX) - similar a ofertas
    ======================================================= */
    case 'promociones':
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;
        if ($limit <= 0) { $limit = 3; }

        $sql = "
            SELECT 
                p.id_producto   AS id,
                p.nombre,
                p.descripcion_corta,
                p.precio,
                CASE pr.tipo_descuento
                    WHEN 'porcentaje' THEN ROUND(p.precio * (1 - pr.valor_descuento/100), 2)
                    WHEN 'monto'      THEN GREATEST(0, p.precio - pr.valor_descuento)
                    ELSE p.precio
                END AS precio_oferta,
                c.slug          AS categoria,
                (
                    SELECT url_imagen
                    FROM producto_imagenes i
                    WHERE i.id_producto = p.id_producto
                    ORDER BY i.es_principal DESC, i.orden ASC, i.id_imagen ASC
                    LIMIT 1
                ) AS imagen
            FROM productos p
            JOIN categorias c          ON c.id_categoria = p.id_categoria
            JOIN promocion_productos pp ON pp.id_producto = p.id_producto
            JOIN promociones pr         ON pr.id_promocion = pp.id_promocion
            WHERE p.activo = 1
              AND pr.activo = 1
            ORDER BY pr.fecha_inicio DESC, p.id_producto ASC
            LIMIT $limit
        ";

        $stmt = $pdo->query($sql);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($productos);
        break;

    /* ======================================================
       ACCIÓN NO VÁLIDA
    ======================================================= */
    default:
        echo json_encode(["error" => "Acción no válida"]);
        break;
}
