<?php
// Model/Service/Admin/AdminPedidoService.php

require_once __DIR__ . "/../../DAO/Admin/AdminPedidoDAO.php";

class AdminPedidoService {
    private AdminPedidoDAO $dao;
    public function __construct(private PDO $pdo) {
        $this->dao = new AdminPedidoDAO($pdo);
    }

    public function listarPagados(string $q = ""): array {
        return $this->dao->listarPagados($q);
    }

    public function obtenerConDetalle(int $id): array {
        $pedido = $this->dao->obtener($id);
        if (!$pedido) throw new Exception("Pedido no encontrado.");
        $detalle = $this->dao->detalle($id);
        return ["pedido" => $pedido, "detalle" => $detalle];
    }
}
