<?php
namespace App\Services;

class VersionService {
    const CURRENT_VERSION = '0.2.0';
    
    const UPDATE_URL = 'https://drivemania.github.io/custardboard-doc/version.json';
    
    private $cacheFile;

    public function __construct() {
        $this->cacheFile = __DIR__ . '/../cache/version_check.json';
    }

    public function checkUpdate() {
        if ($this->hasValidCache()) {
            return $this->getFromCache();
        }

        return $this->fetchFromRemote();
    }

    private function hasValidCache() {
        if (!file_exists($this->cacheFile)) return false;
        
        $data = json_decode(file_get_contents($this->cacheFile), true);
        if (!$data || !isset($data['checked_at'])) return false;

        // 현재시간 - 체크시간 < 86400초(24시간)
        return (time() - $data['checked_at']) < 86400;
    }

    private function getFromCache() {
        $data = json_decode(file_get_contents($this->cacheFile), true);
        return $this->processVersionData($data);
    }

    private function fetchFromRemote() {
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        
        $json = @file_get_contents(self::UPDATE_URL, false, $ctx);
        
        if ($json === false) {
            return ['has_update' => false, 'error' => '서버 연결 실패'];
        }

        $data = json_decode($json, true);
        
        $data['checked_at'] = time();
        
        if (!is_dir(dirname($this->cacheFile))) {
            mkdir(dirname($this->cacheFile), 0777, true);
        }
        file_put_contents($this->cacheFile, json_encode($data));

        return $this->processVersionData($data);
    }

    private function processVersionData($remoteData) {
        if (!isset($remoteData['latest_version'])) {
            return ['has_update' => false];
        }

        $hasUpdate = version_compare(self::CURRENT_VERSION, $remoteData['latest_version'], '<');

        return [
            'has_update'     => $hasUpdate,
            'current_version'=> self::CURRENT_VERSION,
            'latest_version' => $remoteData['latest_version'],
            'message'        => $remoteData['message'] ?? '',
            'link'           => $remoteData['download_url'] ?? '#',
            'importance'     => $remoteData['importance'] ?? 'normal'
        ];
    }
}