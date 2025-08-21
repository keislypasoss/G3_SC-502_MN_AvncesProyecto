<?php

class UsuarioModel
{
    private $db;

    public function __construct(mysqli $mysqli)
    {
        $this->db = $mysqli;
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT
                u.id_usuario, u.correo, u.activo, u.fecha_registro, u.rol,
                c1.id_cliente, c1.nombre AS nombre_cliente, c1.telefono, c1.direccion,
                e1.id_empleado, e1.nombre AS nombre_empleado, e1.puesto
            FROM Usuario u
            LEFT JOIN (
                SELECT c.* FROM Cliente c
                JOIN (
                    SELECT id_usuario, MAX(id_cliente) AS last_id
                    FROM Cliente GROUP BY id_usuario
                ) lc ON lc.id_usuario = c.id_usuario AND lc.last_id = c.id_cliente
            ) c1 ON c1.id_usuario = u.id_usuario
            LEFT JOIN (
                SELECT e.* FROM Empleado e
                JOIN (
                    SELECT id_usuario, MAX(id_empleado) AS last_id
                    FROM Empleado GROUP BY id_usuario
                ) le ON le.id_usuario = e.id_usuario AND le.last_id = e.id_empleado
            ) e1 ON e1.id_usuario = u.id_usuario
            WHERE u.id_usuario = ?
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) return null;

