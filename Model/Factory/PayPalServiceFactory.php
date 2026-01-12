<?php
// Model/Factory/PayPalServiceFactory.php
require_once __DIR__ . "/BaseServiceFactory.php";
require_once __DIR__ . "/../Service/PayPalService.php";

class PayPalServiceFactory extends BaseServiceFactory {
    protected function factoryMethod(PDO $pdo): object {
        return new PayPalService($pdo);
    }
}