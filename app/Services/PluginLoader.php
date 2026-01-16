<?php
namespace App\Services;

use Illuminate\Database\Capsule\Manager as DB;
use Slim\App;

class PluginLoader {
    protected $app;

    public function __construct(App $app) {
        $this->app = $app;
    }

    private static $blackList = [
        'Illuminate\Database',
        'DB::',
        'exec(',
        'system(',
        'shell_exec',
        'eval(',
        'DROP TABLE',
        'DELETE FROM users',
    ];

    public function boot() {
        try {
            $actives = DB::table('plugins')->where('is_active', 1)->pluck('directory')->toArray();
        } catch (\Exception $e) {
            return;
        }

        foreach ($actives as $dir) {
            $file = __DIR__ . '/../../public/plugins/' . $dir . '/Plugin.php';
            
            if (file_exists($file)) {
                $content = file_get_contents($file);

                foreach (self::$blackList as $keyword) {
                    if (stripos($content, $keyword) !== false) {
                        throw new \Exception("보안 경고: 플러그인($file)에서 금지된 키워드('$keyword')가 발견되어 로드를 중단했습니다.");
                    }
                }
                require_once $file;
            }
        }
    }

}