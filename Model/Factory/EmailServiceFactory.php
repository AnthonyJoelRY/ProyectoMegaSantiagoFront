<?php
// Model/Factory/EmailServiceFactory.php
require_once __DIR__ . "/BaseServiceFactory.php";
require_once __DIR__ . "/../Service/EmailService.php";

class EmailServiceFactory extends BaseServiceFactory {

    protected function factoryMethod(PDO $pdo): object {
        return new EmailService();
    }
}
