<?php
// Model/Service/Admin/AdminPedidoService.php

require_once __DIR__ . "/../../DAO/Admin/AdminPedidoDAO.php";
require_once __DIR__ . "/../../Entity/Pedido.php";
require_once __DIR__ . "/../../Entity/PedidoDetalle.php";

class AdminPedidoService {
    private AdminPedidoDAO $dao;
    public function __construct(private PDO $pdo) {
        $this->dao = new AdminPedidoDAO($pdo);
    }

    public function listarPagados(string $q = ""): array {
        $rows = $this->dao->listarPagados($q);
        return array_map(fn($r) => is_array($r) ? Pedido::fromRow($r)->toArray() : $r, $rows);
    }

    public function obtenerConDetalle(int $id): array {
        $pedido = $this->dao->obtener($id);
        if (!$pedido) throw new Exception("Pedido no encontrado.");
        $detalle = $this->dao->detalle($id);

        // Entity base (mantiene compatibilidad con tu vista actual)
        $pedidoArr = Pedido::fromRow($pedido)->toArray();

        // Adjuntar info de entrega/sucursal si viene del JOIN (la Entity la ignora)
        $extraKeys = [
            // retiro
            'sucursal_retiro_nombre','sucursal_retiro_direccion','sucursal_retiro_ciudad','sucursal_retiro_telefono','sucursal_retiro_horario',
            // origen
            'sucursal_origen_nombre','sucursal_origen_direccion','sucursal_origen_ciudad','sucursal_origen_telefono','sucursal_origen_horario',
            // direccion envio
            'direccion_envio_tipo','direccion_envio_direccion','direccion_envio_ciudad','direccion_envio_provincia','direccion_envio_codigo_postal','direccion_envio_referencia',
            // cliente ya viene en join
            'cliente'
        ];
        foreach ($extraKeys as $k) {
            if (array_key_exists($k, $pedido)) {
                $pedidoArr[$k] = $pedido[$k];
            }
        }

        return [
            "pedido" => $pedidoArr,
            "detalle" => array_map(fn($r) => is_array($r) ? PedidoDetalle::fromRow($r)->toArray() : $r, $detalle)
        ];
    }


    public function cambiarEstado(int $idPedido, string $nuevoEstado): void {
        // Valida según tipo_entrega
        $pedido = $this->dao->obtener($idPedido);
        if (!$pedido) throw new Exception("Pedido no encontrado.");

        $tipo = (string)($pedido["tipo_entrega"] ?? "envio");
        $nuevo = strtolower(trim($nuevoEstado));

        $permitidos = [];
        if ($tipo === "retiro_local") {
            $permitidos = ["en_proceso", "listo_para_entregar"];
        } else {
            $permitidos = ["en_proceso", "en_camino", "entregado"];
        }

        if (!in_array($nuevo, $permitidos, true)) {
            throw new Exception("Estado inválido para el tipo de entrega.");
        }

        $this->dao->actualizarEstado($idPedido, $nuevo);
    }
}
