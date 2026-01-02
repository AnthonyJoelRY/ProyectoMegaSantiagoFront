<?php
// Model/Factory/ReporteServiceFactory.php
require_once __DIR__ . "/BaseServiceFactory.php";
require_once __DIR__ . "/../Service/ReporteService.php";

class ReporteServiceFactory extends BaseServiceFactory {

    protected function factoryMethod(PDO $pdo): object {
        return new ReporteService($pdo);
    }
}
