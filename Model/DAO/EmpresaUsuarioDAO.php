<?php
// Model/DAO/EmpresaUsuarioDAO.php

class EmpresaUsuarioDAO {
    public function __construct(private PDO $pdo) {}

    public function vincular(int $empresaId, int $usuarioId, string $rol = "admin"): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO empresa_usuarios (empresa_id, usuario_id, rol)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$empresaId, $usuarioId, $rol]);
    }

    public function usuarioTieneEmpresa(int $usuarioId): bool {
        $stmt = $this->pdo->prepare("
            SELECT 1
            FROM empresa_usuarios
            WHERE usuario_id = ?
            LIMIT 1
        ");
        $stmt->execute([$usuarioId]);
        return (bool)$stmt->fetchColumn();
    }

    public function obtenerEmpresaPorUsuario(int $usuarioId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT e.*
            FROM empresas e
            INNER JOIN empresa_usuarios eu ON eu.empresa_id = e.id_empresa
            WHERE eu.usuario_id = ?
            LIMIT 1
        ");
        $stmt->execute([$usuarioId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
