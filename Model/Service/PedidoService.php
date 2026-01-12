<?php
// Model/Service/PedidoService.php
//
// ✅ Adaptado para usar Entities (Model/Entity) sin romper compatibilidad.
// - Sigue recibiendo $carrito como array (localStorage)
// - Persiste usando DAOs y construye Entities Pedido/PedidoDetalle
// - Devuelve el id_pedido igual que antes

declare(strict_types=1);

require_once __DIR__ . "/../DAO/EmpresaUsuarioDAO.php";
require_once __DIR__ . "/../DAO/PedidoDAO.php";
require_once __DIR__ . "/../DAO/PedidoDetalleDAO.php";
require_once __DIR__ . "/../DAO/InventarioSucursalDAO.php";
require_once __DIR__ . "/../DAO/DireccionUsuarioDAO.php";
require_once __DIR__ . "/../Entity/Pedido.php";
require_once __DIR__ . "/../Entity/PedidoDetalle.php";
require_once __DIR__ . "/../Entity/Producto.php";

class PedidoService
{
    private EmpresaUsuarioDAO $empresaUsuarioDAO;
    private PedidoDAO $pedidoDAO;
    private PedidoDetalleDAO $pedidoDetalleDAO;
    private InventarioSucursalDAO $inventarioDAO;
    private DireccionUsuarioDAO $direccionDAO;

    public function __construct(private PDO $pdo)
    {
        $this->empresaUsuarioDAO = new EmpresaUsuarioDAO($pdo);
        $this->pedidoDAO = new PedidoDAO($pdo);
        $this->pedidoDetalleDAO = new PedidoDetalleDAO($pdo);
        $this->inventarioDAO = new InventarioSucursalDAO($pdo);
        $this->direccionDAO = new DireccionUsuarioDAO($pdo);
    }

    /**
     * Crea un pedido en estado 'pagado' basado en un carrito (items con id, cantidad, precio/precio_oferta opcional).
     * @param array<int,array<string,mixed>> $carrito
     */
    public function crearPedido(int $idUsuario, array $carrito): int
    {
        // Compatibilidad: si no se manda checkout, se asume envío sin dirección
        return $this->crearPedidoCheckout($idUsuario, $carrito, []);
    }