        $tipo = $this->inferTipo($row['rol'] ?? null, $row['id_cliente'] ?? null, $row['id_empleado'] ?? null);
        return $this->buildUsuarioFromRow($row, $tipo);
    }


    public function listAll(int $limit = 50, int $offset = 0, string $orderBy = 'u.fecha_registro DESC'): array
    {
        $validOrders = ['u.fecha_registro', 'u.correo', 'u.id_usuario'];
        $orderByCol  = explode(' ', $orderBy)[0];
        if (!in_array($orderByCol, $validOrders, true)) {
            $orderBy = 'u.fecha_registro DESC';
        }

        $sql = "SELECT
                u.id_usuario, u.correo, u.activo, u.fecha_registro, u.rol,
                COALESCE(c1.nombre, e1.nombre) AS nombre,
                CASE
                    WHEN c1.id_cliente IS NOT NULL THEN 'cliente'
                    WHEN e1.id_empleado IS NOT NULL THEN 'empleado'
                    ELSE NULL
                END AS tipo
            FROM Usuario u
            LEFT JOIN (
                SELECT c.* FROM Cliente c
                JOIN (
                    SELECT id_usuario, MAX(id_cliente) AS last_id
                    FROM Cliente GROUP BY id_usuario
                ) lc ON lc.id_usuario = c.id_usuario AND lc.last_id = c.id_cliente
            ) c1 ON c1.id_usuario = u.id_usuario
            LEFT JOIN (
                SELECT e.* FROM Empleado e
                JOIN (
                    SELECT id_usuario, MAX(id_empleado) AS last_id
                    FROM Empleado GROUP BY id_usuario
                ) le ON le.id_usuario = e.id_usuario AND le.last_id = e.id_empleado
            ) e1 ON e1.id_usuario = u.id_usuario
            ORDER BY $orderBy
            LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();
        return $rows;
    }

    public function searchByNombre(string $q, int $limit = 50, int $offset = 0): array
    {
        $like = '%' . $q . '%';
        $sql = "SELECT
                    u.id_usuario, u.correo, u.activo, u.fecha_registro, u.rol,
                    COALESCE(c.nombre, e.nombre) AS nombre,
                    CASE
                        WHEN c.id_cliente IS NOT NULL THEN 'cliente'
                        WHEN e.id_empleado IS NOT NULL THEN 'empleado'
                        ELSE NULL
                    END AS tipo
                FROM Usuario u
                LEFT JOIN Cliente c  ON c.id_usuario  = u.id_usuario
                LEFT JOIN Empleado e ON e.id_usuario  = u.id_usuario
                WHERE (c.nombre LIKE ? OR e.nombre LIKE ?)
                ORDER BY u.fecha_registro DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssii", $like, $like, $limit, $offset);
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();
        return $rows;
    }


    public function update(int $id, array $data): bool
    {
        $this->db->begin_transaction();
        try {
            $sets = [];
            $types = '';
            $vals  = [];

            if (isset($data['correo'])) {
                if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException("Correo inválido");
                }
                $sets[] = "correo = ?";
                $types .= "s";
                $vals[]  = $data['correo'];
            }

            if (array_key_exists('activo', $data)) {
                $sets[] = "activo = ?";
                $types .= "i";
                $vals[]  = (int)$data['activo'];
            }

            if (isset($data['rol'])) {
                $sets[] = "rol = ?";
                $types .= "s";
                $vals[]  = (string)$data['rol'];
            }

            if (!empty($data['password'])) {
                $hash = password_hash((string)$data['password'], PASSWORD_DEFAULT);
                $sets[] = "contrasena = ?";
                $types .= "s";
                $vals[]  = $hash;
            }

            if (!empty($sets)) {
                $sqlU = "UPDATE Usuario SET " . implode(", ", $sets) . " WHERE id_usuario = ?";
                $types .= "i";
                $vals[]  = $id;

                $stmtU = $this->db->prepare($sqlU);
                $stmtU->bind_param($types, ...$vals);
                $stmtU->execute();
                $stmtU->close();
            }

            if (isset($data['tipo'])) {
                $tipo = $data['tipo'];

                if ($tipo === 'cliente') {
                    $c = $data['cliente'] ?? [];
                    $nombre    = trim($c['nombre']    ?? '');
                    $telefono  = trim($c['telefono']  ?? '');
                    $direccion = trim($c['direccion'] ?? '');

                    $sqlC = "INSERT INTO Cliente (id_usuario, nombre, telefono, direccion)
                             VALUES (?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE
                               nombre = VALUES(nombre),
                               telefono = VALUES(telefono),
                               direccion = VALUES(direccion)";
                    $stmtC = $this->db->prepare($sqlC);
                    $stmtC->bind_param("isss", $id, $nombre, $telefono, $direccion);
                    $stmtC->execute();
                    $stmtC->close();

                    $stmtDelE = $this->db->prepare("DELETE FROM Empleado WHERE id_usuario = ?");
                    $stmtDelE->bind_param("i", $id);
                    $stmtDelE->execute();
                    $stmtDelE->close();
                } elseif ($tipo === 'empleado') {
                    $e = $data['empleado'] ?? [];
                    $nombre = trim($e['nombre'] ?? '');
                    $puesto = trim($e['puesto'] ?? '');

                    $sqlE = "INSERT INTO Empleado (id_usuario, nombre, puesto)
                             VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE
                               nombre = VALUES(nombre),
                               puesto = VALUES(puesto)";
                    $stmtE = $this->db->prepare($sqlE);
                    $stmtE->bind_param("iss", $id, $nombre, $puesto);
                    $stmtE->execute();
                    $stmtE->close();

                    $stmtDelC = $this->db->prepare("DELETE FROM Cliente WHERE id_usuario = ?");
                    $stmtDelC->bind_param("i", $id);
                    $stmtDelC->execute();
                    $stmtDelC->close();
                } else {
                }
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    private function inferTipo(?string $rol, $idCliente, $idEmpleado): ?string
    {
        $rol = $rol ? strtolower($rol) : null;
        if ($rol === 'cliente')  return 'cliente';
        if ($rol === 'empleado') return 'empleado';
        if (!empty($idCliente))  return 'cliente';
        if (!empty($idEmpleado)) return 'empleado';
        return null;
    }

    private function buildUsuarioFromRow(array $row, ?string $tipo): array
    {
        $base = [
            'id_usuario'    => (int)$row['id_usuario'],
            'correo'        => (string)$row['correo'],
            'activo'        => (int)$row['activo'],
            'fecha_registro' => (string)$row['fecha_registro'],
            'rol'           => (string)$row['rol'],
            'tipo'          => $tipo,
        ];

        if ($tipo === 'cliente') {
            $base['perfil'] = [
                'id_cliente' => $row['id_cliente'] ? (int)$row['id_cliente'] : null,
                'nombre'     => $row['nombre_cliente'] ?? null,
                'telefono'   => $row['telefono'] ?? null,
                'direccion'  => $row['direccion'] ?? null,
            ];
        } elseif ($tipo === 'empleado') {
            $base['perfil'] = [
                'id_empleado' => $row['id_empleado'] ? (int)$row['id_empleado'] : null,
                'nombre'      => $row['nombre_empleado'] ?? null,
                'puesto'      => $row['puesto'] ?? null,
            ];
        } else {
            $base['perfil'] = null;
        }

        return $base;
    }
}
