<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as DB;

class BoardController extends Model
{
    protected $blade;
    protected $basePath;
    protected $returnUrl;

    public function __construct($blade, $basePath)
    {
        $this->blade = $blade;
        $this->basePath = $basePath;
        $this->returnUrl = "";
    }

    public function index(Request $request, Response $response, $args)
    {
        
        $groupSlug = $args['group_slug'] ?? "";
        $menuSlug = $args['menu_slug'] ?? "";
        $action   = $args['action'] ?? null;
        
        if($groupSlug != ""){
            $group = DB::table('groups')->where('slug', $groupSlug)->first();
            $mainUrl = $this->basePath . '/au/' . $groupSlug;
        }else{
            $group = DB::table('groups')->where('is_default', 1)->first();
            $mainUrl = $this->basePath . '/';
        }


        if (!$group) {
            $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
        }

        if(empty($menuSlug) || $menuSlug == ""){
            $group = DB::table('groups')
                ->where('slug', $groupSlug)
                ->where('is_deleted', 0)
                ->first();
            $board = "";
            
            if (!$group) {
                $_SESSION['flash_message'] = "생성된 커뮤니티 그룹이 없습니다. 관리자 페이지에서 설정을 진행해주세요.";
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $this->basePath . '/admin')->withStatus(302);
            }
        
            $themeUrl = $this->basePath . '/public/themes/' . $group->theme;
        
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
            $currentUrl = $this->basePath . "/au/" . $groupSlug;
            $content = $this->blade->render($mainIndex, [
                'group' => $group,
                'currentUrl' => $currentUrl,
                'board' => $board,
                'title' => $group->name,
                'themeUrl' => $themeUrl,
                'mainUrl' => $mainUrl,
                'themeLayout' => $themeLayout
            ]);
            $response->getBody()->write($content);
            return $response;
        }
        
        if (in_array($menuSlug, ['admin', 'login', 'logout', 'register', 'au', 'comment', 'page', 'memo', 'plugin'])) {
            $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
        }
        
        $menu = DB::table('menus')
            ->where('group_id', $group->id)
            ->where('slug', $menuSlug)
            ->where('is_deleted', 0)
            ->first();
        
        if (!$menu) {
            $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
        }
        
        $themeName = $group->theme ?? 'basic';
        $themeLayout = $themeName . ".layout";
        
        $themeUrl = $this->basePath . '/public/themes/' . $group->theme;
        
        $board = DB::table('boards')
            ->where('id', $menu->target_id)
            ->where('is_deleted', 0)
            ->first();
        
        if (!$board) {
            $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
        }

        $currentUrl = $this->basePath . '/au/' . $group->slug . '/' . $menu->slug;
        if($groupSlug == "") $currentUrl = $this->basePath . '/' . $menu->slug;
        
