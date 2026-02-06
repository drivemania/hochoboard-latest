<?php 
$step = 4; // 완료 상태
include __DIR__ . '/header.php'; 
?>

<div class="text-center py-8">
    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
    </div>

    <h2 class="text-2xl font-bold text-gray-800 mb-2">설치가 완료되었습니다!</h2>
    <p class="text-gray-500 mb-8">이제 관리자 계정으로 로그인하여<br>사이트를 꾸며보세요.</p>

    <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-8 text-sm text-left">
        <strong>📌 보안 주의사항</strong><br>
        설치가 완료되었으므로 자동으로 잠금 처리되었습니다.<br>
        다시 설치하려면 <code class="bg-white px-1 rounded border">.env</code> 파일을 삭제하세요.
    </div>

    <a href="<?php echo $basePath; ?>/" class="block w-full py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold transition">
        메인으로 이동하기
    </a>
</div>

<?php include __DIR__ . '/footer.php'; ?>