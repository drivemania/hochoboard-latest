<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;

class MemoController {
    protected $blade;
    protected $basePath;

    public function __construct($blade, $basePath) {
        $this->blade = $blade;
        $this->basePath = $basePath;
    }

    public function index (Request $request, Response $response) {
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

        $content = $this->blade->render('memo.index', [
            'type' => $type,
            'messages' => $messages
        ]);
        $response->getBody()->write($content);
        return $response;
    }

    public function view (Request $request, Response $response, $args) {
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

        $content = $this->blade->render('memo.view', ['msg' => $msg]);
        $response->getBody()->write($content);
        return $response;
    }

    public function write (Request $request, Response $response) {
        $toId = $_GET['to_id'] ?? '';
        $toUser = null;
        if($toId) {
            $toUser = DB::table('users')->find($toId);
        }

        $sendUserList = DB::table('users')->where('is_deleted', '=', 0)->get();

        $content = $this->blade->render('memo.write', ['toUser' => $toUser, 'receiverId' => $sendUserList]);
        $response->getBody()->write($content);
        return $response;
    }

    public function send (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $receiverId = trim($data['receiver_id']);
        $content = trim($data['content']);

        $receiver = DB::table('users')->where('user_id', $receiverId)->first();
        if (!$receiver) {
            $_SESSION['flash_message'] = '존재하지 않는 사용자입니다.';
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $this->basePath."/memo/write")->withStatus(302);
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
        return $response->withHeader('Location', $this->basePath."/memo")->withStatus(302);
    }

    public function delete (Request $request, Response $response) {
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
        return $response->withHeader('Location', $this->basePath."/memo")->withStatus(302);
    }
}