<?php 
$step = 2;
include __DIR__ . '/header.php'; 
?>

<h2 class="text-xl font-bold text-gray-800 mb-2">라이선스 이용 동의</h2>
<p class="text-sm text-gray-500 mb-6">서비스 이용을 위해 약관에 동의해주세요.</p>

<div class="bg-white border border-gray-200 rounded-lg p-4 h-64 overflow-y-auto mb-6 text-sm text-gray-600 leading-relaxed shadow-inner">
    <div class="space-y-4 text-gray-700">
        <h3 class="text-lg font-bold text-gray-900">CUSTARD-BOARD 소프트웨어 사용권 계약</h3>
        <p class="text-xs text-gray-500 pb-2 border-b border-gray-200">
            본 소프트웨어를 설치하거나 사용하는 것은 아래의 약관에 동의하는 것으로 간주됩니다.
        </p>

        <h4 class="font-bold text-gray-800 mt-4">제 1 조 (정의 및 목적)</h4>
        <p>
            <strong>CUSTARD-BOARD</strong>(이하 "소프트웨어")는 사용자의 웹사이트 구축 및 운영을 돕기 위해 제작된 오픈소스 콘텐츠 관리 시스템(CMS)입니다. 
            본 소프트웨어는 <strong>GNU 일반 공중 사용 허가서(GNU General Public License, 이하 GPL) 버전 3</strong>에 따라 배포됩니다.
        </p>

        <h4 class="font-bold text-gray-800 mt-4">제 2 조 (사용의 권리)</h4>
        <p>
            사용자는 본 소프트웨어를 개인적 목적 또는 상업적 목적에 관계없이 자유롭게 무료로 설치하고 사용할 수 있습니다. 
            또한, 사용자는 소프트웨어의 소스 코드를 연구하고 자신의 필요에 맞게 수정하여 사용할 권리를 가집니다.
        </p>

        <h4 class="font-bold text-gray-800 mt-4">제 3 조 (배포 및 공유의 의무)</h4>
        <p>
            사용자가 본 소프트웨어를 수정한 후 이를 타인에게 배포(재배포)하거나, 이를 기반으로 파생 저작물을 만들어 배포하는 경우, 
            해당 저작물 또한 <strong>GPL v3 라이선스 하에 배포되어야 합니다.</strong> 
            이는 수정한 소스 코드를 공개해야 할 의무를 포함합니다. 단, 내부적으로만 사용하고 외부에 배포하지 않는 경우에는 소스 코드 공개 의무가 발생하지 않습니다.
        </p>

        <h4 class="font-bold text-gray-800 mt-4">제 4 조 (책임의 한계 및 보증의 부인)</h4>
        <p class="text-red-600 font-medium">
            본 소프트웨어는 "있는 그대로(AS-IS)" 제공되며, 개발자는 본 소프트웨어의 사용으로 인해 발생하는 어떠한 문제에 대해서도 보증하지 않습니다.
        </p>
        <ul class="list-disc pl-5 mt-1 space-y-1 text-sm">
            <li>특정 목적에의 적합성, 기능의 정확성, 무결성에 대해 보증하지 않습니다.</li>
            <li>본 소프트웨어 사용 중 발생한 데이터 손실, 서버 장애, 보안 문제, 금전적 손실 등에 대해 개발자 및 저작권자는 어떠한 법적 책임도 지지 않습니다.</li>
            <li>사용자는 자신의 책임하에 데이터 백업 및 보안 조치를 취해야 합니다.</li>
        </ul>

        <h4 class="font-bold text-gray-800 mt-4">제 5 조 (저작권의 귀속)</h4>
        <p>
            본 소프트웨어의 핵심 코어에 대한 저작권은 원작자에게 있으며, 
            사용자가 작성한 게시물, 데이터, 그리고 추가로 설치한 서드파티 플러그인에 대한 권리는 해당 사용자 및 플러그인 제작자에게 있습니다.
        </p>

        <h4 class="font-bold text-gray-800 mt-4">제 6 조 (라이선스 전문)</h4>
        <p>
            본 약관에서 명시하지 않은 사항은 GNU GPL v3의 영문 원문을 따릅니다. 
            라이선스 전문은 <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank" class="text-amber-600 underline">https://www.gnu.org/licenses/gpl-3.0.html</a>에서 확인할 수 있습니다.
        </p>

        <br>
        <p class="text-sm text-gray-500 text-center">
            Copyright © 2026 CUSTARD-BOARD Development LH커뮤공사. All rights reserved.
        </p>
    </div>
</div>

<div class="flex items-center mb-8 p-4 bg-amber-50 rounded-lg border border-amber-100">
    <input id="agree_check" type="checkbox" class="w-5 h-5 text-amber-600 rounded border-gray-300 focus:ring-amber-500 cursor-pointer">
    <label for="agree_check" class="ml-3 text-sm font-bold text-gray-700 cursor-pointer select-none">
        위 라이선스 내용을 모두 읽었으며, 이에 동의합니다.
    </label>
</div>

<div class="flex justify-between">
    <a href="<?php echo $basePath; ?>/" class="text-sm text-gray-500 hover:text-gray-800 self-center">이전</a>
    
    <a id="next_btn" href="<?php echo $basePath; ?>/step3" class="px-6 py-2 bg-gray-300 text-gray-500 rounded-lg font-bold transition pointer-events-none shadow-none">
        다음 단계로 >
    </a>
</div>

<script>
    const check = document.getElementById('agree_check');
    const btn = document.getElementById('next_btn');

    check.addEventListener('change', function() {
        if (this.checked) {
            btn.classList.remove('bg-gray-300', 'text-gray-500', 'pointer-events-none', 'shadow-none');
            btn.classList.add('bg-amber-600', 'text-white', 'hover:bg-amber-700', 'shadow');
        } else {
            btn.classList.add('bg-gray-300', 'text-gray-500', 'pointer-events-none', 'shadow-none');
            btn.classList.remove('bg-amber-600', 'text-white', 'hover:bg-amber-700', 'shadow');
        }
    });
</script>

<?php include __DIR__ . '/footer.php'; ?>