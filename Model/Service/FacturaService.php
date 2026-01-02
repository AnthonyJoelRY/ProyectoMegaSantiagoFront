<?php
// Model/Service/FacturaService.php

class FacturaService
{
    public function __construct(private PDO $pdo) {}

    /**
     * Genera datos y HTML de factura para un pedido.
     * Retorna:
     *  - email
     *  - subject
     *  - html
     */
    public function buildFactura(int $idPedido): array
    {
        // Pedido
        $stmt = $this->pdo->prepare("SELECT * FROM pedidos WHERE id_pedido = ? LIMIT 1");
        $stmt->execute([$idPedido]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pedido) {
            return ["error" => "Pedido no encontrado."];
        }

        // Usuario
        $stmtU = $this->pdo->prepare("SELECT email FROM usuarios WHERE id_usuario = ? LIMIT 1");
        $stmtU->execute([(int)$pedido["id_usuario"]]);
        $usuario = $stmtU->fetch(PDO::FETCH_ASSOC);
        $email = $usuario["email"] ?? "";

        // Detalle + nombre producto
        $stmtD = $this->pdo->prepare("
            SELECT 
                d.id_producto,
                p.nombre,
                d.cantidad,
                d.precio_unit,
                d.subtotal
            FROM pedido_detalle d
            JOIN productos p ON p.id_producto = d.id_producto
            WHERE d.id_pedido = ?
            ORDER BY d.id_producto
        ");
        $stmtD->execute([$idPedido]);
        $items = $stmtD->fetchAll(PDO::FETCH_ASSOC);

        $totalProductos = (float)($pedido["total_productos"] ?? 0);
        $totalIva       = (float)($pedido["total_iva"] ?? 0);
        $totalPagar     = (float)($pedido["total_pagar"] ?? 0);
        $fecha          = (string)($pedido["fecha_pedido"] ?? "");

        $subject = "Factura MegaSantiago - Pedido #" . $idPedido;

        $html = $this->renderHtml($idPedido, $email, $fecha, $items, $totalProductos, $totalIva, $totalPagar);

        return [
            "email" => $email,
            "subject" => $subject,
            "html" => $html,
        ];
    }

    private function renderHtml(int $idPedido, string $email, string $fecha, array $items, float $totalProductos, float $totalIva, float $totalPagar): string
    {
        $fmt = fn($n) => number_format((float)$n, 2, ".", "");
        $rows = "";
        foreach ($items as $it) {
            $rows .= "<tr>"
                . "<td style='padding:8px;border-bottom:1px solid #eee;'>" . htmlspecialchars((string)$it["nombre"]) . "</td>"
                . "<td style='padding:8px;border-bottom:1px solid #eee;text-align:center;'>" . (int)$it["cantidad"] . "</td>"
                . "<td style='padding:8px;border-bottom:1px solid #eee;text-align:right;'>$" . $fmt($it["precio_unit"]) . "</td>"
                . "<td style='padding:8px;border-bottom:1px solid #eee;text-align:right;'>$" . $fmt($it["subtotal"]) . "</td>"
                . "</tr>";
        }

        return "
<!doctype html>
<html lang='es'>
<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>Factura Pedido #{$idPedido}</title>
</head>
<body style='font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;'>
  <div style='max-width:720px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,.08)'>
    <div style='padding:18px 22px;background:#0b63ce;color:#fff;'>
      <div style='font-size:18px;font-weight:700;'>MegaSantiago</div>
      <div style='opacity:.9;'>Factura de compra</div>
    </div>

    <div style='padding:22px;'>
      <div style='display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;'>
        <div>
          <div style='font-weight:700;margin-bottom:4px;'>Pedido #{$idPedido}</div>
          <div style='color:#555;font-size:13px;'>Fecha: {$fecha}</div>
        </div>
        <div>
          <div style='color:#555;font-size:13px;'>Enviado a:</div>
          <div style='font-weight:600;'>{$email}</div>
        </div>
      </div>

      <h3 style='margin:18px 0 10px 0;font-size:16px;'>Detalle</h3>

      <table style='width:100%;border-collapse:collapse;font-size:14px;'>
        <thead>
          <tr>
            <th style='text-align:left;padding:8px;border-bottom:2px solid #ddd;'>Producto</th>
            <th style='text-align:center;padding:8px;border-bottom:2px solid #ddd;'>Cant.</th>
            <th style='text-align:right;padding:8px;border-bottom:2px solid #ddd;'>Precio</th>
            <th style='text-align:right;padding:8px;border-bottom:2px solid #ddd;'>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          {$rows}
        </tbody>
      </table>

      <div style='margin-top:16px;display:flex;justify-content:flex-end;'>
        <table style='width:320px;border-collapse:collapse;font-size:14px;'>
          <tr>
            <td style='padding:6px 0;color:#555;'>Total productos</td>
            <td style='padding:6px 0;text-align:right;font-weight:600;'>$".$fmt($totalProductos)."</td>
          </tr>
          <tr>
            <td style='padding:6px 0;color:#555;'>IVA</td>
            <td style='padding:6px 0;text-align:right;font-weight:600;'>$".$fmt($totalIva)."</td>
          </tr>
          <tr>
            <td style='padding:10px 0;border-top:1px solid #eee;font-size:15px;font-weight:700;'>TOTAL</td>
            <td style='padding:10px 0;border-top:1px solid #eee;text-align:right;font-size:15px;font-weight:700;'>$".$fmt($totalPagar)."</td>
          </tr>
        </table>
      </div>

      <p style='margin-top:18px;color:#666;font-size:12px;line-height:1.5;'>
        Gracias por tu compra. Si tienes dudas, responde a este correo.
      </p>
    </div>
  </div>
</body>
</html>";
    }
}
