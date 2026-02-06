<?php
namespace App\Controller\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;

class AdminStructureController {

    protected $blade;
    protected $basePath;
    protected $returnUrl;

    public function __construct($blade, $basePath)
    {
        $this->blade = $blade;
        $this->basePath = $basePath;
        $this->returnUrl = "";
    }

    public function groupList(Request $request, Response $response) {
        $groups = DB::table('groups')
        ->where('is_deleted', 0)
        ->orderBy('created_at', 'desc')
        ->get();

        $content = $this->blade->render('admin.groups.index', [
            'title' => '커뮤니티 그룹 관리',
            'group' => $groups
        ]);
        $response->getBody()->write($content);
        return $response;
    }

    public function groupEdit(Request $request, Response $response, $args) {
        $id = $args['id'];
            
        $groupData = DB::table('groups')->where('id', $id)->where('is_deleted', 0)->first();
        
        if (!$groupData) {
            $_SESSION['flash_message'] = '존재하지 않는 그룹입니다.';
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/admin/groups')->withStatus(302);
        }

        //커스텀페이지 생성을 위한...
        $pageData = DB::table('boards')->where('type', 'page')->where('is_deleted', 0)->get();

        $themeDir = __DIR__ . '/../../../public/themes';
        $themes = [];
        if (is_dir($themeDir)) {
            $folders = scandir($themeDir);
            foreach ($folders as $folder) {
                if ($folder === '.' || $folder === '..') continue;
                if (!is_dir($themeDir . '/' . $folder)) continue;
    
                $themeInfo = [
                    'id' => $folder,
                    'name' => $folder,
                    'description' => '설명 파일이 없습니다.',
                    'thumb' => '/img/no_image.png',
                ];
    
                $configFile = $themeDir . '/' . $folder . '/theme.json';
                if (file_exists($configFile)) {
                    $config = json_decode(file_get_contents($configFile), true);
                    if ($config) {
                        $themeInfo['name'] = $config['name'] ?? $folder;
                        $themeInfo['description'] = $config['description'] ?? '';
                    }
                }
    
                $thumbFile = $themeDir . '/' . $folder . '/thumb.png';
                if (file_exists($thumbFile)) {
                    $themeInfo['thumb'] = $this->basePath . '/themes/' . $folder . '/thumb.png';
                } else {
                    $themeInfo['thumb'] = null; 
                }
    
                $themes[] = $themeInfo;
            }
        }
    
        $content = $this->blade->render('admin.groups.edit', [
            'title' => '그룹 상세 설정',
            'group' => $groupData,
            'page' => $pageData,
            'themes' => $themes
        ]);
        $response->getBody()->write($content);
        return $response;
    }

