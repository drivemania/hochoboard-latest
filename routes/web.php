<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;

$basePath = $app->getBasePath();

$boardController = new \App\Controller\BoardController($blade, $basePath);
$characterController = new \App\Controller\CharacterController($blade, $basePath);

$secretCheckMiddleware = new \App\Middleware\SecretCheckMiddleware($basePath);

// [공통 함수] HTML Purifier (XSS 방지 필터)
function cleanHtml($html) {
    $config = HTMLPurifier_Config::createDefault();
    
    $cachePath = __DIR__ . '/../cache'; 
    if (!is_dir($cachePath)) mkdir($cachePath, 0777, true);
    $config->set('Cache.SerializerPath', $cachePath);

    $config->set('HTML.Allowed', 'p,b,strong,i,em,u,s,del,a[href|title|target],ul,ol,li,img[src|alt|width|height|style],h1,h2,h3,h4,h5,h6,blockquote,table[border|cellpadding|cellspacing|style],tr,td[rowspan|colspan|style],th,span[style],div[style|class],br,hr');

    $config->set('HTML.Nofollow', true);
    $config->set('HTML.TargetBlank', true);

    $purifier = new HTMLPurifier($config);
    return $purifier->purify($html);
}


$app->get('/', function (Request $request, Response $response) use ($blade, $basePath) {
    $group = DB::table('groups')->where('is_default', 1)->first();
    $board = "";
    
    if (!$group) {
        $_SESSION['flash_message'] = "생성된 커뮤니티 그룹이 없습니다. 관리자 페이지에서 설정을 진행해주세요.";
        $_SESSION['flash_type'] = 'error';
        return $response->withHeader('Location', $basePath . '/admin')->withStatus(302);
    }

    $themeUrl = $basePath . '/public/themes/' . $group->theme;
    $mainUrl = $basePath . "/";

    $themeName = $group->theme ?? 'basic';
    $themeLayout = "";
    $mainIndex = $themeName . '.index';
    if($group->custom_main_id > 0){ //커스텀 메인 페이지
        $board = DB::table('boards')
                ->where('id', $group->custom_main_id)
                ->where('is_deleted', 0)
                ->first();
        $mainIndex = 'page.index';
        $themeLayout = $themeName . ".layout";
        $parser = new \ContentParser($this->basePath); 
        $board->notice = $parser->parse($board->notice);
    }
    $content = $blade->render($mainIndex, [
        'group' => $group,
        'board' => $board,
        'currentUrl' => $basePath,
        'mainUrl' => $mainUrl,
        'title' => $group->name,
        'themeUrl' => $themeUrl,
        'themeLayout' => $themeLayout
    ]);
    $response->getBody()->write($content);
    return $response;
})->add($secretCheckMiddleware);

