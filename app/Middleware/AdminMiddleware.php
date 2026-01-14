<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AdminMiddleware
{
    private $basePath;
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }
    public function __invoke(Request $request, RequestHandler $handler)
    {
        if (!isset($_SESSION['user_id'])) {
            $response = new SlimResponse();
            return $response->withHeader('Location', $this->basePath . '/login')->withStatus(302);
        }
    
        if ($_SESSION['level'] < 10) {
            $_SESSION['flash_message'] = '관리자만 접근할 수 있습니다.';
            $_SESSION['flash_type'] = 'error';
            
            $response = new SlimResponse();
            return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
        }
    
        return $handler->handle($request);
    }
}