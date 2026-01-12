<?php
// Model/DAO/Admin/AdminPedidoDAO.php
// DAO para el panel de administración (pedidos)

declare(strict_types=1);

class AdminPedidoDAO {
    public function __construct(private PDO $pdo) {}

    /**
     * Lista pedidos (por defecto muestra todos los estados operativos).
     * Si $q viene vacío: lista todos.
     * Si $q es numérico o viene como #123: filtra por id_pedido exacto.
     * Caso contrario: busca por email (LIKE, case-insensitive).
     */
    public function listarPagados(string $q = ""): array {
        $q = trim($q);

        $base = "
            SELECT
                p.*, 
                u.email AS cliente
            FROM pedidos p
            JOIN usuarios u ON u.id_usuario = p.id_usuario
            WHERE p.estado IN ('pendiente','pagado','enviado','entregado','cancelado','en_proceso','listo_para_entregar','en_camino')
        ";

        $params = [];
        if ($q !== "") {
            // acepta #28 o 28
            $qNorm = ltrim($q);
            if (str_starts_with($qNorm, '#')) {
                $qNorm = substr($qNorm, 1);
            }
            $qNorm = trim($qNorm);

            if ($qNorm !== '' && ctype_digit($qNorm)) {
                $base .= " AND p.id_pedido = :id ";
                $params['id'] = (int)$qNorm;
            } else {
                // búsqueda por correo (case-insensitive robusta)
                $base .= " AND LOWER(u.email) LIKE :email ";
                $params['email'] = '%' . mb_strtolower($q, 'UTF-8') . '%';
            }
        }

        $base .= " ORDER BY p.id_pedido DESC ";

        $stmt = $this->pdo->prepare($base);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function obtener(int $id): ?array {
        $stmt = $this->pdo->prepare(" 
            SELECT
                p.*,
                u.email AS cliente,

                -- Sucursal de retiro
                sr.nombre    AS sucursal_retiro_nombre,
                sr.direccion AS sucursal_retiro_direccion,
                sr.ciudad    AS sucursal_retiro_ciudad,
                sr.telefono  AS sucursal_retiro_telefono,
                sr.horario   AS sucursal_retiro_horario,

                -- Sucursal origen (para envíos, donde se descuenta stock)
                so.nombre    AS sucursal_origen_nombre,
                so.direccion AS sucursal_origen_direccion,
                so.ciudad    AS sucursal_origen_ciudad,
                so.telefono  AS sucursal_origen_telefono,
                so.horario   AS sucursal_origen_horario,

                -- Dirección de envío (si aplica)
                du.tipo          AS direccion_envio_tipo,
                du.direccion     AS direccion_envio_direccion,
                du.ciudad        AS direccion_envio_ciudad,
                du.provincia     AS direccion_envio_provincia,
                du.codigo_postal AS direccion_envio_codigo_postal,
                du.referencia    AS direccion_envio_referencia

            FROM pedidos p
            JOIN usuarios u ON u.id_usuario = p.id_usuario
            LEFT JOIN sucursales sr ON sr.id_sucursal = p.id_sucursal_retiro
            LEFT JOIN sucursales so ON so.id_sucursal = p.id_sucursal_origen
            LEFT JOIN direcciones_usuario du ON du.id_direccion = p.id_direccion_envio
            WHERE p.id_pedido = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function detalle(int $id): array {
        $stmt = $this->pdo->prepare("
            SELECT
                pr.nombre      AS nombre,
                d.cantidad     AS cantidad,
                d.precio_unit  AS precio_unit,
                d.subtotal     AS subtotal
            FROM pedido_detalle d
            INNER JOIN productos pr ON pr.id_producto = d.id_producto
            WHERE d.id_pedido = ?
            ORDER BY d.id_detalle ASC
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


    public function actualizarEstado(int $idPedido, string $estado): void {
        $stmt = $this->pdo->prepare("UPDATE pedidos SET estado = :e WHERE id_pedido = :id LIMIT 1");
        $stmt->execute(["e" => $estado, "id" => $idPedido]);
    }
}
