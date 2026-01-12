<?php
declare(strict_types=1);

// Model/DAO/PedidoDAO.php
// DAO para tabla `pedidos`. Mantiene compatibilidad: expone métodos que retornan arrays o Entities.

require_once __DIR__ . "/../Entity/Pedido.php";

class PedidoDAO
{
    public function __construct(private PDO $pdo) {}

    /** @return array<int, array<string,mixed>> */
    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM `pedidos` ORDER BY `id_pedido` DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, Pedido> */
    public function listarEntidades(): array
    {
        return array_map(fn($r) => Pedido::fromRow($r), $this->listar());
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `pedidos` WHERE `id_pedido` = :id LIMIT 1");
        $stmt->execute(["id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerEntidadPorId(int $id): ?Pedido
    {
        $row = $this->obtenerPorId($id);
        return $row ? Pedido::fromRow($row) : null;
    }

    /**
     * Lista pedidos de un usuario (cliente). Devuelve solo campos necesarios.
     * @return array<int, array<string,mixed>>
     */
    public function listarPorUsuario(int $idUsuario): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_pedido, fecha_pedido, total_pagar, estado, tipo_entrega
             FROM pedidos
             WHERE id_usuario = :u
             ORDER BY id_pedido DESC"
        );
        $stmt->execute(["u" => $idUsuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtiene un pedido por id validando que pertenezca al usuario.
     */
    public function obtenerPorIdYUsuario(int $idPedido, int $idUsuario): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM pedidos WHERE id_pedido = :id AND id_usuario = :u LIMIT 1"
        );
        $stmt->execute(["id" => $idPedido, "u" => $idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Inserta un pedido y devuelve el id generado */
    public function insertar(array $data): int
    {
        // Columnas soportadas por la tabla (evita "Unknown column")
        $keys = [
            "id_usuario",
            "fecha_pedido",
            "total_productos",
            "total_iva",
            "total_pagar",
            "estado",
            "tipo_entrega",
            "id_sucursal_retiro",
            "id_sucursal_origen",
            "id_direccion_envio",
            "observaciones",
        ];

        $row = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $data)) {
                $row[$k] = $data[$k];
            }
        }

        // Defaults mínimos
        $row["fecha_pedido"] = $row["fecha_pedido"] ?? date("Y-m-d H:i:s");
        $row["estado"] = $row["estado"] ?? "pendiente";
        $row["tipo_entrega"] = $row["tipo_entrega"] ?? "envio";
        $row["total_iva"] = $row["total_iva"] ?? 0;

        // Detectar si id_pedido es AUTO_INCREMENT
        $isAuto = false;
        try {
            $stmt = $this->pdo->prepare("
                SELECT EXTRA
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'pedidos'
                  AND COLUMN_NAME = 'id_pedido'
                LIMIT 1
            ");
            $stmt->execute();
            $extra = (string)($stmt->fetchColumn() ?: "");
            $isAuto = stripos($extra, "auto_increment") !== false;
        } catch (Throwable $e) {
            // Si el host no permite INFORMATION_SCHEMA, asumimos AUTO (y si falla, hacemos fallback).
            $isAuto = true;
        }

        if ($isAuto) {
            // Insert normal (sin id_pedido)
            $cols = array_keys($row);
            $place = array_map(fn($c) => ":" . $c, $cols);

            $sql = "INSERT INTO pedidos (" . implode(",", $cols) . ") VALUES (" . implode(",", $place) . ")";
            $stmt = $this->pdo->prepare($sql);
            foreach ($row as $k => $v) $stmt->bindValue(":" . $k, $v);
            $stmt->execute();

            $id = (int)$this->pdo->lastInsertId();
            if ($id > 0) return $id;

            // Fallback (por si lastInsertId devuelve 0)
            $q = $this->pdo->query("SELECT MAX(id_pedido) FROM pedidos");
            return (int)($q ? $q->fetchColumn() : 0);
        }

        // NO AUTO_INCREMENT: generamos id_pedido nosotros (seguro dentro de transacción)
        $q = $this->pdo->query("SELECT IFNULL(MAX(id_pedido),0)+1 AS next_id FROM pedidos FOR UPDATE");
        $nextId = (int)($q ? $q->fetchColumn() : 0);
        if ($nextId <= 0) $nextId = 1;

        $rowWithId = ["id_pedido" => $nextId] + $row;

        $cols = array_keys($rowWithId);
        $place = array_map(fn($c) => ":" . $c, $cols);

        $sql = "INSERT INTO pedidos (" . implode(",", $cols) . ") VALUES (" . implode(",", $place) . ")";
        $stmt = $this->pdo->prepare($sql);
        foreach ($rowWithId as $k => $v) $stmt->bindValue(":" . $k, $v);
        $stmt->execute();

        return $nextId;
    }

    public function actualizar(int $id, array $data): bool
    {
        $keys = [
            "id_usuario",
            "fecha_pedido",
            "total_productos",
            "total_iva",
            "total_pagar",
            "estado",
            "tipo_entrega",
            "id_sucursal_retiro",
            "id_sucursal_origen",
            "id_direccion_envio",
            "observaciones",
        ];
        $row = $this->filtrar($data, $keys);
        $row["id"] = $id;

        $sql = "UPDATE `pedidos` SET
            `id_usuario` = :id_usuario,
            `fecha_pedido` = :fecha_pedido,
            `total_productos` = :total_productos,
            `total_iva` = :total_iva,
            `total_pagar` = :total_pagar,
            `estado` = :estado,
            `id_direccion_envio` = :id_direccion_envio,
            `observaciones` = :observaciones
            WHERE `id_pedido` = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($row);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `pedidos` WHERE `id_pedido` = :id");
        return $stmt->execute(["id" => $id]);
    }

    /** @param array<string,mixed> $data @param array<int,string> $keys */
    private function filtrar(array $data, array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            // ✅ Claves SIN ':' (PDO las mapea igual al placeholder :nombre)
            if (array_key_exists($k, $data)) $out[$k] = $data[$k];
        }
        return $out;
    }
}
