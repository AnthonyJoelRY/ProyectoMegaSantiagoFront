<?php
// Model/Service/ProductoService.php

require_once __DIR__ . "/../DAO/ProductoDAO.php";
require_once __DIR__ . "/../DAO/BusquedaDAO.php";
require_once __DIR__ . "/../Entity/Producto.php";

class ProductoService
{
    private ProductoDAO $productoDAO;
    private BusquedaDAO $busquedaDAO;

    public function __construct(private PDO $pdo)
    {
        $this->productoDAO = new ProductoDAO($pdo);
        $this->busquedaDAO = new BusquedaDAO($pdo);
    }

    // ============================
    // Mapeo a Entity (sin romper compatibilidad)
    // ============================
    private function mapProductoRow(array $row): array
    {
        // Se instancia la Entity para centralizar el mapeo,
        // pero devolvemos array para no romper vistas/controladores existentes.
        return Producto::fromRow($row)->toArray();
    }

    private function mapProductoRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            if (is_array($r)) $out[] = $this->mapProductoRow($r);
        }
        return $out;
    }


    public function listarPorCategoria(string $categoria): array
    {
        if ($categoria === "") {
            return ["error" => "Falta parámetro categoria"];
        }
        return $this->mapProductoRows($this->productoDAO->listarPorCategoria($categoria));
    }

    public function buscar(string $termino, ?int $idUsuario): array
    {
        if ($termino === "") {
            return ["error" => "Falta parámetro q"];
        }

        $productos = $this->productoDAO->buscarPorNombre($termino);

        // registra historial (no rompe si idUsuario viene null)
        $this->busquedaDAO->registrar($termino, $idUsuario, count($productos));

        return $this->mapProductoRows($productos);
    }

    public function masVendidos(int $limit = 4): array
    {
        if ($limit <= 0) $limit = 4;
        return $this->mapProductoRows($this->productoDAO->masVendidos($limit));
    }

    public function ofertas(int $limit = 6): array
    {
        if ($limit <= 0) $limit = 6;
        return $this->mapProductoRows($this->productoDAO->ofertas($limit));
    }

    public function promociones(int $limit = 3): array
    {
        if ($limit <= 0) $limit = 3;
        return $this->mapProductoRows($this->productoDAO->promociones($limit));
    }

    public function detalle(int $idProducto): array
    {
        if ($idProducto <= 0) {
            return ["error" => "Falta parámetro id"]; 
        }

        $prod = $this->productoDAO->obtenerDetalle($idProducto);
        if (!$prod) {
            return ["error" => "Producto no encontrado"]; 
        }

        return $this->mapProductoRow($prod);
    }

    public function relacionados(int $idProducto, int $limit = 4): array
    {
        if ($idProducto <= 0) {
            return ["error" => "Falta parámetro id"]; 
        }
        if ($limit <= 0) $limit = 4;
        return $this->mapProductoRows($this->productoDAO->relacionados($idProducto, $limit));
    }
}
