<?php
declare(strict_types=1);

// Model/DAO/DireccionUsuarioDAO.php
// DAO para tabla `direcciones_usuario`.
// - En algunos despliegues la tabla puede no existir: devolvemos [] y no rompemos checkout.
// - Soporta escenarios donde `id_direccion` NO es AUTO_INCREMENT (genera MAX+1 dentro de transacción).

// Nota: en este proyecto puede no existir la Entity DireccionUsuario. No debe romper el checkout.
$ent = __DIR__ . "/../Entity/DireccionUsuario.php";
if (file_exists($ent)) { require_once $ent; }

class DireccionUsuarioDAO
{
    public function __construct(private PDO $pdo) {}

    private function tablaDisponible(): bool
    {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'direcciones_usuario'");
            return (bool)$stmt->fetch(PDO::FETCH_NUM);
        } catch (Throwable $e) {
            return false;
        }
    }

    /** @return array<int, array<string,mixed>> */
    public function listarPorUsuario(int $idUsuario): array
    {
        if (!$this->tablaDisponible()) return [];

        $stmt = $this->pdo->prepare("SELECT * FROM `direcciones_usuario` WHERE `id_usuario` = :id ORDER BY `es_principal` DESC, `id_direccion` DESC");
        $stmt->execute(["id" => $idUsuario]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    public function obtenerEntidadPorId(int $idDireccion): ?DireccionUsuario
    {
        if (!$this->tablaDisponible()) return null;

        $stmt = $this->pdo->prepare("SELECT * FROM `direcciones_usuario` WHERE `id_direccion` = :id LIMIT 1");
        $stmt->execute(["id" => $idDireccion]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? DireccionUsuario::fromRow($row) : null;
    }

    /** Inserta y devuelve id_direccion */
    public function insertar(array $data): int
    {
        if (!$this->tablaDisponible()) return 0;

        // Normaliza vacíos a NULL en campos opcionales
        foreach (["ciudad","provincia","codigo_postal","referencia"] as $k) {
            if (array_key_exists($k, $data)) {
                $v = $data[$k];
                if ($v === "" || $v === false) $data[$k] = null;
            }
        }

        // Columnas soportadas según esquema
        $cols = ["id_direccion","id_usuario","tipo","direccion","ciudad","provincia","codigo_postal","referencia","es_principal"];

        // Detecta si id_direccion es AUTO_INCREMENT
        $isAI = false;
        try {
            $rs = $this->pdo->query("SHOW COLUMNS FROM `direcciones_usuario` LIKE 'id_direccion'");
            $col = $rs ? $rs->fetch(PDO::FETCH_ASSOC) : null;
            $extra = strtolower((string)($col["Extra"] ?? ""));
            $isAI = str_contains($extra, "auto_increment");
        } catch (Throwable $e) {
            $isAI = false;
        }

        // Si NO hay AI, generamos id_direccion
        if (!$isAI) {
            // ✅ Evitar transacciones anidadas (ej: checkout/paypal ya abrió una)
            $startedHere = false;
            try {
                if (!$this->pdo->inTransaction()) {
                    $this->pdo->beginTransaction();
                    $startedHere = true;
                }
                $max = (int)$this->pdo->query("SELECT COALESCE(MAX(`id_direccion`),0) AS m FROM `direcciones_usuario` FOR UPDATE")->fetch(PDO::FETCH_ASSOC)["m"];
                $data["id_direccion"] = $max + 1;

                $insertCols = array_values(array_intersect($cols, array_keys($data)));
                $place = array_map(fn($c) => ":" . $c, $insertCols);

                $sql = "INSERT INTO `direcciones_usuario` (" . implode(",", array_map(fn($c)=>"`$c`",$insertCols)) . ")
                        VALUES (" . implode(",", $place) . ")";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($this->filtrar($data, $insertCols));

                if ($startedHere) {
                    $this->pdo->commit();
                }
                return (int)$data["id_direccion"];
            } catch (Throwable $e) {
                if ($startedHere && $this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw $e;
            }
        }

        // Con AI: no incluimos id_direccion
        $dataNoId = $data;
        unset($dataNoId["id_direccion"]);

        $insertCols = array_values(array_intersect($cols, array_keys($dataNoId)));
        $place = array_map(fn($c) => ":" . $c, $insertCols);

        $sql = "INSERT INTO `direcciones_usuario` (" . implode(",", array_map(fn($c)=>"`$c`",$insertCols)) . ")
                VALUES (" . implode(",", $place) . ")";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->filtrar($dataNoId, $insertCols));

        return (int)$this->pdo->lastInsertId();
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