    public function groupStore(Request $request, Response $response) {
        $data = $request->getParsedBody();
            
        $name = trim($data['name']);
        $slug = trim($data['slug']);
        $theme = 'basic';
        $isDefault = isset($data['is_default']) ? 1 : 0;

        if (!$name || !$slug) {
            $_SESSION['flash_message'] = '그룹 이름과 ID는 필수입니다.';
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/admin/groups')->withStatus(302);
        }

        $exists = DB::table('groups')->where('slug', $slug)->exists();
        if ($exists || in_array($slug, ['admin', 'login', 'register', 'logout', 'au', 'page', 'memo', 'plugin', 'comment', 'shop'])) {
            $_SESSION['flash_message'] = '사용할 수 없는 그룹 ID입니다.';
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/admin/groups')->withStatus(302);
        }

        if ($isDefault) {
            DB::table('groups')->update(['is_default' => 0]);
        }

        DB::table('groups')->insert([
            'name' => $name,
            'slug' => $slug,
            'theme' => $theme,
            'is_default' => $isDefault,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $response->withHeader('Location', $this->basePath . '/admin/groups')->withStatus(302);

    }

    public function groupDelete(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $id = $data['id'];

        $target = DB::table('groups')->where('id', $id)->where('is_deleted', 0)->first();
        if ($target && $target->is_default == 1) {
            $_SESSION['flash_message'] = '대표 커뮤니티 그룹은 삭제할 수 없습니다.';
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/admin/groups')->withStatus(302);
        }

        $group = DB::table('groups')->where('id', $id)->where('is_deleted', 0)->first();
        if ($group) {
            $newSlug = $group->slug . '_deleted_' . time();
            DB::table('groups')
                ->where('id', $id)
                ->update([
                    'is_deleted' => 1,
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'slug'       => $newSlug
                ]);
        }

        return $response->withHeader('Location', $this->basePath . '/admin/groups')->withStatus(302);
    }

    public function groupUpdate(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();
        
        $id = $data['id'];
        $name = trim($data['name']);
        $description = trim($data['description']);
        $pointName = trim($data['point_name']);
        $theme = trim($data['theme']);
        $isDefault = isset($data['is_default']) ? 1 : 0;
        $useNotification = isset($data['use_notification']) ? 1 : 0;
        $isCustomMain = isset($data['use_custom_main']) ? 1 : 0;
        $useCustomMain = isset($data['custom_main_id']) ? $data['custom_main_id'] : 0;

        if ($isDefault) {
            DB::table('groups')
                ->where('id', '!=', $id)
                ->update(['is_default' => 0]);
        }

        if (!$isCustomMain) {
            $useCustomMain = 0;
        }

        $uploadDir = __DIR__ . '/../../../public/data';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $updateData = [
            'name' => $name,
            'description' => $description,
            'point_name' => $pointName,
            'theme' => $theme,
            'is_default' => $isDefault,
            'use_notification' => $useNotification,
            'custom_main_id' => $useCustomMain
        ];

        if (isset($uploadedFiles['favicon']) && $uploadedFiles['favicon']->getError() === UPLOAD_ERR_OK) {
            $file = $uploadedFiles['favicon'];
            $filename = 'favicon_' . $id . '_' . uniqid() . '.' . pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
            $file->moveTo($uploadDir . '/' . $filename);
            $updateData['favicon'] = '/public/data/' . $filename;
        }

        if (isset($uploadedFiles['og_image']) && $uploadedFiles['og_image']->getError() === UPLOAD_ERR_OK) {
            $file = $uploadedFiles['og_image'];
            $filename = 'og_' . $id . '_' . uniqid() . '.' . pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
            $file->moveTo($uploadDir . '/' . $filename);
            $updateData['og_image'] = '/public/data/' . $filename;
        }


        DB::table('groups')
            ->where('id', $id)
            ->update($updateData);

        return $response->withHeader('Location', $this->basePath . '/admin/groups/' . $id)->withStatus(302);

    }

    public function menuList(Request $request, Response $response) {
        $groups = DB::table('groups')
        ->where('is_deleted', 0)
        ->orderBy('created_at', 'desc')
        ->get();

        $content = $this->blade->render('admin.menus.index', [
            'title' => '메뉴 관리 - 그룹 선택',
            'group' => $groups
        ]);
        $response->getBody()->write($content);
        return $response;
    }

    public function menuEdit(Request $request, Response $response, $args) {
        $groupId = $args['group_id'];
        $group = DB::table('groups')->find($groupId);

        if (!$group) {
            $_SESSION['flash_message'] = '존재하지 않는 그룹입니다.';
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/admin/menus')->withStatus(302);
        }

        $menus = DB::table('menus')
            ->select('menus.*', 'boards.title as board_title')
            ->leftJoin('boards', 'menus.target_id', '=', 'boards.id')
            ->where('menus.group_id', $groupId)
            ->where('menus.is_deleted', 0)
            ->orderBy('menus.order_num', 'asc')
            ->get();

        $shops = DB::table('shops')
            ->where('group_id', $groupId)
            ->where('is_deleted', 0)
            ->get();

        $allBoards = DB::table('boards')->where('is_deleted', 0)->get();

        $content = $this->blade->render('admin.menus.manage', [
            'title' => $group->name . ' 메뉴 구성',
            'group' => $group,
            'menus' => $menus,
            'shops' => $shops,
            'allBoards' => $allBoards
        ]);
        $response->getBody()->write($content);
        return $response;

    }

    public function menuStore(Request $request, Response $response) {
        $data = $request->getParsedBody();
            
        $groupId = $data['group_id'];
        $slug = $data['slug'] ? trim($data['slug']) : "";
        $menuType = $data['type'];
        $targetId = (int)($data['target_id'] ?? 0);
        $shopId = (int)($data['shop_id'] ?? 0);
        $targetUrl = ($data['target_url'] ?? "");
        $title = trim($data['title']);

        if(!$groupId){
            if($targetId == 0 || $slug == ""){
                $_SESSION['flash_message'] = '필수값이 누락되었습니다.';
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $this->basePath . "/admin/menus/$groupId")->withStatus(302);
            }
        }

        switch ($menuType) {
            case 'link' :{
                if (empty($targetUrl)) {
                    $_SESSION['flash_message'] = '필수값이 누락되었습니다.';
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $this->basePath . "/admin/menus/$groupId")->withStatus(302);
                }
                $slug = "";
                break;
            }
            case 'shop': {
                if (empty($shopId)) {
                    $_SESSION['flash_message'] = '필수값이 누락되었습니다.';
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $this->basePath . "/admin/menus/$groupId")->withStatus(302);
                }
                $targetId = $shopId;
                $slug = "";
                break;
            }
            default: {
                $exists = DB::table('menus')
                ->where('group_id', $groupId)
                ->where('slug', $slug)
                ->exists();
    
                if($targetId == 0 || $slug == ""){
                    $_SESSION['flash_message'] = '필수값이 누락되었습니다.';
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $this->basePath . "/admin/menus/$groupId")->withStatus(302);
                }
    
                if ($exists) {
                    $_SESSION['flash_message'] = '이미 사용 중인 주소입니다.';
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $this->basePath . "/admin/menus/$groupId")->withStatus(302);
                }
    
