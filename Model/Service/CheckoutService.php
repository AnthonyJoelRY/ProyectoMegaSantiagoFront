<?php
declare(strict_types=1);

// Model/Service/CheckoutService.php
// Funciones para checkout (sucursales, direcciones, validación de stock).

require_once __DIR__ . "/../DAO/SucursalDAO.php";
require_once __DIR__ . "/../DAO/DireccionUsuarioDAO.php";
require_once __DIR__ . "/../DAO/InventarioSucursalDAO.php";

class CheckoutService
{
    private SucursalDAO $sucursalDAO;
    private DireccionUsuarioDAO $direccionDAO;
    private InventarioSucursalDAO $inventarioDAO;

    public function __construct(private PDO $pdo)
    {
        $this->sucursalDAO = new SucursalDAO($pdo);
        $this->direccionDAO = new DireccionUsuarioDAO($pdo);
        $this->inventarioDAO = new InventarioSucursalDAO($pdo);
    }

    public function datosCheckout(int $idUsuario): array
    {
        $sucursales = $this->sucursalDAO->listarActivas();
        $direcciones = $idUsuario > 0 ? $this->direccionDAO->listarPorUsuario($idUsuario) : [];
        return [
            "ok" => true,
            "sucursales" => $sucursales,
            "direcciones" => $direcciones,
        ];
    }

    /**
     * Valida stock según tipo_entrega.
     * - retiro_local: requiere id_sucursal_retiro y valida stock ahí
     * - envio: busca cualquier sucursal con stock (devuelve id_sucursal_origen)
     *
     * @param array<int,array<string,mixed>> $carrito
     */
    public function validarStock(int $idUsuario, array $carrito, string $tipoEntrega, ?int $idSucursalRetiro): array
    {
        $tipoEntrega = strtolower(trim($tipoEntrega));
        if ($tipoEntrega !== "retiro_local" && $tipoEntrega !== "envio") {
            return ["ok" => false, "error" => "Tipo de entrega inválido."];
        }

        if (empty($carrito)) {
            return ["ok" => false, "error" => "Carrito vacío."];
        }

        $sucursales = $this->sucursalDAO->listarActivas();
        $sucIds = array_map(fn($s) => (int)$s["id_sucursal"], $sucursales);

        if ($tipoEntrega === "retiro_local") {
            $idSucursal = (int)($idSucursalRetiro ?? 0);
            if ($idSucursal <= 0) return ["ok" => false, "error" => "Selecciona una sucursal para retiro."];

            $faltantes = $this->inventarioDAO->validarStockEnSucursal($idSucursal, $carrito);
            if (!empty($faltantes)) {
                return ["ok" => false, "error" => "Stock agotado en la sucursal seleccionada.", "faltantes" => $faltantes];
            }
            return ["ok" => true, "id_sucursal_retiro" => $idSucursal];
        }

        // envio: buscar sucursal con stock
        $idOrigen = $this->inventarioDAO->buscarSucursalConStock($carrito, $sucIds);
        if (!$idOrigen) {
            return ["ok" => false, "error" => "No hay stock disponible en ninguna sucursal para completar tu pedido."];
        }

        return ["ok" => true, "id_sucursal_origen" => $idOrigen];
    }
}
