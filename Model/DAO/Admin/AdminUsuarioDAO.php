<?php
// Model/DAO/Admin/AdminUsuarioDAO.php

class AdminUsuarioDAO {
    public function __construct(private PDO $pdo) {}

    public function roles(): array {
        return $this->pdo->query("SELECT id_rol, nombre FROM roles ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listar(): array {
        return $this->pdo->query("
            SELECT
                u.id_usuario,
                u.nombre,
                u.apellido,
                u.email,
                u.id_rol,
                r.nombre AS rol_nombre,
                u.activo
            FROM usuarios u
            JOIN roles r ON r.id_rol = u.id_rol
            ORDER BY u.id_usuario DESC
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function obtener(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT id_usuario, nombre, apellido, email, telefono, id_rol, activo
            FROM usuarios
            WHERE id_usuario = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function actualizar(int $id, array $data): void {
        $stmt = $this->pdo->prepare("
            UPDATE usuarios SET
                nombre = ?,
                apellido = ?,
                email = ?,
                telefono = ?,
                id_rol = ?,
                activo = ?
            WHERE id_usuario = ?
        ");
        $stmt->execute([
            $data["nombre"],
            $data["apellido"],
            $data["email"],
            $data["telefono"],
            (int)$data["id_rol"],
            (int)$data["activo"],
            $id
        ]);
    }

    public function cambiarRol(int $id, int $idRol): void {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET id_rol = ? WHERE id_usuario = ?");
        $stmt->execute([$idRol, $id]);
    }

    public function toggleActivo(int $id): void {
        $stmt = $this->pdo->prepare("
            UPDATE usuarios
            SET activo = IF(activo = 1, 0, 1)
            WHERE id_usuario = ?
        ");
        $stmt->execute([$id]);
    }
}
