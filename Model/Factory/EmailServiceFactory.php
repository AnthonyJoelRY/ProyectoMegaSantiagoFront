<?php
// Model/Factory/EmailServiceFactory.php
require_once __DIR__ . "/BaseServiceFactory.php";
require_once __DIR__ . "/../Service/EmailService.php";

class EmailServiceFactory extends BaseServiceFactory {
    protected function factoryMethod(PDO $pdo): object {
        // Se pasa el $pdo por si el servicio necesita registrar logs de correos
        return new EmailService($pdo); 
    }
}