                if (in_array($slug, ['admin', 'login', 'register', 'logout', 'au', 'page', 'memo', 'plugin', 'comment', 'shop'])) {
                    $_SESSION['flash_message'] = '사용할 수 없는 주소입니다.';
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $this->basePath . "/admin/menus/$groupId")->withStatus(302);
                }
            }
        }

        DB::table('menus')->insert([
            'group_id' => $groupId,
            'type' => $menuType,
            'target_id' => $targetId,
            'target_url' => $targetUrl,
            'title' => $title,
            'slug' => $slug,
            'order_num' => 0
        ]);

        $_SESSION['flash_message'] = '메뉴가 추가되었습니다.';
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $this->basePath . "/admin/menus/$groupId")->withStatus(302);

    }

    public function menuDelete(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $menuId = $data['menu_id'];
        $groupId = $data['group_id'];
        $menu = DB::table('menus')->find($menuId);
        if ($menu) {
            $newSlug = $menu->slug . '_deleted_' . time();
            DB::table('menus')
                ->where('id', $menuId)
                ->update([
                    'is_deleted' => 1,
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'slug'       => $newSlug
                ]);
        }
        
        $_SESSION['flash_message'] = '메뉴가 삭제되었습니다.';
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $this->basePath . "/admin/menus/$groupId")->withStatus(302);
    }

    public function menuReorder(Request $request, Response $response) {
        $data = json_decode($request->getBody(), true);
        $orderList = $data['order'] ?? [];

        if (!empty($orderList)) {
            foreach ($orderList as $index => $menuId) {
                DB::table('menus')
                    ->where('id', $menuId)
                    ->update(['order_num' => $index + 1]);
            }
        }

        $payload = json_encode(['success' => true]);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');

    }

}