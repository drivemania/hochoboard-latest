<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - 관리자</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Pretendard', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

    @if(isset($_SESSION['flash_message']))
        <div x-data="{ show: true }" 
            x-init="setTimeout(() => show = false, 3000)" 
            x-show="show" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="fixed top-5 right-5 z-50 min-w-[300px] shadow-lg rounded-lg overflow-hidden border-l-4 
            {{ $_SESSION['flash_type'] == 'error' ? 'bg-white border-red-500 text-red-700' : 'bg-white border-green-500 text-green-700' }}">
            
            <div class="p-4 flex items-center">
                <div class="mr-3">
                    @if($_SESSION['flash_type'] == 'error')
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    @else
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    @endif
                </div>
                <div>
                    <p class="font-bold">{{ $_SESSION['flash_type'] == 'error' ? '오류' : '성공' }}</p>
                    <p class="text-sm">{{ $_SESSION['flash_message'] }}</p>
                </div>
                <button @click="show = false" class="ml-auto pl-3 text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
        
        @php
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
        @endphp
    @endif

    <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">
        <div x-show="sidebarOpen" 
             @click="sidebarOpen = false" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-20 bg-black bg-opacity-50 md:hidden">
        </div>
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-30 w-64 bg-stone-800 text-white flex flex-col transition-transform duration-300 transform md:translate-x-0 md:static md:inset-0">
            <div class="flex justify-between p-6 text-xl font-bold border-b border-stone-700">
                <a href="{{ $base_path }}/admin">커스터드보드 설정</a>
                <button @click="sidebarOpen = false" class="md:hidden text-stone-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <nav class="flex-1 overflow-y-auto py-4">
                <ul class="space-y-1">
                    <li>
                        <a href="{{ $base_path }}/admin" class="block px-6 py-1 hover:bg-stone-700 hover:text-white transition">
                            대시보드
                        </a>
                    </li>
                    <li class="pt-3 pb-2 px-6 text-xs text-stone-500 uppercase tracking-wider font-bold">커뮤니티 그룹 관리</li>
                    <li>
                        <a href="{{ $base_path }}/admin/groups" class="block px-6 py-1 text-gray-300 hover:bg-stone-700 hover:text-white transition">
                            커뮤니티 그룹 설정
                        </a>
                    </li>
                    <li>
                        <a href="{{ $base_path }}/admin/menus" class="block px-6 py-1 text-gray-300 hover:bg-stone-700 hover:text-white transition">
                            메뉴 관리
                        </a>
                    </li>
                    <li class="pt-3 pb-2 px-6 text-xs text-stone-500 uppercase tracking-wider font-bold">캐릭터 관리</li>
                    <li>
                        <a href="{{ $base_path }}/admin/characters" class="block px-6 py-1 text-gray-300 hover:bg-stone-700 hover:text-white transition">
                            캐릭터 관리
                        </a>
                    </li>
                    <li>
                        <a href="{{ $base_path }}/admin/profiles" class="block px-6 py-1 text-gray-300 hover:bg-stone-700 hover:text-white transition">
                            프로필 양식 설정
                        </a>
                    </li>
                    <li class="pt-3 pb-2 px-6 text-xs text-stone-500 uppercase tracking-wider font-bold">사이트 관리</li>
                    <li>
                        <a href="{{ $base_path }}/admin/boards" class="block px-6 py-1 text-gray-300 hover:bg-stone-700 hover:text-white transition">
                            게시판/페이지 관리
                        </a>
                    </li>
                    <li>
                        <a href="{{ $base_path }}/admin/emoticons" class="block px-6 py-1 text-gray-300 hover:bg-stone-700 hover:text-white transition">
                            이모티콘 관리
                        </a>
                    </li>
                    <li class="pt-3 pb-2 px-6 text-xs text-stone-500 uppercase tracking-wider font-bold">아이템/정산 관리</li>
                    <li>
                        <a href="{{ $base_path }}/admin/items" class="block px-6 py-1 text-gray-300 hover:bg-stone-700 hover:text-white transition">
                            아이템 관리
                        </a>
                    </li>
                    <li>
                        <a href="{{ $base_path }}/admin/settlements" class="block px-6 py-1 text-gray-300 hover:bg-stone-700 hover:text-white transition">
                            정산 관리
                        </a>
                    </li>
                    <li>
                        <a href="{{ $base_path }}/admin/shops" class="block px-6 py-1 text-gray-300 hover:bg-stone-700 hover:text-white transition">
                            상점 관리
                        </a>
                    </li>
                    <li class="pt-3 pb-2 px-6 text-xs text-stone-500 uppercase tracking-wider font-bold">플러그인 관리</li>
                    <li>
                        <a href="{{ $base_path }}/admin/plugins" class="block px-6 py-1 text-gray-300 hover:bg-stone-700 hover:text-white transition">
                            플러그인 관리
                        </a>
                    </li>
                    @php \App\Support\Hook::filter('plugin_menu', $base_path); @endphp
                    <li class="pt-2 px-6 text-xs text-stone-500 uppercase tracking-wider font-bold">회원 관리</li>
                    <li>
                        <a href="{{ $base_path }}/admin/users" class="block px-6 py-1 text-gray-300 hover:bg-stone-700 hover:text-white transition">
                            회원 관리
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="p-4 border-t border-gray-700">
                <a href="{{ $base_path }}/" class="block w-full text-center py-1 bg-gray-700 rounded hover:bg-gray-600 transition text-sm">
                    사이트로 돌아가기
                </a>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm p-4 flex justify-between items-center z-10">
                <div class="flex items-center">
                    <button @click="sidebarOpen = true" class="text-stone-500 focus:outline-none md:hidden mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    
                    <h2 class="text-lg font-semibold text-gray-700">@yield('header', '대시보드')</h2>
                </div>

                <div class="text-sm text-gray-600">
                    <span class="hidden sm:inline">접속자:</span> 
                    <strong>{{ $_SESSION['nickname'] }}</strong>
                    <span class="hidden sm:inline">(최고관리자)</span>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>