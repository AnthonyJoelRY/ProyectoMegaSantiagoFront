<?php
// Model/Factory/ProductoServiceFactory.php
require_once __DIR__ . "/BaseServiceFactory.php";
require_once __DIR__ . "/../Service/ProductoService.php";

class ProductoServiceFactory extends BaseServiceFactory {

    protected function factoryMethod(PDO $pdo): object {
        return new ProductoService($pdo);
    }
}