//메모 처리부
$app->group('/memo', function ($group) use ($blade, $basePath) {

    $group->get('', function (Request $request, Response $response) use ($blade) {
        if (!isset($_SESSION['user_idx'])) return $response->withStatus(403);

        $type = $_GET['type'] ?? 'recv';
        $page = $_GET['page'] ?? 1;

        $query = DB::table('messages');

        if ($type === 'sent') {
            $query->where('sender_id', $_SESSION['user_idx'])
                  ->where('is_deleted_sender', 0);
        } else {
            $query->where('receiver_id', $_SESSION['user_idx'])
                  ->where('is_deleted_receiver', 0);
        }

        $messages = $query->orderBy('id', 'desc')->paginate(15, ['*'], 'page', $page);

        $content = $blade->render('memo.index', [
            'type' => $type,
            'messages' => $messages
        ]);
        $response->getBody()->write($content);
        return $response;
    });

    // 쪽지 읽기 (읽음 처리)
    $group->get('/view/{id}', function (Request $request, Response $response, $args) use ($blade) {
        $id = $args['id'];
        $myId = $_SESSION['user_idx'];

        $msg = DB::table('messages')->find($id);
        if (!$msg) {
            $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', '/')->withStatus(302);
        };

        if ($msg->sender_id != $myId && $msg->receiver_id != $myId) {
            $response->getBody()->write("권한이 없습니다.");
            return $response;
        }

        if ($msg->receiver_id == $myId && $msg->read_at == null) {
            DB::table('messages')
                ->where('id', $id)
                ->update(['read_at' => date('Y-m-d H:i:s')]);
        }

        $content = $blade->render('memo.view', ['msg' => $msg]);
        $response->getBody()->write($content);
        return $response;
    });

    // 쪽지 쓰기 폼
    $group->get('/write', function (Request $request, Response $response) use ($blade) {
        $toId = $_GET['to_id'] ?? '';
        $toUser = null;
        if($toId) {
            $toUser = DB::table('users')->find($toId);
        }

        $sendUserList = DB::table('users')->where('is_deleted', '=', 0)->get();

        $content = $blade->render('memo.write', ['toUser' => $toUser, 'receiverId' => $sendUserList]);
        $response->getBody()->write($content);
        return $response;
    });

    // 쪽지 발송
    $group->post('/send', function (Request $request, Response $response) use ($basePath) {
        $data = $request->getParsedBody();
        $receiverId = trim($data['receiver_id']);
        $content = trim($data['content']);

        $receiver = DB::table('users')->where('user_id', $receiverId)->first();
        if (!$receiver) {
            $_SESSION['flash_message'] = '존재하지 않는 사용자입니다.';
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $basePath."/memo/write")->withStatus(302);
        }

        DB::table('messages')->insert([
            'sender_id' => $_SESSION['user_idx'],
            'sender_nickname' => $_SESSION['nickname'],
            'receiver_id' => $receiver->id,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $group = DB::table('groups')->where('is_default', 1)->first();
        $currentGroupId = $group->id ?? 0;

        DB::table('notifications')->insert([
            'group_id' => $currentGroupId,
            'user_id' => $receiver->id,
            'sender_id' => $_SESSION['user_idx'],
            'type' => 'memo',
            'message' => $_SESSION['nickname'] . '님이 쪽지를 보냈습니다.',
            'url' => '/memo/view',
            'is_viewed' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $_SESSION['flash_message'] = '쪽지를 보냈습니다.';
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $basePath."/memo")->withStatus(302);
    });

    $group->post('/delete', function (Request $request, Response $response) use ($basePath) {
        $data = $request->getParsedBody();
        $id = trim($data['id']);
        $myId = $_SESSION['user_idx'];

        $msg = DB::table('messages')->find($id);
        if (!$msg) {
            $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', '/')->withStatus(302);
        };

        if ($msg->sender_id != $myId && $msg->receiver_id != $myId) {
            $response->getBody()->write("권한이 없습니다.");
            return $response;
        }

        if ($msg->receiver_id == $myId) {
            DB::table('messages')
                ->where('id', $id)
                ->update(['is_deleted_reciver' => date('Y-m-d H:i:s')]);
        }
        if ($msg->sender_id == $myId) {
            DB::table('messages')
                ->where('id', $id)
                ->update(['is_deleted_sender' => date('Y-m-d H:i:s')]);
        }

        $_SESSION['flash_message'] = '삭제되었습니다.';
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $basePath."/memo")->withStatus(302);
    });
    
});

$app->group('/emoticon', function ($group) use ($blade, $basePath) {
    $group->get('', function (Request $request, Response $response) use ($blade) {

        $emoticons = DB::table(table: 'emoticons')->get();
        
        $content = $blade->render('page.emoticon', [
            'emoticon' => $emoticons
        ]);
        $response->getBody()->write($content);
        return $response;
    });
});
//댓글 처리부
$app->post('/comment/delete', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $cmtId = $data['comment_id'];
    $docId = $data['doc_id'];

    $check = DB::table(table: 'comments')->where('id',  $cmtId)->first();
    if (!$check) {
        $_SESSION['flash_message'] = '처리중 오류가 발생했습니다.';
        $_SESSION['flash_type'] = 'error';
        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
    }
    $myId = $_SESSION['user_idx'] ?? 0;
    $myLevel = $_SESSION['level'] ?? 0;

    if ($check->user_id != $myId && $myLevel < 10) {
        $_SESSION['flash_message'] = '수정 권한이 없습니다.';
        $_SESSION['flash_type'] = 'error';
        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
    }
    
    DB::table('comments')
    ->where('id', $cmtId)
    ->update([
        'is_deleted' => 1,
        'deleted_at' => date('Y-m-d H:i:s')
    ]);
    DB::table('documents')->where('id', $docId)->decrement('comment_count');

    return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
});

