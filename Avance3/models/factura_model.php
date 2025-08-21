<?php

class FacturaModel
{
    private mysqli $db;
    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function obtenerMetodoPagoIdPorNombre(string $nombre): ?int
    {
        $sql = "SELECT id_metodo_pago FROM Metodo_Pago WHERE nombre=? LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->bind_param('s', $nombre);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        return $r ? (int)$r['id_metodo_pago'] : null;
    }

    public function crearFactura(?int $id_cliente, int $id_pedido, int $id_metodo_pago, float $total): int
    {
        $sql = "INSERT INTO Factura (id_cliente, id_pedido, fecha, total, id_metodo_pago)
                VALUES (?,?,?,?,?)";
        $st = $this->db->prepare($sql);
        $fecha = date('Y-m-d H:i:s');
        $st->bind_param('iisdi', $id_cliente, $id_pedido, $fecha, $total, $id_metodo_pago);
        $st->execute();
        return (int)$this->db->insert_id;
    }

    public function agregarDetalleFactura(int $id_factura, int $id_producto, int $cantidad, float $precio_unitario, string $nombre_producto): void
    {
        $sql = "INSERT INTO Detalle_Factura (id_factura, id_producto, cantidad, precio_unitario, nombre_producto)
                VALUES (?,?,?,?,?)";
        $st = $this->db->prepare($sql);
        $st->bind_param('iiids', $id_factura, $id_producto, $cantidad, $precio_unitario, $nombre_producto);
        $st->execute();
    }

    public function obtenerConDetalles(int $id_factura): array
    {
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

    public function obtenerIdClientePorUsuario(int $id_usuario): ?int
    {
        $st = $this->db->prepare("SELECT id_cliente FROM Cliente WHERE id_usuario=? LIMIT 1");
        $st->bind_param('i', $id_usuario);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        return $r ? (int)$r['id_cliente'] : null;
    }

    /** Lista facturas del cliente (paginado) */
    public function listarFacturasPorCliente(int $id_cliente, int $limit = 10, int $offset = 0, string $order = 'DESC'): array
    {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $sql = "SELECT f.id_factura, f.fecha, f.total,
                   mp.nombre AS metodo_pago,
                   p.fecha_creacion AS fecha_pedido
            FROM Factura f
            LEFT JOIN Metodo_Pago mp ON mp.id_metodo_pago = f.id_metodo_pago
            LEFT JOIN Pedido p ON p.id_pedido = f.id_pedido
            WHERE f.id_cliente = ?
            ORDER BY f.fecha $order
            LIMIT ? OFFSET ?";
        $st = $this->db->prepare($sql);
        $st->bind_param('iii', $id_cliente, $limit, $offset);
        $st->execute();
        $rs = $st->get_result();

        $rows = [];
        while ($r = $rs->fetch_assoc()) {
            $r['id_factura'] = (int)$r['id_factura'];
            $r['total']      = (float)$r['total'];
            $rows[] = $r;
        }
        return $rows;
    }

    /** Total de facturas del cliente (para paginación) */
    public function contarFacturasPorCliente(int $id_cliente): int
    {
        $st = $this->db->prepare("SELECT COUNT(*) c FROM Factura WHERE id_cliente=?");
        $st->bind_param('i', $id_cliente);
        $st->execute();
        return (int)$st->get_result()->fetch_column();
    }

    /** Detalles de una factura (líneas) */
    public function obtenerDetallesFactura(int $id_factura): array
    {
        $st = $this->db->prepare("SELECT id_producto, nombre_producto, cantidad, precio_unitario,
                                     (cantidad*precio_unitario) AS subtotal
                              FROM Detalle_Factura
                              WHERE id_factura=?");
        $st->bind_param('i', $id_factura);
        $st->execute();
        $rs = $st->get_result();

        $items = [];
        while ($r = $rs->fetch_assoc()) {
            $r['cantidad']        = (int)$r['cantidad'];
            $r['precio_unitario'] = (float)$r['precio_unitario'];
            $r['subtotal']        = (float)$r['subtotal'];
            $items[] = $r;
        }
        return $items;
    }

    public function listarTodas(array $filtros = [], int $limit = 20, int $offset = 0, string $order = 'DESC'): array
    {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $conds = [];
        $types = '';
        $vals  = [];

        if (!empty($filtros['id_factura'])) {
            $conds[] = 'f.id_factura = ?';
            $types .= 'i';
            $vals[]  = (int)$filtros['id_factura'];
        }
        if (!empty($filtros['desde'])) {
            $conds[] = 'f.fecha >= ?';
            $types .= 's';
            $vals[]  = $filtros['desde'] . ' 00:00:00';
        }
        if (!empty($filtros['hasta'])) {
            $conds[] = 'f.fecha <= ?';
            $types .= 's';
            $vals[]  = $filtros['hasta'] . ' 23:59:59';
        }

        $where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $sql = "SELECT
                f.id_factura, f.fecha, f.total, f.id_pedido,
                mp.nombre AS metodo_pago,
                c.nombre  AS cliente_nombre,
                u.correo  AS correo_cliente
            FROM Factura f
            LEFT JOIN Metodo_Pago mp ON mp.id_metodo_pago = f.id_metodo_pago
            LEFT JOIN Cliente c      ON c.id_cliente      = f.id_cliente
            LEFT JOIN Usuario u      ON u.id_usuario      = c.id_usuario
            $where
            ORDER BY f.fecha $order
            LIMIT ? OFFSET ?";

        $types .= 'ii';
        $vals[]  = $limit;
        $vals[]  = $offset;

        $st = $this->db->prepare($sql);
        $st->bind_param($types, ...$vals);
        $st->execute();
        $rs = $st->get_result();

        $rows = [];
        while ($r = $rs->fetch_assoc()) {
            $r['id_factura'] = (int)$r['id_factura'];
            $r['id_pedido']  = (int)$r['id_pedido'];
            $r['total']      = (float)$r['total'];
            $rows[] = $r;
        }
        return $rows;
    }

    /** Cuenta total para paginar con los mismos filtros */
    public function contarTodas(array $filtros = []): int
    {
        $conds = [];
        $types = '';
        $vals  = [];

        if (!empty($filtros['id_factura'])) {
            $conds[] = 'f.id_factura = ?';
            $types .= 'i';
            $vals[]  = (int)$filtros['id_factura'];
        }
        if (!empty($filtros['desde'])) {
            $conds[] = 'f.fecha >= ?';
            $types .= 's';
            $vals[]  = $filtros['desde'] . ' 00:00:00';
        }
        if (!empty($filtros['hasta'])) {
            $conds[] = 'f.fecha <= ?';
            $types .= 's';
            $vals[]  = $filtros['hasta'] . ' 23:59:59';
        }

        $where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $sql = "SELECT COUNT(*) c
            FROM Factura f
            LEFT JOIN Cliente c ON c.id_cliente = f.id_cliente
            $where";

        $st = $this->db->prepare($sql);
        if ($types) $st->bind_param($types, ...$vals);
        $st->execute();
        return (int)$st->get_result()->fetch_column();
    }
}
