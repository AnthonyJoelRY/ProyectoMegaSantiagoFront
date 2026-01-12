<?php
declare(strict_types=1);

// Model/DAO/PedidoDetalleDAO.php
// DAO para tabla `pedido_detalle`. Mantiene compatibilidad: expone métodos que retornan arrays o Entities.

require_once __DIR__ . "/../Entity/PedidoDetalle.php";

class PedidoDetalleDAO
{
    private bool $idDetalleAutoIncrement = true;

    public function __construct(private PDO $pdo)
    {
        // En algunos despliegues la tabla `pedido_detalle.id_detalle` NO es AUTO_INCREMENT.
        // Si insertamos sin `id_detalle`, MySQL/MariaDB lanza un error y (si estás en
        // una transacción) hace rollback del pedido completo.
        //
        // Para no obligarte a cambiar la BD, detectamos si el campo es AUTO_INCREMENT.
        // Si no lo es, generamos manualmente el siguiente id_detalle (MAX+1) dentro
        // de la transacción.
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `pedido_detalle` LIKE 'id_detalle'");
            $stmt->execute();
            $col = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $extra = strtolower((string)($col['Extra'] ?? ''));
            $this->idDetalleAutoIncrement = str_contains($extra, 'auto_increment');
        } catch (Throwable $e) {
            // Si algo falla en la detección, asumimos AUTO_INCREMENT para no romper.
            $this->idDetalleAutoIncrement = true;
        }
    }

    /** @return array<int, array<string,mixed>> */
    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM `pedido_detalle` ORDER BY `id_detalle` DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, PedidoDetalle> */
    public function listarEntidades(): array
    {
        return array_map(fn($r) => PedidoDetalle::fromRow($r), $this->listar());
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `pedido_detalle` WHERE `id_detalle` = :id LIMIT 1");
        $stmt->execute(["id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerEntidadPorId(int $id): ?PedidoDetalle
    {
        $row = $this->obtenerPorId($id);
        return $row ? PedidoDetalle::fromRow($row) : null;
    }

    /** Inserta un detalle y devuelve el id generado */
    public function insertar(array $data): int
    {
        // Si NO es AUTO_INCREMENT, debemos incluir id_detalle sí o sí.
        $keys = $this->idDetalleAutoIncrement
            ? ["id_pedido", "id_producto", "cantidad", "precio_unit", "subtotal"]
            : ["id_detalle", "id_pedido", "id_producto", "cantidad", "precio_unit", "subtotal"];

        $row = $this->filtrar($data, $keys);

        if (!$this->idDetalleAutoIncrement) {
            // Generar id_detalle manual dentro de la transacción.
            if (!isset($row['id_detalle']) || (int)$row['id_detalle'] <= 0) {
                $row['id_detalle'] = $this->nextIdDetalle();
            }

            $sql = "INSERT INTO `pedido_detalle`
                (`id_detalle`,`id_pedido`,`id_producto`,`cantidad`,`precio_unit`,`subtotal`)
                VALUES
                (:id_detalle,:id_pedido,:id_producto,:cantidad,:precio_unit,:subtotal)";
        } else {
            $sql = "INSERT INTO `pedido_detalle`
                (`id_pedido`,`id_producto`,`cantidad`,`precio_unit`,`subtotal`)
                VALUES
                (:id_pedido,:id_producto,:cantidad,:precio_unit,:subtotal)";
        }

        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute($row);

        if (!$ok) return 0;

        // lastInsertId() solo aplica cuando hay AUTO_INCREMENT.
        return $this->idDetalleAutoIncrement
            ? (int)$this->pdo->lastInsertId()
            : (int)$row['id_detalle'];
    }

    /**
     * Obtiene el siguiente id_detalle (MAX+1) bloqueando la tabla/filas dentro
     * de la transacción para evitar colisiones.
     */
    private function nextIdDetalle(): int
    {
        // FOR UPDATE funciona con InnoDB dentro de transacción.
        $stmt = $this->pdo->query("SELECT COALESCE(MAX(`id_detalle`), 0) AS m FROM `pedido_detalle` FOR UPDATE");
        $m = (int)(($stmt->fetch(PDO::FETCH_ASSOC)['m'] ?? 0));
        return $m + 1;
    }

    public function actualizar(int $id, array $data): bool
    {
        $keys = ["id_pedido", "id_producto", "cantidad", "precio_unit", "subtotal"];
        $row = $this->filtrar($data, $keys);
        $row["id"] = $id;

        $sql = "UPDATE `pedido_detalle` SET
            `id_pedido` = :id_pedido,
            `id_producto` = :id_producto,
            `cantidad` = :cantidad,
            `precio_unit` = :precio_unit,
            `subtotal` = :subtotal
            WHERE `id_detalle` = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($row);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `pedido_detalle` WHERE `id_detalle` = :id");
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
