<?php
// Model/Factory/BaseServiceFactory.php

interface ServiceFactory {
    public function create(PDO $pdo): object;
}

abstract class BaseServiceFactory implements ServiceFactory {
    final public function create(PDO $pdo): object {
        return $this->factoryMethod($pdo);
    }
    abstract protected function factoryMethod(PDO $pdo): object;
}