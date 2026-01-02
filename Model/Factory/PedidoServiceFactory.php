<?php
// Model/Factory/PedidoServiceFactory.php
require_once __DIR__ . "/BaseServiceFactory.php";
require_once __DIR__ . "/../Service/PedidoService.php";

class PedidoServiceFactory extends BaseServiceFactory {

    protected function factoryMethod(PDO $pdo): object {
        return new PedidoService($pdo);
    }
}
