<?php
// Model/Service/UsuarioService.php

declare(strict_types=1);

require_once __DIR__ . "/../DAO/UsuarioDAO.php";
require_once __DIR__ . "/../DAO/RolDAO.php";
require_once __DIR__ . "/../DAO/EmpresaDAO.php";

class UsuarioService {

    private UsuarioDAO $usuarioDAO;
    private RolDAO $rolDAO;
    private EmpresaDAO $empresaDAO;

    public function __construct(private PDO $pdo) {
        $this->usuarioDAO = new UsuarioDAO($pdo);
        $this->rolDAO     = new RolDAO($pdo);
        $this->empresaDAO = new EmpresaDAO($pdo);
    }

    // ============================
    // REGISTRO VENDEDOR
    // ============================
    public function registrarVendedor(string $email, string $clave): array {

        $email = trim($email);
        $clave = trim($clave);

        if ($email === "" || $clave === "") {
            return ["error" => "Faltan datos de registro."];
        }

        if ($this->usuarioDAO->existePorEmail($email)) {
            return ["error" => "Este correo ya está registrado."];
        }

        $idRol = $this->rolDAO->obtenerIdVendedor();
        $hash  = password_hash($clave, PASSWORD_BCRYPT);

        $idUsuario = $this->usuarioDAO->crear($idRol, $email, $hash);

        return [
            "exito" => true,
            "usuario" => [
                "id"    => $idUsuario,
                "email" => $email,
                "rol"   => $idRol
            ]
        ];
    }

    // ============================
    // REGISTRO EMPRESA + PROPIETARIO
    // ============================
    public function registrarEmpresa(array $data): array {

        // -------- Datos empresa --------
        $nombreLegal = trim($data["nombre_legal"] ?? "");
        $ruc         = trim($data["ruc"] ?? "");
        $emailEmp    = trim($data["email_empresa"] ?? "");
        $telefono    = trim($data["telefono"] ?? "");
        $direccion   = trim($data["direccion_fiscal"] ?? "");
        $ciudad      = trim($data["ciudad"] ?? "");
        $pais        = trim($data["pais"] ?? "Ecuador");
        $tipoNegocio = trim($data["tipo_negocio"] ?? "");

        // -------- Datos usuario dueño --------
        $nombre   = trim($data["nombre"] ?? "");
        $apellido = trim($data["apellido"] ?? "");
        $emailUsr = trim($data["email"] ?? "");
        $clave    = trim($data["clave"] ?? "");

        if (
            $nombreLegal === "" || $ruc === "" || $emailEmp === "" ||
            $nombre === "" || $apellido === "" || $emailUsr === "" || $clave === ""
        ) {
            return ["error" => "Faltan datos obligatorios."];
        }

        // Validar duplicados básicos
        if ($this->usuarioDAO->existePorEmail($emailUsr)) {
            return ["error" => "Este correo ya está registrado."];
        }

        try {
            $this->pdo->beginTransaction();

            // 1) Crear empresa
            $idEmpresa = $this->empresaDAO->crear([
                "nombre_legal"      => $nombreLegal,
                "ruc"               => $ruc,
                "email_empresa"     => $emailEmp,
                "telefono"          => $telefono,
                "direccion_fiscal"  => $direccion,
                "ciudad"            => $ciudad,
                "pais"              => $pais,
                "tipo_negocio"      => $tipoNegocio,
            ]);

            // 2) Crear usuario propietario (rol vendedor)
            $idRolVendedor = $this->rolDAO->obtenerIdVendedor();
            $claveHash     = password_hash($clave, PASSWORD_BCRYPT);

            $idUsuario = $this->usuarioDAO->crear($idRolVendedor, $emailUsr, $claveHash);

            // 3) Relacionar empresa ↔ usuario
            $stmt = $this->pdo->prepare("
                INSERT INTO empresa_usuarios
                (id_empresa, id_usuario, cargo)
                VALUES (?, ?, 'propietario')
            ");
            $stmt->execute([$idEmpresa, $idUsuario]);

            $this->pdo->commit();

            return [
                "exito" => true,
                "empresa" => [
                    "id"     => $idEmpresa,
                    "nombre" => $nombreLegal
                ],
                "usuario" => [
                    "id"    => $idUsuario,
                    "email" => $emailUsr,
                    "rol"   => $idRolVendedor
                ]
            ];

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                "error"   => "Error al registrar empresa",
                "detalle" => $e->getMessage()
            ];
        }
    }

    // ============================
    // LOGIN (reusa UsuarioDAO)
    // ============================
    public function login(string $email, string $clave): array {

        $email = trim($email);
        $clave = trim($clave);

        if ($email === "" || $clave === "") {
            return ["error" => "Faltan datos de acceso."];
        }

        $usuario = $this->usuarioDAO->obtenerPorEmail($email);

        if (!$usuario || !password_verify($clave, $usuario->clave_hash)) {
            return ["error" => "Correo o clave incorrectos."];
        }

        if ((int)$usuario->activo !== 1) {
            return ["error" => "Usuario inactivo."];
        }

        // Si existe relación empresa (para vendedor), la devolvemos como dato extra
        $idEmpresa = null;
        try {
            $stmt = $this->pdo->prepare("
                SELECT eu.id_empresa
                FROM empresa_usuarios eu
                WHERE eu.id_usuario = ?
                LIMIT 1
            ");
            $stmt->execute([(int)$usuario->id_usuario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row["id_empresa"])) {
                $idEmpresa = (int)$row["id_empresa"];
            }
        } catch (Throwable $e) {
            // No rompemos el login si no existe tabla/relación
        }

        return [
            "exito" => true,
            "usuario" => [
                "id"        => (int)$usuario->id_usuario,
                "email"     => $usuario->email,
                "id_rol"    => (int)$usuario->id_rol,
                "id_empresa"=> $idEmpresa
            ]
        ];
    }
}
