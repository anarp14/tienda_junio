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
    private $oferta;


    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->codigo = $campos['codigo'];
        $this->descripcion = $campos['descripcion'];
        $this->precio = $campos['precio'];
        $this->stock = $campos['stock'];
        $this->categoria_id = $campos['categoria_id'];
        $this->oferta = null;
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


    public function getOferta(?PDO $pdo = null)
    {
        $pdo = $pdo ?? conectar();
        $sent = $pdo->prepare('SELECT ofertas.oferta FROM articulos 
                              LEFT JOIN ofertas 
                              ON articulos.oferta_id = ofertas.id
                              WHERE articulos.id = :id');
        $sent->execute([':id' => $this->id]);
        $this->oferta = $sent->fetchColumn();
        
        return $this->oferta;
    }
    


    public function aplicarOferta(string $oferta, int $cantidad, float $precioUnidad): array
    {
        $importe_original = $cantidad * $precioUnidad;
        $importe = 0;

        switch ($oferta) {
            case '2x1':
                $unidadesCompletas = floor($cantidad / 2);
                $unidadesIndividuales = $cantidad % 2;
                $importe = ($precioUnidad * $unidadesCompletas) + ($unidadesIndividuales * $precioUnidad);
                break;
            case '50%':
                $importe = ($importe_original) / 2;
                break;
            case '2ª Unidad a mitad de precio':
                for ($i = 1; $i <= $cantidad; $i++) {
                    if ($i % 2 !== 0) {
                        $importe += $precioUnidad;
                    } else {
                        $importe += $precioUnidad / 2;
                    }
                }
                break;
            default:
                $importe = $importe_original;
                break;
        }
        $ahorro = $importe_original - $importe;

        return ['importe' => $importe, 'ahorro' => $ahorro];
    }
}
