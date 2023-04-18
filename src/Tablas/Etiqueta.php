<?php

namespace App\Tablas;

use App\Tablas\Modelo;

use PDO;

class Etiqueta extends Modelo
{
    protected static string $tabla = 'etiquetas';

    public $id;
    public $etiqueta;

    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->etiqueta = $campos['etiqueta'];
    }

    public static function consultaId(string $param, ?PDO $pdo = null)
    {
        $sent = $pdo->prepare('SELECT id 
                                FROM  etiquetas 
                                WHERE unaccent(LOWER(e.etiqueta)) LIKE unaccent(LOWER(:etiqueta))');
        $sent->execute([':etiqueta' => '%' . $param . '%']);
        $idEtiqueta = $sent->fetch();
        return $idEtiqueta ?: null;
    }

    public static function consultaEtiqueta(string $id, ?PDO $pdo = null)
    {
        $sent = $pdo->prepare('SELECT etiqueta FROM  etiquetas WHERE id = :id');
        $sent->execute([':etiqueta' => $id]);
        $etiqueta = $sent->fetch();
        return $etiqueta ?: null;
    }
}
