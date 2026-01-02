<?php
// Model/Factory/FacturaServiceFactory.php
require_once __DIR__ . "/BaseServiceFactory.php";
require_once __DIR__ . "/../Service/FacturaService.php";

class FacturaServiceFactory extends BaseServiceFactory {

    protected function factoryMethod(PDO $pdo): object {
        return new FacturaService($pdo);
    }
}
