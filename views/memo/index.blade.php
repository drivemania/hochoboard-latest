<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>쪽지함</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Pretendard', sans-serif; }
    </style>
</head>
<body x-data="{ sidebarOpen: false }" class="bg-gray-50 h-screen flex flex-col">

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

    <header class="bg-white border-b px-4 py-3 flex justify-between items-center shrink-0 z-20 relative">
        <div class="flex items-center gap-3">
            <button @click="sidebarOpen = true" class="text-gray-500 md:hidden hover:text-blue-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h1 class="font-bold text-gray-800 text-lg">쪽지함</h1>
        </div>
        <button onclick="window.close()" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </header>

    <div class="flex-1 flex overflow-hidden relative">
        
        <div x-show="sidebarOpen" 
             @click="sidebarOpen = false"
             x-transition.opacity
             class="fixed inset-0 bg-black bg-opacity-50 z-30 md:hidden">
        </div>

        <nav :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
             class="fixed inset-y-0 left-0 z-40 bg-white border-r flex flex-col py-4 shrink-0 transition-transform duration-300 w-64 md:translate-x-0 md:static md:w-40 md:shadow-none shadow-xl">
            
            <div class="px-4 mb-4 flex justify-between items-center md:hidden">
                <span class="font-bold text-gray-500">메뉴</span>
                <button @click="sidebarOpen = false" class="text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <a href="{{ $base_path }}/memo/write" class="mx-4 mb-4 bg-blue-600 text-white text-center py-2 rounded hover:bg-blue-700 font-bold shadow-sm text-sm">
                쪽지 쓰기
            </a>
            
            <a href="{{ $base_path }}/memo?type=recv" class="px-4 py-2 text-sm font-medium hover:bg-gray-50 {{ $type == 'recv' ? 'text-blue-600 bg-blue-50 border-r-2 border-blue-600' : 'text-gray-600' }}">
                받은 쪽지함
            </a>
            <a href="{{ $base_path }}/memo?type=sent" class="px-4 py-2 text-sm font-medium hover:bg-gray-50 {{ $type == 'sent' ? 'text-blue-600 bg-blue-50 border-r-2 border-blue-600' : 'text-gray-600' }}">
                보낸 쪽지함
            </a>
        </nav>

        <main class="flex-1 overflow-y-auto p-4 w-full">
            <div class="bg-white rounded border shadow-sm overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 border-b">
                        <tr>
                            <th class="px-4 py-2 w-24 whitespace-nowrap">{{ $type == 'sent' ? '받는사람' : '보낸사람' }}</th>
                            <th class="px-4 py-2">내용</th>
                            <th class="px-4 py-2 w-20 text-right whitespace-nowrap">날짜</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($messages as $msg)
                        <tr class="hover:bg-gray-50 cursor-pointer transition {{ ($type == 'recv' && !$msg->read_at) ? 'bg-blue-50' : '' }}"
                            onclick="location.href='{{ $base_path }}/memo/view/{{ $msg->id }}'">
                            
                            <td class="px-4 py-3">
                                <span class="font-bold text-gray-700 block truncate w-20">
                                    {{ $type == 'sent' ? (\Illuminate\Database\Capsule\Manager::table('users')->find($msg->receiver_id)->nickname ?? '알수없음') : $msg->sender_nickname }}
                                </span>
                            </td>
                            
                            <td class="px-4 py-3">
                                <div class="truncate max-w-[150px] md:max-w-xs {{ ($type == 'recv' && !$msg->read_at) ? 'font-bold text-gray-900' : 'text-gray-600' }}">
                                    {{ $msg->content }}
                                </div>
                            </td>
                            
                            <td class="px-4 py-3 text-right text-xs text-gray-400 whitespace-nowrap">
                                {{ (date("Ymd") != date("Ymd", strtotime($msg->created_at)) ? date('m.d', strtotime($msg->created_at)) : date('H:i', strtotime($msg->created_at))) }}
                                @if($type == 'sent')
                                    <div class="mt-0.5">
                                        <span class="{{ $msg->read_at ? 'text-green-600' : 'text-gray-400' }}">
                                            {{ $msg->read_at ? '읽음' : '안읽음' }}
                                        </span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-gray-400">
                                쪽지가 없습니다.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($messages->lastPage() > 1)
            <div class="mt-4 flex justify-center space-x-1 pb-4">
                @for($i = 1; $i <= $messages->lastPage(); $i++)
                    <a href="?type={{ $type }}&page={{ $i }}" 
                       class="px-2 py-1 rounded border text-xs {{ $messages->currentPage() == $i ? 'bg-blue-600 text-white' : 'bg-white text-gray-600' }}">
                       {{ $i }}
                    </a>
                @endfor
            </div>
            @endif
        </main>
    </div>
</body>
</html>