<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;

class MemberController {
    protected $blade;
    protected $basePath;

    public function __construct($blade, $basePath) {
        $this->blade = $blade;
        $this->basePath = $basePath;
    }

    public function index(Request $request, Response $response) {

        if (!isset($_SESSION['user_idx'])) {
            $_SESSION['flash_message'] = "로그인이 필요합니다.";
            $_SESSION['flash_type'] = "error";
            return $response->withHeader('Location', $this->basePath . '/login')->withStatus(302);
        }

        $prefix = DB::connection()->getTablePrefix();

        $userId = $_SESSION['user_idx'];

        $user = DB::table('users')->find($userId);

        $notifications = DB::table('notifications')
            ->where('user_id', $userId)
            ->where('type', '!=', 'memo')
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'noti_page');

        $shopQuery = DB::table('shop_purchase_logs')
            ->join('items', 'shop_purchase_logs.shop_item_id', '=', 'items.id')
            ->where('shop_purchase_logs.user_id', $userId)
            ->select([
                DB::raw("'shop' as log_type"),
                'items.name as title',
                DB::raw("{$prefix}shop_purchase_logs.quantity as quantity_or_desc"),
                DB::raw("({$prefix}shop_purchase_logs.price_at_purchase * -1) as point_change"),
                'shop_purchase_logs.purchased_at as created_at'
            ]);

        $settleQuery = DB::table('settlement_logs')
            ->whereJsonContains('target_list', [['user_id' => $userId]]) 
            ->select([
                DB::raw("'settlement' as log_type"),     
                'reason as title',                       
                DB::raw("CASE WHEN items_json IS NOT NULL THEN '아이템 포함' ELSE '-' END as quantity_or_desc"), 
                'point_amount as point_change',          
                'created_at'
            ]);

        $history = $shopQuery->union($settleQuery)
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'history_page');

        $documents = DB::table('documents')
            ->leftJoin('boards', 'documents.board_id', '=', 'boards.id')
            ->leftJoin('menus', function($join) {
                $join->on('documents.board_id', '=', 'menus.target_id')
                    ->on('menus.group_id', '=', 'documents.group_id')
                     ->whereIn('menus.type', ['load']);
            })
            ->where('documents.user_id', $userId)
            ->where('documents.is_deleted', 0)
            ->where('menus.is_deleted', 0)
            ->select(
                'documents.*', 
                'menus.slug as menu_slug'
            )
            ->orderBy('documents.id', 'desc')
            ->paginate(10, ['*'], 'doc_page');


        foreach ($documents as $doc) {
            if ($doc->group_id) {
                $group = DB::table('groups')->find($doc->group_id);
                $doc->group_slug = $group ? $group->slug : '';
                $comments = DB::table('comments')
                    ->leftJoin('characters', function($join) use ($doc) {
                        $join->on('comments.user_id', '=', 'characters.user_id')
                            ->where('characters.group_id', $doc->group_id)
                            ->where('characters.is_main', 1);
                    })
                    ->where('comments.doc_id', $doc->id)
                    ->select(
                        'comments.*', 
                        'characters.name as char_name'
                    )
                    ->orderBy('comments.id', 'desc')
                    ->limit(3)->get();
                $doc->comments = $comments;
            }
        }

        $characters = DB::table('characters')
            ->join('groups', 'characters.group_id', '=', 'groups.id')
            ->leftJoin('menus', function($join) {
                $join->on('characters.board_id', '=', 'menus.target_id')
                    ->on('menus.group_id', '=', 'characters.group_id')
                    ->where('menus.type', 'character')
                    ->where('menus.is_deleted', 0);
            })
            ->where('characters.user_id', $userId)
            ->where('characters.is_deleted', 0)
            ->select(
                'characters.*', 
                'groups.name as group_name', 
                'groups.slug as group_slug',
                'menus.slug as menu_slug'
            )
            ->get();

        $mainGroup = DB::table('groups')
            ->where('is_default', 1)
            ->where('is_deleted', 0)
            ->first();
        $themeName = $mainGroup->theme ?? 'basic';
        $themeLayout = $themeName . ".layout";
        $themeUrl = $this->basePath . '/public/themes/' . $mainGroup->theme;
        $mainUrl = $this->basePath . '/';

        $content = $this->blade->render('member.info', [
            'user' => $user,
            'group' => $mainGroup,
            'notifications' => $notifications,
            'history' => $history,
            'documents' => $documents,
            'characters' => $characters,
            'currentTab' => $_GET['tab'] ?? 'noti',
            'themeLayout' => $themeLayout,
            'themeUrl' => $themeUrl,
            'mainUrl' => $mainUrl,
        ]);

        $response->getBody()->write($content);
        return $response;
    }

    public function updatePassword(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $userId = $_SESSION['user_idx'];

        $user = DB::table('users')->find($userId);
        
        if (!password_verify($data['current_password'], $user->password)) {
            $_SESSION['flash_message'] = "현재 비밀번호가 일치하지 않습니다.";
            $_SESSION['flash_type'] = "error";
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
        }

        DB::table('users')->where('id', $userId)->update([
            'password' => password_hash($data['new_password'], PASSWORD_DEFAULT)
        ]);

        $_SESSION['flash_message'] = "비밀번호가 변경되었습니다.";
        $_SESSION['flash_type'] = "success";
        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
    }
}