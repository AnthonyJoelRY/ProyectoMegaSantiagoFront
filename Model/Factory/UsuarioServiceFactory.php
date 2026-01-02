<?php
// Model/Factory/UsuarioServiceFactory.php

require_once __DIR__ . "/BaseServiceFactory.php";
require_once __DIR__ . "/../Service/UsuarioService.php";

class UsuarioServiceFactory extends BaseServiceFactory {

    protected function factoryMethod(PDO $pdo): object {
        return new UsuarioService($pdo);
    }
}
