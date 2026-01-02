<?php
// Model/Factory/BaseServiceFactory.php
//
// Factory Method (GoF):
// - BaseServiceFactory define el método plantilla create().
// - Las subclases implementan factoryMethod() para decidir qué Service crear.
// Esto reduce el acoplamiento en Controllers sin cambiar la lógica existente
// dentro de los Services ni otros patrones ya integrados.

interface ServiceFactory {
    public function create(PDO $pdo): object;
}

abstract class BaseServiceFactory implements ServiceFactory {

    final public function create(PDO $pdo): object {
        return $this->factoryMethod($pdo);
    }

    // Factory Method: las subclases deciden qué instancia devolver
    abstract protected function factoryMethod(PDO $pdo): object;
}
