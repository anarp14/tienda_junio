<?php

namespace App\Tablas;

use PDO;

class Articulo extends Modelo
{
    protected static string $tabla = 'articulos';

    public $id;
    private $codigo;
    private $descripcion;
    private $precio;
    private $stock;

    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->codigo = $campos['codigo'];
        $this->descripcion = $campos['descripcion'];
        $this->precio = $campos['precio'];
        $this->stock = $campos['stock'];
    }

    public static function existe(int $id, ?PDO $pdo = null): bool
    {
        return static::obtener($id, $pdo) !== null;
    }

    public function getCodigo()
    {
        return $this->codigo;
    }

    public function getDescripcion()
    {
        return $this->descripcion;
    }

    public function getPrecio()
    {
        return $this->precio;
    }

    public function getStock()
    {
        return $this->stock;
    }

    public function getCategoria(?PDO $pdo = null)
    {
        $pdo = $pdo ?? conectar();
        $sent = $pdo->prepare("
            SELECT c.nombre
            FROM articulos_categorias ac
            JOIN categorias c ON ac.categoria_id = c.id
            WHERE ac.articulo_id = :articulo_id
        ");
    
        $sent->execute(['articulo_id' => $this->id]);
        $categorias = $sent->fetchAll(PDO::FETCH_COLUMN);
        return implode(', ', $categorias);
    }

}
