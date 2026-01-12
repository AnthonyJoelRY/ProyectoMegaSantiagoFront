<?php
// Model/DAO/ProductoDAO.php

class ProductoDAO
{
    public function __construct(private PDO $pdo) {}

    public function listarPorCategoria(string $slug): array
    {
        $sql = "
            SELECT 
                p.id_producto AS id,
                p.nombre,
                p.descripcion_corta,
                p.precio,
                p.precio_oferta,
                c.slug AS categoria,
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

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([":slug" => $slug]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorNombre(string $termino): array
    {
        $sql = "
            SELECT 
                p.id_producto AS id,
                p.nombre,
                p.descripcion_corta,
                p.precio,
                p.precio_oferta,
                c.slug AS categoria,
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

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([":busq" => "%{$termino}%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function masVendidos(int $limit): array
    {
        $limit = max(1, (int)$limit);

        $sql = "
            SELECT 
                p.id_producto AS id,
                p.nombre,
                p.descripcion_corta,
                p.precio,
                NULL AS precio_oferta,
                c.slug AS categoria,
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
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ofertas(int $limit): array
    {
        $limit = max(1, (int)$limit);

        $sql = "
            SELECT 
                p.id_producto AS id,
                p.nombre,
                p.descripcion_corta,
                p.precio,
                CASE pr.tipo_descuento
                    WHEN 'porcentaje' THEN ROUND(p.precio * (1 - pr.valor_descuento/100), 2)
                    WHEN 'monto'      THEN GREATEST(0, p.precio - pr.valor_descuento)
                    ELSE p.precio
                END AS precio_oferta,
                c.slug AS categoria,
                (
                    SELECT url_imagen
                    FROM producto_imagenes i
                    WHERE i.id_producto = p.id_producto
                    ORDER BY i.es_principal DESC, i.orden ASC, i.id_imagen ASC
                    LIMIT 1
                ) AS imagen
            FROM productos p
            JOIN categorias c            ON c.id_categoria = p.id_categoria
            JOIN promocion_productos pp  ON pp.id_producto = p.id_producto
            JOIN promociones pr          ON pr.id_promocion = pp.id_promocion
            WHERE p.activo = 1
              AND pr.activo = 1
            ORDER BY pr.fecha_inicio DESC, p.id_producto ASC
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function promociones(int $limit): array
    {
        return $this->ofertas($limit);
    }

    public function obtenerPorIds(array $ids): array
    {
        if (count($ids) === 0) return [];

        // Sanitiza ids
        $ids = array_values(array_filter(array_map(fn($x) => (int)$x, $ids), fn($x) => $x > 0));
        if (count($ids) === 0) return [];

        $placeholders = implode(",", array_fill(0, count($ids), "?"));

        // ✅ CAMBIO: tabla correcta es "productos"
        $sql = "
            SELECT 
                p.id_producto AS id,
                p.precio,
                p.precio_oferta
            FROM productos p
            WHERE p.id_producto IN ($placeholders)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve el detalle completo de un producto.
     * Si en el futuro agregas variantes (colores/tallas), este método se puede extender.
     */
    public function obtenerDetalle(int $idProducto): ?array
    {
        $sql = "
            SELECT
                p.id_producto AS id,
                p.nombre,
                p.descripcion_corta,
                p.descripcion_larga,
                p.precio,
                p.precio_oferta,
                p.sku,
                p.aplica_iva,
                c.id_categoria,
                c.nombre AS categoria_nombre,
                c.slug   AS categoria_slug
            FROM productos p
            JOIN categorias c ON c.id_categoria = p.id_categoria
            WHERE p.id_producto = :id
              AND p.activo = 1
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([":id" => $idProducto]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $row["imagenes"] = $this->obtenerImagenes($idProducto);

        // Por defecto no manejamos variantes.
        $row["colores"] = [];

        return $row;
    }

    public function obtenerImagenes(int $idProducto): array
    {
        $sql = "
            SELECT url_imagen, es_principal, orden
            FROM producto_imagenes
            WHERE id_producto = :id
            ORDER BY es_principal DESC, orden ASC, id_imagen ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([":id" => $idProducto]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function relacionados(int $idProducto, int $limit = 4): array
    {
        $limit = max(1, (int)$limit);

        // Toma la categoría del producto y devuelve otros productos de esa misma categoría.
        $sql = "
            SELECT
                p.id_producto AS id,
                p.nombre,
                p.descripcion_corta,
                p.precio,
                p.precio_oferta,
                c.slug AS categoria,
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
              AND p.id_categoria = (SELECT id_categoria FROM productos WHERE id_producto = :id)
              AND p.id_producto <> :id
            ORDER BY p.fecha_creacion DESC, p.id_producto DESC
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([":id" => $idProducto]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function listarConPromocion(int $limit = 6): array
{
    $sql = "
        SELECT
            p.id_producto,
            p.nombre,
            p.precio,
            img.url_imagen AS imagen,
            pr.valor_descuento AS descuento
        FROM productos p
        INNER JOIN promocion_productos pp ON pp.id_producto = p.id_producto
        INNER JOIN promociones pr ON pr.id_promocion = pp.id_promocion
        LEFT JOIN producto_imagenes img
            ON img.id_producto = p.id_producto AND img.es_principal = 1
        WHERE p.activo = 1
          AND pr.activo = 1
          AND pr.tipo_descuento = 'porcentaje'
        ORDER BY pr.fecha_inicio DESC
        LIMIT :lim
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(":lim", $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

}
