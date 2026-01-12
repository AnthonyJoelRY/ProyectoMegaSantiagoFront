<?php
// Controller/CheckoutController.php
// ✅ Integra: (1) carga sucursales como dashboard + (2) validar-stock ESTRICTO por sucursal (no permite sobrepasar)

require_once __DIR__ . "/_helpers/Bootstrap.php";
require_once __DIR__ . "/../Model/DB/db.php";
require_once __DIR__ . "/../Model/DAO/SucursalDAO.php";
require_once __DIR__ . "/../Model/DAO/InventarioSucursalDAO.php";

$pdo = obtenerConexion();

function leerJsonBody(): array {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function request_data(): array {
    $data = leerJsonBody();
    if (!empty($data)) return $data;
    return array_merge($_GET ?? [], $_POST ?? []);
}

function q_one(PDO $pdo, string $sql, array $params = []): ?array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function q_all(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function tablaExiste(PDO $pdo, string $tabla): bool {
    try {
        $r = q_one($pdo, "SHOW TABLES LIKE :t", ["t" => $tabla]);
        return (bool)$r;
    } catch (Throwable $e) {
        return false;
    }
}

function columnaExiste(PDO $pdo, string $tabla, string $columna): bool {
    try {
        $r = q_one($pdo, "SHOW COLUMNS FROM `$tabla` LIKE :c", ["c" => $columna]);
        return (bool)$r;
    } catch (Throwable $e) {
        return false;
    }
}

$accion = $_GET["accion"] ?? ($_POST["accion"] ?? "");
$data = request_data();

try {

    // =========================
    // 1) DATA: SUCURSALES + DIRECCIONES (NO TOCAR: igual que dashboard)
    // =========================
    if ($accion === "data") {

        $idUsuario = (int)($data["id_usuario"] ?? 0);

        // ✅ SUCURSALES: usar DAO (como dashboard)
        $dao = new SucursalDAO($pdo);
        $sucursales = $dao->listarActivas(); // debe devolver activo=1

        // Asegurar estructura que el JS espera: id_sucursal, nombre, ciudad
        $outSuc = [];
        foreach ($sucursales as $s) {
            $outSuc[] = [
                "id_sucursal" => (int)($s["id_sucursal"] ?? 0),
                "nombre"      => (string)($s["nombre"] ?? ""),
                "ciudad"      => (string)($s["ciudad"] ?? ""),
            ];
        }

        // ✅ DIRECCIONES (si existen tablas)
        $direcciones = [];
        if ($idUsuario > 0) {
            $tablaDir = tablaExiste($pdo, "direcciones_usuario") ? "direcciones_usuario"
                     : (tablaExiste($pdo, "direcciones") ? "direcciones" : null);

            if ($tablaDir) {
                $campos = ["id_direccion","direccion","ciudad","provincia","codigo_postal","referencia"];
                $select = [];
                foreach ($campos as $c) {
                    if (columnaExiste($pdo, $tablaDir, $c)) $select[] = $c;
                }

                if (!empty($select)) {
                    $selectSql = implode(",", $select);

                    if (columnaExiste($pdo, $tablaDir, "id_usuario")) {
                        $direcciones = q_all($pdo, "SELECT $selectSql FROM `$tablaDir` WHERE id_usuario = :u ORDER BY id_direccion DESC", ["u"=>$idUsuario]);
                    } elseif (columnaExiste($pdo, $tablaDir, "usuario_id")) {
                        $direcciones = q_all($pdo, "SELECT $selectSql FROM `$tablaDir` WHERE usuario_id = :u ORDER BY id_direccion DESC", ["u"=>$idUsuario]);
                    }
                    foreach ($direcciones as &$d) {
                        foreach ($campos as $c) if (!array_key_exists($c, $d)) $d[$c] = "";
                    }
                    unset($d);

                    // ✅ Solo devolver direcciones dentro de Loja (provincia)
                    // (El front además fuerza el input de provincia.)
                    $direcciones = array_values(array_filter($direcciones, function($d) {
                        $prov = trim(strtolower((string)($d["provincia"] ?? "")));
                        if ($prov === "") return true; // compatibilidad si la BD no guarda provincia
                        return $prov === "loja";
                    }));
                }
            }
        }

        api_responder([
            "ok" => true,
            "sucursales" => $outSuc,
            "direcciones" => $direcciones
        ], 200);
    }

    // =========================
    // 2) VALIDAR-STOCK (ESTRICTO)
    // - Si el carrito sobrepasa lo que hay en existencia => bloquea.
    // - Retiro local: valida contra la sucursal seleccionada.
    // - Envío: elige la sucursal que MÁS stock tiene (entre las que cumplen).
    // =========================
    if ($accion === "validar-stock") {

        $tipoEntrega = (string)($data["tipo_entrega"] ?? "");
        $idSucursalRetiro = isset($data["id_sucursal_retiro"]) ? (int)$data["id_sucursal_retiro"] : 0;

        $carrito = $data["cart"] ?? [];
        if (!is_array($carrito)) $carrito = [];

        $items = [];
        foreach ($carrito as $it) {
            $idp  = (int)($it["id_producto"] ?? $it["id"] ?? 0);
            $cant = (int)($it["cantidad"] ?? $it["qty"] ?? 0);
            if ($idp > 0 && $cant > 0) $items[] = ["id_producto"=>$idp, "cantidad"=>$cant];
        }
        if (empty($items)) api_responder(["ok"=>false, "error"=>"Carrito vacío."], 400);

        // Sucursales activas (igual que dashboard)
        $dao = new SucursalDAO($pdo);
        $sucActivas = $dao->listarActivas();
        $sucIds = array_values(array_filter(array_map(fn($r)=> (int)($r["id_sucursal"] ?? 0), $sucActivas)));

        if (empty($sucIds)) {
            api_responder(["ok"=>false, "error"=>"No hay sucursales activas."], 400);
        }

        // ✅ ESTRICTO: siempre se valida contra inventario_sucursal (si existe) y/o inventario (fallback).
        // Si no existe registro de inventario, el DAO devuelve 0 => bloquea.
        $invDao = new InventarioSucursalDAO($pdo);

        $faltantesEn = function(int $idSucursal) use ($items, $invDao): array {
            $falt = [];
            foreach ($items as $it) {
                $stock = (int)$invDao->obtenerStock($idSucursal, (int)$it["id_producto"]);
                if ($stock < (int)$it["cantidad"]) {
                    $falt[] = [
                        "id_producto" => (int)$it["id_producto"],
                        "necesario"   => (int)$it["cantidad"],
                        "stock"       => (int)$stock,
                    ];
                }
            }
            return $falt;
        };

        if ($tipoEntrega === "retiro_local") {
            if ($idSucursalRetiro <= 0) api_responder(["ok"=>false, "error"=>"Selecciona una sucursal para retiro."], 400);
            $falt = $faltantesEn($idSucursalRetiro);
            if (!empty($falt)) api_responder(["ok"=>false, "error"=>"Sobrepasa lo que hay existencia", "faltantes"=>$falt], 409);
            api_responder(["ok"=>true, "id_sucursal_retiro"=>$idSucursalRetiro], 200);
        }

        // envio: elegir la sucursal que MÁS stock tiene (entre las que pueden cubrir TODO el carrito)
        $mejor = null;
        $mejorScore = -1;
        foreach ($sucIds as $sid) {
            $falt = $faltantesEn($sid);
            if (!empty($falt)) continue;

            // score = suma del stock de cada item (mayor = "la que más tiene")
            $score = 0;
            foreach ($items as $it) {
                $score += (int)$invDao->obtenerStock((int)$sid, (int)$it["id_producto"]);
            }
            if ($score > $mejorScore) {
                $mejorScore = $score;
                $mejor = (int)$sid;
            }
        }

        if ($mejor === null) {
            // Ninguna sucursal puede cubrir el pedido
            api_responder(["ok"=>false, "error"=>"Sobrepasa lo que hay existencia"], 409);
        }

        api_responder(["ok"=>true, "id_sucursal_origen"=>$mejor], 200);
    }

    api_responder(["ok"=>false, "error"=>"Acción no válida"], 400);

} catch (Throwable $e) {
    api_responder(["ok"=>false, "error"=>"CheckoutController error", "message"=>$e->getMessage()], 500);
}