        switch ($menu->type){
            case 'board':{
                $myLevel = $_SESSION['level'] ?? 0; 
                if ($myLevel < $board->read_level) {
                    $_SESSION['flash_message'] = "권한이 없습니다.";
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
                }
        
                $skinUrl = (object) array(
                    'documents' => $this->basePath . '/public/skins/board/' . $board->board_skin
                );
        
                // 글쓰기
                if ($action === 'write') {
                    if ($myLevel < $board->write_level) {
                        $_SESSION['flash_message'] = "권한이 없습니다.";
                        $_SESSION['flash_type'] = 'error';
                        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
                    }
                    $skinView = 'board.' . ($board->board_skin ?? 'basic') . '.write';
                    $content = $this->blade->render($skinView, [
                        'themeLayout' => $themeLayout,
                        'themeUrl' => $themeUrl,
                        'skinUrl' => $skinUrl,
                        'mainUrl' => $mainUrl,
                        'group'       => $group,
                        'board'       => $board,
                        'menu'        => $menu,
                        'title'       => $menu->title . ' - 글쓰기',
                        'currentUrl'  => $currentUrl
                    ]);
                    $response->getBody()->write($content);
                    return $response;
                }
                
                //상세보기
                if (is_numeric($action)) {
                    $docNum = $action;
                    
                    $document = DB::table('documents')->where('board_id', $board->id)->where('doc_num', $docNum)->first();
                    if (!$document || $document->is_deleted == 1) {
                        $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
                        $_SESSION['flash_type'] = 'error';
                        return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
                    }

                    if ($document->is_secret > 0 && $document->user_id != $_SESSION['user_idx'] && $_SESSION['user_idx'] < 10) {
                        $_SESSION['flash_message'] = "권한이 없습니다.";
                        $_SESSION['flash_type'] = 'error';
                        return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
                    }

                    $docId = $document->id;
        
                    DB::table('documents')->where('id', $docId)->increment('hit');
        
                    $comments = DB::table('comments')
                        ->where('doc_id', $docId)
                        ->where('is_deleted', 0)
                        ->orderBy('id', 'asc')
                        ->get();

                    if(!empty($comments)){
                        foreach($comments as $cmt){
                            $cmt->plugin = $this->getPluginId('comment', $cmt->id);
                        }
                    }

                    $skinView = 'board.' . ($board->board_skin ?? 'basic') . '.view';
                    $listUrl = $currentUrl; // 목록 주소
        
                    $skinUrl = (object) array(
                        'documents' => $this->basePath . '/public/skins/board/' . $board->board_skin
                    );

                    $emoticon = DB::table('emoticons')
                    ->get();

                    foreach ($emoticon as $emo) {
                        $imgTag = '<img src="'.$this->basePath . $emo->image_path . '" alt="'.$emo->code.'" class="hc-emoticon" style="display:inline-block; vertical-align:middle;">';
                        $document->content = str_replace($emo->code, $imgTag, $document->content);
                        foreach ($comments as $cmt) {
                            $cmt->content = str_replace($emo->code, $imgTag, $cmt->content);
                        }
                    }
                    
                    $document->content = \App\Support\Hook::filter('post_content', $document->content);
        
                    $content = $this->blade->render($skinView, [
                        'themeLayout' => $themeLayout,
                        'themeUrl'    => $themeUrl,
                        'skinUrl'     => $skinUrl,
                        'mainUrl'     => $mainUrl,
                        'group'       => $group,
                        'board'       => $board,
                        'menu'        => $menu,
                        'document'    => $document,
                        'comments'    => $comments,
                        'currentUrl'  => $currentUrl . '/' . $docNum, // 현재 글 주소
                        'listUrl'     => $listUrl
                    ]);
                    $response->getBody()->write($content);
                    return $response;
                }
        
                //여기서부터 그냥 조회
                $page = $_GET['page'] ?? 1;
                $perPage = $board->list_count ?? 20;
        
                $searchTarget = $_GET['search_target'] ?? "";
                $keyword = $_GET['keyword'] ?? "";
        
                $query = DB::table('documents')
                    ->leftJoin('characters', function($join) use ($group) {
                        $join->on('documents.user_id', '=', 'characters.user_id')
                            ->where('characters.group_id', '=', $group->id)
                            ->where('characters.is_main', '=', 1);
                    })
                    ->leftJoin('groups as char_group', 'characters.group_id', '=', 'char_group.id')
                    ->leftJoin('menus as char_menu', function($join) {
                        $join->on('characters.board_id', '=', 'char_menu.target_id')
                            ->where('char_menu.type', '=', 'character')
                            ->where('char_menu.is_deleted', '=', 0);
                    })
                    ->where('documents.board_id', $board->id)
                    ->where('documents.is_deleted', 0);
        
                if ($keyword != "") {
                    switch ($searchTarget) {
                        case "member":{
                            $query->where('documents.nickname', 'like', "%{$keyword}%");
                            break;
                        }
                        case "hashtag":{
                            $hashCmtArr = array();
                            $hashCmt = DB::table('comments')
                                ->where('content', 'like', "%{$keyword}%")
                                ->where('is_deleted', '=', 0)
                                ->get();
                            foreach ( $hashCmt as $cmt ) {
                                if(! in_array($cmt->board_id, $hashCmtArr) ){
                                    $hashCmtArr[] = $cmt->board_id;
                                }                        
                            }
                            $query->whereIn('documents.id', $hashCmtArr);
                            break;
                        }
                        case "title":{
                            $query->where('documents.title', 'like', "%{$keyword}%");
                            break;
                        }
                        case "content":{
                            $query->where('documents.content', 'like', "%{$keyword}%");
                            break;
                        }
                        case "tnc":{
                            $query->where(function($q) use ($keyword) {
                                $q->where('documents.title', 'like', "%{$keyword}%")
                                ->orWhere('documents.content', 'like', "%{$keyword}%");
                            });
                            break;
                        }
                    }
                }
        
                $documents = $query->orderBy('documents.is_notice', 'desc')
                    ->orderBy('documents.id', 'desc')
                    ->paginate($perPage, [
                        'documents.*',
                        'characters.id as char_id',
                        'characters.image_path as char_image',
                        'characters.name as char_name',
                        'char_group.slug as char_group_slug',
                        'char_menu.slug as char_menu_slug'
                    ], 'page', $page);
        
                $skinView = 'board.' . ($board->board_skin ?? 'basic') . '.list';
        
                $emoticon = DB::table('emoticons')->get();
                foreach ($documents as $doc) {
                    $doc->comments = DB::table('comments')
                        ->leftJoin('characters', function($join) use ($group) {
                            $join->on('comments.user_id', '=', 'characters.user_id')
                                ->where('characters.group_id', '=', $group->id)
                                ->where('characters.is_main', '=', 1);
                        })
                        ->leftJoin('groups as char_group', 'characters.group_id', '=', 'char_group.id')
                        ->leftJoin('menus as char_menu', function($join) {
                            $join->on('characters.board_id', '=', 'char_menu.target_id')
                                ->where('char_menu.type', '=', 'character')
                                ->where('char_menu.is_deleted', '=', 0);
                        })
                        ->where('comments.doc_id', $doc->id)
                        ->where('comments.is_deleted', 0)
                        ->orderBy('comments.created_at', 'asc')
                        ->select([
                            'comments.*',
                            'characters.id as char_id',
                            'characters.image_path as char_image',
                            'characters.name as char_name',
                            'char_group.slug as char_group_slug',
                            'char_menu.slug as char_menu_slug'
                        ])
                        ->get();

                    foreach ($emoticon as $emo) {
                        $imgTag = '<img src="'.$this->basePath . $emo->image_path . '" alt="'.$emo->code.'" class="hc-emoticon" style="display:inline-block; vertical-align:middle;">';
                        foreach ($doc->comments as $cmt) {
                            $cmt->content = str_replace($emo->code, $imgTag, $cmt->content);
                        }
                    }
                }

                $parser = new \ContentParser($this->basePath); 
                $board->notice = $parser->parse($board->notice);
        
                $content = $this->blade->render($skinView, [
                    'themeLayout' => $themeLayout,
                    'themeUrl'    => $themeUrl,
                    'skinUrl'     => $skinUrl,
                    'mainUrl'     => $mainUrl,
                    'group'       => $group,
                    'board'       => $board,
                    'menu'        => $menu,
                    'documents'   => $documents,
                    'title'       => $menu->title . ' - ' . $group->name,
                    'currentUrl'  => $currentUrl
                ]);
                
                $response->getBody()->write($content);
                return $response;
            }
            case 'character':{
        
                $myLevel = $_SESSION['level'] ?? 0;
                if ($myLevel < $board->read_level) {
                    $_SESSION['flash_message'] = "접근 권한이 없습니다.";
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
                }
        
                $skinName = $board->board_skin ?? 'basic';
                $skinViewPath = 'character.' . $skinName;
        
                $themeLayout = ($group->theme ?? 'basic') . ".layout";
                $skinUrl = (object) array(
                    'documents' => $this->basePath . '/public/skins/character/' . $board->board_skin
                );
        
                //글쓰기
                if ($action === 'write') {
                    if ($myLevel < $board->write_level) {
                            $_SESSION['flash_message'] = "캐릭터 생성 권한이 없습니다.";
                            $_SESSION['flash_type'] = 'error';
                            return $response->withHeader('Location', $currentUrl)->withStatus(302);
                        }
                        
                        $content = $this->blade->render($skinViewPath . '.write', [
                        'themeLayout' => $themeLayout,
                        'themeUrl' => $themeUrl,
                        'skinUrl' => $skinUrl,
                        'group' => $group,
                        'mode' => 'create',
                        'mainUrl' => $mainUrl,
                        'character' => null,
                        'actionUrl' => $currentUrl . '/store',
                        'currentUrl' => $currentUrl
                    ]);
                    $response->getBody()->write($content);
                    return $response;
                }
        
                //상세조회
                if (is_numeric($action)) {
                    $charId = $action;
                    $character = DB::table('characters')->find($charId);
                    
                    if (!$character || $character->is_deleted) {
                        $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
                        $_SESSION['flash_type'] = 'error';
                        return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
                    };

                    $relations = json_decode($character->relationship ?? '[]', true);
                    $finalRelations = [];
                    $targetIds = [];

                    if($relations && count($relations) > 0) {
                        $targetIds = array_column($relations, 'target_id');

                        $targets = [];
                        if (!empty($targetIds)) {
                            $targets = DB::table('characters')
                                ->where('is_deleted', 0)
                                ->whereIn('id', $targetIds)
                                ->get()
                                ->keyBy('id');
                        }
    
    
                        foreach ($relations as $rel) {
                            $tid = $rel['target_id'];
                            
                            if (isset($targets[$tid])) {
                                $rel['target_name']  = $targets[$tid]->name;
                                $rel['target_image'] = $targets[$tid]->image_path;
                                $finalRelations[] = $rel;
                            }
                        }

                    }

                    $otherCharacters = DB::table('characters')
                        ->where('group_id', $character->group_id)
                        ->where('is_deleted', 0)
                        ->whereNotIn('id', $targetIds)
                        ->orderBy('name', 'asc')
                        ->get();
                    
                    $owner = DB::table('users')->find($character->user_id);
                    
                    $content = $this->blade->render($skinViewPath . '.view', [
                        'themeLayout' => $themeLayout,
                        'themeUrl' => $themeUrl,
                        'mainUrl' => $mainUrl,
                        'skinUrl' => $skinUrl,
                        'group' => $group,
                        'character' => $character,
                        'owner' => $owner ? $owner->nickname : '알수없음',
                        'profile' => $character->profile_data ? json_decode($character->profile_data, associative: true) : [],
                        'relations' => $finalRelations,
                        'otherCharacters' => $otherCharacters,
                        'currentUrl' => $currentUrl
                    ]);
                    $response->getBody()->write($content);
                    return $response;
                }
        
                //그냥 조회
                $characters = DB::table('characters')
                    ->join('users', 'characters.user_id', '=', 'users.id')
                    ->where('characters.group_id', $group->id)
                    ->where('characters.board_id', $board->id)
                    ->where('characters.is_deleted', 0)
                    ->orderBy('characters.created_at', 'asc')
                    ->select('characters.*', 'users.nickname')
                    ->get();

        
                $content = $this->blade->render($skinViewPath . '.list', [
                    'themeLayout' => $themeLayout,
                    'themeUrl' => $themeUrl,
                    'skinUrl' => $skinUrl,
                    'mainUrl' => $mainUrl,
                    'group' => $group,
                    'characters' => $characters,
                    'title' => $menu->title,
                    'currentUrl' => $currentUrl
                ]);
                $response->getBody()->write($content);
                return $response;
            }
            case 'load': {
                $myLevel = $_SESSION['level'] ?? 0; 
                if ($myLevel < $board->read_level) {
                    $_SESSION['flash_message'] = "권한이 없습니다.";
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
                }
            
                $skinUrl = (object) array(
                    'documents' => $this->basePath . '/public/skins/load/' . ($board->board_skin ?? 'basic')
                );
            
                $skinView = 'load.' . ($board->board_skin ?? 'basic') . '.list';

                //상세보기
                if (is_numeric($action)) {
                    $docId = $action;
                    
                    $document = DB::table('documents')
                    ->where('documents.board_id', $board->id)
                    ->where('documents.doc_num', $docId)
                    ->where('documents.is_deleted', 0)
                    ->leftJoin('characters', function($join) use ($group) {
                        $join->on('documents.user_id', '=', 'characters.user_id')
                             ->where('characters.group_id', '=', $group->id)
                             ->where('characters.is_main', '=', 1);
                    })
                    ->leftJoin('menus as char_menu', function($join) {
                        $join->on('characters.board_id', '=', 'char_menu.target_id')
                             ->where('char_menu.type', '=', 'character')
                             ->where('char_menu.is_deleted', '=', 0);
                    })
                    ->orderBy('documents.is_notice', 'desc')
                    ->orderBy('documents.id', 'desc')
                    ->select([
                        'documents.*',
                        'characters.id as char_id',
                        'characters.image_path as char_image',
                        'characters.name as char_name',
                        'char_menu.slug as char_menu_slug',
                    ])
                    ->first();

                    if (!$document || $document->is_deleted == 1) {
                        $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
                        $_SESSION['flash_type'] = 'error';
                        return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
                    }
        
                    DB::table('documents')->where('doc_num', $docId)->increment('hit');

                    $comments = DB::table('comments')
                        ->where('comments.doc_id', $document->id)
                        ->where('comments.is_deleted', 0)
                        ->leftJoin('characters', function($join) use ($group) {
                            $join->on('comments.user_id', '=', 'characters.user_id')
                                ->where('characters.group_id', '=', $group->id)
                                ->where('characters.is_main', '=', 1);
                        })
                        ->leftJoin('menus as char_menu', function($join) {
                            $join->on('characters.board_id', '=', 'char_menu.target_id')
                                ->where('char_menu.type', '=', 'character')
                                ->where('char_menu.is_deleted', '=', 0);
                        })
                        ->orderBy('comments.created_at', 'asc')
                        ->select([
                            'comments.*',
                            'characters.id as char_id',
                            'characters.image_path as char_image',
                            'characters.name as char_name',
                            'char_menu.slug as char_menu_slug',
                        ])
                        ->get();

                    if(!empty($comments)){
                        foreach($comments as $cmt){
                            $cmt->plugin = $this->getPluginId('comment', $cmt->id);
                        }
                    }
        
                    $skinView = 'load.' . ($board->board_skin ?? 'basic') . '.view';
                    $listUrl = $currentUrl; // 목록 주소
        
                    $skinUrl = (object) array(
                        'documents' => $this->basePath . '/public/skins/load/' . $board->board_skin
                    );

                    $emoticon = DB::table('emoticons')
                    ->get();

                    foreach ($emoticon as $emo) {
                        $imgTag = '<img src="'.$this->basePath . $emo->image_path . '" alt="'.$emo->code.'" class="hc-emoticon" style="display:inline-block; vertical-align:middle;">';
                        $document->content = str_replace($emo->code, $imgTag, $document->content);
                        foreach ($comments as $cmt) {
                            $cmt->content = str_replace($emo->code, $imgTag, $cmt->content);
                        }
                    }
                    
        
                    $content = $this->blade->render($skinView, [
                        'themeLayout' => $themeLayout,
                        'themeUrl'    => $themeUrl,
                        'skinUrl'     => $skinUrl,
                        'mainUrl'     => $mainUrl,
                        'group'       => $group,
                        'board'       => $board,
                        'menu'        => $menu,
                        'document'    => $document,
                        'comments'    => $comments,
                        'currentUrl'  => $currentUrl . '/' . $docId,
                        'listUrl'     => $listUrl
                    ]);
                    $response->getBody()->write($content);
                    return $response;
                }
            
                $page = $_GET['page'] ?? 1;
                $perPage = $board->list_count ?? 20;
                $searchTarget = $_GET['search_target'] ?? "";
                $keyword = $_GET['keyword'] ?? "";
            
                $query = DB::table('documents')
                    ->leftJoin('characters', function($join) use ($group) {
                        $join->on('documents.user_id', '=', 'characters.user_id')
                            ->where('characters.group_id', '=', $group->id)
                            ->where('characters.is_main', '=', 1);
                    })
                    ->leftJoin('groups as char_group', 'characters.group_id', '=', 'char_group.id')
                    ->leftJoin('menus as char_menu', function($join) {
                        $join->on('characters.board_id', '=', 'char_menu.target_id')
                            ->where('char_menu.type', '=', 'character')
                            ->where('char_menu.is_deleted', '=', 0);
                    })
                    ->where('documents.board_id', $board->id)
                    ->where('documents.is_deleted', 0);
            
                if ($keyword != "") {
                    switch ($searchTarget) {
                        case "character":
                            $query->where('characters.name', 'like', "%{$keyword}%");
                            break;
                        case "member":
                            $query->where('documents.nickname', 'like', "%{$keyword}%");
                            break;
                        case "anchor":
                            $query->where('documents.doc_num', '<=', $keyword);
                            break;
                        case "hashtag":
                            $keyword = ltrim($keyword, '#');
                            $hashCmtArr = DB::table('comments')
                                ->where('content', 'like', "%#{$keyword}%")
                                ->where('is_deleted', '=', 0)
                                ->pluck('doc_id')
                                ->toArray();
                            
                            if (!empty($hashCmtArr)) {
                                $query->whereIn('documents.id', $hashCmtArr);
                            } else {
                                $query->where('documents.id', '=', 0);
                            }
                            break;
                    }
                }
            
                $documents = $query->orderBy('documents.is_notice', 'desc')
                    ->orderBy('documents.id', 'desc')
                    ->paginate($perPage, [
                        'documents.*',
                        'characters.id as char_id',
                        'characters.image_path as char_image',
                        'characters.name as char_name',
                        'char_group.slug as char_group_slug',
                        'char_menu.slug as char_menu_slug'
                    ], 'page', $page);

                $emoticon = DB::table('emoticons')
                ->get();
            
                foreach ($documents as $doc) {
                    $doc->comments = DB::table('comments')
                        ->leftJoin('characters', function($join) use ($group) {
                            $join->on('comments.user_id', '=', 'characters.user_id')
                                ->where('characters.group_id', '=', $group->id)
                                ->where('characters.is_main', '=', 1);
                        })
                        ->leftJoin('groups as char_group', 'characters.group_id', '=', 'char_group.id')
                        ->leftJoin('menus as char_menu', function($join) {
                            $join->on('characters.board_id', '=', 'char_menu.target_id')
                                ->where('char_menu.type', '=', 'character')
                                ->where('char_menu.is_deleted', '=', 0);
                        })
                        ->where('comments.doc_id', $doc->id)
                        ->where('comments.is_deleted', 0)
                        ->orderBy('comments.created_at', 'asc')
                        ->select([
                            'comments.*',
                            'characters.id as char_id',
                            'characters.image_path as char_image',
                            'characters.name as char_name',
                            'char_group.slug as char_group_slug',
                            'char_menu.slug as char_menu_slug'
                        ])
                        ->get();

                    foreach ($emoticon as $emo) {
                        $imgTag = '<img src="'.$this->basePath . $emo->image_path . '" alt="'.$emo->code.'" class="hc-emoticon" style="display:inline-block; vertical-align:middle;">';
                        $doc->content = str_replace($emo->code, $imgTag, $doc->content);
                        foreach ($doc->comments as $cmt) {
                            $cmt->content = str_replace($emo->code, $imgTag, $cmt->content);
                        }
                    }

                    foreach($doc->comments as $cmt){
                        $cmt->plugin = $this->getPluginId('comment', $cmt->id);
                    }
                }

                $parser = new \ContentParser($this->basePath); 
                $board->notice = $parser->parse($board->notice);

                $content = $this->blade->render($skinView, [
                    'themeLayout' => $themeLayout,
                    'themeUrl'    => $themeUrl,
                    'skinUrl'     => $skinUrl,
                    'mainUrl' => $mainUrl,
                    'group'       => $group,
                    'board'       => $board,
                    'menu'        => $menu,
                    'documents'   => $documents,
                    'title'       => $menu->title . ' - ' . $group->name,
                    'currentUrl'  => $currentUrl
                ]);
                
                $response->getBody()->write($content);
                return $response;
            }
            case 'page' : {
                $myLevel = $_SESSION['level'] ?? 0; 
                if ($myLevel < $board->read_level) {
                    $_SESSION['flash_message'] = "권한이 없습니다.";
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
                }

                $parser = new \ContentParser($this->basePath); 
                $board->notice = $parser->parse($board->notice);

                $board->notice = \App\Support\Hook::filter('post_content', $board->notice);

                $emoticon = DB::table('emoticons')
                ->get();

                foreach ($emoticon as $emo) {
                    $imgTag = '<img src="'.$this->basePath . $emo->image_path . '" alt="'.$emo->code.'" class="hc-emoticon" style="display:inline-block; vertical-align:middle;">';
                    $board->notice = str_replace($emo->code, $imgTag, $board->notice);
                }

                $content = $this->blade->render('page.index', [
                    'themeLayout' => $themeLayout,
                    'themeUrl'    => $themeUrl,
                    'mainUrl' => $mainUrl,
                    'group'       => $group,
                    'board'       => $board,
                    'menu'        => $menu,
                    'title'       => $menu->title . ' - ' . $group->name,
                    'currentUrl'  => $currentUrl
                ]);
                
                $response->getBody()->write($content);
                return $response;
                
            }
        }
        
