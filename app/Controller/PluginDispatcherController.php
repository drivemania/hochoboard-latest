<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PluginDispatcherController {
    public function dispatch(Request $request, Response $response, $args) {
        $directory = $args['directory'];
        $action = $args['action'];

        $className = "\\Plugins\\" . ucfirst($directory) . "\\ApiController";

        if (class_exists($className)) {
            $controller = new $className();
            if (method_exists($controller, $action)) {
                return $controller->$action($request, $response, $args);
            }
        }

        $response->getBody()->write(json_encode(['error' => 'API not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
}