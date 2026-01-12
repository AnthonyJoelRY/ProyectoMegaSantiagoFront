<?php
// Model/DAO/Admin/AdminProductoDAO.php

class AdminProductoDAO {
    public function __construct(private PDO $pdo) {}
    
    private function assertIdProducto(int $idProducto): void
{
    if ($idProducto <= 0) {
        throw new Exception("❌ ID de producto inválido: " . $idProducto);
    }
}


    public function listar(?string $q = null): array {
        $sql = "
            SELECT
                p.id_producto,
                p.nombre,
                p.sku,
                p.precio,
                p.precio_oferta,
                p.activo,
                IFNULL(i.stock_actual, 0) AS stock,
                img.url_imagen
            FROM productos p
            LEFT JOIN inventario i ON i.id_producto = p.id_producto
            LEFT JOIN producto_imagenes img
                ON img.id_producto = p.id_producto AND img.es_principal = 1
        ";
        $params = [];
        if ($q !== null && trim($q) !== '') {
            $sql .= " WHERE p.nombre LIKE :q OR p.sku LIKE :q ";
            $params[":q"] = "%" . trim($q) . "%";
        }
        $sql .= " ORDER BY p.id_producto DESC ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function obtenerPorId(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.id_producto,
                p.id_categoria,
                p.nombre,
                p.descripcion_corta,
                p.descripcion_larga,
                p.precio,
                p.precio_oferta,
                p.sku,
                p.aplica_iva,
                p.activo,
                p.stock_minimo,
                IFNULL(i.stock_actual, 0) AS stock_actual,
                img.url_imagen
            FROM productos p
            LEFT JOIN inventario i ON i.id_producto = p.id_producto
            LEFT JOIN producto_imagenes img 
                ON img.id_producto = p.id_producto AND img.es_principal = 1
            WHERE p.id_producto = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function categoriasActivas(): array {
        return $this->pdo->query("
            SELECT id_categoria, nombre
            FROM categorias
            WHERE activo = 1
            ORDER BY nombre
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

public function insertarProducto(array $params): int
{
    $stmt = $this->pdo->prepare("
        INSERT INTO productos (
            id_categoria,
            nombre,
            descripcion_corta,
            descripcion_larga,
            precio,
            precio_oferta,
            sku,
            aplica_iva,
            stock_minimo
        ) VALUES (
            :id_categoria,
            :nombre,
            :descripcion_corta,
            :descripcion_larga,
            :precio,
            :precio_oferta,
            :sku,
            :aplica_iva,
            :stock_minimo
        )
    ");

    $stmt->execute([
        ":id_categoria"        => $params["id_categoria"],
        ":nombre"              => $params["nombre"],
        ":descripcion_corta"   => $params["descripcion_corta"],
        ":descripcion_larga"   => $params["descripcion_larga"],
        ":precio"              => $params["precio"],
        ":precio_oferta"       => $params["precio_oferta"] ?? null,
        ":sku"                 => $params["sku"],
        ":aplica_iva"          => $params["aplica_iva"],
        ":stock_minimo"        => $params["stock_minimo"],
    ]);

    $id = (int)$this->pdo->lastInsertId();

    if ($id <= 0) {
        throw new Exception("❌ No se pudo obtener el id_producto");
    }

    return $id;
}




 
    
public function actualizarInventario(int $idProducto, array $data): void {
    $this->assertIdProducto($idProducto);

    $stmt = $this->pdo->prepare("
        UPDATE inventario
        SET stock_actual = :stock_actual,
            ubicacion_almacen = :ubicacion_almacen,
            ultima_actualizacion = :ultima_actualizacion
        WHERE id_producto = :id
    ");
    $stmt->execute([
        ":stock_actual" => $data["stock_actual"],
        ":ubicacion_almacen" => $data["ubicacion_almacen"],
        ":ultima_actualizacion" => $data["ultima_actualizacion"],
        ":id" => $idProducto
    ]);
}




    public function resetImagenPrincipal(int $idProducto): void {
        $stmt = $this->pdo->prepare("
            UPDATE producto_imagenes
            SET es_principal = 0
            WHERE id_producto = ?
        ");
        $stmt->execute([$idProducto]);
    }

    public function insertarImagenPrincipal(int $idProducto, string $urlImagen): void {
    $this->assertIdProducto($idProducto);

    $stmt = $this->pdo->prepare("
        INSERT INTO producto_imagenes (id_producto, url_imagen, es_principal)
        VALUES (?, ?, 1)
    ");
    $stmt->execute([$idProducto, $urlImagen]);
}


    public function actualizarProducto(int $idProducto, array $data): void {
    $data[":id_producto"] = $idProducto;

    $stmt = $this->pdo->prepare("
        UPDATE productos SET
            id_categoria = :id_categoria,
            nombre = :nombre,
            descripcion_corta = :descripcion_corta,
            descripcion_larga = :descripcion_larga,
            precio = :precio,
            precio_oferta = :precio_oferta,
            sku = :sku,
            aplica_iva = :aplica_iva,
            stock_minimo = :stock_minimo
        WHERE id_producto = :id_producto
    ");

    $stmt->execute($data);
}


    public function setActivo(int $idProducto, int $activo): void {
        $stmt = $this->pdo->prepare("UPDATE productos SET activo = ? WHERE id_producto = ?");
        $stmt->execute([$activo, $idProducto]);
    }

    // Promociones (tal como está en tu implementación actual)
    public function crearPromocionParaProducto(int $idProducto, string $nombrePromo, float $valorDescuento): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO promociones
                (nombre, tipo_descuento, valor_descuento, activo, fecha_inicio)
            VALUES
                (:nombre, 'porcentaje', :valor, 1, NOW())
        ");
        $stmt->execute([":nombre" => $nombrePromo, ":valor" => $valorDescuento]);
        $idPromocion = (int)$this->pdo->lastInsertId();

        $stmt2 = $this->pdo->prepare("INSERT INTO promocion_productos (id_promocion, id_producto) VALUES (?, ?)");
        $stmt2->execute([$idPromocion, $idProducto]);
    }

    public function desactivarPromocionesDeProducto(int $idProducto): void {
        $stmt = $this->pdo->prepare("
            UPDATE promociones pr
            JOIN promocion_productos pp ON pp.id_promocion = pr.id_promocion
            SET pr.activo = 0
            WHERE pp.id_producto = ?
        ");
        $stmt->execute([$idProducto]);
    }

    public function borrarVinculosPromocionProducto(int $idProducto): void {
        $stmt = $this->pdo->prepare("DELETE FROM promocion_productos WHERE id_producto = ?");
        $stmt->execute([$idProducto]);
    }
    
    
public function insertarInventarioConStock(int $idProducto, int $stock): void
{
    // DEBUG: registrar el id que llega a inventario
    file_put_contents(
        __DIR__ . "/debug_inventario.log",
        date("Y-m-d H:i:s") . " | insertarInventarioConStock | idProducto=" . $idProducto . " | stock=" . $stock . PHP_EOL,
        FILE_APPEND
    );

    if ($idProducto <= 0) {
        throw new Exception("❌ ID de producto inválido para inventario: " . $idProducto);
    }

    $stmt = $this->pdo->prepare("
        INSERT INTO inventario (id_producto, stock_actual, ubicacion_almacen, ultima_actualizacion)
        VALUES (?, ?, 'Bodega principal', NOW())
    ");
    $stmt->execute([$idProducto, $stock]);
}



}
