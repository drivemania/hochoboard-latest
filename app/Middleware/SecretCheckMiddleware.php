<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use Illuminate\Database\Capsule\Manager as DB;

class SecretCheckMiddleware
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

        $slug = $route->getArgument('menu_slug');

        if ($slug) {
            $group = DB::table('groups')
                ->where('slug', $slug)
                ->where('is_deleted', 0)
                ->select('is_secret')
                ->first();
        }else{
            $group = DB::table('groups')
            ->where('is_deleted', 0)
            ->select('is_secret')
            ->first();
        }

        if ($group && $group->is_secret > 1) {
            if (empty($_SESSION['user_id'])) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['flash_message'] = '비공개 커뮤니티입니다. 로그인해주세요.';
                $_SESSION['flash_type'] = 'error';

                $response = new Response();
                return $response
                    ->withHeader('Location', $this->basePath . '/login')
                    ->withStatus(302);
            }
        }
        
        return $handler->handle($request);
    }
}