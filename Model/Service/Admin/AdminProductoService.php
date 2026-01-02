<?php
// Model/Service/Admin/AdminProductoService.php

require_once __DIR__ . "/../../DAO/Admin/AdminProductoDAO.php";

class AdminProductoService {
    private AdminProductoDAO $dao;

    public function __construct(private PDO $pdo) {
        $this->dao = new AdminProductoDAO($pdo);
    }

    public function listar(string $q = ""): array {
        return $this->dao->listar($q);
    }

    public function categorias(): array {
        return $this->dao->categoriasActivas();
    }

    public function obtener(int $id): ?array {
        return $this->dao->obtenerPorId($id);
    }

    public function crear(array $post): int {
        $idCategoria       = (int)($post["id_categoria"] ?? 0);
        $nombre            = trim($post["nombre"] ?? "");
        $descCorta         = trim($post["descripcion_corta"] ?? "");
        $descLarga         = trim($post["descripcion_larga"] ?? "");
        $precio            = (float)($post["precio"] ?? 0);
        $precioOfertaRaw   = $post["precio_oferta"] ?? null;
        $sku               = trim($post["sku"] ?? "");
        $aplicaIva         = isset($post["aplica_iva"]) ? 1 : 0;
        $imagen            = trim($post["imagen"] ?? "");

        $precioOferta = null;
        if ($precioOfertaRaw !== null && $precioOfertaRaw !== '') {
            $precioOferta = (float)$precioOfertaRaw;
            if ($precioOferta < 1 || $precioOferta > 90) {
                throw new Exception("❌ El descuento debe estar entre 1% y 90%");
            }
        }

        if ($idCategoria <= 0 || $nombre === "" || $precio <= 0 || $sku === "") {
            throw new Exception("❌ Faltan datos obligatorios del producto.");
        }

        $this->pdo->beginTransaction();
        try {
            $idProducto = $this->dao->insertarProducto([
                ":id_categoria" => $idCategoria,
                ":nombre" => $nombre,
                ":descripcion_corta" => $descCorta,
                ":descripcion_larga" => $descLarga,
                ":precio" => $precio,
                ":precio_oferta" => $precioOferta,
                ":sku" => $sku,
                ":aplica_iva" => $aplicaIva,
            ]);

            $this->dao->insertarInventario($idProducto);

            if ($imagen !== "") {
                $this->dao->insertarImagenPrincipal($idProducto, $imagen);
            }

            // Si hay descuento, creamos promo (como en tu lógica actual)
            if ($precioOferta !== null) {
                $this->dao->crearPromocionParaProducto($idProducto, "Promo - " . $nombre, $precioOferta);
            }

            $this->pdo->commit();
            return $idProducto;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function editar(int $idProducto, array $post): void {
        if ($idProducto <= 0) throw new Exception("ID inválido.");

        $idCategoria       = (int)($post["id_categoria"] ?? 0);
        $nombre            = trim($post["nombre"] ?? "");
        $descCorta         = trim($post["descripcion_corta"] ?? "");
        $descLarga         = trim($post["descripcion_larga"] ?? "");
        $precio            = (float)($post["precio"] ?? 0);
        $precioOfertaRaw   = $post["precio_oferta"] ?? null;
        $sku               = trim($post["sku"] ?? "");
        $aplicaIva         = isset($post["aplica_iva"]) ? 1 : 0;
        $imagen            = trim($post["imagen"] ?? "");

        $precioOferta = null;
        if ($precioOfertaRaw !== null && $precioOfertaRaw !== '') {
            $precioOferta = (float)$precioOfertaRaw;
            if ($precioOferta < 1 || $precioOferta > 90) {
                throw new Exception("❌ El descuento debe estar entre 1% y 90%");
            }
        }

        if ($idCategoria <= 0 || $nombre === "" || $precio <= 0 || $sku === "") {
            throw new Exception("❌ Faltan datos obligatorios del producto.");
        }

        $this->pdo->beginTransaction();
        try {
            $this->dao->actualizarProducto($idProducto, [
                ":id_categoria" => $idCategoria,
                ":nombre" => $nombre,
                ":descripcion_corta" => $descCorta,
                ":descripcion_larga" => $descLarga,
                ":precio" => $precio,
                ":precio_oferta" => $precioOferta,
                ":sku" => $sku,
                ":aplica_iva" => $aplicaIva,
            ]);

            if ($imagen !== "") {
                $this->dao->resetImagenPrincipal($idProducto);
                $this->dao->insertarImagenPrincipal($idProducto, $imagen);
            }

            // Promos: si hay descuento, (re)creamos; si no, desactivamos existentes
            if ($precioOferta !== null) {
                $this->dao->crearPromocionParaProducto($idProducto, "Promo - " . $nombre, $precioOferta);
            } else {
                $this->dao->desactivarPromocionesDeProducto($idProducto);
                $this->dao->borrarVinculosPromocionProducto($idProducto);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function desactivar(int $idProducto): void {
        $this->dao->setActivo($idProducto, 0);
    }

    public function activar(int $idProducto): void {
        $this->dao->setActivo($idProducto, 1);
    }
}
