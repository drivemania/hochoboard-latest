<?php

class InstallerController {

    public function step1($request, $response) {
        $dataDir = __DIR__ . '/../../public/data';
        $is_writable = is_writable($dataDir);
        $perms = "생성안됨";
        if($is_writable) $perms = substr(sprintf('%o', fileperms($dataDir)), offset: -4);

        $requirements = [
            'PHP Version >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
            'PDO Extension' => extension_loaded('pdo'),
            'Mbstring Extension' => extension_loaded('mbstring'),
            "Data Dir Writable (현재: {$perms})" => $is_writable,
        ];
        
        return renderInstallView($response, 'step1', ['reqs' => $requirements]);
    }

    public function step2($request, $response) {
        return renderInstallView($response, 'step2');
    }

    public function step3($request, $response) {
        return renderInstallView($response, 'step3');
    }

    public function checkDbConnection($request, $response) {
        $data = $request->getParsedBody();
        global $basePath;

        try {
            $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $data['db_user'], $data['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['install_data'] = $data;

            return $response
                ->withHeader('Location', $basePath . '/step4')
                ->withStatus(302);

        } catch (PDOException $e) {
            $errMsg = $e->getMessage();
            $errCode = $e->getCode();
            switch ($errCode) {
                case 1045: {
                    $errMsg = "DB 아이디 또는 비밀번호가 일치하지 않습니다.";
                    break;
                }
                case 1049: {
                    $errMsg = "DB를 찾을 수 없습니다. DB Name에 오타가 있는지 확인해주세요.";
                    break;
                }
                case 2002: {
                    $errMsg = "DB서버에 접속할 수 없습니다. DB Host 혹은 Port가 맞는지 확인해주세요.";
                    break;
                }
                
            }
            return renderInstallView($response, 'step3', [
                'error' => 'DB 연결 실패: ' . $errMsg, 
                'data' => $data
            ]);
        }
    }

    public function step4($request, $response) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['install_data'])) {
            global $basePath;
            return $response
                ->withHeader('Location', $basePath . '/step3')
                ->withStatus(302);
        }

        return renderInstallView($response, 'step4');
    }

    private function debug_log($msg) {
        // public 폴더에 로그 파일 생성 (브라우저로 확인 가능)
        file_put_contents(__DIR__ . '/../../public/install_debug.txt', date('H:i:s') . " - " . $msg . "\n", FILE_APPEND);
    }
    public function runInstall($request, $response) {
        $this->debug_log("1");
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $adminData = $request->getParsedBody();

        $this->debug_log("2");

        $dbData = $_SESSION['install_data'] ?? null;

        if (!$dbData) {
            return renderInstallView($response, 'step4', ['error' => '세션이 만료되었습니다. 처음부터 다시 시도해주세요.']);
        }
        $this->debug_log("2-1");
        $data = array_merge($dbData, $adminData);
        $prefix = $data['db_prefix'] ?? 'hc_';

        try {
            $this->debug_log("2-2");
            $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $data['db_user'], $data['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->debug_log("3");

            $sqlFile = __DIR__ . '/../../database/schema.sql';
            if (file_exists($sqlFile)) {
                $rawSql = file_get_contents($sqlFile);
                $finalSql = str_replace('__PREFIX__', $prefix, $rawSql);
                $pdo->exec($finalSql);
            }

            $this->debug_log("4");

            $stmt = $pdo->prepare("INSERT INTO {$prefix}users (user_id, password, nickname, level, created_at) VALUES (?, ?, ?, ?, NOW())");
            $hashedPw = password_hash($data['admin_pw'], PASSWORD_DEFAULT);
            $stmt->execute([$data['admin_id'], $hashedPw, '관리자', 10]);

            $this->debug_log("5");

            $stmtGroup = $pdo->prepare("INSERT INTO {$prefix}groups (slug, name, is_default, created_at) VALUES (?, ?, 1, NOW())");
            $stmtGroup->execute([
                $adminData['group_slug'], 
                $adminData['group_name']
            ]);

            $this->debug_log("6");

            $this->createEnvFile($data);

            $this->debug_log("7");

            unset($_SESSION['install_data']); 
            
            return renderInstallView($response, 'complete');

        } catch (Exception $e) {
            return renderInstallView($response, 'step4', ['error' => '설치 오류: ' . $e->getMessage()]);
        }
    }
    private function createEnvFile($data) {
        $envContent = "";
        $envContent .= "DB_HOST={$data['db_host']}\n";
        $envContent .= "DB_PORT={$data['db_port']}\n";
        $envContent .= "DB_DATABASE={$data['db_name']}\n";
        $envContent .= "DB_USERNAME={$data['db_user']}\n";
        $envContent .= "DB_PASSWORD={$data['db_pass']}\n";
        $envContent .= "TABLE_PREFIX={$data['db_prefix']}\n";
        
        file_put_contents(__DIR__ . '/../../.env', $envContent);
    }
}