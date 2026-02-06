<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use Illuminate\Database\Capsule\Manager as DB;

class MemoCheckMiddleware
{
    private $basePath;
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function __invoke(Request $request, RequestHandler $handler)
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        if (!$route) {
            return $handler->handle($request);
        }

        $group = DB::table('groups')
        ->where('is_deleted', 0)
        ->where('is_default', 1)
        ->select('is_memo_use')
        ->first();

        if ($group && $group->is_memo_use === 0) {
            $html = "
                <!DOCTYPE html>
                <html>
                <head><meta charset='utf-8'></head>
                <body>
                    <script>
                        alert('쪽지 사용이 불가능한 커뮤니티입니다.');
                        
                        window.close();
                        setTimeout(function() {
                            if (!window.closed) {
                                window.location.href = '" . $this->basePath . "/';
                            }
                        }, 100);
                    </script>
                </body>
                </html>
            ";

            $response = new Response();
            $response->getBody()->write($html);
            return $response->withHeader('Content-Type', 'text/html');
        }
        
        return $handler->handle($request);
    }
}