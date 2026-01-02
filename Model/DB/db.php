<?php
/**
 * Model/DB/db.php
 *
 * Mantiene la función obtenerConexion() para no tocar tu código existente,
 * pero ahora internamente usa un Singleton (DBConnection).
 */

require_once __DIR__ . '/DBConnection.php';

function obtenerConexion() {
    return DBConnection::getInstance();
}

