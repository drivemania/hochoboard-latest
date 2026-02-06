<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>쪽지 읽기</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <style>body { font-family: 'Pretendard', sans-serif; }</style>
</head>
<body class="bg-gray-50 p-4 h-screen flex flex-col">
    @if(isset($_SESSION['flash_message']))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" 
             class="fixed top-5 right-5 z-50 min-w-[300px] bg-white border-l-4 p-4 shadow-lg rounded 
             {{ $_SESSION['flash_type'] == 'error' ? 'border-red-500 text-red-700' : 'border-green-500 text-green-700' }}">
             <p class="font-bold">{{ $_SESSION['flash_type'] == 'error' ? '오류' : '성공' }}</p>
             <button @click="show = false" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
             </button>
             <p>{{ $_SESSION['flash_message'] }}</p>
        </div>
        @php unset($_SESSION['flash_message'], $_SESSION['flash_type']); @endphp
    @endif
    
    <div class="flex justify-between items-center mb-4">
        <a href="javascript:history.back()" class="text-gray-500 hover:text-gray-800 flex items-center text-sm font-bold">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            목록으로
        </a>
    
    </div>

    <div class="bg-white border rounded-lg shadow-sm flex-1 flex flex-col overflow-hidden">
        
        <div class="p-4 border-b bg-gray-50 flex justify-between items-start">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 font-bold text-lg mr-3">
                    {{ mb_substr($msg->sender_nickname, 0, 1) }}
                </div>
                <div>
                    <div class="font-bold text-gray-800">{{ $msg->sender_nickname }}</div>
                    <div class="text-xs text-gray-500">{{ date('Y년 m월 d일 H:i', strtotime($msg->created_at)) }}</div>
                </div>
            </div>
        </div>

        <div class="p-6 overflow-y-auto flex-1 text-gray-800 leading-relaxed whitespace-pre-wrap">{{ $msg->content }}</div>

        <div class="p-4 border-t bg-gray-50 justify-between flex">
            <form action="{{ $base_path }}/memo/delete" method="POST" onsubmit="return confirm('삭제하시겠습니까?');">
                <input type="hidden" name="id" value="{{ $msg->id }}">
                <button class="text-red-500 text-sm hover:underline">쪽지 삭제</button>
            </form>
            @if($msg->receiver_id == $_SESSION['user_idx'])
            <a href="{{ $base_path }}/memo/write?to_id={{ $msg->sender_id }}" class="bg-blue-600 text-white px-3 py-1.5 rounded text-sm hover:bg-blue-700 font-bold">
                답장하기
            </a>
            @endif
        </div>
    </div>

</body>
</html>