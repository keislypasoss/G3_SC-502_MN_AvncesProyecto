<?php

class CarritoModel {
    private const KEY = 'carrito';

    public function __construct() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION[self::KEY]) || !is_array($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = []; 
        }
    }

    private function &store(): array {
        return $_SESSION[self::KEY];
    }

    public function add(int $id, string $nombre, float $precio, int $cantidad = 1, string $imagen = ''): void {
        $cantidad = max(1, (int)$cantidad);
        $s = &$this->store();
        if (isset($s[$id])) {
            $s[$id]['cantidad'] += $cantidad;
        } else {
            $s[$id] = [
                'id'       => $id,
                'nombre'   => $nombre,
                'precio'   => $precio,
                'cantidad' => $cantidad,
                'imagen'   => $imagen
            ];
        }
    }

    public function setQuantity(int $id, int $cantidad): void {
        $s = &$this->store();
        if (!isset($s[$id])) return;
        if ($cantidad <= 0) { unset($s[$id]); return; }
        $s[$id]['cantidad'] = (int)$cantidad;
    }

    public function increment(int $id, int $delta = 1): void {
        $s = &$this->store();
        if (!isset($s[$id])) return;
        $this->setQuantity($id, $s[$id]['cantidad'] + $delta);
    }

    public function remove(int $id): void {
        $s = &$this->store();
        unset($s[$id]);
    }

    public function clear(): void {
        $_SESSION[self::KEY] = [];
    }

    public function items(): array {
        $out = [];
        foreach ($this->store() as $it) {
            $it['subtotal'] = (float)$it['precio'] * (int)$it['cantidad'];
            $out[] = $it;
        }
        return $out;
    }

    public function total(): float {
        $t = 0.0;
        foreach ($this->store() as $it) {
            $t += (float)$it['precio'] * (int)$it['cantidad'];
        }
        return $t;
    }

    public function count(): int {
        $c = 0;
        foreach ($this->store() as $it) {
            $c += (int)$it['cantidad'];
        }
        return $c;
    }
}
