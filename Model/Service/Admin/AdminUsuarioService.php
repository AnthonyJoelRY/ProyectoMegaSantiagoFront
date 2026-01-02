<?php
// Model/Service/Admin/AdminUsuarioService.php

require_once __DIR__ . "/../../DAO/Admin/AdminUsuarioDAO.php";

class AdminUsuarioService {
    private AdminUsuarioDAO $dao;

    public function __construct(private PDO $pdo) {
        $this->dao = new AdminUsuarioDAO($pdo);
    }

    public function roles(): array { return $this->dao->roles(); }
    public function listar(): array { return $this->dao->listar(); }
    public function obtener(int $id): ?array { return $this->dao->obtener($id); }

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
