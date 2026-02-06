<?php
namespace App\Controller\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;

class AdminUserController {

    protected $blade;
    protected $basePath;
    protected $returnUrl;

    public function __construct($blade, $basePath)
    {
        $this->blade = $blade;
        $this->basePath = $basePath;
        $this->returnUrl = "";
    }

    public function userList(Request $request, Response $response) {
        $page = $_GET['page'] ?? 1;

        $sort = $_GET['sort'] ?? 'created_at'; // 기본값 created_at
        $order = $_GET['order'] ?? 'desc';     // 기본값 desc

        $users = DB::table('users')
            ->orderBy($sort, $order)
            ->paginate(15, ['*'], 'page', $page);

        $content = $this->blade->render('admin.users.index', [
            'title' => '회원 관리',
            'users' => $users,
            'sort'  => $sort,
            'order' => $order,
        ]);
        $response->getBody()->write($content);
        return $response;
    }

    public function userEdit(Request $request, Response $response, $args) {
        $id = $args['id'];
        $user = DB::table('users')->find($id);

        if (!$user) {
            $_SESSION['flash_message'] = '존재하지 않는 회원입니다.';
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/admin/users')->withStatus(302);
        }

        $content = $this->blade->render('admin.users.edit', [
            'title' => '회원 정보 수정',
            'user' => $user
        ]);
        $response->getBody()->write($content);
        return $response;
    }

    public function userUpdate(Request $request, Response $response) {
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
        return $response->withHeader('Location', $this->basePath . '/admin/users/' . $id)->withStatus(302);
    }

    public function userDelete(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $id = $data['id'];

        if ($id == $_SESSION['user_idx']) {
            $_SESSION['flash_message'] = '자기 자신을 삭제할 수는 없습니다.';
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/admin/users')->withStatus(302);
        }

        DB::table('users')
        ->where('id', $id)
        ->update([
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s')
        ]);

        $_SESSION['flash_message'] = '회원이 삭제되었습니다.';
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $this->basePath . '/admin/users')->withStatus(302);
    }

    public function userDeleteList(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $id = $data['ids']; //array

        if (in_array($_SESSION['user_idx'], $id)) {
            $_SESSION['flash_message'] = '자기 자신을 삭제할 수는 없습니다.';
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath . '/admin/users')->withStatus(302);
        }

        DB::table('users')
        ->whereIn('id', $id)
        ->update([
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s')
        ]);

        $_SESSION['flash_message'] = '선택한 회원이 삭제되었습니다.';
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $this->basePath . '/admin/users')->withStatus(302);
    }
}