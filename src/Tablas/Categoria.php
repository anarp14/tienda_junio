<?php
namespace App\Tablas;

use PDO;

class Categoria extends Modelo
{
    protected static string $tabla = 'categorias';

    public $id;
    public $nombre;

    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->nombre = $campos['nombre'];
    }
}
