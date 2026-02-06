<?php 
$step = 2;
include __DIR__ . '/header.php'; 
?>

<h2 class="text-xl font-bold text-gray-800 mb-2">데이터베이스 설정</h2>
<p class="text-sm text-gray-500 mb-6">MySQL 데이터베이스 연결 정보를 입력하세요.</p>

<?php if (!empty($error)): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-700 text-sm rounded border border-red-200">
        ⚠️ <?php echo $error; ?>
    </div>
<?php endif; ?>

<form action="<?php echo $basePath; ?>/step3" method="POST" class="space-y-4">
    <div class="grid grid-cols-3 gap-4">
        <div class="col-span-2">
            <label class="block text-xs font-bold text-gray-600 mb-1">DB Host</label>
            <input type="text" name="db_host" value="<?php echo $data['db_host'] ?? '127.0.0.1'; ?>" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-amber-500" required>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1">Port</label>
            <input type="text" name="db_port" value="<?php echo $data['db_port'] ?? '3306'; ?>" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-amber-500" required>
        </div>
    </div>

    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1">DB Name</label>
        <input type="text" name="db_name" value="<?php echo $data['db_name'] ?? ''; ?>" placeholder="ex) my_board" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-amber-500" required>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1">Username</label>
            <input type="text" name="db_user" value="<?php echo $data['db_user'] ?? ''; ?>" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-amber-500" required>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1">Password</label>
            <input type="password" name="db_pass" value="<?php echo $data['db_pass'] ?? ''; ?>" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-amber-500">
        </div>
    </div>

    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1">테이블 접두사(prefix)</label>
        <input type="text" name="db_prefix" value="<?php echo $data['db_prefix'] ?? 'hc_'; ?>" placeholder="ex) hc_" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-amber-500" required>
    </div>

    <div class="pt-4 flex justify-between">
        <a href="<?php echo $basePath; ?>/" class="text-sm text-gray-500 hover:text-gray-800 self-center">이전</a>
        <button type="submit" class="px-6 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold transition shadow">
            연결 확인
        </button>
    </div>
</form>

<?php include __DIR__ . '/footer.php'; ?>