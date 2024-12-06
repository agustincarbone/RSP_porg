<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

use Carbone\Agustin\Usuario;
use Carbone\Agustin\Perfil;

require_once '../src/poo/usuario.php';
require_once '../src/poo/perfil.php';
require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();


$app->post('/usuario', function (Request $request, Response $response, array $args) {

    $datos = $request->getParsedBody();

    $resultado = Usuario::Agregar($datos);

    $response->withStatus($resultado["status"],$resultado["mensaje"]);

    $response->getBody()->write($resultado);

    return $response;
});

$app->get('/', function (Request $request, Response $response, array $args): Response {

    //$resultado = \Carbone\Agustin\Usuario::TraerTodos();

    $resultado = "API => Bienvenido!!! a SlimFramework 4";

    //$response->withStatus($resultado["status"],$resultado["mensaje"]);

    $response->getBody()->write($resultado);

    return $response;
});

/* $app->post('/', function (Request $request, Response $response, array $args) {

    $datos = $request->getParsedBody();

    $resultado = Perfil::Agregar($datos);

    $response->getBody()->write(json_encode($resultado));

    return $response;
});

$app->get('/perfil', function (Request $request, Response $response, array $args) : Response {

    $resultado = Perfil::TraerTodos();

    $response->withStatus($resultado["status"],$resultado["mensaje"]);

    $response->getBody()->write($resultado["dato"]);

    return $response;
});

$app->post('/login', function (Request $request, Response $response, array $args) {

    $datos = $request->getParsedBody();

    $resultado = Usuario::Crear($datos);

    $response->getBody()->write(json_encode($resultado));

    return $response;
}); */

$app->run();