$app->post('/comment/update', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $cmtId = $data['comment_id'];
    $content = trim($data['content']);

    $comment = DB::table('comments')->find($cmtId);
    if (!$comment) {
        $_SESSION['flash_message'] = '존재하지 않는 댓글입니다.';
        $_SESSION['flash_type'] = 'error';
        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
    }

    $myId = $_SESSION['user_idx'] ?? 0;
    $myLevel = $_SESSION['level'] ?? 0;

    if ($comment->user_id != $myId && $myLevel < 10) {
        $_SESSION['flash_message'] = '수정 권한이 없습니다.';
        $_SESSION['flash_type'] = 'error';
        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
    }

    $content = \App\Support\Hook::filter('before_comment_save', ['content' => $content]);
    $content = cleanHtml($content['content']);

    DB::table('comments')
        ->where('id', $cmtId)
        ->update([
            'content' => $content
        ]);

    $after =  \App\Support\Hook::filter('after_comment_save', $cmtId);

    return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
});

// 대표 캐릭터 변경
$app->post('/character/set-main', function (Request $request, Response $response) use ($app) {
    $data = $request->getParsedBody();
    $charId = $data['id'];
    
    $char = DB::table('characters')->find($charId);
    if (!$char || $char->user_id != $_SESSION['user_idx']) {
        return $response->withStatus(403);
    }

    DB::table('characters')
        ->where('group_id', $char->group_id)
        ->where('user_id', $_SESSION['user_idx'])
        ->update(['is_main' => 0]);

    DB::table('characters')
        ->where('id', $charId)
        ->update(['is_main' => 1]);

    return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/');
});

$app->any('/plugin/{plugin_name}/{action}', \App\Controller\PluginDispatcherController::class . ':dispatch');

//긴주소
$app->get('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/edit', [$boardController, 'getEdit'])->add($secretCheckMiddleware);

$app->post('/au/{group_slug}/{menu_slug}/store', [$characterController, 'store'])->add($secretCheckMiddleware);

$app->post('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/update', [$characterController, 'update']);

$app->post('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/relation/add', [$characterController, 'addRelation']);

$app->post('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/relation/update', [$characterController, 'updateRelation']);

$app->post('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/relation/delete', [$characterController, 'delRelation']);

$app->post('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/relation/reorder', [$characterController, 'reorderRelation']);

$app->post('/au/{group_slug}/{menu_slug}/{doc_id:[0-9]+}/edit', [$boardController, 'edit'])->setArgument('is_short', false)->add($secretCheckMiddleware);

$app->post('/au/{group_slug}/{menu_slug}/{doc_id:[0-9]+}/delete', [$boardController, 'bdelete'])->setArgument('is_short', false);

$app->post('/au/{group_slug}/{menu_slug}/{doc_id:[0-9]+}/comment', [$boardController, 'comment'])->setArgument('is_short', false)->add($secretCheckMiddleware);

$app->post('/au/{group_slug}/{menu_slug}/write', [$boardController, 'write']);

$app->get('/au/{group_slug}[/]', [$boardController, 'index'])->add($secretCheckMiddleware);

$app->get('/au/{group_slug}/{menu_slug}[/]', [$boardController, 'index'])->add($secretCheckMiddleware);

$app->get('/au/{group_slug}/{menu_slug}/{action}[/]', [$boardController, 'index'])->add($secretCheckMiddleware);

//짧은주소
$app->post('/{menu_slug}/write', [$boardController, 'write']);

$app->post('/{menu_slug}/store', [$characterController, 'store'])->add($secretCheckMiddleware);

$app->get('/{menu_slug}/{id:[0-9]+}/edit', [$boardController, 'getEdit'])->add($secretCheckMiddleware);

$app->post('/{menu_slug}/{id:[0-9]+}/edit', [$boardController, 'edit'])->setArgument('is_short', true);

$app->post('/{menu_slug}/{id:[0-9]+}/delete', [$boardController, 'bdelete'])->setArgument('is_short', true);

$app->post('/{menu_slug}/{id:[0-9]+}/comment', [$boardController, 'comment'])->setArgument('is_short', false)->add($secretCheckMiddleware);

$app->post('/{menu_slug}/{id:[0-9]+}/update', [$characterController, 'update']);

$app->post('/{menu_slug}/{id:[0-9]+}/relation/add', [$characterController, 'addRelation']);

$app->post('/{menu_slug}/{id:[0-9]+}/relation/update', [$characterController, 'updateRelation']);

$app->post('/{menu_slug}/{id:[0-9]+}/relation/delete', [$characterController, 'delRelation']);

$app->post('/{menu_slug}/{id:[0-9]+}/relation/reorder', [$characterController, 'reorderRelation']);

$app->get('/{menu_slug}[/]', [$boardController, 'index'])->add($secretCheckMiddleware);

$app->get('/{menu_slug}/{action}[/]', callable: [$boardController, 'index'])->add($secretCheckMiddleware);