<?php
// Model/Service/Admin/AdminUsuarioService.php

require_once __DIR__ . "/../../DAO/Admin/AdminUsuarioDAO.php";
require_once __DIR__ . "/../../Entity/Usuario.php";

class AdminUsuarioService {
    private AdminUsuarioDAO $dao;

    public function __construct(private PDO $pdo) {
        $this->dao = new AdminUsuarioDAO($pdo);
    }

    public function roles(): array { return $this->dao->roles(); }
    public function listar(): array {
        $rows = $this->dao->listar();
        return array_map(fn($r) => is_array($r) ? Usuario::fromRow($r)->toArray() : $r, $rows);
    }
    public function obtener(int $id): ?array {
        $row = $this->dao->obtener($id);
        return $row ? Usuario::fromRow($row)->toArray() : null;
    }

    public function actualizar(int $id, array $post): void {
        $nombre = trim($post["nombre"] ?? "");
        $apellido = trim($post["apellido"] ?? "");
        $email = trim($post["email"] ?? "");
        $telefono = trim($post["telefono"] ?? "");
        $idRol = (int)($post["id_rol"] ?? 0);
        $activo = isset($post["activo"]) ? 1 : 0;

        if ($nombre === "" || $email === "" || $idRol <= 0) {
            throw new Exception("❌ Faltan datos obligatorios del usuario.");
        }

        $this->dao->actualizar($id, [
            "nombre" => $nombre,
            "apellido" => $apellido,
            "email" => $email,
            "telefono" => $telefono,
            "id_rol" => $idRol,
            "activo" => $activo,
        ]);
    }

    public function cambiarRol(int $id, int $idRol): void {
        $this->dao->cambiarRol($id, $idRol);
    }

    public function toggle(int $id): void {
        $this->dao->toggleActivo($id);
    }
}
