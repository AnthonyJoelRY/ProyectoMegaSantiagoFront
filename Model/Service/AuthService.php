<?php
// Model/Service/AuthService.php

require_once __DIR__ . "/../DAO/EmpresaDAO.php";
require_once __DIR__ . "/../DAO/UsuarioDAO.php";
require_once __DIR__ . "/../DAO/RolDAO.php";
require_once __DIR__ . "/../DAO/EmpresaUsuarioDAO.php"; // ✅ FALTABA

class AuthService
{
    private UsuarioDAO $usuarioDAO;
    private EmpresaDAO $empresaDAO;
    private EmpresaUsuarioDAO $empresaUsuarioDAO;
    private RolDAO $rolDAO; // ✅ FALTABA

    public function __construct(
        private PDO $pdo,
        UsuarioDAO $usuarioDAO,
        EmpresaDAO $empresaDAO,
        EmpresaUsuarioDAO $empresaUsuarioDAO
    ) {
        $this->usuarioDAO = $usuarioDAO;
        $this->empresaDAO = $empresaDAO;
        $this->empresaUsuarioDAO = $empresaUsuarioDAO;

        // ✅ crear RolDAO aquí para no pedirlo en el constructor (no rompe nada)
        $this->rolDAO = new RolDAO($pdo);
    }

    // ============================
    // REGISTRO CLIENTE
    // ============================
    public function registrarCliente(string $nombre, string $apellido, string $email, string $clave): array
    {
        $nombre   = trim($nombre);
        $apellido = trim($apellido);
        $email    = trim($email);
        $clave    = trim($clave);

        if ($nombre === "" || $apellido === "" || $email === "" || $clave === "") {
            return ["error" => "Faltan datos de registro."];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["error" => "Correo inválido."];
        }

        if ($this->usuarioDAO->existePorEmail($email)) {
            return ["error" => "Este correo ya está registrado."];
        }

        $idRol = $this->rolDAO->obtenerIdCliente(); // ✅ ya existe
        $hash  = password_hash($clave, PASSWORD_BCRYPT);

        $idUsuario = $this->usuarioDAO->crear($idRol, $nombre, $apellido, $email, $hash);

        return [
            "exito" => true,
            "usuario" => [
                "id" => $idUsuario,
                "nombre" => $nombre,
                "apellido" => $apellido,
                "email" => $email,
                "rol" => $idRol
            ]
        ];
    }

    // ============================
    // LOGIN
    // ============================
    public function login(string $email, string $clave): array
    {
        $email = trim($email);
        $clave = trim($clave);

        if ($email === "" || $clave === "") {
            return ["error" => "Faltan datos de acceso."];
        }

        $usuario = $this->usuarioDAO->obtenerPorEmail($email);

        if (!$usuario || !password_verify($clave, $usuario->clave_hash)) {
            return ["error" => "Credenciales incorrectas"];
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION["id"]    = (int)$usuario->id_usuario;
        $_SESSION["rol"]   = (int)$usuario->id_rol;
        $_SESSION["email"] = $usuario->email;
        $_SESSION["usuario"] = $usuario->email; // ✅ requerido por dashboard

        if (property_exists($usuario, "nombre"))   $_SESSION["nombre"] = $usuario->nombre;
        if (property_exists($usuario, "apellido")) $_SESSION["apellido"] = $usuario->apellido;

        return [
            "exito" => true,
            "usuario" => [
                "id" => (int)$usuario->id_usuario,
                "email" => $usuario->email,
                "rol" => (int)$usuario->id_rol,
                "nombre" => property_exists($usuario, "nombre") ? ($usuario->nombre ?? null) : null,
                "apellido" => property_exists($usuario, "apellido") ? ($usuario->apellido ?? null) : null,
            ]
        ];
    }

    // ============================
    // REGISTRO EMPRESA
    // ============================
    public function registrarEmpresa(array $data): array
    {
        $req = ["nombre_legal","ruc","email_empresa","telefono","direccion_fiscal","ciudad","pais","tipo_negocio","clave"];
        foreach ($req as $k) {
            if (!isset($data[$k]) || trim((string)$data[$k]) === "") {
                return ["error" => "Falta el campo obligatorio: $k"];
            }
        }

        $email = trim((string)$data["email_empresa"]);
        $clave = trim((string)$data["clave"]);

        if ($this->usuarioDAO->existePorEmail($email)) {
            return ["error" => "Este correo ya está registrado"];
        }

        // ✅ puedes dejarlo fijo si tu BD ya lo usa así
        $idRolEmpresa = 2;

        $nombreCuenta = trim((string)$data["nombre_legal"]);
        $apellidoCuenta = "Empresa";
        $claveHash = password_hash($clave, PASSWORD_BCRYPT);

        $this->pdo->beginTransaction();
        try {
            $empresaId = $this->empresaDAO->crear($data);

            $usuarioId = $this->usuarioDAO->crear(
                $idRolEmpresa,
                $nombreCuenta,
                $apellidoCuenta,
                $email,
                $claveHash
            );

            $this->empresaUsuarioDAO->vincular($empresaId, $usuarioId, "admin");

            $this->pdo->commit();
            return ["ok" => true, "empresa_id" => $empresaId, "usuario_id" => $usuarioId];

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return ["error" => "No se pudo registrar la empresa: " . $e->getMessage()];
        }
    }
}
