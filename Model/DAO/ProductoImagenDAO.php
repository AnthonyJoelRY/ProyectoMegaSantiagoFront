<?php
declare(strict_types=1);

// Model/DAO/ProductoImagenDAO.php
// DAO para tabla `producto_imagenes`. Mantiene compatibilidad: expone métodos que retornan arrays o Entities.

require_once __DIR__ . "/../Entity/ProductoImagen.php";

class ProductoImagenDAO
{
    public function __construct(private PDO $pdo) {}

    /** @return array<int, array<string,mixed>> */
    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM `producto_imagenes`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, ProductoImagen> */
    public function listarEntidades(): array
    {
        return array_map(fn($r) => ProductoImagen::fromRow($r), $this->listar());
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `producto_imagenes` WHERE `id_imagen` = :id LIMIT 1");
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerEntidadPorId(int $id): ?ProductoImagen
    {
        $row = $this->obtenerPorId($id);
        return $row ? ProductoImagen::fromRow($row) : null;
    }

    public function insertar(array $data): int
    {
        $sql = "INSERT INTO `producto_imagenes` (`id_imagen`, `id_producto`, `url_imagen`, `es_principal`, `orden`) VALUES (:id_imagen, :id_producto, :url_imagen, :es_principal, :orden)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->filtrar($data, ['id_imagen', 'id_producto', 'url_imagen', 'es_principal', 'orden']));
        return (int)($data['id_imagen'] ?? 0);
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE `producto_imagenes` SET `id_producto` = :id_producto, `url_imagen` = :url_imagen, `es_principal` = :es_principal, `orden` = :orden WHERE `id_imagen` = :id";
        $stmt = $this->pdo->prepare($sql);
        $params = $this->filtrar($data, ['id_producto', 'url_imagen', 'es_principal', 'orden']);
        $params[":id"] = $id;
        return $stmt->execute($params);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `producto_imagenes` WHERE `id_imagen` = :id");
        return $stmt->execute([":id" => $id]);
    }

    /** @param array<string,mixed> $data @param array<int,string> $keys */
    private function filtrar(array $data, array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $data)) $out[":".$k] = $data[$k];
        }
        return $out;
    }
}