<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;

$app->get('/api/memo/count', function (Request $request, Response $response) {
    if (!isset($_SESSION['user_idx'])) {
        $payload = json_encode(['count' => 0]);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    $count = DB::table('messages')
        ->where('receiver_id', $_SESSION['user_idx'])
        ->where('read_at', null)
        ->where('is_deleted_receiver', 0)
        ->count();

    $payload = json_encode(['count' => $count]);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/notifications/check', function (Request $request, Response $response) {
    if (!isset($_SESSION['user_idx'])) {
        return $response->withStatus(401);
    }

    $userId = $_SESSION['user_idx'];

    $notis = DB::table('notifications')
        ->leftJoin('characters', function($join) {
            $join->on('notifications.sender_id', '=', 'characters.user_id')
                ->on('notifications.group_id', '=', 'characters.group_id')
                ->where('characters.is_main', 1);
        })
        ->where('notifications.user_id', $userId)
        ->where('notifications.is_viewed', 0)
        ->select(
            'notifications.*', 
            'characters.image_path as char_img'
        )
        ->orderBy('notifications.id', 'asc')
        ->get();


    $payload = json_encode(['notifications' => $notis]);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/notifications/read', function (Request $request, Response $response,) {
    if (!isset($_SESSION['user_idx'])) {
        return $response->withStatus(401);
    }

    $data = json_decode($request->getBody(), true);
    $ids = $data['id'];

    DB::table('notifications')
        ->where('id', $ids)
        ->update(['is_viewed' => 1]);

    $payload = json_encode(['success' => true]);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});