<?php 
$step = 1;
include __DIR__ . '/header.php'; 

$allPassed = true;
?>

<h2 class="text-xl font-bold text-gray-800 mb-2">서버 환경 점검</h2>
<p class="text-sm text-gray-500 mb-6">커스터드보드를 설치해주셔서 감사합니다!<BR>설치를 위해 서버 환경을 확인할게요.</p>

<div class="space-y-3 mb-8">
<?php foreach ($reqs as $name => $pass): ?>
    <div class="flex items-center justify-between p-3 rounded-lg <?php echo $pass ? 'bg-green-50' : 'bg-red-50'; ?>">
        <span class="text-sm font-medium <?php echo $pass ? 'text-gray-700' : 'text-red-600'; ?>">
            <?php echo $name; ?>
        </span>
        <div class="text-right">
            <?php if ($pass): ?>
                <span class="text-green-600 font-bold text-sm">OK ✅</span>
            <?php else: $allPassed = false; ?>
                <span class="text-red-500 font-bold text-sm">FAIL ❌</span>
                <?php if (strpos($name, 'Data Dir') !== false): ?>
                    <p class="text-xs text-red-400 mt-1">public/data 디렉토리를 확인해주세요.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<div class="flex justify-end">
    <?php if ($allPassed): ?>
        <a href="<?php echo $basePath; ?>/step2" class="px-6 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold transition">
            다음 단계로 >
        </a>
    <?php else: ?>
        <button disabled class="px-6 py-2 bg-gray-300 text-gray-500 rounded-lg font-bold cursor-not-allowed">
            문제를 해결해주세요
        </button>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>