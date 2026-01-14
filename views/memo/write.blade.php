<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>쪽지 쓰기</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
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
        <h2 class="font-bold text-lg text-gray-800">✉️ 쪽지 보내기</h2>
        <a href="{{ $base_path }}/memo" class="text-gray-500 text-sm hover:underline">취소</a>
    </div>

    <form action="{{ $base_path }}/memo/send" method="POST" class="flex-1 flex flex-col">
        
        <div class="bg-white border rounded-lg shadow-sm p-4 flex-1 flex flex-col space-y-4">
            
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">받는 사람 (아이디)</label>
                @if($toUser)
                <input type="text" name="receiver_id" 
                       value="{{ $toUser->user_id ? $toUser->user_id : '' }}" 
                       class="w-full border-b-2 border-gray-200 py-2 focus:outline-none focus:border-blue-600 font-bold text-gray-800" 
                       placeholder="상대방 아이디 입력" required readonly>
                    <p class="text-xs text-blue-600 mt-1">To. {{ $toUser->nickname }}님에게 답장</p>
                @else
                <select id="receiver_id" name="receiver_id" class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500" required>
                    @foreach($receiverId as $rec)
                        <option value="{{ $rec->user_id }}">{{ $rec->user_id }}({{ $rec->nickname }})</option>
                    @endforeach
                </select>
                @endif
            </div>

            <div class="flex-1 flex flex-col">
                <label class="block text-xs font-bold text-gray-500 mb-1">내용</label>
                <textarea name="content" class="flex-1 w-full border rounded p-3 resize-none focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="내용을 입력하세요..." required></textarea>
            </div>

            <div>
                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 shadow-md transition">
                    전송하기 🚀
                </button>
            </div>
        </div>
    </form>

</body>
@if(!$toUser)
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new TomSelect("#receiver_id", {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            },
            placeholder: "캐릭터 이름을 입력하세요...",
        });
    });
</script>
@endif
</html>