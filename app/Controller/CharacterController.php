<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;

class CharacterController
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


    public function store(Request $request, Response $response, $args)
    {
        $groupSlug = $args['group_slug'] ?? '';
        $menuSlug = $args['menu_slug'];

        if($groupSlug != ""){
            $group = DB::table('groups')->where('slug', $groupSlug)->first();
            $this->returnUrl = $this->basePath . "/au/$groupSlug/$menuSlug";
        }else{
            $group = DB::table('groups')->where('is_default', 1)->first();
            $this->returnUrl = $this->basePath . "/$menuSlug";
        }
        
        if (!$group) {
                $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', '/')->withStatus(302);
            };
    
        $menu = DB::table('menus')
            ->where('group_id', $group->id)
            ->where('slug', $menuSlug)
            ->first();
        
        if (!$menu || $menu->type !== 'character') {
                $_SESSION['flash_message'] = "페이지를 찾을 수 없습니다.";
                $_SESSION['flash_type'] = 'error';
                return $response->withHeader('Location', '/')->withStatus(302);
            };
    
        $board = DB::table('boards')->find($menu->target_id);
    
        $jsonProfile = $this->processCharacterData($request, $group);
        
        $data = $request->getParsedBody();
        $isMain = isset($data['is_main']) ? 1 : 0;
        
        if ($isMain) {
            DB::table('characters')
                ->where('group_id', $group->id)
                ->where('user_id', $_SESSION['user_idx'])
                ->update(['is_main' => 0]);
        }
    
        // 여기서부터 두상,전신 처리부
        $imageInputName = array('image_path', 'image_path2');
        $image_path = $image_path2 = "";
        $uploadedFiles = $request->getUploadedFiles(); 
        $jsonRel = isset($data['relationship']) ? json_encode($data['relationship']) : "";
    
        foreach ($imageInputName as $value) {
            if (isset($uploadedFiles[$value]) && $uploadedFiles[$value]->getError() === UPLOAD_ERR_OK) {
                $file = $uploadedFiles[$value];
                
                $uploadDir = __DIR__ . '/../../public/data/uploads/char';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $filename = uniqid() . '_' . $file->getClientFilename();
                $file->moveTo($uploadDir . '/' . $filename);
                
                $$value = $this->basePath . '/public/data/uploads/char/' . $filename; 
            }
            else {
                if (!empty($value) && !preg_match('/^https?:\/\//i', $value) && !str_starts_with($value, '/uploads/')) {
                    //필요하다면...지금은 아님
                }
                $$value = $data[$value]; 
            }
        }
        
    
        DB::table('characters')->insert([
            'group_id' => $group->id,
            'board_id' => $board->id,
            'user_id' => $_SESSION['user_idx'],
            'name' => trim($data['name']),
            'description' => trim($data['description']),
            'profile_data' => $jsonProfile,
            'relationship' => $jsonRel,
            'is_main' => $isMain,
            'image_path' => $image_path,
            'image_path2' => $image_path2,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    
        return $response->withHeader('Location', $this->returnUrl)->withStatus(302);
    
    }

    public function update(Request $request, Response $response, $args)
    {
        $charId = $args['id'];
        $groupSlug = $args['group_slug'] ?? '';
        $menuSlug = $args['menu_slug'];
        $data = $request->getParsedBody();

        $char = DB::table('characters')->find($charId);
        if ((!$char || $char->user_id != $_SESSION['user_idx']) && $_SESSION['level'] > 10) {
            $_SESSION['flash_message'] = "권한이 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
        } 
        
        if($groupSlug != ""){
            $group = DB::table('groups')->where('slug', $groupSlug)->first();
            $this->returnUrl = $this->basePath . "/au/$groupSlug/$menuSlug/$charId";
        }else{
            $group = DB::table('groups')->where('is_default', 1)->first();
            $this->returnUrl = $this->basePath . "/$menuSlug/$charId";
        }
        
        $jsonProfile = $this->processCharacterData($request, $group);
        
        $isMain = isset($data['is_main']) ? 1 : 0;

        // 여기서부터 두상,전신 처리부
        $imageInputName = array('image_path', 'image_path2');
        $image_path = $image_path2 = "";
        $uploadedFiles = $request->getUploadedFiles(); 

        foreach ($imageInputName as $value) {
            if (isset($uploadedFiles[$value]) && $uploadedFiles[$value]->getError() === UPLOAD_ERR_OK) {
                $file = $uploadedFiles[$value];
                
                $uploadDir = __DIR__ . '/../../public/data/uploads/char';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $filename = uniqid() . '_' . $file->getClientFilename();
                $file->moveTo($uploadDir . '/' . $filename);
                
                $$value = $this->basePath . '/public/data/uploads/char/' . $filename; 
            }
            else {
                if (!empty($value) && !preg_match('/^https?:\/\//i', $value) && !str_starts_with($value, '/uploads/')) {
                    //필요하다면...지금은 아님
                }
                $$value = $data[$value]; 
            }
        }

        // $jsonRel = isset($data['relationship']) ? json_encode($data['relationship']) : "";
        
        if ($isMain) {
            DB::table('characters')
                ->where('group_id', $group->id)
                ->where('user_id', $_SESSION['user_idx'])
                ->update(['is_main' => 0]);
        }

        DB::table('characters')
            ->where('id', $charId)
            ->update([
                'name' => trim($data['name']),
                'description' => trim($data['description']),
                'profile_data' => $jsonProfile,
                'is_main' => $isMain,
                'image_path' => $image_path,
                'image_path2' => $image_path2,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        $_SESSION['flash_message'] = "성공적으로 처리되었습니다.";
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $this->returnUrl ?? $this->basePath . '/')->withStatus(302);
    }

    public function addRelation(Request $request, Response $response, $args)
    {
        $id = $args['id'];
        $data = $request->getParsedBody();
        $character = DB::table('characters')->find($id);

        if ((!$character || $character->user_id != $_SESSION['user_idx']) && $_SESSION['level'] > 10) {
            $_SESSION['flash_message'] = "권한이 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
        } 

        $currentRels = $character->relationship ? json_decode($character->relationship, true) : [];
    
        $newRel = [
            'target_id' => (int) $data['to_char_id'],
            'favor'     => (int) ($data['favor'] ?? 0),
            'text'      => cleanHtml($data['relation_text'])
        ];
    
        $currentRels[] = $newRel;
    
        DB::table('characters')->where('id', $id)->update([
            'relationship' => json_encode($currentRels, JSON_UNESCAPED_UNICODE)
        ]);
    
        $_SESSION['flash_message'] = "관계가 추가되었습니다.";
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
    }

    public function delRelation(Request $request, Response $response, $args)
    {
        $id = $args['id'];
        $targetIdToDelete = (int) $request->getParsedBody()['target_id'];

        $character = DB::table('characters')->find($id);

        if ((!$character || $character->user_id != $_SESSION['user_idx']) && $_SESSION['level'] > 10) {
            $_SESSION['flash_message'] = "권한이 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
        } 

        $currentRels = json_decode($character->relationship ?? '[]', true);

        $newRels = array_filter($currentRels, function($rel) use ($targetIdToDelete) {
            return $rel['target_id'] !== $targetIdToDelete;
        });

        DB::table('characters')->where('id', $id)->update([
            'relationship' => json_encode(array_values($newRels), JSON_UNESCAPED_UNICODE)
        ]);

        $_SESSION['flash_message'] = "관계가 삭제되었습니다.";
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
    }

    public function updateRelation(Request $request, Response $response, $args)
    {
        $id = $args['id'];
        $data = $request->getParsedBody();
        $targetIdToUpdate = (int) $data['target_id'];
        $relationText = $data['relation_text'];
        $relationFavor = (int) $data['favor'];

        $character = DB::table('characters')->find($id);

        if ((!$character || $character->user_id != $_SESSION['user_idx']) && $_SESSION['level'] > 10) {
            $_SESSION['flash_message'] = "권한이 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
        } 

        $currentRels = json_decode($character->relationship ?? '[]', true);

        $newRels = array_map(function($rel) use ($targetIdToUpdate, $relationText, $relationFavor) {
            if ($rel['target_id'] == $targetIdToUpdate){
                $rel['favor'] = $relationFavor;
                $rel['text'] = cleanHtml($relationText);
            }
            return $rel;
        }, $currentRels);

        DB::table('characters')->where('id', $id)->update([
            'relationship' => json_encode(array_values($newRels), JSON_UNESCAPED_UNICODE)
        ]);

        $_SESSION['flash_message'] = "관계가 수정되었습니다.";
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
    }

    public function reorderRelation($request, $response, $args)
    {
        $id = $args['id'];
        $data = json_decode($request->getBody(), true);
        $newOrderIds = $data['order'] ?? [];

        if (empty($newOrderIds)) {
            $response->getBody()->write(json_encode(['success' => false]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $character = DB::table('characters')->where('id', $id)->where('is_deleted', 0)->first();
        if ((!$character || $character->user_id != $_SESSION['user_idx']) && $_SESSION['level'] > 10) {
            $response->getBody()->write(json_encode(['success' => false]));
            return $response->withHeader('Content-Type', 'application/json');
        } 
        
        $currentRels = json_decode($character->relationship ?? '[]', true);
    
        $relMap = [];
        foreach ($currentRels as $rel) {
            $relMap[$rel['target_id']] = $rel;
        }
    
        $sortedRels = [];

        foreach ($newOrderIds as $targetId) {
            if (isset($relMap[$targetId])) {
                $sortedRels[] = $relMap[$targetId];
                unset($relMap[$targetId]);
            }
        }

        if (!empty($relMap)) {
            foreach ($relMap as $rel) {
                $sortedRels[] = $rel;
            }
        }

        DB::table('characters')->where('id', $id)->update([
            'relationship' => json_encode($sortedRels, JSON_UNESCAPED_UNICODE)
        ]);
    
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function processCharacterData($request, $group) {
        $data = $request->getParsedBody();
        // $uploadedFiles = $request->getUploadedFiles(); 
        $finalProfile = [];
    
        if ($group->use_fixed_char_fields) {
            $fixedData = $data['fixed_data'] ?? [];
            
            foreach ($fixedData as $index => $field) {
                $key = $field['key'];
                $type = $field['type'];
                $value = trim($field['value'] ?? ''); 
                $value = cleanHtml($value);
    
                // 파일처리 안하기로...
                // if ($type === 'file') {
                //     $fileInputName = 'fixed_file_' . $index;
                    
                //     if (isset($uploadedFiles[$fileInputName]) && $uploadedFiles[$fileInputName]->getError() === UPLOAD_ERR_OK) {
                //         $file = $uploadedFiles[$fileInputName];
                        
                //         $uploadDir = __DIR__ . '/../public/data/uploads/char';
                //         if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        
                //         $filename = uniqid() . '_' . $file->getClientFilename();
                //         $file->moveTo($uploadDir . '/' . $filename);
                        
                //         $value = '/data/uploads/char/' . $filename; 
                //     }
                //     else {
                //         if (!empty($value) && !preg_match('/^https?:\/\//i', $value) && !str_starts_with($value, '/uploads/')) {
                //              // $value = ''; 
                //         }
                //     }
                // }
    
                $finalProfile[] = ['key' => $key, 'value' => $value, 'type' => $type];
            }
    
        } else {
            $profileData = $data['profile'] ?? [];
            if (is_array($profileData)) {
                foreach ($profileData as $item) {
                    if (!empty($item['key']) && !empty($item['value'])) {
                        $finalProfile[] = ['key' => $item['key'], 'value' => $item['value'], 'type' => 'text'];
                    }
                }
            }
        }
    
        return !empty($finalProfile) ? json_encode($finalProfile, JSON_UNESCAPED_UNICODE) : null;
    }
    
}