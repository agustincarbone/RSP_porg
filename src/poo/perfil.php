<?php
namespace Carbone\Agustin;

use PDO;
use PDOException;


class Perfil
{

    const DB = 'mysql:host=slim4_parciales;dbname=administracion_bd';
    const Tabla = 'perfiles';
    private $id;
    private $descripcion;
    private $estado;

    public function __construct($id, $descripcion, $estado)
    {
        $this->id = $id;
        $this->descripcion = $descripcion;
        $this->estado = $estado;
    }

    public static function Modificar($params)
    {
        $retorno = false;
        $objPerfil = new Perfil(
            $params['id'] ?? '',
            $params['descripcion'] ?? '',
            $params['estado'] ?? ''
        );

        try {
            $sql = "UPDATE " . self::Tabla . " SET descripcion = :descripcion, estado = :estado WHERE id = :id";

            $pdo = new PDO(self::DB, 'root', '');
            $sql = $pdo->prepare($sql);
            $sql->bindParam(':id', $objPerfil->id, PDO::PARAM_STR, 50);
            $sql->bindParam(':descripcion', $objPerfil->descripcion, PDO::PARAM_STR, 50);
            $sql->bindParam(':estado', $objPerfil->estado, PDO::PARAM_STR, 50);

            if ($sql->execute()) {
                $retorno = true;
            }

            if ($sql->rowCount() > 0) {
                $retorno = true;
            }

            return ['exito' => $retorno, 'mensaje' => $retorno ? 'Modificado con exito' : 'No se pudo modificar'];

        } catch (PDOException $e) {
            return ['exito' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function TraerTodos() : string
    {
        try {
            $pdo = new PDO(self::DB, 'root', '');
            $sql = $pdo->prepare("SELECT * FROM " . self::Tabla);

            if ($sql->execute()) {
                $res = $sql->fetchAll();
                $arrayObj = [];

                foreach ($res as $fila) {
                    $arrayObj[] = new Perfil($fila['id'], $fila['descripcion'], $fila['estado']);
                }

                return json_encode(['exito' => true, 'mensaje' => 'Listado de perfiles', 'dato' => $arrayObj, 'status' => 200]);
                 
            } else {
                return json_encode(['exito' => false, 'mensaje' => 'error', 'dato' => '', 'status' => 424]);
            }
        } catch (PDOException $e) {
            return json_encode(['exito' => false, 'mensaje' => $e->getMessage()]);
        }
    }
}