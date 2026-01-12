<?php
declare(strict_types=1);

// Model/DAO/CarritoDAO.php
// DAO para tabla `carritos`. Mantiene compatibilidad: expone métodos que retornan arrays o Entities.

require_once __DIR__ . "/../Entity/Carrito.php";

class CarritoDAO
{
    public function __construct(private PDO $pdo) {}

    /** @return array<int, array<string,mixed>> */
    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM `carritos`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, Carrito> */
    public function listarEntidades(): array
    {
        return array_map(fn($r) => Carrito::fromRow($r), $this->listar());
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `carritos` WHERE `id_carrito` = :id LIMIT 1");
        $stmt->execute(["id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerEntidadPorId(int $id): ?Carrito
    {
        $row = $this->obtenerPorId($id);
        return $row ? Carrito::fromRow($row) : null;
    }

    public function insertar(array $data): int
    {
        $sql = "INSERT INTO `carritos` (`id_carrito`, `id_usuario`, `token_sesion`, `fecha_creacion`, `estado`) VALUES (:id_carrito, :id_usuario, :token_sesion, :fecha_creacion, :estado)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->filtrar($data, ['id_carrito', 'id_usuario', 'token_sesion', 'fecha_creacion', 'estado']));
        return (int)($data['id_carrito'] ?? 0);
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE `carritos` SET `id_usuario` = :id_usuario, `token_sesion` = :token_sesion, `fecha_creacion` = :fecha_creacion, `estado` = :estado WHERE `id_carrito` = :id";
        $stmt = $this->pdo->prepare($sql);
        $params = $this->filtrar($data, ['id_usuario', 'token_sesion', 'fecha_creacion', 'estado']);
        $params["id"] = $id;
        return $stmt->execute($params);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `carritos` WHERE `id_carrito` = :id");
        return $stmt->execute(["id" => $id]);
    }

    /** @param array<string,mixed> $data @param array<int,string> $keys */
    private function filtrar(array $data, array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $data)) $out[$k] = $data[$k];
        }
        return $out;
    }
}