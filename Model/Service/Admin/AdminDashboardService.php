<?php
// Model/Service/Admin/AdminDashboardService.php

require_once __DIR__ . "/../../DAO/Admin/AdminDashboardDAO.php";

class AdminDashboardService {
    private AdminDashboardDAO $dao;

    public function __construct(private PDO $pdo) {
        $this->dao = new AdminDashboardDAO($pdo);
    }

public function obtenerResumen(): array {
    return [
        "totalProductos"   => $this->dao->countProductos(),
        "totalUsuarios"    => $this->dao->countUsuarios(),
        "productosOferta"  => $this->dao->countProductosOferta(),

        "productosActivos" => $this->dao->countProductosActivos(),
        "sinStock"         => $this->dao->countInventarioSinStock(),
        "stockBajo"        => $this->dao->countInventarioBajoStock(), // ✅ ESTA ES LA CLAVE

        "admins"           => $this->dao->countAdmins(),
        "clientes"         => $this->dao->countClientes(),

        "ultimoProducto"   => $this->dao->ultimoProductoNombre(),
        "ultimoUsuario"    => $this->dao->ultimoUsuarioEmail(),
    ];
}

}
