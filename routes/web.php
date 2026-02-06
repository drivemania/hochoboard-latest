<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;

use App\Controller\BoardController;
use App\Controller\CharacterController;
use App\Controller\ShopController;
use App\Controller\PluginDispatcherController;
use App\Controller\MemberController;
use App\Controller\MemoController;
use App\Controller\CommentController;

use App\Middleware\SecretCheckMiddleware;
use App\Middleware\MemoCheckMiddleware;


$basePath = $app->getBasePath();

$boardController = new BoardController($blade, $basePath);
$characterController = new CharacterController($blade, $basePath);
$shopController = new ShopController($blade, $basePath);
$memberController = new MemberController($blade, $basePath);
$memoController = new MemoController($blade, $basePath);
$commentController = new CommentController($blade, $basePath);
$pluginDispatcherController = new PluginDispatcherController();

$secretCheckMiddleware = new SecretCheckMiddleware($basePath);
$memoCheckMiddleware = new MemoCheckMiddleware($basePath);

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

//내정보
$app->group('/info', function ($group) use ($memberController) {
    $group->get('', [$memberController, 'index']); // 내 정보 메인
    $group->post('/password', [$memberController, 'updatePassword']); // 비밀번호 변경 처리
});

$app->post('/image/upload', function (Request $request, Response $response) use ($basePath) {
    $files = $request->getUploadedFiles();
    
    if (empty($files['upload']) || $files['upload']->getError() !== UPLOAD_ERR_OK) {
        $data = ['error' => ['message' => '파일 업로드 실패']];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $uploadFile = $files['upload'];
    $extension = pathinfo($uploadFile->getClientFilename(), PATHINFO_EXTENSION);
    
    $directory = __DIR__ . '/../public/data/uploads/editor';
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $filename = uniqid() . '_' . time() . '.' . $extension;
    $uploadFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    $data = [
        'url' => $basePath . '/public/data/uploads/editor/' . $filename
    ];

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

//메모 처리부
$app->group('/memo', function ($group) use ($memoController) {

    $group->get('', [$memoController, 'index']);

    // 쪽지 읽기 (읽음 처리)
    $group->get('/view/{id}', [$memoController, 'view']);

    // 쪽지 쓰기 폼
    $group->get('/write', [$memoController, 'write']);

    // 쪽지 발송
    $group->post('/send', [$memoController, 'send']);

    $group->post('/delete', [$memoController, 'delete']);
    
})->add($memoCheckMiddleware);

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
$app->post('/comment/delete', [$commentController, 'delete']);

$app->post('/comment/update', [$commentController, 'update']);

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

$app->any('/plugin/{plugin_name}/{action}', [$pluginDispatcherController, 'dispatch']);

//긴주소

$app->get('/au/{group_slug}/shop/{shop_id:[0-9]+}', [$shopController, 'shopView']);

$app->post('/au/{group_slug}/shop/{shop_id:[0-9]+}/purchase', [$shopController, 'shopPurchase']);

$app->get('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/edit', [$boardController, 'getEdit'])->add($secretCheckMiddleware);

$app->post('/au/{group_slug}/{menu_slug}/store', [$characterController, 'store'])->add($secretCheckMiddleware);

$app->post('/au/{group_slug}/{menu_slug}/item/{inv_id:[0-9]+}/use', [$shopController, 'itemUse']);

$app->post('/au/{group_slug}/{menu_slug}/item/{inv_id:[0-9]+}/sell', [$shopController, 'itemSell']);

$app->post('/au/{group_slug}/{menu_slug}/item/{inv_id:[0-9]+}/gift', [$shopController, 'itemGift']);

$app->post('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/update', [$characterController, 'update']);

$app->post('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/relation/add', [$characterController, 'addRelation']);

$app->post('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/relation/update', [$characterController, 'updateRelation']);

$app->post('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/relation/delete', [$characterController, 'delRelation']);

$app->post('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/relation/reorder', [$characterController, 'reorderRelation']);

$app->post('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/edit', [$boardController, 'edit'])->setArgument('is_short', false)->add($secretCheckMiddleware);

$app->post('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/delete', [$boardController, 'bdelete'])->setArgument('is_short', false);

$app->post('/au/{group_slug}/{menu_slug}/{id:[0-9]+}/comment', [$commentController, 'comment'])->setArgument('is_short', false)->add($secretCheckMiddleware);

$app->post('/au/{group_slug}/{menu_slug}/write', [$boardController, 'write']);

$app->get('/au/{group_slug}[/]', [$boardController, 'index'])->add($secretCheckMiddleware);

$app->get('/au/{group_slug}/{menu_slug}[/]', [$boardController, 'index'])->add($secretCheckMiddleware);

$app->get('/au/{group_slug}/{menu_slug}/{action}[/]', [$boardController, 'index'])->add($secretCheckMiddleware);

//짧은주소

$app->get('/shop/{shop_id:[0-9]+}', [$shopController, 'shopView']);

$app->post('/shop/{shop_id:[0-9]+}/purchase', [$shopController, 'shopPurchase']);

$app->post('/{menu_slug}/write', [$boardController, 'write']);

$app->post('/{menu_slug}/store', [$characterController, 'store'])->add($secretCheckMiddleware);

$app->post('/{menu_slug}/item/{inv_id:[0-9]+}/use', [$shopController, 'itemUse']);

$app->post('/{menu_slug}/item/{inv_id:[0-9]+}/sell', [$shopController, 'itemSell']);

$app->post('/{menu_slug}/item/{inv_id:[0-9]+}/gift', [$shopController, 'itemGift']);

$app->get('/{menu_slug}/{id:[0-9]+}/edit', [$boardController, 'getEdit'])->add($secretCheckMiddleware);

$app->post('/{menu_slug}/{id:[0-9]+}/edit', [$boardController, 'edit'])->setArgument('is_short', true);

$app->post('/{menu_slug}/{id:[0-9]+}/delete', [$boardController, 'bdelete'])->setArgument('is_short', true);

$app->post('/{menu_slug}/{id:[0-9]+}/comment', [$commentController, 'comment'])->setArgument('is_short', false)->add($secretCheckMiddleware);

$app->post('/{menu_slug}/{id:[0-9]+}/update', [$characterController, 'update']);

$app->post('/{menu_slug}/{id:[0-9]+}/relation/add', [$characterController, 'addRelation']);

$app->post('/{menu_slug}/{id:[0-9]+}/relation/update', [$characterController, 'updateRelation']);

$app->post('/{menu_slug}/{id:[0-9]+}/relation/delete', [$characterController, 'delRelation']);

$app->post('/{menu_slug}/{id:[0-9]+}/relation/reorder', [$characterController, 'reorderRelation']);

$app->get('/{menu_slug}[/]', [$boardController, 'index'])->add($secretCheckMiddleware);

$app->get('/{menu_slug}/{action}[/]', callable: [$boardController, 'index'])->add($secretCheckMiddleware);