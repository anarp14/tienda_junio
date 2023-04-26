<?php

namespace App\Tablas;

use PDO;
use App\Tablas\Etiqueta;

class Articulo extends Modelo
{
    protected static string $tabla = 'articulos';

    private $id;
    private $codigo;
    private $descripcion;
    private $precio;
    private $stock;
    private $categoria_id;
    private Etiqueta $etiqueta;

    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->codigo = $campos['codigo'];
        $this->descripcion = $campos['descripcion'];
        $this->precio = $campos['precio'];
        $this->stock = $campos['stock'];
        $this->categoria_id = $campos['categoria_id'];
        $this->etiqueta = Etiqueta::obtener($campos['id']);
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

    public function getId()
    {
        return $this->id;
    }

    public function getCategoriaNombre(PDO $pdo)
    {
        $sent = $pdo->prepare("SELECT categoria FROM categorias WHERE id = :categoria_id");
        $sent->execute(['categoria_id' => $this->categoria_id]);
        return $sent->fetchColumn();
    }

    public function getEtiquetaNombre(?PDO $pdo = null)
    {
        $pdo = $pdo ?? conectar();
        $sent = $pdo->prepare("SELECT e.etiqueta 
                                FROM etiquetas e JOIN articulos_etiquetas ae ON (e.id = ae.etiqueta_id)
                                WHERE ae.articulo_id = :articulo_id");
        $sent->execute(['articulo_id' => $this->id]);
        $etiquetas = $sent->fetchAll(PDO::FETCH_COLUMN);
        return implode(', ', $etiquetas);
    }

}
