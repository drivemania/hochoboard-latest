<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

class AutoLoginMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (isset($_SESSION['user_id'])) {
            return $handler->handle($request);
        }

        if (isset($_COOKIE['AUTOLOGIN'])) {
            list($keyId, $token) = explode('|', $_COOKIE['AUTOLOGIN']);

            // 3. DB에서 식별자($keyId)로 조회
            $authData = DB::table('user_autologin')
                          ->where('key_id', $keyId)
                          ->where('expires_at', '>', date('Y-m-d H:i:s')) // 만료 안 된 것만
                          ->first();

            if ($authData) {
                if (password_verify($token, $authData->token)) {
                    $user = DB::table('users')->find($authData->user_id);
                    $_SESSION['user_idx'] = $user->id;
                    $_SESSION['user_id']  = $user->user_id;
                    $_SESSION['nickname'] = $user->nickname;
                    $_SESSION['level']    = $user->level;
                }
            }
        }

        return $handler->handle($request);
    }
}