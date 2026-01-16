<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SystemController {

    protected $basePath;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    public function clearViewCache(Request $request, Response $response) {
        $cacheDir = __DIR__ . '/../../cache'; 
        if (is_dir($cacheDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
    
            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                    @unlink($file->getRealPath());
                }
            }
        }
    
        return $this->redirectBack($response, '뷰 캐시(PHP 파일)가 삭제되었습니다.');
    }

    public function clearSession(Request $request, Response $response) {
        $sessionPath = session_save_path();
        if (empty($sessionPath)) {
            $sessionPath = sys_get_temp_dir();
        }
        $files = glob(rtrim($sessionPath, '/\\') . '/sess_*');

        $deletedCount = 0;
        foreach ($files as $file) {
            if (is_file($file) && is_writable($file)) {
                unlink($file);
                $deletedCount++;
            }
        }
        session_destroy();
        session_start();
        return $this->redirectBack($response, '모든 세션이 초기화되었습니다. 다시 로그인해주세요.');
    }

    private function emptyDir($dir) {
        if (!is_dir($dir)) return;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }
    }

    private function redirectBack($response, $msg) {
        $_SESSION['flash_msg'] = $msg; 
        return $response
            ->withHeader('Location', $this->basePath . '/admin') // 대시보드로 복귀
            ->withStatus(302);
    }
}