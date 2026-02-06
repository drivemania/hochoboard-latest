<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>이모티콘 선택</title>
    <script src="https://cdn.tailwindcss.com"></script> 
    <style>
        /* 스크롤바 디자인 (크롬/사파리) */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 h-screen flex flex-col overflow-hidden">

    <header class="bg-white border-b border-gray-200 px-4 py-3 flex justify-between items-center shrink-0">
        <h1 class="text-base font-bold text-gray-800 flex items-center">
            <span class="text-xl mr-2">😊</span> 이모티콘
        </h1>
        <button onclick="window.close()" class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </header>

    <main class="flex-1 overflow-y-auto p-4">
        @if($emoticon->isEmpty())
            <div class="h-full flex flex-col items-center justify-center text-gray-400">
                <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-sm">등록된 이모티콘이 없습니다.</p>
            </div>
        @else
            <div class="grid grid-cols-4 gap-2">
                @foreach($emoticon as $emo)
                <button onclick="copyEmoticon('{{ $emo->code }}')" 
                        class="group flex flex-col items-center justify-center p-2 bg-white border border-gray-200 rounded-lg hover:border-amber-400 hover:shadow-sm hover:bg-amber-50 transition active:scale-95 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        title="{{ $emo->code }}">
                    
                    <div class="h-10 w-10 flex items-center justify-center mb-1">
                        <img src="{{ $base_path }}{{ $emo->image_path }}" alt="{{ $emo->code }}" class="max-w-full max-h-full object-contain pointer-events-none">
                    </div>
                    
                    <span class="text-[10px] text-gray-400 group-hover:text-amber-600 truncate max-w-full px-1 font-mono">
                        {{ $emo->code }}
                    </span>
                </button>
                @endforeach
            </div>
        @endif
    </main>

    <script>
        function copyEmoticon(code) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(code).then(() => {
                    alert("명령어가 복사되었습니다. :" + code);
                }).catch(err => {
                });
            } 
            else {
                let textArea = document.createElement("textarea");
                textArea.value = code;
                
                textArea.style.position = "fixed";
                textArea.style.left = "-9999px";
                document.body.appendChild(textArea);
                
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand('copy');
                    alert("명령어가 복사되었습니다. :" + code);
                } catch (err) {
                    console.error('Fallback copy failed', err);
                    alert("이 브라우저에서는 복사를 지원하지 않습니다.");
                }
                
                document.body.removeChild(textArea);
            }
        }
    </script>
</body>
</html>