<?php
// models/ProductoModel.php

class ProductoModel {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    /* ====== CREATE ====== */
    public function crear(array $p): int {
        $sql = "INSERT INTO Producto (nombre, descripcion, precio, disponible, imagen, id_categoria)
                VALUES (?,?,?,?,?,?)";
        $stmt = $this->db->prepare($sql);

        $nombre       = trim($p['nombre'] ?? '');
        $descripcion  = trim($p['descripcion'] ?? '');
        $precio       = (float)($p['precio'] ?? 0);
        $disponible   = isset($p['disponible']) ? (int)(bool)$p['disponible'] : 1;
        $imagen       = trim($p['imagen'] ?? '');
        $id_categoria = (int)($p['id_categoria'] ?? 0);

        $stmt->bind_param('ssdisi', $nombre, $descripcion, $precio, $disponible, $imagen, $id_categoria);
        $stmt->execute();

        return $this->db->insert_id;
    }

    /* ====== READ (uno) ====== */
    public function obtenerPorId(int $id_producto): ?array {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre
                FROM Producto p
                LEFT JOIN Categoria_Producto c ON c.id_categoria = p.id_categoria
                WHERE p.id_producto = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id_producto);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return $row ?: null;
    }

    /* ====== UPDATE ====== */
    public function actualizar(int $id_producto, array $p): bool {
        $sql = "UPDATE Producto
                   SET nombre=?, descripcion=?, precio=?, disponible=?, imagen=?, id_categoria=?
                 WHERE id_producto=?";
        $stmt = $this->db->prepare($sql);

        $nombre       = trim($p['nombre'] ?? '');
        $descripcion  = trim($p['descripcion'] ?? '');
        $precio       = (float)($p['precio'] ?? 0);
        $disponible   = isset($p['disponible']) ? (int)(bool)$p['disponible'] : 1;
        $imagen       = trim($p['imagen'] ?? '');
        $id_categoria = (int)($p['id_categoria'] ?? 0);

        $stmt->bind_param('ssdisii', $nombre, $descripcion, $precio, $disponible, $imagen, $id_categoria, $id_producto);
        return $stmt->execute();
    }

    /* ====== DELETE (soft) ====== */
    public function eliminar(int $id_producto): bool {
        $stmt = $this->db->prepare("UPDATE Producto SET disponible = 0 WHERE id_producto = ?");
        $stmt->bind_param('i', $id_producto);
        return $stmt->execute();
    }

    /* Opcional: restaurar disponibilidad */
    public function restaurar(int $id_producto): bool {
        $stmt = $this->db->prepare("UPDATE Producto SET disponible = 1 WHERE id_producto = ?");
        $stmt->bind_param('i', $id_producto);
        return $stmt->execute();
    }

    /* ====== LISTAR + FILTROS + PAGINACIÓN ====== */
    public function listar(array $filtros = [], int $page = 1, int $perPage = 20, string $orderBy = 'p.id_producto DESC'): array {
        $page    = max(1, (int)$page);
        $perPage = max(1, min(200, (int)$perPage));
        $offset  = ($page - 1) * $perPage;

        $sql = "SELECT p.*, c.nombre AS categoria_nombre
                FROM Producto p
                LEFT JOIN Categoria_Producto c ON c.id_categoria = p.id_categoria";

        $conds  = [];
        $params = [];
        $types  = '';

        // Filtro por nombre (LIKE)
        if (!empty($filtros['nombre'])) {
            $conds[]  = "p.nombre LIKE ?";
            $params[] = '%' . $filtros['nombre'] . '%';
            $types   .= 's';
        }

        // Filtro por id_categoria
        if (!empty($filtros['id_categoria'])) {
            $conds[]  = "p.id_categoria = ?";
            $params[] = (int)$filtros['id_categoria'];
            $types   .= 'i';
        }

        // Filtro por disponible (0/1). Si quieres ver todos, pasa null y no se filtra.
        if (array_key_exists('disponible', $filtros) && $filtros['disponible'] !== null) {
            $conds[]  = "p.disponible = ?";
            $params[] = (int)$filtros['disponible'];
            $types   .= 'i';
        }

        if ($conds) {
            $sql .= " WHERE " . implode(" AND ", $conds);
        }

        // Sanitizar orderBy a columnas permitidas
        $orderBySafe = preg_match('/^\s*p\.(id_producto|nombre|precio|disponible)\s+(ASC|DESC)\s*$/i', $orderBy)
            ? $orderBy
            : 'p.id_producto DESC';

        // LIMIT/OFFSET integrados como enteros sanitizados para evitar problemas con placeholders en algunas versiones
        $sql .= " ORDER BY {$orderBySafe} LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    /* Para paginar: total coincidente con filtros */
    public function contar(array $filtros = []): int {
        $sql = "SELECT COUNT(*) AS total FROM Producto p";
        $conds  = [];
        $params = [];
        $types  = '';

        if (!empty($filtros['nombre'])) {
            $conds[]  = "p.nombre LIKE ?";
            $params[] = '%' . $filtros['nombre'] . '%';
            $types   .= 's';
        }
        if (!empty($filtros['id_categoria'])) {
            $conds[]  = "p.id_categoria = ?";
            $params[] = (int)$filtros['id_categoria'];
            $types   .= 'i';
        }
        if (array_key_exists('disponible', $filtros) && $filtros['disponible'] !== null) {
            $conds[]  = "p.disponible = ?";
            $params[] = (int)$filtros['disponible'];
            $types   .= 'i';
        }

        if ($conds) {
            $sql .= " WHERE " . implode(" AND ", $conds);
        }

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int)$row['total'];
    }

    /* Utilidad: listar categorías (por si quieres poblar selects) */
    public function categorias(): array {
        $res = $this->db->query("SELECT id_categoria, nombre FROM Categoria_Producto ORDER BY nombre");
        return $res->fetch_all(MYSQLI_ASSOC);
    }
}