    /**
     * Crea un pedido luego de un pago exitoso.
     * @param array<int,array<string,mixed>> $carrito
     * @param array<string,mixed> $checkout
     */
    public function crearPedidoCheckout(int $idUsuario, array $carrito, array $checkout): int
    {
        if ($idUsuario <= 0) {
            throw new Exception("Usuario inválido");
        }

        if (empty($carrito)) {
            throw new Exception("Carrito vacío");
        }

        $this->pdo->beginTransaction();

        try {
            // 1) Obtener precios reales desde BD (evita confiar en el front)
            $ids = [];
            foreach ($carrito as $p) {
                // soportar carritos con {id} o {id_producto}
                $id = (int)($p["id"] ?? $p["id_producto"] ?? 0);
                if ($id > 0) $ids[] = $id;
            }
            $ids = array_values(array_filter(array_unique($ids), fn($v) => $v > 0));

            if (empty($ids)) {
                throw new Exception("Carrito inválido (sin productos)");
            }

            $placeholders = implode(",", array_fill(0, count($ids), "?"));

            $stmt = $this->pdo->prepare("
                SELECT 
                    id_producto AS id,
                    nombre,
                    precio,
                    precio_oferta
                FROM productos
                WHERE id_producto IN ($placeholders)
            ");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int,Producto> $productosMap */
            $productosMap = [];
            foreach ($rows as $r) {
                $prod = Producto::fromRow($r);
                $productosMap[(int)$prod->id] = $prod;
            }

            // 2) Calcular totales (usa precio_oferta si aplica)
            $totalProductos = 0.0;

            // Guardamos detalles como Entities antes de persistir
            /** @var PedidoDetalle[] $detalles */
            $detalles = [];

            foreach ($carrito as $item) {
                $idProducto = (int)($item["id"] ?? $item["id_producto"] ?? 0);
                $cantidad   = (int)($item["cantidad"] ?? $item["qty"] ?? 0);

                if ($idProducto <= 0 || $cantidad <= 0) continue;

                if (!isset($productosMap[$idProducto])) {
                    throw new Exception("Producto no encontrado (id=$idProducto)");
                }

                $p = $productosMap[$idProducto];
                $precioUnit = ((float)($p->precio_oferta ?? 0)) > 0 ? (float)$p->precio_oferta : (float)$p->precio;
                $subtotal = round($precioUnit * $cantidad, 2);

                $totalProductos += $subtotal;

                $d = new PedidoDetalle();
                $d->id_pedido = 0; // se asigna después de insertar
                $d->id_producto = $idProducto;
                $d->cantidad = $cantidad;
                $d->precio_unit = $precioUnit;
                $d->subtotal = $subtotal;
                $detalles[] = $d;
            }

            if ($totalProductos <= 0 || empty($detalles)) {
                throw new Exception("Carrito inválido (sin items válidos)");
            }

            // 2.1) Descuento empresa (si aplica)
            $esEmpresa = (bool)$this->empresaUsuarioDAO->obtenerEmpresaPorUsuario($idUsuario);
            $porcentajeDescuento = $esEmpresa ? 0.10 : 0.00; // 10%
            $descuento = round($totalProductos * $porcentajeDescuento, 2);
            $subtotalConDescuento = round($totalProductos - $descuento, 2);

            // 2.2) IVA y total final (IVA sobre subtotal con descuento)
            $iva = round($subtotalConDescuento * 0.12, 2);
            $totalPagar = round($subtotalConDescuento + $iva, 2);

            // 3) Construir Entity Pedido y persistir usando DAO
            $pedido = new Pedido();
            $pedido->id_usuario = $idUsuario;
            $pedido->fecha_pedido = date("Y-m-d H:i:s");
            $pedido->total_productos = $subtotalConDescuento; // total después del descuento
            $pedido->total_iva = $iva;
            $pedido->total_pagar = $totalPagar;
            $estado = trim((string)($checkout["estado"] ?? ""));
            if ($estado === "") $estado = "en_proceso";
            $pedido->estado = $estado;
            $pedido->tipo_entrega = (string)($checkout["tipo_entrega"] ?? "envio");
            $pedido->id_sucursal_retiro = isset($checkout["id_sucursal_retiro"]) ? (int)$checkout["id_sucursal_retiro"] : null;
            $pedido->id_sucursal_origen = isset($checkout["id_sucursal_origen"]) ? (int)$checkout["id_sucursal_origen"] : null;

            // Dirección de envío: si viene id_direccion_envio usarla; si viene direccion_nueva crear y usar el id.
            $idDireccion = null;
            if (isset($checkout["id_direccion_envio"]) && $checkout["id_direccion_envio"] !== null) {
                $idDireccion = (int)$checkout["id_direccion_envio"];
            }

            // ✅ Restricción: solo direcciones dentro de Loja (provincia)
            if (($pedido->tipo_entrega ?? "") === "envio") {
                // Si viene dirección guardada, validamos provincia en BD
                if ($idDireccion) {
                    $entDir = $this->direccionDAO->obtenerEntidadPorId($idDireccion);
                    if ($entDir && $entDir->provincia !== null) {
                        $prov = trim(mb_strtolower((string)$entDir->provincia));
                        if ($prov !== "" && $prov !== "loja") {
                            throw new Exception("Solo se permiten direcciones dentro de Loja");
                        }
                    }
                    // ✅ Restricción ciudad
                    if ($entDir && $entDir->ciudad !== null) {
                        $ciu = trim(mb_strtolower((string)$entDir->ciudad));
                        if ($ciu !== "" && $ciu !== "loja") {
                            throw new Exception("Solo se permiten direcciones dentro de Loja");
                        }
                    }
                }
                // Si viene dirección nueva, validamos provincia del payload
                if (!$idDireccion && isset($checkout["direccion_nueva"]) && is_array($checkout["direccion_nueva"])) {
                    $provN = trim(mb_strtolower((string)($checkout["direccion_nueva"]["provincia"] ?? "")));
                    if ($provN !== "" && $provN !== "loja") {
                        throw new Exception("Solo se permiten direcciones dentro de Loja");
                    }
                    $ciuN = trim(mb_strtolower((string)($checkout["direccion_nueva"]["ciudad"] ?? "")));
                    if ($ciuN !== "" && $ciuN !== "loja") {
                        throw new Exception("Solo se permiten direcciones dentro de Loja");
                    }
                }
            }
            if (!$idDireccion && isset($checkout["direccion_nueva"]) && is_array($checkout["direccion_nueva"])) {
                $dir = $checkout["direccion_nueva"];
                $direccionTxt = trim((string)($dir["direccion"] ?? ""));
                if ($direccionTxt !== "") {
                    $idDireccion = $this->direccionDAO->insertar([
                        "id_usuario" => $idUsuario,
                        "tipo" => "envio",
                        "direccion" => $direccionTxt,
                        "ciudad" => (string)($dir["ciudad"] ?? null),
                        // Forzamos provincia Loja en BD aunque el navegador autofill intente otra
                        "provincia" => "Loja",
                        "codigo_postal" => (string)($dir["codigo_postal"] ?? null),
                        "referencia" => (string)($dir["referencia"] ?? null),
                        "es_principal" => 0,
                    ]);
                }
            }
            $pedido->id_direccion_envio = $idDireccion ? $idDireccion : null;
            $pedido->observaciones = "";

            $idPedido = $this->pedidoDAO->insertar($pedido->toArray());

            if ($idPedido <= 0) {
                throw new Exception("No se pudo crear el pedido");
            }

            // 4) Insertar detalles (Entities)
            foreach ($detalles as $d) {
                $d->id_pedido = $idPedido;
                $this->pedidoDetalleDAO->insertar($d->toArray());
            }

            // 4) Descontar stock en la sucursal correspondiente
            $idSucursalStock = null;
            if ($pedido->tipo_entrega === "retiro_local") {
                $idSucursalStock = $pedido->id_sucursal_retiro;
            } else {
                $idSucursalStock = $pedido->id_sucursal_origen;
            }
            if ($idSucursalStock) {
                foreach ($detalles as $d) {
                    $this->inventarioDAO->reducirStock((int)$idSucursalStock, (int)$d->id_producto, (int)$d->cantidad);
                }
            }

            $this->pdo->commit();
            return (int)$idPedido;

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
