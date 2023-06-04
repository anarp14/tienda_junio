<?php

namespace App\Tablas;

use PDO;

class Factura extends Modelo
{
    protected static string $tabla = 'facturas';

    public $id;
    public $created_at;
    public $usuario_id;
    public $metodo_pago;
    public $cupon_id;
    private $total;

    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->created_at = $campos['created_at'];
        $this->usuario_id = $campos['usuario_id'];
        $this->metodo_pago = $campos['metodo_pago'];
        $this->cupon_id = isset($campos['cupon_id']) ? $campos['cupon_id'] : null;
        $this->total = isset($campos['total']) ? $campos['total'] : null;
    }

    public static function existe(int $id, ?PDO $pdo = null): bool
    {
        return static::obtener($id, $pdo) !== null;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function getUsuarioId()
    {
        return $this->usuario_id;
    }

    public function getMetodo_pago()
    {
        return $this->metodo_pago;
    }

    public function getCupon_id()
    {
        return $this->cupon_id;
    }



    public function getTotal(?PDO $pdo = null)
    {
        $pdo = $pdo ?? conectar();

        if (!isset($this->total)) {
            $sent = $pdo->prepare('SELECT SUM(cantidad * precio) AS total
                                     FROM articulos_facturas l
                                     JOIN articulos a
                                       ON l.articulo_id = a.id
                                    WHERE factura_id = :id');
            $sent->execute([':id' => $this->id]);
            $this->total = $sent->fetchColumn();
        }

        return $this->total;
    }

    public static function todosConTotal(
        array $where = [],
        array $execute = [],
        ?PDO $pdo = null
    ): array {
        $pdo = $pdo ?? conectar();

        $where = !empty($where)
            ? 'WHERE ' . implode(' AND ', $where)
            : '';

        $sent = $pdo->prepare("SELECT f.*, SUM(CASE
        WHEN o.oferta IS NULL THEN a.precio * ae.cantidad
        WHEN o.oferta = '2x1' THEN (FLOOR(ae.cantidad / 2) + ae.cantidad % 2) * a.precio
        WHEN o.oferta = '50%' THEN (ae.cantidad * a.precio) / 2
        WHEN o.oferta = '2ª Unidad a mitad de precio' THEN (FLOOR(ae.cantidad / 2) * a.precio) + (ae.cantidad - FLOOR(ae.cantidad / 2)) * (a.precio / 2)
        ELSE a.precio * ae.cantidad
    END) AS total
    FROM facturas f
    JOIN articulos_facturas ae ON ae.factura_id = f.id
    JOIN articulos a ON a.id = ae.articulo_id
    LEFT JOIN ofertas o ON o.id = a.oferta_id
    $where
    GROUP BY f.id");

        $sent->execute($execute);
        $filas = $sent->fetchAll(PDO::FETCH_ASSOC);
        $res = [];

        foreach ($filas as $fila) {
            $fila['total'] = ($fila['total'] * 1.21);
            $res[] = new static($fila);
        }

        return $res;
    }


    public function getLineas(?PDO $pdo = null): array
    {
        $pdo = $pdo ?? conectar();

        $sent = $pdo->prepare('SELECT *
                                 FROM articulos_facturas
                                WHERE factura_id = :factura_id');
        $sent->execute([':factura_id' => $this->id]);
        $lineas = $sent->fetchAll(PDO::FETCH_ASSOC);
        $res = [];
        foreach ($lineas as $linea) {
            $res[] = new Linea($linea);
        }
        return $res;
    }

    public function getArticuloId()
    {
        // Recuperar el id del artículo asociado a esta factura
        $pdo = conectar();
        $stmt = $pdo->prepare('SELECT articulo_id FROM articulos_facturas WHERE factura_id = :factura_id');
        $stmt->execute([':factura_id' => $this->id]);
        return $stmt->fetchColumn();
    }

    // ...

    public function getArticulosComprados(): array
    {
        $pdo = conectar();

        $sent = $pdo->prepare('
        SELECT DISTINCT art.*, af.cantidad
        FROM articulos art
        JOIN articulos_facturas af ON (art.id = af.articulo_id)
        JOIN facturas f ON (f.id = af.factura_id)
        WHERE f.id = :factura_id
    ');

        $sent->execute([':factura_id' => $this->id]);
        $articulos = $sent->fetchAll(PDO::FETCH_ASSOC);

        return $articulos;
    }


    public function seHaComprado(int $articuloId): bool
    {
        $articulosComprados = $this->getArticulosComprados();

        foreach ($articulosComprados as $articuloComprado) {
            if ($articuloComprado['id'] === $articuloId) {
                return true; // El artículo se ha comprado
            }
        }

        return false; // El artículo no se ha comprado
    }

    public function getArticulos(): array
    {
        $pdo = conectar();

        $sent = $pdo->prepare('SELECT a.* FROM articulos a
                               JOIN articulos_facturas af ON af.articulo_id = a.id
                               WHERE af.factura_id = :factura_id');
        $sent->execute([':factura_id' => $this->id]);
        $filas = $sent->fetchAll(PDO::FETCH_ASSOC);

        $articulos = [];
        foreach ($filas as $fila) {
            $articulos[] = new Articulo($fila);
        }

        return $articulos;
    }
}
