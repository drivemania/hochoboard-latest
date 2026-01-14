<?php 
$step = 3;
include __DIR__ . '/header.php'; 

?>

<h2 class="text-xl font-bold text-gray-800 mb-2">초기 설정</h2>
<p class="text-sm text-gray-500 mb-6">관리자 계정과 생성할 커뮤니티 정보를 입력하세요.</p>

<?php if (!empty($error)): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-700 text-sm rounded">⚠️ <?php echo $error; ?></div>
<?php endif; ?>

<form action="<?php echo $basePath; ?>/install" method="POST" class="space-y-4">
    
    <?php foreach ($_SESSION['install_data'] as $key => $val): ?>
        <?php if(strpos($key, 'db_') === 0) echo '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($val).'">'; ?>
    <?php endforeach; ?>

    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1">관리자 ID</label>
        <input type="text" name="admin_id" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-indigo-500" required>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1">비밀번호</label>
            <input type="password" name="admin_pw" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-indigo-500" required>
        </div>
        <div>
             <label class="block text-xs font-bold text-gray-600 mb-1">닉네임</label>
            <input type="text" name="admin_nick" value="관리자" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-indigo-500" required>
        </div>
    </div>

    <div class="space-y-4">
        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider border-b pb-1 mt-2">커뮤니티</h3>
        
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">커뮤니티 이름</label>
                <input type="text" name="group_name" value="" placeholder="OO아파트, 무협 커뮤니티 XXX" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-indigo-500" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">주소 ID (Slug)</label>
                <input type="text" name="group_slug" value="main" pattern="[a-z0-9\-]+" title="영문 소문자, 숫자, 하이픈(-)만 가능합니다." class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-indigo-500 bg-gray-50" required>
                <p class="text-xs text-gray-400 mt-1">URL에 사용됩니다. (예: /main)</p>
            </div>
        </div>
    </div>

    <div class="pt-6">
        <button type="submit" id="install_btn" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold text-lg shadow-lg transition transform hover:-translate-y-0.5">
            설치 시작하기
        </button>
    </div>
</form>

<div id="loading_overlay" class="hidden fixed inset-0 bg-gray-900 bg-opacity-75 z-50 flex flex-col items-center justify-center transition-opacity duration-300">
    <svg class="animate-spin h-16 w-16 text-white mb-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    
    <h3 class="text-white text-2xl font-bold mb-2">설치 진행 중...</h3>
    <p class="text-gray-300 text-sm animate-pulse">데이터베이스 테이블을 생성하고 있습니다.<br>잠시만 기다려주세요.</p>
</div>

<script>
    const form = document.querySelector('form');
    const overlay = document.getElementById('loading_overlay');
    const btn = document.getElementById('install_btn');

    form.addEventListener('submit', function(e) {
        if (!confirm('입력하신 정보로 설치를 진행하시겠습니까?\n(설치 중 페이지를 닫지 마세요)')) {
            e.preventDefault();
            return;
        }

        overlay.classList.remove('hidden');
        
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        btn.innerHTML = '설치 중...';
        
    });
</script>

<?php include __DIR__ . '/footer.php'; ?>