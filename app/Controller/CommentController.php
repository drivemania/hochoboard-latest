<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;

class CommentController {
    protected $blade;
    protected $basePath;
    protected $returnUrl;

    public function __construct($blade, $basePath)
    {
        $this->blade = $blade;
        $this->basePath = $basePath;
        $this->returnUrl = "";
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

        $content = trim(cleanHtml($data['content']));

        $summonArr = [];

        if (preg_match_all('/\[\[(.*?)\]\]/', $content, $matches)) {
            foreach ($matches[1] as $value) {
                if(!in_array($value, $summonArr)) $summonArr[] = $value;
            }
        }

        $data = \App\Support\Hook::filter('before_comment_save', $data);
    
        $cmtId = DB::table('comments')->insertGetId([
            'board_id' => $doc->board_id,
            'doc_id' => $docId,
            'user_id' => $_SESSION['user_idx'] ?? 0,
            'nickname' => $_SESSION['nickname'] ?? '손님',
            'content' => $content,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $after = \App\Support\Hook::filter('after_comment_save', $cmtId);
        if($isShort){
            $this->returnUrl = "/{$menu->slug}/{$doc->doc_num}#comment_{$cmtId}";
        }else{
            $group = DB::table('groups')->find($menu->group_id);
            $this->returnUrl = "/au/{$group->slug}/{$menu->slug}/{$doc->doc_num}#comment_{$cmtId}";
        }
        
        //호출, 앵커 체크
        foreach ($summonArr as $value) {
            $summon = DB::table('users')->where('nickname', trim($value))->where('is_deleted', 0)->first();
            if($summon){
                $summmonMsg = $content;
                $summonUrl = $this->returnUrl;

                $this->setNotification($menu->group_id, $summon->id, $_SESSION['user_idx'], $summmonMsg, $summonUrl);
            }
        }
        
    
        DB::table('documents')->where('id', $docId)->increment('comment_count');
    
        return $response->withHeader('Location', $this->basePath . $this->returnUrl)->withStatus(302);
    }

    public function update(Request $request, Response $response){
        $data = $request->getParsedBody();
        $cmtId = $data['comment_id'];
        $isShort = $args['is_short'] ?? false;
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

        $summonArr = [];

        if (preg_match_all('/\[\[(.*?)\]\]/', $content, $matches)) {
            foreach ($matches[1] as $value) {
                if(!in_array($value, $summonArr)) {
                    preg_match_all('/\[\[(.*?)\]\]/', $comment->content, $matches2);
                    if(!empty($matches2) && !in_array($value, $matches2[1])){
                        $summonArr[] = $value;
                    }
                }
            }
        }
    
        $content = \App\Support\Hook::filter('before_comment_save', ['content' => $content]);
        $content = cleanHtml($content['content']);
    
        DB::table('comments')
            ->where('id', $cmtId)
            ->update([
                'content' => $content
            ]);
    
        $after =  \App\Support\Hook::filter('after_comment_save', $cmtId);

        //호출, 앵커 체크
        foreach ($summonArr as $value) {
            $document = DB::table('documents')->where('id', $comment->doc_id)->where('is_deleted', 0)->first();
            $board = DB::table('boards')->where('id', $document->board_id)->where('is_deleted', 0)->first();
            $menu = DB::table('menus')->where('target_id', $board->id)->where('group_id', $document->group_id)->where('is_deleted', 0)->first();
            $summon = DB::table('users')->where('nickname', trim($value))->where('is_deleted', 0)->first();
            if($summon){
                $summmonMsg = $content;
                if($isShort){
                    $summonUrl = "/{$menu->slug}/{$document->doc_num}#comment_{$cmtId}";
                }else{
                    $group = DB::table('groups')->find($menu->group_id);
                    $summonUrl = "/au/{$group->slug}/{$menu->slug}/{$document->doc_num}#comment_{$cmtId}";
                }

                $this->setNotification($menu->group_id, $summon->id, $_SESSION['user_idx'], $summmonMsg, $summonUrl);
            }
        }
    
        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
    }

    public function delete(Request $request, Response $response){
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
    }

    private function setNotification($groupId, $receiverId, $senderId, $message, $url) {
        $message = preg_replace('/\r\n|\r|\n/', '', $message);
        if( mb_strlen($message) > 255 ){
            $message = mb_substr($message, 0, 252) . '...';
        }
        DB::table('notifications')->insert([
            'group_id' => $groupId,
            'user_id' => $receiverId,
            'sender_id' => $senderId,
            'type' => 'document',
            'message' => $message,
            'url' => $url,
            'is_viewed' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}