        return $response;
    }
    
    public function write(Request $request, Response $response, $args) 
    {
        $groupSlug = $args['group_slug'] ?? "";
        $menuSlug = $args['menu_slug'];

        if($groupSlug != ""){
            $group = DB::table('groups')->where('slug', $groupSlug)->first();
            $this->returnUrl = "/au/$groupSlug/$menuSlug";
        }else{
            $group = DB::table('groups')->where('is_default', 1)->first();
            $this->returnUrl = "/$menuSlug";
        }


        if (!$group) {
            $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다. 1";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
        };

        $menu = DB::table('menus')->where('group_id', $group->id)->where('slug', $menuSlug)->first();
        if (!$menu || !in_array($menu->type, array('board', 'load'))) {
            $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다. 2";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
        };

        $board = DB::table('boards')->find($menu->target_id);
        if (!$board) {
                $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다. 3";
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
            };

        if( $board->type == "load" ) {
            $result = $this->saveLoadPost($request, $board, $menu);
        }else{
            $result = $this->savePost($request, $board, $menu);
        }


        if (!$result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
        }

        return $response->withHeader('Location', $this->basePath . $this->returnUrl)->withStatus(302);
    }

    public function bdelete(Request $request, Response $response, $args) 
    {
        $docNum = $args['id'];
        $slug = $args['menu_slug'];
        $isShort = $args['is_short'] ?? false;

        $slugChk = DB::table(table: 'menus')->where('slug',  $slug)->where('is_deleted', 0)->first();
        if (!$slugChk) {
            $_SESSION['flash_message'] = '존재하지 않는 메뉴입니다.';
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
        }


        $boardType = $slugChk->type;
        $boardId = $slugChk->target_id;

        switch($boardType){
            case 'load':
            case 'board':{
                $check = DB::table(table: 'documents')->where('board_id', $boardId)->where('doc_num',  $docNum)->first();
                if (!$check) {
                    $_SESSION['flash_message'] = '처리중 오류가 발생했습니다.';
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
                }
                $myId = $_SESSION['user_idx'] ?? 0;
                $myLevel = $_SESSION['level'] ?? 0;
            
                if ($check->user_id != $myId && $myLevel < 10) {
                    $_SESSION['flash_message'] = '삭제 권한이 없습니다.';
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
                }
            
                DB::table('documents')
                ->where('doc_num', $docNum)
                ->where('board_id', $boardId)
                ->update([
                    'is_deleted' => 1,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);
                break;
            }
            case 'character':{
                $check = DB::table(table: 'characters')->where('id',  $docNum)->first();
                if (!$check) {
                    $_SESSION['flash_message'] = '처리중 오류가 발생했습니다.';
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
                }
                $myId = $_SESSION['user_idx'] ?? 0;
                $myLevel = $_SESSION['level'] ?? 0;
            
                if ($check->user_id != $myId && $myLevel < 10) {
                    $_SESSION['flash_message'] = '삭제 권한이 없습니다.';
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
                }
            
                DB::table('characters')
                ->where('id', $docNum)
                ->update([
                    'is_deleted' => 1,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);
                break;
            }
        }

        if($isShort){
            $this->returnUrl = $this->basePath . '/' . $args['menu_slug'];
        }else{
            $this->returnUrl = $this->basePath . '/au/' . $args['group_slug'] . '/' . $args['menu_slug'];
        }
    
        return $response->withHeader('Location', $this->returnUrl)->withStatus(302);
    }

    public function comment(Request $request, Response $response, $args) 
    {
        $docNum = $args['id'];
        $isShort = $args['is_short'] ?? false;
        $menuSlug = $args['menu_slug'];
        $data = $request->getParsedBody();
        
        $menu = DB::table('menus')->where('slug', $menuSlug)->where('is_deleted', 0)->first();
        $doc = DB::table('documents')->where('board_id', $menu->target_id)->where('doc_num', $docNum)->first();
        $board = DB::table('boards')->where('id', $doc->board_id)->where('is_deleted', 0)->first();
        $docId = $doc->id;

        if (($_SESSION['level'] ?? 0) < $board->comment_level) {
            $_SESSION['flash_message'] = '댓글 권한이 없습니다.';
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
        }

        $data = \App\Support\Hook::filter('before_comment_save', $data);
    
        $cmtId = DB::table('comments')->insertGetId([
            'board_id' => $doc->board_id,
            'doc_id' => $docId,
            'user_id' => $_SESSION['user_idx'] ?? 0,
            'nickname' => $_SESSION['nickname'] ?? '손님',
            'content' => trim(cleanHtml($data['content'])),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $after = \App\Support\Hook::filter('after_comment_save', $cmtId);

        if($isShort){
            $this->returnUrl = $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/';
        }else{
            $this->returnUrl = $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/'; //혹시모르니까
        }
    
        DB::table('documents')->where('id', $docId)->increment('comment_count');
    
        return $response->withHeader('Location', $this->returnUrl)->withStatus(302);
    }

    public function edit(Request $request, Response $response, $args) 
    {
        $docNum = $args['id'];
        $isShort = $args['is_short'] ?? false;
        $data = $request->getParsedBody();

        if(!$isShort){
            $group = DB::table('groups')->where('slug', $args['group_slug'])->first();
        }else{
            $group = DB::table('groups')->where('is_default', 1)->first();
        }

        $menu = DB::table('menus')->where('group_id', $group->id)->where('slug', $args['menu_slug'])->first();
        $board = DB::table('boards')->find($menu->target_id);

        $check = DB::table('documents')->where('board_id', $board->id)->where('doc_num', $docNum)->first();
        if (!$check) return $response; // 에러처리 생략
        
        if($check->user_id !== ($_SESSION['user_idx'] ?? 0) && ($_SESSION['level'] ?? 0) != 10) {
            $_SESSION['flash_message'] = '수정 권한이 없습니다.';
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
        }

        $customData = [];
        $rawCustom = $data['custom'] ?? [];
        $definedFields = $board->custom_fields ? json_decode($board->custom_fields, true) : [];
        foreach ($definedFields as $field) {
            $fieldName = $field['name'];
            $val = $rawCustom[$fieldName] ?? null;
            if (is_array($val)) $val = implode(',', $val);
            $customData[$fieldName] = $val;
        }
        $jsonCustomData = !empty($customData) ? json_encode($customData, JSON_UNESCAPED_UNICODE) : null;

        $content = trim($data['content']);
        $content = cleanHtml($content);

        if($isShort){
            $this->returnUrl = $this->basePath . '/' . $args['menu_slug'] . '/' . $docNum;
        }else{
            $this->returnUrl = $this->basePath . '/au/' . $args['group_slug'] . '/' . $args['menu_slug'] . '/' . $docNum;
        }

        DB::table('documents')
            ->where('doc_num', $docNum)
            ->where('board_id', $board->id)
            ->update([
                'title' => trim($data['subject']),
                'content' => $content,
                'custom_data' => $jsonCustomData,
                'is_notice' => isset($data['is_notice']) ? 1 : 0,
                'is_secret' => isset($data['is_secret']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return $response->withHeader('Location', $this->returnUrl)->withStatus(302);
    }

    public function getEdit(Request $request, Response $response, $args)
    {
        $groupSlug = $args['group_slug'] ?? "";
        $menuSlug = $args['menu_slug'];
        $id = $args['id'];

        if($groupSlug != ""){
            $group = DB::table('groups')->where('slug', $groupSlug)->first();
            $mainUrl = $this->basePath . '/au/' . $groupSlug;
        }else{
            $group = DB::table('groups')->where('is_default', 1)->first();
            $mainUrl = $this->basePath;
        }

        if (!$group) {
                $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
            };

        $menu = DB::table('menus')
            ->where('group_id', $group->id)
            ->where('slug', $menuSlug)
            ->first();

        if (!$menu) {
                $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
            };

        $themeUrl = $this->basePath . '/public/themes/' . $group->theme;

        if ($menu->type === 'board') {
            $board = DB::table('boards')->find($menu->target_id);
            $document = DB::table('documents')
            ->where('board_id', $board->id)
            ->where('doc_num', $id) ->first();

            $myId = $_SESSION['user_idx'] ?? null;
            $myLevel = $_SESSION['level'] ?? 0;
            
            if (!$document || $document->is_deleted == 1 || ($document->user_id != $myId && $myLevel < 10)) {
                $_SESSION['flash_message'] = '수정 권한이 없습니다.';
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
            }

            $themeLayout = ($group->theme ?? 'basic') . ".layout";
            $skinView = 'board.' . ($board->board_skin ?? 'basic') . '.edit';
            $skinUrl = (object) array(
                'documents' => $this->basePath . '/public/skins/board/' . $board->board_skin
            );

            $content = $this->blade->render($skinView, [
                'themeLayout' => $themeLayout,
                'themeUrl' => $themeUrl,
                'skinUrl' => $skinUrl,
                'group' => $group,
                'board' => $board,
                'document' => $document,
                'currentUrl' => $this->basePath . '/au/' . $groupSlug . '/' . $menuSlug . '/' . $id
            ]);
            $response->getBody()->write($content);
            return $response;
        }

        elseif ($menu->type === 'character') {
            $char = DB::table('characters')->find($id);
            $board = DB::table('boards')->find($menu->target_id);

            if (!$board) {
                $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
            };

            $myLevel = $_SESSION['level'] ?? 0;
            if ($myLevel < $board->write_level) {
                $_SESSION['flash_message'] = "쓰기 권한이 없습니다.";
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
            }

            if(!empty($char) && $char->user_id != $_SESSION['user_idx']){
                $_SESSION['flash_message'] = "수정 권한이 없습니다.";
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
            }

            $skinName = $board->board_skin ?? 'basic';
            $skinViewPath = 'character.' . $skinName;

            $themeLayout = ($group->theme ?? 'basic') . ".layout";
            $currentUrl = $mainUrl . "/$menuSlug";
            $skinUrl = (object) array(
                'documents' => $this->basePath . '/public/skins/board/' . $board->board_skin
            );


            $content = $this->blade->render( $skinViewPath . '.write', [
                'themeLayout' => $themeLayout,
                'themeUrl' => $themeUrl,
                'skinUrl' => $skinUrl,
                'mainUrl' => $mainUrl,
                'group' => $group,
                'mode' => 'edit',
                'character' => $char,
                'profile' => $char->profile_data ? json_decode($char->profile_data, true) : [],
                'actionUrl' => $currentUrl . '/' . $id . '/update',
                'currentUrl' => $currentUrl
            ]);
            $response->getBody()->write($content);
            return $response;
        }

        {
            $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/')->withStatus(302);
        };
    }

    private function savePost($request, $board, $menu) 
    {
        $data = $request->getParsedBody();
        DB::connection()->transaction(function () use ($data, $board, $menu) {
            $title = isset($data['subject']) ? trim($data['subject']) : "";
            $content = $data['content'];
            $isNotice = isset($data['is_notice']) ? 1 : 0;
            $isSecret = isset($data['is_secret']) ? 1 : 0;
        
            $customData = [];
            $rawCustom = $data['custom'] ?? [];
        
            $myLevel = $_SESSION['level'] ?? 0;
            if ($myLevel < $board->write_level) {
                return ['success' => false, 'message' => '글 쓰기 권한이 없습니다.'];
            }
    
            if($board->use_secret < 1){
                $isSecret = 0;
            }
        
            $content = cleanHtml($content);
            
            $definedFields = $board->custom_fields ? json_decode($board->custom_fields, true) : [];
        
            foreach ($definedFields as $field) {
                $fieldName = $field['name'];
                $val = $rawCustom[$fieldName] ?? null;
        
                if (is_array($val)) {
                    $val = implode(',', $val);
                }
                
                if (!empty($field['required']) && empty($val)) {
                     return ['success' => false, 'message' => $fieldName . ' 필드는 필수입니다.'];
                }
        
                $customData[$fieldName] = $val;
            }
        
            $jsonCustomData = !empty($customData) ? json_encode($customData, JSON_UNESCAPED_UNICODE) : null;
        
            $userId = $_SESSION['user_idx'] ?? 0;
            $nickname = $_SESSION['nickname'] ?? '손님';
            $ip = $_SERVER['REMOTE_ADDR'];
        
            $seq = DB::table('board_sequences')
                     ->where('board_id', $board->id)
                     ->lockForUpdate()
                     ->first();
        
            if (!$seq) {
                DB::table('board_sequences')->insert(['board_id' => $board->id, 'last_num' => 0]);
                $nextNum = 1;
            } else {
                $nextNum = $seq->last_num + 1;
            }
        
            DB::table('documents')->insert([
                'group_id' => $menu->group_id,
                'board_id' => $board->id,
                'doc_num' => $nextNum,
                'user_id' => $userId,
                'nickname' => $nickname,
                'title' => $title,
                'content' => $content,
                'custom_data' => $jsonCustomData,
                'is_notice' => $isNotice,
                'is_secret' => $isSecret,
                'hit' => 0,
                'comment_count' => 0,
                'ip_address' => $ip,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        
            DB::table('board_sequences')
              ->where('board_id', $board->id)
              ->update(['last_num' => $nextNum]);
        });
    
    
        return ['success' => true];
    }

    private function saveLoadPost($request, $board, $menu)  
    {
        $data = $request->getParsedBody();
    
        DB::connection()->transaction(function () use ($request, $data, $board, $menu) {
    
            $title = "로드 게시물";
            $content = "";
            $isSecret = isset($data['is_secret']) ? 1 : 0;
            
            $reply = isset($data['reply']) ? $data['reply'] : "";
    
            $uploadedFiles = $request->getUploadedFiles();
            $fileInputName = 'content';
    
            if (isset($uploadedFiles[$fileInputName]) && $uploadedFiles[$fileInputName]->getError() === UPLOAD_ERR_OK) {
                $file = $uploadedFiles[$fileInputName];
                
                $uploadDir = __DIR__ . '/../public/data/uploads/images';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $filename = uniqid() . '_' . $file->getClientFilename();
                $file->moveTo($uploadDir . '/' . $filename);
                
                $content = '/public/data/uploads/images/' . $filename; 
            }
    
            $userId = $_SESSION['user_idx'] ?? 0;
            $nickname = $_SESSION['nickname'] ?? '손님';
            $ip = $_SERVER['REMOTE_ADDR'];
    
            $replyCnt = ($reply != "" ? 1 : 0);
    
            $seq = DB::table('board_sequences')
                     ->where('board_id', $board->id)
                     ->lockForUpdate()
                     ->first();
        
            if (!$seq) {
                DB::table('board_sequences')->insert(['board_id' => $board->id, 'last_num' => 0]);
                $nextNum = 1;
            } else {
                $nextNum = $seq->last_num + 1;
            }
        
            $docId = DB::table('documents')->insertGetId([
                'group_id' => $menu->group_id,
                'board_id' => $board->id,
                'user_id' => $userId,
                'nickname' => $nickname,
                'title' => $title,
                'content' => $content,
                'custom_data' => null,
                'is_notice' => 0,
                'is_secret' => $isSecret,
                'hit' => 0,
                'comment_count' => $replyCnt,
                'ip_address' => $ip,
                'created_at' => date('Y-m-d H:i:s')
            ]);
    
            if ($reply != "") {
                $reply = cleanHtml($reply);
                DB::table('comments')->insert([
                    'board_id' => $board->id,
                    'doc_id' => $docId,
                    'user_id' => $userId,
                    'nickname' => $nickname,
                    'content' => trim($reply),
                    'ip_address' => $ip,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        
            DB::table('board_sequences')
              ->where('board_id', $board->id)
              ->update(['last_num' => $nextNum]);
        });
        
    
        return ['success' => true];
    
    }

    private function getPluginId($target, $id){
        $plugins = DB::table('plugin_meta')
        ->where('target_type', $target)
        ->where('target_id', $id)
        ->first();
        return $plugins->value ?? '';
    }
    

}