<?php

use App\Tablas\Modelo;

class Categoria extends Modelo
{
    protected static string $tabla = 'categorias';

    public $id;
    public $categoria;

    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->categoria = $campos['categoria'];
    }

    public static function obtener(int $id, ?PDO $pdo = null): ?self
    {
        $sent = $pdo->prepare("SELECT * FROM categorias WHERE id = :id");
        $sent->execute(['id' => $id]);
        $registro = $sent->fetch();
        return $registro ? new self($registro) : null;
    }
}
