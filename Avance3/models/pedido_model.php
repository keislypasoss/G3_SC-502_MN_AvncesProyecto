<?php // moldes/PedidoModel.php 
class PedidoModel
{
    private mysqli $db;
    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }
    public function obtenerEstadoPedidoId(string $nombre = 'Pagado'): ?int
    {
        $sql = "SELECT id_estado FROM Estado WHERE nombre=? AND tipo_estado='Pedido' LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->bind_param('s', $nombre);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        return $r ? (int)$r['id_estado'] : null;
    }
    public function crearPedido(?int $id_cliente, ?string $nota, ?int $id_estado): int
    {
        $sql = "INSERT INTO Pedido (id_cliente, fecha_creacion, nota_cliente, id_estado) VALUES (?,?,?,?)";
        $st = $this->db->prepare($sql);
        $fecha = date('Y-m-d H:i:s');
        $st->bind_param('issi', $id_cliente, $fecha, $nota, $id_estado);
        $st->execute();
        return (int)$this->db->insert_id;
    }
    public function agregarDetalle(int $id_pedido, int $id_producto, int $cantidad, float $precio_unitario): void
    {
        $sql = "INSERT INTO Detalle_pedido (id_carrito, id_producto, cantidad, precio_unitario) VALUES (?,?,?,?)";
        $st = $this->db->prepare($sql);
        $st->bind_param('iiid', $id_pedido, $id_producto, $cantidad, $precio_unitario);
        $st->execute();
    }
}
