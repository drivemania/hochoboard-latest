<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PluginDispatcherController {

    private static $blackList = [
        'Illuminate\Database',
        'DB::',
        'exec(',
        'system(',
        'shell_exec',
        'eval(',
        'DROP TABLE',
        'DELETE FROM users',
    ];

    public function dispatch(Request $request, Response $response, $args) {
        $directory = $args['directory'];
        $action = $args['action'];

        $pluginPath = __DIR__ . '/../../public/plugins/' . ucfirst($directory) . '/ApiController.php';
        
        if (file_exists($pluginPath)) {
            $content = file_get_contents($pluginPath);
            
            foreach (self::$blackList as $keyword) {
                if (stripos($content, $keyword) !== false) {
                    $response->getBody()->write(json_encode([
                        'error' => 'Security Violation',
                        'message' => "플러그인에서 허용되지 않은 코드('$keyword')가 발견되어 실행이 차단되었습니다."
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
                }
            }
        } else {
             $response->getBody()->write(json_encode(['error' => 'Plugin file not found']));
             return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

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