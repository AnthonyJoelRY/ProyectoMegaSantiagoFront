<?php
class EmpresaDAO {
    public function __construct(private PDO $pdo) {}

    public function crear(array $e): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO empresas
              (nombre_legal, ruc, email_empresa, telefono, direccion_fiscal, ciudad, pais, tipo_negocio, activo)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $e["nombre_legal"],
            $e["ruc"],
            $e["email_empresa"] ?? null,
            $e["telefono"] ?? null,
            $e["direccion_fiscal"] ?? null,
            $e["ciudad"] ?? null,
            $e["pais"] ?? "Ecuador",
            $e["tipo_negocio"] ?? null
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
