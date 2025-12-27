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
}
