<?php
// moldes/FacturaModel.php
class FacturaModel {
    private mysqli $db;
    public function __construct(mysqli $db){ $this->db = $db; }

    public function obtenerMetodoPagoIdPorNombre(string $nombre): ?int {
        $sql = "SELECT id_metodo_pago FROM Metodo_Pago WHERE nombre=? LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->bind_param('s', $nombre);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        return $r ? (int)$r['id_metodo_pago'] : null;
    }

    public function crearFactura(?int $id_cliente, int $id_pedido, int $id_metodo_pago, float $total): int {
        $sql = "INSERT INTO Factura (id_cliente, id_pedido, fecha, total, id_metodo_pago)
                VALUES (?,?,?,?,?)";
        $st = $this->db->prepare($sql);
        $fecha = date('Y-m-d H:i:s');
        $st->bind_param('iisdi', $id_cliente, $id_pedido, $fecha, $total, $id_metodo_pago);
        $st->execute();
        return (int)$this->db->insert_id;
    }

    public function agregarDetalleFactura(int $id_factura, int $id_producto, int $cantidad, float $precio_unitario, string $nombre_producto): void {
        $sql = "INSERT INTO Detalle_Factura (id_factura, id_producto, cantidad, precio_unitario, nombre_producto)
                VALUES (?,?,?,?,?)";
        $st = $this->db->prepare($sql);
        $st->bind_param('iiids', $id_factura, $id_producto, $cantidad, $precio_unitario, $nombre_producto);
        $st->execute();
    }

    public function obtenerConDetalles(int $id_factura): array {
        $st = $this->db->prepare("SELECT f.*, p.fecha_creacion, c.nombre AS cliente_nombre, c.telefono, c.direccion
                                  FROM Factura f
                                  LEFT JOIN Pedido p ON p.id_pedido = f.id_pedido
                                  LEFT JOIN Cliente c ON c.id_cliente = f.id_cliente
                                  WHERE f.id_factura=?");
        $st->bind_param('i', $id_factura);
        $st->execute();
        $factura = $st->get_result()->fetch_assoc();

        $st2 = $this->db->prepare("SELECT df.* FROM Detalle_Factura df WHERE df.id_factura=?");
        $st2->bind_param('i', $id_factura);
        $st2->execute();
        $detalles = $st2->get_result()->fetch_all(MYSQLI_ASSOC);

        return ['factura' => $factura, 'detalles' => $detalles];
    }
}
