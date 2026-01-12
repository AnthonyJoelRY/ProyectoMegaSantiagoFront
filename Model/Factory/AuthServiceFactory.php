<?php
// Model/Factory/AuthServiceFactory.php

require_once __DIR__ . "/BaseServiceFactory.php";

require_once __DIR__ . "/../DAO/UsuarioDAO.php";
require_once __DIR__ . "/../DAO/EmpresaDAO.php";
require_once __DIR__ . "/../DAO/EmpresaUsuarioDAO.php";
require_once __DIR__ . "/../Service/AuthService.php";

class AuthServiceFactory extends BaseServiceFactory {

    protected function factoryMethod(PDO $pdo): object {

        $usuarioDAO = new UsuarioDAO($pdo);
        $empresaDAO = new EmpresaDAO($pdo);
        $empresaUsuarioDAO = new EmpresaUsuarioDAO($pdo);

        return new AuthService(
            $pdo,
            $usuarioDAO,
            $empresaDAO,
            $empresaUsuarioDAO
        );
    }
}
