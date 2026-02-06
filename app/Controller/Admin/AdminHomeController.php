<?php
namespace App\Controller\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Services\VersionService;

class AdminHomeController {

    protected $blade;
    protected $basePath;
    protected $returnUrl;

    public function __construct($blade, $basePath)
    {
        $this->blade = $blade;
        $this->basePath = $basePath;
        $this->returnUrl = "";
    }

    public function index(Request $request, Response $response) {

        $docs = DB::table('documents')
            ->join('menus', function($join) {
                $join->on('documents.board_id', '=', 'menus.target_id')
                    ->on('documents.group_id', '=', 'menus.group_id')
                    ->whereIn('menus.type', array('board', 'load'));
            })
            ->where('documents.is_deleted', 0)
            ->where('menus.is_deleted', 0)
            ->where('documents.is_secret', 0)
            ->select(
                'documents.title as subject',
                'documents.created_at',
                'menus.slug as menu_slug',
                'documents.id as doc_id',
                'documents.doc_num as doc_num',
                'menus.type as menu_type',
                DB::raw("NULL as comment_id"),
                DB::raw("'doc' as type")
            );

        $comments = DB::table('comments')
            ->join('documents', 'comments.doc_id', '=', 'documents.id')
            ->join('menus', function($join) {
                $join->on('documents.board_id', '=', 'menus.target_id')
                    ->on('documents.group_id', '=', 'menus.group_id')
                    ->whereIn('menus.type', array('board', 'load'));
            })
            ->where('comments.is_deleted', 0)
            ->where('menus.is_deleted', 0)
            ->where('documents.is_secret', 0)
            ->where('documents.is_deleted', 0)
            ->select(
                'comments.content as subject',
                'comments.created_at',
                'menus.slug as menu_slug',
                'documents.id as doc_id',
                'documents.doc_num as doc_num',
                'menus.type as menu_type',
                'comments.id as comment_id',
                DB::raw("'cmt' as type")
            );

        $items = $docs->unionAll($comments)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $users = DB::table('users')
            ->where('is_deleted', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $groups = DB::table('groups')
            ->where('is_deleted', 0)
            ->orderBy('created_at', 'desc')
            ->first();

        $vService = new VersionService();
        $updateInfo = $vService->checkUpdate();
        $content = $this->blade->render('admin.index', [
            'title' => '관리자 페이지',
            'updateInfo' => $updateInfo,
            'board' => $items,
            'user' => $users,
            'group' => $groups
        ]);
        $response->getBody()->write($content);
        return $response;
    }

    public function issecret(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $isSecret = (int) $data['is_secret'];
        DB::table('groups')
            ->where('is_deleted', 0)
            ->update([
                'is_secret' => $isSecret,
            ]);

        $_SESSION['flash_message'] = '변경되었습니다.';
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $this->basePath . '/admin')->withStatus(302);
    }

    public function ismemouse(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $isMemoUse = (int) $data['is_memo_use'];
        DB::table('groups')
            ->where('is_deleted', 0)
            ->update([
                'is_memo_use' => $isMemoUse,
            ]);

        $_SESSION['flash_message'] = '변경되었습니다.';
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $this->basePath . '/admin')->withStatus(302);
    }
}