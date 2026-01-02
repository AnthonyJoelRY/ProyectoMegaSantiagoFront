<?php
// Model/Factory/AuthServiceFactory.php
require_once __DIR__ . "/BaseServiceFactory.php";
require_once __DIR__ . "/../Service/AuthService.php";

class AuthServiceFactory extends BaseServiceFactory {

    protected function factoryMethod(PDO $pdo): object {
        return new AuthService($pdo);
    }
}
