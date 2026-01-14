<?php
use DI\Container;
use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Pagination\Paginator;
use Jenssegers\Blade\Blade;

$sessionLifeTime = 86400; 
ini_set('session.gc_maxlifetime', $sessionLifeTime); 
ini_set('session.cookie_lifetime', $sessionLifeTime); 
$sessionPath = __DIR__ . '/../cache/sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
ini_set('session.save_path', $sessionPath);

session_start();

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Asia/Seoul');

if (!file_exists(__DIR__ . '/../.env')) {
    require __DIR__ . '/../lib/Installer/installer_routes.php';
    exit;
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => env('DB_HOST', '127.0.0.1') . ':'. env('DB_PORT', '3306'),
    'database'  => env('DB_DATABASE', ''),
    'username'  => env('DB_USERNAME', 'root'),
    'password'  => env('DB_PASSWORD', ''),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => env('TABLE_PREFIX', 'hc_'),
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

Paginator::currentPathResolver(function () {
    return isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '/';
});

Paginator::currentPageResolver(function ($pageName = 'page') {
    $page = $_GET[$pageName] ?? 1;
    return filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1 ? (int) $page : 1;
});

$app = AppFactory::create();

$basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);

if (strpos($_SERVER['REQUEST_URI'], $basePath) === false) {
    $basePath = str_replace('/public', '', $basePath);
}

$app->setBasePath($basePath);

$views = [
    __DIR__ . '/../views',
    __DIR__ . '/themes',
    __DIR__ . '/skins',
];
$cache = __DIR__ . '/../cache';

$blade = new Blade($views, $cache);

require __DIR__ . '/../lib/Widget.php';
require __DIR__ . '/../lib/Helper.php';
require __DIR__ . '/../lib/ContentParser.php';
require __DIR__ . '/../lib/VersionService.php';

$pluginLoader = new \App\Services\PluginLoader($app);
$pluginLoader->boot();

$app->add(new \App\Middleware\AutoLoginMiddleware());

require __DIR__ . '/../routes/auth.php';
require __DIR__ . '/../routes/admin.php';
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

$app->add(function ($request, $handler) use ($blade, $app) {
    $blade->share('base_path', $app->getBasePath());
    return $handler->handle($request);
});


$blade->compiler()->directive('hc_menu', function ($expression) {
    return "<?php echo Widget::menu(\$base_path, $expression); ?>";
});

$blade->compiler()->directive('hc_login', function ($expression) {
    return "<?php echo Widget::login(\$base_path, $expression ?? null) ?>";
});

$blade->compiler()->directive('hc_latestPost', function ($page=10, $subLimit=20, $gSlug = null) {
    return "<?php echo Widget::latestPosts(\$base_path, $page, $subLimit, $gSlug) ?>";
});

$blade->compiler()->directive('hook', function ($expression) {
    return "<?php \App\Support\Hook::trigger($expression); ?>";
});

$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorMiddleware->setErrorHandler(
    \Slim\Exception\HttpNotFoundException::class,
    function () use ($app, $blade) {
        $response = $app->getResponseFactory()->createResponse();
        $content = $blade->render('errors.404', [
            'title' => '페이지를 찾을 수 없습니다',
            'errorMessage' => '요청하신 페이지가 존재하지 않습니다.'
        ]);
        $response->getBody()->write($content);
        return $response->withStatus(404);
    }
);

$app->run();