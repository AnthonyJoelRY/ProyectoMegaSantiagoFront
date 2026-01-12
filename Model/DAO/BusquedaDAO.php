<?php
// Model/DAO/BusquedaDAO.php

class BusquedaDAO
{
    public function __construct(private PDO $pdo) {}

    public function registrar(string $termino, ?int $idUsuario, int $resultados): void
    {
        $sql = "
            INSERT INTO busquedas (termino, id_usuario, resultados)
            VALUES (:termino, :id_usuario, :resultados)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ":termino"    => $termino,
            ":id_usuario" => $idUsuario, // puede ser NULL
            ":resultados" => $resultados
        ]);
    }
}
