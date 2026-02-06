<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as DB;

class ShopController extends Model
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

    public function itemUse(Request $request, Response $response, $args) {
        $invId = $args['inv_id'];
    
        $inventory = DB::table('character_items')
            ->join('items', 'character_items.item_id', '=', 'items.id')
            ->where('character_items.id', $invId)
            ->select('character_items.*', 'items.effect_type', 'items.effect_data', 'items.name', 'items.is_permanent')
            ->first();
    
        if (!$inventory || $inventory->quantity < 1) {
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
        }
        
        $char = DB::table('characters')->find($inventory->character_id);
        if ($char->user_id != $_SESSION['user_idx']) {
            $_SESSION['flash_message'] = "권한이 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
        }
    
        if ($inventory->effect_type === 'none') {
            $_SESSION['flash_message'] = "사용할 수 없는 아이템입니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
        }
    
        $msg = "{$inventory->name}을(를) 사용했습니다.";

        $group = DB::table('groups')->where('id', $char->group_id)->first();
        $pointName = $group->point_name;

        switch($inventory->effect_type) {
            case 'lottery':{
                $data = json_decode($inventory->effect_data, true);
                $point = rand($data['min_point'], $data['max_point']);
                
                DB::table('users')->where('id', $_SESSION['user_idx'])->increment('user_point', $point);
                $msg = "{$point} {$pointName}를 획득했습니다.";
                break;
            }
            case 'create_item':{
                $data = $request->getParsedBody();
                $uploadedFiles = $request->getUploadedFiles();
        
                if (isset($uploadedFiles['icon']) && $uploadedFiles['icon']->getError() === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../../public/data/uploads/items';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    
                    $filename = uniqid() . '_' . $uploadedFiles['icon']->getClientFilename();
                    $uploadedFiles['icon']->moveTo($uploadDir . '/' . $filename);
                    $iconPath = '/public/data/uploads/items/' . $filename;
                }else{
                    $_SESSION['flash_message'] = "아이콘은 필수 입력 사항입니다.";
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
                }

                $saveData = [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'icon_path' => $iconPath,
                    'effect_type' => "none",
                    'effect_data' => null,
                    'is_sellable' => 0,
                    'sell_price' => 0,
                ];
                $newItemId = DB::table('items')->insertGetId($saveData);

                $saveCharData = [
                    'character_id' => $char->id,
                    'item_id' => $newItemId,
                ];

                DB::table('character_items')->insert($saveCharData);

                $msg = '아이템이 생성되었습니다.';
                break;
            }
            case 'random_box':{
                $data = json_decode($inventory->effect_data, true);
                $itemList = [];
                $getItem = [];
                $getItemId = "";

                foreach($data['pool'] as $list){
                    for($i=0; $i<$list['weight']; $i++){
                        $itemList[] = $list['item_id'];
                    }
                }

                $getItemId = $itemList[array_rand($itemList)];

                $getItem = DB::table('items')->where('id', $getItemId)->where('is_deleted', 0)->first();

                if(!$getItem){
                    $_SESSION['flash_message'] = "삭제한 아이템이 포함되어 있어 사용할 수 없습니다.";
                    $_SESSION['flash_type'] = 'error';
                    return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
                }

                $saveCharData = [
                    'character_id' => $char->id,
                    'item_id' => $getItemId,
                ];

                DB::table('character_items')->insert($saveCharData);

                $msg = "{$getItem->name} 아이템을 획득했습니다.";
                break;
            }
        }
    
        if ($inventory->is_permanent < 1) {
            if ($inventory->quantity > 1) {
                DB::table('character_items')->where('id', $invId)->decrement('quantity');
            } else {
                DB::table('character_items')
                ->where('id', $invId)
                ->update([
                    'is_deleted' => 1,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        $_SESSION['flash_message'] = $msg;
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
    }
    public function itemSell(Request $request, Response $response, $args) {
        $invId = $args['inv_id'];
    
        $inventory = DB::table('character_items')
            ->join('items', 'character_items.item_id', '=', 'items.id')
            ->where('character_items.id', $invId)
            ->select('character_items.*', 'items.is_sellable', 'items.sell_price', 'items.name')
            ->first();
    
        $char = DB::table('characters')->find($inventory->character_id);
        if ($char->user_id != $_SESSION['user_idx']) {
            $_SESSION['flash_message'] = "권한이 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
        }
    
        if (!$inventory->is_sellable) {
            $_SESSION['flash_message'] = "판매할 수 없는 아이템입니다.";
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
        }
    
        if ($inventory->quantity > 1) {
            DB::table('character_items')->where('id', $invId)->decrement('quantity');
        } else {
            DB::table('character_items')
            ->where('id', $invId)
            ->update([
                'is_deleted' => 1,
                'deleted_at' => date('Y-m-d H:i:s')
            ]);
        }
    
        DB::table('users')->where('id', $_SESSION['user_idx'])->increment('user_point', $inventory->sell_price);
    
        $_SESSION['flash_message'] = "{$inventory->name}을(를) 판매하여 {$inventory->sell_price}P를 획득했습니다.";
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
    }
    public function itemGift(Request $request, Response $response, $args) {
        $invId = $args['inv_id'];
        $data = $request->getParsedBody();
        $targetId = trim($data['target_id']);
        $comment = cleanHtml($data['comment']);
    
        $inventory = DB::table('character_items')
            ->join('items', 'character_items.item_id', '=', 'items.id')
            ->where('character_items.id', $invId)
            ->select('character_items.*', 'items.name', 'items.is_binding')
            ->first();

        $char = DB::table('characters')->find($inventory->character_id);

        if ($char->user_id != $_SESSION['user_idx']) {
            $_SESSION['flash_message'] = "권한이 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
        }
    
        if ($inventory->is_binding == 1) {
            $_SESSION['flash_message'] = "이 아이템은 귀속되어 선물할 수 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
        }
    
        
        $targetChar = DB::table('characters')
            ->where('group_id', $char->group_id)
            ->where('id', $targetId)
            ->where('is_deleted', 0)
            ->first();
    
        if (!$targetChar) {
            $_SESSION['flash_message'] = "대상 캐릭터를 찾을 수 없습니다. (정확한 이름을 입력해주세요)";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
        }
    
        if ($targetChar->id == $char->id) {
            $_SESSION['flash_message'] = "자신에게 선물할 수 없습니다.";
            $_SESSION['flash_type'] = 'error';
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
        }

        $comment = $comment . "\n\rFrom. " . $char->name;
    
        DB::connection()->transaction(function () use ($inventory, $targetChar, $invId, $comment) {
            
            if ($inventory->quantity > 1) {
                DB::table('character_items')->where('id', $invId)->decrement('quantity');
            } else {
                DB::table('character_items')
                ->where('id', $invId)
                ->update([
                    'is_deleted' => 1,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);
            }

            //수량 증가하지 말고 그냥 새로운 아이템 추가(코멘트 붙여야되니까)
    
            DB::table('character_items')->insert([
                'character_id' => $targetChar->id,
                'item_id' => $inventory->item_id,
                'quantity' => 1,
                'comment' => $comment
            ]);
        });

        $targets = [
            array(
                'id' => $targetChar->id,
                'user_id' => $targetChar->user_id,
                'name' => $targetChar->name
            )
        ];

        $gaveItems = [
            array(
                'id' => $inventory->id,
                'name' => $inventory->name
            )
        ];

        DB::table('settlement_logs')->insert([
            'group_id' => $char->group_id,
            'admin_id' => 0,
            'target_count' => 1,
            'target_list' => !empty($targets) ? json_encode($targets, JSON_UNESCAPED_UNICODE) : null,
            'point_amount' => 0,
            'items_json' => !empty($gaveItems) ? json_encode($gaveItems, JSON_UNESCAPED_UNICODE) : null,
            'reason' =>  "{$char->name}님께 {$inventory->name}을(를) 선물받았습니다.",
            'created_at' => date('Y-m-d H:i:s')
        ]);
    
        $_SESSION['flash_message'] = "{$targetChar->name}님에게 {$inventory->name}을(를) 선물했습니다.";
        $_SESSION['flash_type'] = 'success';
        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
    }

    public function shopView(Request $request, Response $response, $args) {
        $shopId = $args['shop_id'];
        $groupSlug = $args['group_slug'] ?? "";
        $userId = $_SESSION['user_idx'] ?? 0;

        $shop = DB::table('shops')->find($shopId);
        if (!$shop || !$shop->is_open) {
            $_SESSION['flash_message'] = '운영 중인 상점이 아닙니다.';
            $_SESSION['flash_type'] = "error";
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
        }

        $user = DB::table('users')->find($userId);
        if (!$user) {
            $_SESSION['flash_message'] = '로그인이 필요합니다.';
            $_SESSION['flash_type'] = "error";
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
        }

        $myCharacters = DB::table('characters')
            ->where('user_id', $userId)
            ->where('group_id', $shop->group_id)
            ->where('is_deleted', 0)
            ->select('id', 'name', 'image_path')
            ->get();

        if ($myCharacters->isEmpty()) {
            $_SESSION['flash_message'] = '이 상점을 이용할 수 있는 캐릭터가 없습니다.';
            $_SESSION['flash_type'] = "error";
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
        }

        $group = DB::table('groups')
        ->where('id', $shop->group_id)
        ->where('is_deleted', 0)
        ->first();

        $currentUrl = "{$this->basePath}/au/{$group->slug}/shop/{$shopId}";
        if($group->is_default > 0) {
            $currentUrl = "{$this->basePath}/shop/{$shopId}";
        }

        $prefix = DB::connection()->getTablePrefix();

        $items = DB::table('shop_items')
            ->join('items', 'shop_items.item_id', '=', 'items.id')
            ->leftJoin('shop_purchase_logs', function($join) use ($userId) {
                $join->on('shop_items.id', '=', 'shop_purchase_logs.shop_item_id')
                    ->where('shop_purchase_logs.user_id', '=', $userId);
            })
            ->where('shop_items.shop_id', $shopId)
            ->groupBy('shop_items.id')
            ->orderBy('shop_items.display_order', 'asc')
            ->select([
                'shop_items.*',
                'items.name',
                'items.description',
                'items.icon_path',
                'items.is_binding',
                'items.is_permanent',
                DB::raw("IFNULL(SUM({$prefix}shop_purchase_logs.quantity), 0) as my_purchased_count")
            ])
            ->get();

        $themeUrl = $this->basePath . '/public/themes/' . $group->theme;
        $themeName = $group->theme ?? 'basic';
        $themeLayout = $themeName . ".layout";

        if($group->is_default > 0){
            $mainUrl = $this->basePath . '/';
        }else{
            $mainUrl = $this->basePath . '/au/' . $groupSlug;
        }

        $content = $this->blade->render('page.shop', [
            'shop' => $shop,
            'user' => $user,
            'items' => $items,
            'myCharacters' => $myCharacters,
            'currentUrl' => $currentUrl,
            'userPoint' => $user->user_point,
            'themeUrl' => $themeUrl,
            'themeLayout' => $themeLayout,
            'mainUrl' => $mainUrl,
            'group' => $group
        ]);
        $response->getBody()->write($content);
        return $response;
    }

    public function shopPurchase(Request $request, Response $response, $args) {
        $shopId = $args['shop_id'];
        $userId = $_SESSION['user_idx'];
        $data = $request->getParsedBody();

        $shopItemId = $data['item_id'];
        $characterId = $data['target_character_id'];
        $quantity = (int)($data['quantity'] ?? 1);

        if ($quantity < 1) {
            $_SESSION['flash_message'] = '최소 1개 이상 구매해야 합니다.';
            $_SESSION['flash_type'] = "error";
            return $response->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/')->withStatus(302);
        }

        try {
            DB::connection()->transaction(function () use ($shopId, $userId, $shopItemId, $characterId, $quantity) {
                
                $shopItem = DB::table('shop_items')
                    ->join('items', 'shop_items.item_id', '=', 'items.id')
                    ->where('shop_items.id', $shopItemId)
                    ->where('shop_items.shop_id', $shopId)
                    ->select('shop_items.*', 'items.name', 'items.is_permanent')
                    ->lockForUpdate()
                    ->first();

                if (!$shopItem) throw new \Exception("존재하지 않는 상품입니다.");

                if ($shopItem->purchase_limit > 0) {
                    $myLogCount = DB::table('shop_purchase_logs')
                        ->where('user_id', $userId)
                        ->where('shop_item_id', $shopItemId)
                        ->sum('quantity');
                    
                    if (($myLogCount + $quantity) > $shopItem->purchase_limit) {
                        throw new \Exception("구매 제한을 초과했습니다. (남은 횟수: " . ($shopItem->purchase_limit - $myLogCount) . "회)");
                    }
                }

                $totalPrice = $shopItem->price * $quantity;
                $user = DB::table('users')->where('id', $userId)->lockForUpdate()->first();
                
                if ($user->user_point < $totalPrice) {
                    throw new \Exception("포인트가 부족합니다. (필요: {$totalPrice} P)");
                }

                $myChar = DB::table('characters')->where('id', $characterId)->where('user_id', $userId)->first();
                if (!$myChar) throw new \Exception("내 캐릭터가 아닙니다.");

                DB::table('users')->where('id', $userId)->decrement('user_point', $totalPrice);

                $existingItem = DB::table('character_items')
                    ->where('character_id', $characterId)
                    ->where('item_id', $shopItem->item_id)
                    ->first();

                if ($existingItem && $shopItem->is_permanent != 1) {
                    DB::table('character_items')
                        ->where('id', $existingItem->id)
                        ->where('comment', null)
                        ->increment('quantity', $quantity);
                } else {
                    if ($existingItem) {
                        DB::table('character_items')->where('id', $existingItem->id)->increment('quantity', $quantity);
                    } else {
                        DB::table('character_items')->insert([
                            'character_id' => $characterId,
                            'item_id' => $shopItem->item_id,
                            'quantity' => $quantity
                        ]);
                    }
                }

                DB::table('shop_purchase_logs')->insert([
                    'shop_item_id' => $shopItem->item_id,
                    'user_id' => $userId,
                    'character_id' => $characterId,
                    'quantity' => $quantity,
                    'price_at_purchase' => $shopItem->price,
                    'purchased_at' => date('Y-m-d H:i:s')
                ]);
            });

            $_SESSION['flash_message'] = "구매가 완료되었습니다!";
            $_SESSION['flash_type'] = "success";

        } catch (\Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = "error";
        }

        return $response->withHeader('Location', $_SERVER['HTTP_REFERER'])->withStatus(302);
    }
}
