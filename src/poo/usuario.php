<?php

namespace Carbone\Agustin;

use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;

class Usuario
{
    public $id;
    public $correo;
    public $clave;
    public $nombre;
    public $apellido;
    public $pathFoto;
    public $id_perfil;

    const DB = 'mysql:host=slim4_parciales;dbname=administracion_bd';
    const Tabla = 'usuarios';
    const Img = '../fotos/';
    //const FBorrados = './archivos/genericaBD_borrados.txt';
    const Modif = './genericoModificados/';

    public function __construct($id, $correo, $clave, $nombre, $apellido, $id_perfil, $foto = "")
    {
        $this->id = $id;
        $this->correo = $correo;
        $this->clave = $clave;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->pathFoto = $foto;
        $this->id_perfil = $id_perfil;
    }

    public static function Agregar($params): string
    {
        $usuarioOBJ = new Usuario(0, $params['correo'], $params['clave'], $params['nombre'], $params['apellido'], $params['id_perfil']);

        $query = "INSERT INTO " . self::Tabla . "(correo, clave, nombre, apellido, id_perfil, pathFoto) 
                VALUES (:correo, :clave, :nombre, :apellido, :id_perfil, :pathFoto)";

        try {
            $pdo = new PDO(self::DB, 'root', '');
            $sql = $pdo->prepare($query);
            $sql->bindParam(':correo', $usuarioOBJ->correo, PDO::PARAM_STR, 50);
            $sql->bindParam(':clave', $usuarioOBJ->clave, PDO::PARAM_STR, 50);
            $sql->bindParam(':nombre', $usuarioOBJ->nombre, PDO::PARAM_STR, 50);
            $sql->bindParam(':apellido', $usuarioOBJ->apellido, PDO::PARAM_STR, 50);
            $sql->bindParam(':id_perfil', $usuarioOBJ->id_perfil, PDO::PARAM_STR, 50);

            if ($usuarioOBJ->pathFoto != "") {
                $sql->bindParam(':pathFoto', $usuarioOBJ->pathFoto, PDO::PARAM_STR);

                if ($usuarioOBJ->upload()) {
                    if ($sql->execute()) {
                        $usuarioOBJ->cambiarIDFoto();
                        return json_encode(['exito' => true, 'mensaje' => 'Guardado con exito', 'status' => 200]);
                    } else {
                        unlink($usuarioOBJ->pathFoto);
                        return json_encode(['exito' => false, 'mensaje' => 'No se pudo guardar', 'status' => 418]);
                    }
                }
            } else {
                $sql->bindParam(':pathFoto', $usuarioOBJ->pathFoto, PDO::PARAM_NULL);

                if ($sql->execute()) {
                    return ['exito' => true, 'mensaje' => 'Guardado con exito'];
                } else {
                    return ['exito' => false, 'mensaje' => 'No se pudo guardar'];
                }
            }
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
                    $arrayObj[] = new Usuario($fila['var1'], $fila['var2'], $fila['var3'], $fila['var4'], $fila['var5'], $fila['pathFoto']);
                }

                return json_encode(['exito' => true, 'mensaje' => 'Listado de usuarios', 'dato' => $arrayObj, 'status' => 200]);
                 
            } else {
                return json_encode(['exito' => false, 'mensaje' => 'error', 'dato' => '', 'status' => 424]);
            }
        } catch (PDOException $e) {
            return json_encode(['exito' => false, 'mensaje' => $e->getMessage()]);
        }
    }

    public function crear(Request $request, Response $response, array $args) : Response {

        $arrayDeParametros = $request->getParsedBody();

        $token = Autentificadora::crearJWT($arrayDeParametros, 45);

        $newResponse = $response->withStatus(200);

        $newResponse->getBody()->write(json_encode($token));
    
        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    public function MoverFoto(): bool
    {
        if ($this->pathFoto != "") {
            $pathDestino = self::Modif . basename($this->pathFoto);
            if (file_exists($this->pathFoto)) {
                if (rename($this->pathFoto, $pathDestino)) {
                    $this->pathFoto = $pathDestino;
                    return true;
                }
            }
            return false;
        }
    }

    private function upload(): bool
    {
        $nombreFoto = $this->pathFoto;

        $destino = "./fotos/" . $this->id . "_" . $this->apellido . "." . pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);

        $uploadOk = TRUE;

        $tipoArchivo = pathinfo($destino, PATHINFO_EXTENSION);

        if (file_exists($nombreFoto)) {
            echo "El archivo ya existe. Verifique!!!";
            $uploadOk = FALSE;
        }

        $esImagen = getimagesize($_FILES["foto"]["tmp_name"]);

        if ($esImagen === FALSE) {

            if ($tipoArchivo != "doc" && $tipoArchivo != "txt" && $tipoArchivo != "rar") {
                echo "Solo son permitidos archivos con extension DOC, TXT o RAR.";
                $uploadOk = FALSE;
            }
        } else {

            if (
                $tipoArchivo != "jpg" && $tipoArchivo != "jpeg" && $tipoArchivo != "gif"
                && $tipoArchivo != "png"
            ) {
                echo "Solo son permitidas imagenes con extension JPG, JPEG, PNG o GIF.";
                $uploadOk = FALSE;
            }
        }

        if ($uploadOk === FALSE) {

            echo "<br/>NO SE PUDO SUBIR EL ARCHIVO.";
        } else {

            if (!move_uploaded_file($_FILES["foto"]["tmp_name"], $this->pathFoto)) {
                echo "<br/>Lamentablemente ocurrió un error y no se pudo subir el archivo.";
            }
        }

        return $uploadOk;
    }

    private function cambiarIDFoto(): void
    {
        try {

            $query = "SELECT id FROM " . self::Tabla . " ORDER BY id DESC LIMIT 1";
            $pdo = new PDO(self::DB, 'root', '');
            $sql = $pdo->prepare($query);
            $sql->execute();
            $id = $sql->fetch(PDO::FETCH_ASSOC);
            $this->id = $id['id'];

            $nuevoNombreFoto = $this->id . "_" . $this->apellido . "." . pathinfo($this->pathFoto, PATHINFO_EXTENSION);
            $nuevoPathFoto = self::Img . $nuevoNombreFoto;
            if (rename($this->pathFoto, $nuevoPathFoto)) {
                $this->pathFoto = $nuevoPathFoto;
            } else {
                echo "<br/>Error al renombrar la foto.";
            }

            $this->pathFoto = self::Img . $this->id . "_" . $this->apellido . "." . pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);

            $query = "UPDATE " . self::Tabla . " SET foto = :nuevoPathFoto WHERE id = :id";
            $sql = $pdo->prepare($query);
            $sql->execute(array(':nuevoPathFoto' => $this->pathFoto, ':id' => $this->id));
        } catch (PDOException $e) {
            echo "<br/>Error: " . $e->getMessage();
        }
    }
}
