<?php
declare(strict_types=1);

// Model/DAO/CategoriaDAO.php
// DAO para tabla `categorias`. Mantiene compatibilidad: expone métodos que retornan arrays o Entities.

require_once __DIR__ . "/../Entity/Categoria.php";

class CategoriaDAO
{
    public function __construct(private PDO $pdo) {}

    /** @return array<int, array<string,mixed>> */
    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM `categorias`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, Categoria> */
    public function listarEntidades(): array
    {
        return array_map(fn($r) => Categoria::fromRow($r), $this->listar());
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `categorias` WHERE `id_categoria` = :id LIMIT 1");
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerEntidadPorId(int $id): ?Categoria
    {
        $row = $this->obtenerPorId($id);
        return $row ? Categoria::fromRow($row) : null;
    }

    public function insertar(array $data): int
    {
        $sql = "INSERT INTO `categorias` (`id_categoria`, `nombre`, `slug`, `descripcion`, `id_padre`, `orden`, `activo`) VALUES (:id_categoria, :nombre, :slug, :descripcion, :id_padre, :orden, :activo)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->filtrar($data, ['id_categoria', 'nombre', 'slug', 'descripcion', 'id_padre', 'orden', 'activo']));
        return (int)($data['id_categoria'] ?? 0);
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE `categorias` SET `nombre` = :nombre, `slug` = :slug, `descripcion` = :descripcion, `id_padre` = :id_padre, `orden` = :orden, `activo` = :activo WHERE `id_categoria` = :id";
        $stmt = $this->pdo->prepare($sql);
        $params = $this->filtrar($data, ['nombre', 'slug', 'descripcion', 'id_padre', 'orden', 'activo']);
        $params[":id"] = $id;
        return $stmt->execute($params);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `categorias` WHERE `id_categoria` = :id");
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