<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use Slim\Routing\RouteCollectorProxy;
use Slim\Psr7\Response as SlimResponse;
use App\Services\VersionService;
use App\Controller\AdminPluginController;
use App\Middleware\AdminMiddleware;

$basePath = $app->getBasePath();

$adminPluginController = new AdminPluginController($blade, $basePath);
$adminMiddleware = new AdminMiddleware($basePath);

function getSkinList($basePath, $type = 'document') {
    $skinDir = __DIR__ . '/../public/skins/'.$type;
    $skins = [];

    if (is_dir($skinDir)) {
        $folders = scandir($skinDir);
        foreach ($folders as $folder) {
            if ($folder === '.' || $folder === '..') continue;
            if (!is_dir($skinDir . '/' . $folder)) continue;

            $skinInfo = [
                'id' => $folder,
                'name' => $folder,
                'description' => '설명 파일이 없습니다.'
            ];

            $configFile = $skinDir . '/' . $folder . '/skin.json';
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                if ($config) {
                    $skinInfo['name'] = $config['name'] ?? $folder;
                    $skinInfo['description'] = $config['description'] ?? '';
                }
            }

            $skins[] = $skinInfo;
        }
    }
    return $skins;
}

$app->group('/admin', function (RouteCollectorProxy $group) use ($blade, $basePath, $adminPluginController) {

    $group->get('', function (Request $request, Response $response) use ($blade) {

        $docs = DB::table('documents')
            ->join('menus', function($join) {
                $join->on('documents.board_id', '=', 'menus.target_id')
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
            ->limit(5)
            ->get();

        $groups = DB::table('groups')
            ->where('is_deleted', 0)
            ->orderBy('created_at', 'desc')
            ->first();

        $vService = new VersionService();
        $updateInfo = $vService->checkUpdate();
        $content = $blade->render('admin.index', [
            'title' => '관리자 페이지',
            'updateInfo' => $updateInfo,
            'board' => $items,
            'user' => $users,
            'group' => $groups
        ]);
        $response->getBody()->write($content);
        return $response;
    });

    $group->post('/issecret', function ($request, $response) use ($basePath){
        $data = $request->getParsedBody();
        $isSecret = (int) $data['is_secret'];
        DB::table('groups')
            ->where('is_deleted', 0)
            ->update([
                'is_secret' => $isSecret,
            ]);

        $_SESSION['flash_message'] = '변경되었습니다.';
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $basePath . '/admin')->withStatus(302);
    });

    $group->group('/system', function ($group)  use ($basePath) {
        $systemController = new \App\Controller\SystemController($basePath);
        $group->post('/clear-cache', [$systemController, 'clearViewCache']);
        $group->post('/clear-session', [$systemController, 'clearSession']);
    });

    $group->group('/groups', function (RouteCollectorProxy $group) use ($blade, $basePath) {

        $group->get('', function (Request $request, Response $response) use ($blade) {
            $groups = DB::table('groups')
                ->where('is_deleted', 0)
                ->orderBy('created_at', 'desc')
                ->get();

            $content = $blade->render('admin.groups.index', [
                'title' => '커뮤니티 그룹 관리',
                'group' => $groups
            ]);
            $response->getBody()->write($content);
            return $response;
        });

        $group->post('', function (Request $request, Response $response) use ($basePath) {
            $data = $request->getParsedBody();
            
            $name = trim($data['name']);
            $slug = trim($data['slug']);
            $theme = trim($data['theme']) ?: 'basic';
            $isDefault = isset($data['is_default']) ? 1 : 0;

            if (!$name || !$slug) {
                $_SESSION['flash_message'] = '그룹 이름과 ID는 필수입니다.';
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $basePath . '/admin/groups')->withStatus(302);
            }

            $exists = DB::table('groups')->where('slug', $slug)->exists();
            if ($exists || in_array($slug, ['admin', 'login', 'register', 'logout', 'au', 'page', 'memo', 'plugin', 'comment'])) {
                $_SESSION['flash_message'] = '사용할 수 없는 그룹 ID입니다.';
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $basePath . '/admin/groups')->withStatus(302);
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

            return $response->withHeader('Location', $basePath . '/admin/groups')->withStatus(302);
        });

        $group->post('/delete', function (Request $request, Response $response) use ($basePath) {
            $data = $request->getParsedBody();
            $id = $data['id'];

            $target = DB::table('groups')->where('id', $id)->where('is_deleted', 0)->first();
            if ($target && $target->is_default == 1) {
                $_SESSION['flash_message'] = '대표 커뮤니티 그룹은 삭제할 수 없습니다.';
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $basePath . '/admin/groups')->withStatus(302);
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

            return $response->withHeader('Location', $basePath . '/admin/groups')->withStatus(302);
        });

        $group->get('/{id}', function (Request $request, Response $response, $args) use ($blade, $basePath) {
            $id = $args['id'];
            
            $groupData = DB::table('groups')->where('id', $id)->where('is_deleted', 0)->first();
            
            if (!$groupData) {
                $_SESSION['flash_message'] = '존재하지 않는 그룹입니다.';
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $basePath . '/admin/groups')->withStatus(302);
            }

            //커스텀페이지 생성을 위한...
            $pageData = DB::table('boards')->where('type', 'page')->where('is_deleted', 0)->get();

            $themeDir = __DIR__ . '/../public/themes';
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
                        $themeInfo['thumb'] = $basePath . '/themes/' . $folder . '/thumb.png';
                    } else {
                        $themeInfo['thumb'] = null; 
                    }
        
                    $themes[] = $themeInfo;
                }
            }
        
            $content = $blade->render('admin.groups.edit', [
                'title' => '그룹 상세 설정',
                'group' => $groupData,
                'page' => $pageData,
                'themes' => $themes
            ]);
            $response->getBody()->write($content);
            return $response;
        });

        $group->post('/update', function (Request $request, Response $response) use ($basePath) {
            $data = $request->getParsedBody();
            $uploadedFiles = $request->getUploadedFiles();
            
            $id = $data['id'];
            $name = trim($data['name']);
            $description = trim($data['description']);
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
                $useCustomMain = "";
            }

            $uploadDir = __DIR__ . '/../public/data';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $updateData = [
                'name' => $name,
                'description' => $description,
                'theme' => $theme,
                'is_default' => $isDefault,
                'use_notification' => $useNotification,
                'custom_main_id' => $useCustomMain
            ];

            if (isset($uploadedFiles['favicon']) && $uploadedFiles['favicon']->getError() === UPLOAD_ERR_OK) {
                $file = $uploadedFiles['favicon'];
                $filename = 'favicon_' . $id . '_' . uniqid() . '.' . pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                $file->moveTo($uploadDir . '/' . $filename);
                $updateData['favicon'] = '/data/' . $filename;
            }

            if (isset($uploadedFiles['og_image']) && $uploadedFiles['og_image']->getError() === UPLOAD_ERR_OK) {
                $file = $uploadedFiles['og_image'];
                $filename = 'og_' . $id . '_' . uniqid() . '.' . pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                $file->moveTo($uploadDir . '/' . $filename);
                $updateData['og_image'] = '/data/' . $filename;
            }

            DB::table('groups')
                ->where('id', $id)
                ->update($updateData);

            return $response->withHeader('Location', $basePath . '/admin/groups/' . $id)->withStatus(302);
        });
    });
    $group->group('/boards', function (RouteCollectorProxy $group) use ($blade, $basePath) {

        $group->get('', function (Request $request, Response $response) use ($blade) {
            $boards = DB::table('boards')
                ->where('is_deleted', 0)
                ->orderBy('created_at', 'desc')
                ->get();

            $content = $blade->render('admin.boards.index', [
                'title' => '게시판 원본 관리',
                'boards' => $boards
            ]);
            $response->getBody()->write($content);
            return $response;
        });

        $group->post('', function (Request $request, Response $response) use ($basePath) {
            $data = $request->getParsedBody();
            
            $title = trim($data['title']);

            DB::table('boards')->insert([
                'title' => $title,
                'board_skin' => 'basic',
                'type' => $data['type'] ?? 'document',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $_SESSION['flash_message'] = '게시판이 생성되었습니다. 이제 그룹 메뉴 설정에서 연결하세요.';
            $_SESSION['flash_type'] = 'success';
            return $response->withHeader('Location', $basePath . '/admin/boards')->withStatus(302);
        });

        $group->get('/{id}', function (Request $request, Response $response, $args) use ($blade, $basePath) {
            $id = $args['id'];
            $board = DB::table('boards')->find($id);
        
            $boardSkins = getSkinList($basePath, 'board');
            $charSkins = getSkinList($basePath, 'character');
            $loadSkins = getSkinList($basePath, 'load');
            
            $content = $blade->render('admin.boards.edit', [
                'title' => '게시판 설정',
                'board' => $board,
                'boardSkins' => $boardSkins,
                'charSkins' => $charSkins,
                'loadSkins' => $loadSkins
            ]);
            $response->getBody()->write($content);
            return $response;
        });

        $group->post('/update', function (Request $request, Response $response) use ($basePath) {
            $data = $request->getParsedBody();
            $id = $data['id'];
            $customFields = $data['custom_fields'] ?? [];
            $notice = trim($data['notice']);

            $cleanFields = [];
            if (is_array($customFields)) {
                foreach ($customFields as $field) {
                    if (!empty($field['name'])) {
                        $cleanFields[] = [
                            'name' => trim($field['name']),
                            'type' => $field['type'],
                            'required' => isset($field['required']) ? 1 : 0,
                            'options' => trim($field['options'] ?? '')
                        ];
                    }
                }
            }
            
            $jsonFields = !empty($cleanFields) ? json_encode($cleanFields, JSON_UNESCAPED_UNICODE) : null;

            $notice = cleanHtml($notice);

            DB::table('boards')
                ->where('id', $id)
                ->update([
                    'title' => trim($data['title']),
                    'notice' => $notice,
                    'board_skin' => $data['board_skin'],
                    'list_count' => (int)$data['list_count'],
                    'read_level' => (int)$data['read_level'],
                    'write_level' => (int)$data['write_level'],
                    'comment_level' => (int)$data['comment_level'],
                    'use_secret' => isset($data['use_secret']) ? 1 : 0,
                    'use_editor' => isset($data['use_editor']) ? 1 : 0,
                    'custom_fields' => $jsonFields
                ]);

            $_SESSION['flash_message'] = '저장되었습니다.';
            $_SESSION['flash_type'] = 'success';
            return $response->withHeader('Location', $basePath . '/admin/boards/' . $id)->withStatus(302);
        });

        $group->post('/delete', function (Request $request, Response $response) use ($basePath) {
            $data = $request->getParsedBody();

            $menu = DB::table('menus')
            ->where('type', 'board')
            ->where('target_id', $data['id'])
            ->get();
        
            foreach ($menu as $menu) {
                $newSlug = $menu->slug . '_deleted_' . time() . '_' . $menu->id;
            
                DB::table('menus')
                    ->where('id', $menu->id)
                    ->update([
                        'is_deleted' => 1,
                        'deleted_at' => date('Y-m-d H:i:s'),
                        'slug'       => $newSlug
                    ]);
            }

            $board = DB::table('boards')->find($data['id']);
            if ($board) {
                DB::table('boards')
                    ->where('id', $data['id'])
                    ->update([
                        'is_deleted' => 1,
                        'deleted_at' => date('Y-m-d H:i:s')
                    ]);
            }
            
            $_SESSION['flash_message'] = '게시판이 삭제되었습니다.';
            $_SESSION['flash_type'] = 'success';
            return $response->withHeader('Location', $basePath . '/admin/boards')->withStatus(302);
        });

        $group->post('/copy', function (Request $request, Response $response) {
            $data = $request->getParsedBody();
            $originId = $data['board_id'];

            $origin = DB::table('boards')->find($originId);
            if (!$origin) {
                $_SESSION['flash_message'] = "원본 게시판을 찾을 수 없습니다.";
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? '/admin')->withStatus(302);
            }

            $newData = (array)$origin;
            
            unset($newData['id']);
            $newData['title'] = $origin->title . ' (복사본)';
            $newData['created_at'] = date('Y-m-d H:i:s');
            
            DB::table('boards')->insert($newData);

            $_SESSION['flash_message'] = "게시판이 복제되었습니다.";
            $_SESSION['flash_type'] = 'success';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? '/admin')->withStatus(302);
        });
    });
    $group->group('/menus', function (RouteCollectorProxy $group) use ($blade, $basePath) {

        $group->get('', function (Request $request, Response $response) use ($blade) {
            $groups = DB::table('groups')
                ->orderBy('created_at', 'desc')
                ->get();

            $content = $blade->render('admin.menus.index', [
                'title' => '메뉴 관리 - 그룹 선택',
                'group' => $groups
            ]);
            $response->getBody()->write($content);
            return $response;
        });

        $group->get('/{group_id}', function (Request $request, Response $response, $args) use ($blade, $basePath) {
            $groupId = $args['group_id'];
            $group = DB::table('groups')->find($groupId);

            if (!$group) {
                $_SESSION['flash_message'] = '존재하지 않는 그룹입니다.';
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $basePath . '/admin/menus')->withStatus(302);
            }

            $menus = DB::table('menus')
                ->select('menus.*', 'boards.title as board_title')
                ->leftJoin('boards', 'menus.target_id', '=', 'boards.id')
                ->where('menus.group_id', $groupId)
                ->where('menus.is_deleted', 0)
                ->orderBy('menus.order_num', 'asc')
                ->get();

            $allBoards = DB::table('boards')->where('is_deleted', 0)->get();

            $content = $blade->render('admin.menus.manage', [
                'title' => $group->name . ' 메뉴 구성',
                'group' => $group,
                'menus' => $menus,
                'allBoards' => $allBoards
            ]);
            $response->getBody()->write($content);
            return $response;
        });

        $group->post('', function (Request $request, Response $response) use ($basePath) {
            $data = $request->getParsedBody();
            
            $groupId = $data['group_id'];
            $slug = trim($data['slug']);
            $menuType = $data['type'];
            $targetId = (int)($data['target_id'] ?? 0);
            $title = trim($data['title']);

            $exists = DB::table('menus')
                ->where('group_id', $groupId)
                ->where('slug', $slug)
                ->exists();

            if ($exists) {
                $_SESSION['flash_message'] = '이미 사용 중인 주소입니다.';
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $basePath . "/admin/menus/$groupId")->withStatus(302);
            }

            if (in_array($slug, ['admin', 'login', 'register', 'logout', 'au', 'page'])) {
                $_SESSION['flash_message'] = '사용할 수 없는 주소입니다.';
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $basePath . "/admin/menus/$groupId")->withStatus(302);
            }

            DB::table('menus')->insert([
                'group_id' => $groupId,
                'type' => $menuType,
                'target_id' => $targetId,
                'title' => $title,
                'slug' => $slug,
                'order_num' => 0
            ]);

            $_SESSION['flash_message'] = '메뉴가 추가되었습니다.';
            $_SESSION['flash_type'] = 'success';
            return $response->withHeader('Location', $basePath . "/admin/menus/$groupId")->withStatus(302);
        });

        $group->post('/delete', function (Request $request, Response $response) use ($basePath) {
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
            return $response->withHeader('Location', $basePath . "/admin/menus/$groupId")->withStatus(302);
        });

        $group->post('/reorder', function (Request $request, Response $response) {
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
        });

    });
    $group->group('/users', function (RouteCollectorProxy $group) use ($blade, $basePath) {

        $group->get('', function (Request $request, Response $response) use ($blade) {
            $page = $_GET['page'] ?? 1;
            
            $users = DB::table('users')
                ->orderBy('created_at', 'desc')
                ->paginate(15, ['*'], 'page', $page);

            $content = $blade->render('admin.users.index', [
                'title' => '회원 관리',
                'users' => $users
            ]);
            $response->getBody()->write($content);
            return $response;
        });

        $group->get('/{id}', function (Request $request, Response $response, $args) use ($blade, $basePath) {
            $id = $args['id'];
            $user = DB::table('users')->find($id);

            if (!$user) {
                $_SESSION['flash_message'] = '존재하지 않는 회원입니다.';
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $basePath . '/admin/users')->withStatus(302);
            }

            $content = $blade->render('admin.users.edit', [
                'title' => '회원 정보 수정',
                'user' => $user
            ]);
            $response->getBody()->write($content);
            return $response;
        });

        $group->post('/update', function (Request $request, Response $response) use ($basePath) {
            $data = $request->getParsedBody();
            $id = $data['id'];

            $updateData = [
                'nickname' => trim($data['nickname']),
                'email' => trim($data['email']),
                'level' => (int)$data['level']
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            DB::table('users')
                ->where('id', $id)
                ->update($updateData);

            $_SESSION['flash_message'] = '회원 정보가 수정되었습니다.';
            $_SESSION['flash_type'] = 'success';
            return $response->withHeader('Location', $basePath . '/admin/users/' . $id)->withStatus(302);
        });

        $group->post('/delete', function (Request $request, Response $response) use ($basePath) {
            $data = $request->getParsedBody();
            $id = $data['id'];

            if ($id == $_SESSION['user_idx']) {
                $_SESSION['flash_message'] = '자기 자신을 삭제할 수는 없습니다.';
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $basePath . '/admin/users')->withStatus(302);
            }

            DB::table('users')
            ->where('id', $id)
            ->update([
                'is_deleted' => 1,
                'deleted_at' => date('Y-m-d H:i:s')
            ]);

            $_SESSION['flash_message'] = '회원이 삭제되었습니다.';
            $_SESSION['flash_type'] = 'success';
            return $response->withHeader('Location', $basePath . '/admin/users')->withStatus(302);
        });
    });
    $group->group('/profiles', function (RouteCollectorProxy $group) use ($blade, $basePath) {

        $group->get('', function (Request $request, Response $response) use ($blade) {
            $page = $_GET['page'] ?? 1;

            $groups = DB::table('groups')
                ->orderBy('created_at', 'desc')
                ->paginate(15, ['*'], 'page', $page);

            $content = $blade->render('admin.profiles.index', [
                'title' => '프로필 양식 설정',
                'group' => $groups
            ]);
            $response->getBody()->write($content);
            return $response;
        });

        $group->get('/{id}', function (Request $request, Response $response, $args) use ($blade, $basePath) {
            $id = $args['id'];
            $groupData = DB::table('groups')->where('id', $id)->where('is_deleted', 0)->first();

            if (!$groupData) {
                return $response->withHeader('Location', $basePath . '/admin/profiles')->withStatus(302);
            }

            $content = $blade->render('admin.profiles.edit', [
                'title' => '프로필 양식 설정 - ' . $groupData->name,
                'group' => $groupData
            ]);
            $response->getBody()->write($content);
            return $response;
        });

        $group->post('/update', function (Request $request, Response $response) use ($basePath) {
            $data = $request->getParsedBody();
            $id = $data['id'];

            $charFields = $data['char_fields'] ?? [];
            $cleanFields = [];
            if (is_array($charFields)) {
                foreach ($charFields as $field) {
                    if (!empty($field['name'])) {
                        $cleanFields[] = [
                            'name' => trim($field['name']),
                            'type' => $field['type'],
                            'required' => isset($field['required']) ? 1 : 0,
                            'options' => isset($field['options']) ? $field['options'] : ""
                        ];
                    }
                }
            }
            $jsonCharFields = !empty($cleanFields) ? json_encode($cleanFields, JSON_UNESCAPED_UNICODE) : null;

            DB::table('groups')
                ->where('id', $id)
                ->update([
                    'use_fixed_char_fields' => isset($data['use_fixed_char_fields']) ? (int)$data['use_fixed_char_fields'] : 0,
                    'char_fixed_fields' => $jsonCharFields
                ]);

            $_SESSION['flash_message'] = '프로필 양식 설정이 저장되었습니다.';
            $_SESSION['flash_type'] = 'success';
            
            return $response->withHeader('Location', $basePath . '/admin/profiles/' . $id)->withStatus(302);
        });
    });
    $group->group('/characters', function (RouteCollectorProxy $group) use ($blade, $basePath) {

        $group->get('', function (Request $request, Response $response) use ($blade) {
            $page = $_GET['page'] ?? 1;
            $search = $_GET['search'] ?? '';

            $query = DB::table('characters')
                ->join('users', 'characters.user_id', '=', 'users.id')
                ->join('groups', 'characters.group_id', '=', 'groups.id')
                ->leftJoin('boards', 'characters.board_id', '=', 'boards.id')
                ->select(
                    'characters.*', 
                    'users.nickname as owner_name', 
                    'groups.name as group_name',
                    'boards.title as board_title'
                )
                ->where('characters.is_deleted', 0);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('characters.name', 'LIKE', "%$search%")
                      ->orWhere('users.nickname', 'LIKE', "%$search%");
                });
            }

            $characters = $query->orderBy('characters.id', 'desc')->paginate(15, ['*'], 'page', $page);
            foreach($characters as $cha){
                if( mb_strlen($cha->name) > 15 ){
                    $cha->name = mb_substr($cha->name, 0, 12) . '...';
                }
            }

            $content = $blade->render('admin.characters.index', [
                'characters' => $characters,
                'search' => $search
            ]);
            $response->getBody()->write($content);
            return $response;
        });

        $group->get('/boards/{group_id}', function (Request $request, Response $response, $args) {
            $groupId = $args['group_id'];
            
            $boards = DB::table('boards')
                ->join('menus', 'boards.id', '=', 'menus.target_id')
                ->where('menus.group_id', $groupId)
                ->where('boards.type', 'character')
                ->where('boards.is_deleted', 0)
                ->select('boards.id', 'boards.title')
                ->distinct()
                ->get();
                
            $payload = json_encode($boards);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->post('/move', function (Request $request, Response $response) {
            $data = $request->getParsedBody();
            
            $idsParam = $data['char_ids'] ?? '';
            $targetBoardId = $data['target_board_id'];

            if (empty($idsParam) || empty($targetBoardId)) {
                $_SESSION['flash_message'] = "잘못된 요청입니다.";
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? '/admin')->withStatus(302);
            }

            $idArray = explode(',', $idsParam);

            DB::table('characters')
                ->whereIn('id', $idArray)
                ->update([
                    'board_id' => $targetBoardId,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            $count = count($idArray);
            $_SESSION['flash_message'] = "{$count}명의 캐릭터가 이동되었습니다.";
            $_SESSION['flash_type'] = 'success';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? '/admin')->withStatus(302);
        });

    });
    $group->group('/emoticons', function (RouteCollectorProxy $group) use ($blade, $basePath) {

        $group->get('', function (Request $request, Response $response) use ($blade) {
            $emoticon = DB::table('emoticons')
                        ->get();

            $content = $blade->render('admin.emoticons.index', [
                'emoticons' => $emoticon
            ]);
            $response->getBody()->write($content);
            return $response;
        });

        $group->post('', function (Request $request, Response $response) {
            $data = $request->getParsedBody();
            $files = $request->getUploadedFiles();
    
            $code = trim($data['code'] ?? '');
            $uploadedFile = $files['image'] ?? null;
    
            if (empty($code) || !$uploadedFile || $uploadedFile->getError() !== UPLOAD_ERR_OK) {
                $_SESSION['flash_message'] = '예약어와 이미지를 모두 입력해주세요.';
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? '/admin')->withStatus(302);
            }
    
            if (DB::table('emoticons')->where('code', $code)->exists()) {
                $_SESSION['flash_message'] = '이미 등록된 예약어입니다.';
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? '/admin')->withStatus(302);
            }
    
            $directory = __DIR__ . '/../public/data/uploads/emoticons';
            if (!is_dir($directory)) {
                @mkdir($directory, 0777, true);
            }

    
            $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
            $filename = 'emo_' . uniqid() . '.' . $extension;
            
            $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
    
            DB::table('emoticons')->insert([
                'code' => $code,
                'image_path' => '/public/data/uploads/emoticons/' . $filename,
                'created_at' => date('Y-m-d H:i:s')
            ]);
    
            $_SESSION['flash_message'] = '이모티콘이 등록되었습니다.';
            $_SESSION['flash_type'] = 'success';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? '/admin')->withStatus(302);
        });

        $group->post('/delete', function (Request $request, Response $response) {
            $data = $request->getParsedBody();
            $id = $data['id'];

            $emoticon = DB::table('emoticons')->find($id);

            if ($emoticon) {
                $filePath = __DIR__ . '/../public/data/uploads/emoticons/' . $emoticon->image_path;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                DB::table('emoticons')->delete($id);
            }

            $_SESSION['flash_message'] = '삭제처리되었습니다.';
            $_SESSION['flash_type'] = 'success';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? '/admin')->withStatus(302);
        });

    });
    $group->get('/plugins', [$adminPluginController, 'index']);
    $group->post('/plugins/toggle', [$adminPluginController, 'toggle']);

})->add($adminMiddleware);