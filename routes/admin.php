<?php
use Slim\Routing\RouteCollectorProxy;
use App\Middleware\AdminMiddleware;

use App\Controller\Admin\AdminPluginController;
use App\Controller\Admin\AdminStructureController;
use App\Controller\Admin\AdminCharacterController;
use App\Controller\Admin\AdminContentController;
use App\Controller\Admin\AdminUserController;
use App\Controller\Admin\AdminHomeController;
use App\Controller\Admin\AdminShopController;

$basePath = $app->getBasePath();

$adminPluginController = new AdminPluginController($blade, $basePath);
$adminStructureController = new AdminStructureController($blade, $basePath);
$adminCharacterController = new AdminCharacterController($blade, $basePath);
$adminContentController = new AdminContentController($blade, $basePath);
$adminUserController = new AdminUserController($blade, $basePath);
$adminHomeController = new AdminHomeController($blade, $basePath);
$adminShopController = new AdminShopController($blade, $basePath);
$adminMiddleware = new AdminMiddleware($basePath);

$app->group('/admin', function (RouteCollectorProxy $group) use (
    $basePath,
    $adminPluginController,
    $adminStructureController,
    $adminCharacterController,
    $adminContentController,
    $adminUserController,
    $adminHomeController,
    $adminShopController,
    ) {

    $group->get('', [$adminHomeController, 'index']);

    $group->post('/issecret', [$adminHomeController, 'issecret']);
    
    $group->post('/ismemouse', [$adminHomeController, 'ismemouse']);

    $group->group('/system', function ($group)  use ($basePath) {
        $systemController = new \App\Controller\SystemController($basePath);
        $group->post('/clear-cache', [$systemController, 'clearViewCache']);
        $group->post('/clear-session', [$systemController, 'clearSession']);
    });

    $group->group('/groups', function (RouteCollectorProxy $group) use ($adminStructureController) {

        $group->get('', [$adminStructureController, 'groupList']);

        $group->post('', [$adminStructureController, 'groupStore']);

        $group->post('/delete',[$adminStructureController, 'groupDelete']);

        $group->get('/{id}', [$adminStructureController, 'groupEdit']);

        $group->post('/update', [$adminStructureController, 'groupUpdate']);

    });
    $group->group('/boards', function (RouteCollectorProxy $group) use ($adminContentController) {

        $group->get('', [$adminContentController, 'boardList']);

        $group->post('', [$adminContentController, 'boardStore']);

        $group->get('/{id}', [$adminContentController, 'boardEdit']);

        $group->post('/update', [$adminContentController, 'boardUpdate']);

        $group->post('/delete', [$adminContentController, 'boardDelete']);

        $group->post('/copy', [$adminContentController, 'boardCopy']);
    });
    $group->group('/menus', function (RouteCollectorProxy $group) use ($adminStructureController) {

        $group->get('', [$adminStructureController, 'menuList']);

        $group->get('/{group_id}', [$adminStructureController, 'menuEdit']);

        $group->post('', [$adminStructureController, 'menuStore']);

        $group->post('/delete', [$adminStructureController, 'menuDelete']);

        $group->post('/reorder', [$adminStructureController, 'menuReorder']);

    });
    $group->group('/users', function (RouteCollectorProxy $group) use ($adminUserController) {

        $group->get('', [$adminUserController, 'userList']);

        $group->get('/{id}', [$adminUserController, 'userEdit']);

        $group->post('/update', [$adminUserController, 'userUpdate']);

        $group->post('/delete', [$adminUserController, 'userDelete']);
        
        $group->post('/deleteList', [$adminUserController, 'userDeleteList']);
    });
    $group->group('/profiles', function (RouteCollectorProxy $group) use ($adminCharacterController) {

        $group->get('', [$adminCharacterController, 'profileList']);

        $group->get('/{id}', [$adminCharacterController, 'profileEdit']);

        $group->post('/update', [$adminCharacterController, 'profileUpdate']);
    });
    $group->group('/characters', function (RouteCollectorProxy $group) use ($adminCharacterController) {

        $group->get('', [$adminCharacterController, 'characterList']);

        $group->get('/boards/{group_id}', [$adminCharacterController, 'characterDetail']);

        $group->post('/move', [$adminCharacterController, 'characterMove']);

    });
    $group->group('/emoticons', function (RouteCollectorProxy $group) use ($adminContentController) {

        $group->get('', [$adminContentController, 'emoticonList']);

        $group->post('', [$adminContentController, 'emoticonStore']);

        $group->post('/delete', [$adminContentController, 'emoticonDelete']);

    });
    $group->group('/items', function (RouteCollectorProxy $group) use ($adminShopController) {

        $group->get('', [$adminShopController, 'itemList']);

        $group->post('/store', [$adminShopController, 'itemStore']);

        $group->post('/delete', [$adminShopController, 'itemDelete']);

    });
    $group->group('/plugins', function (RouteCollectorProxy $group) use ($adminPluginController) {

        $group->get('', [$adminPluginController, 'index']);

        $group->get('/setting', [$adminPluginController, 'setting']);

        $group->post('/toggle', [$adminPluginController, 'toggle']);
        
    });
    $group->group('/settlements', function (RouteCollectorProxy $group) use ($adminShopController) {

        $group->get('', [$adminShopController, 'settlementList']);

        $group->get('/{group_id}', [$adminShopController, 'settlementManage']);

        $group->post('/distribute', [$adminShopController, 'settlementDist']);

    });
    $group->group('/shops', function ($group) use ($adminShopController) {
        $group->get('', [$adminShopController, 'shopList']);
        $group->post('', [$adminShopController, 'shopStore']);
        $group->get('/{id}', [$adminShopController, 'shopEdit']);
        $group->post('/update', [$adminShopController, 'shopUpdate']);
        $group->post('/delete', [$adminShopController, 'shopDelete']);
        
        $group->post('/items/add', [$adminShopController, 'shopAddItem']);
        $group->post('/items/delete', [$adminShopController, 'shopDeleteItem']);
    });


})->add($adminMiddleware);