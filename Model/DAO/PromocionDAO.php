<?php
declare(strict_types=1);

// Model/DAO/PromocionDAO.php
// DAO para tabla `promociones`. Mantiene compatibilidad: expone métodos que retornan arrays o Entities.

require_once __DIR__ . "/../Entity/Promocion.php";

class PromocionDAO
{
    public function __construct(private PDO $pdo) {}

    /** @return array<int, array<string,mixed>> */
    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM `promociones`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, Promocion> */
    public function listarEntidades(): array
    {
        return array_map(fn($r) => Promocion::fromRow($r), $this->listar());
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `promociones` WHERE `id_promocion` = :id LIMIT 1");
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerEntidadPorId(int $id): ?Promocion
    {
        $row = $this->obtenerPorId($id);
        return $row ? Promocion::fromRow($row) : null;
    }

    public function insertar(array $data): int
    {
        $sql = "INSERT INTO `promociones` (`id_promocion`, `nombre`, `descripcion`, `fecha_inicio`, `fecha_fin`, `tipo_descuento`, `valor_descuento`, `activo`) VALUES (:id_promocion, :nombre, :descripcion, :fecha_inicio, :fecha_fin, :tipo_descuento, :valor_descuento, :activo)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->filtrar($data, ['id_promocion', 'nombre', 'descripcion', 'fecha_inicio', 'fecha_fin', 'tipo_descuento', 'valor_descuento', 'activo']));
        return (int)($data['id_promocion'] ?? 0);
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE `promociones` SET `nombre` = :nombre, `descripcion` = :descripcion, `fecha_inicio` = :fecha_inicio, `fecha_fin` = :fecha_fin, `tipo_descuento` = :tipo_descuento, `valor_descuento` = :valor_descuento, `activo` = :activo WHERE `id_promocion` = :id";
        $stmt = $this->pdo->prepare($sql);
        $params = $this->filtrar($data, ['nombre', 'descripcion', 'fecha_inicio', 'fecha_fin', 'tipo_descuento', 'valor_descuento', 'activo']);
        $params[":id"] = $id;
        return $stmt->execute($params);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `promociones` WHERE `id_promocion` = :id");
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