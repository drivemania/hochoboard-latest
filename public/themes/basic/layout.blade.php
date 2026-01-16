@extends('layouts.app')

@section('theme_content')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<link href="{{ $themeUrl }}/style.css?v={{ date("YmdHis") }}"  rel="stylesheet" type="text/css"></link>
@endpush

<div x-data="{ mobileMenuOpen: false }" class="relative min-h-screen flex flex-col pt-[61px]">

    {{-- [Basic 테마] 상단 네비게이션 --}}
    <header class="fixed top-0 w-full left-0 bg-white border-b border-gray-200 z-40">
        <div class="max-w-5xl mx-auto px-4 py-4 flex justify-between items-center">
            
            <a href="{{ $mainUrl }}" class="text-xl font-bold text-indigo-600 relative shrink-0">
                {{ $group->name ?? 'HOCHOBOARD' }}
            </a>

            <div class="hidden md:flex items-center space-x-6 text-sm font-medium text-gray-500">
                @if(isset($group))
                    @hc_menu($group->slug)
                @endif
            </div>

            <div class="flex items-center gap-4 shrink-0">
                <nav class="hidden md:flex items-center space-x-4 text-sm font-medium text-gray-500">
                    @if(isset($_SESSION['user_id']))
                        <span class="text-gray-800">{{ $_SESSION['nickname'] }}님</span>
                        <a href="{{ $base_path }}/logout" class="text-red-500 hover:text-red-700">로그아웃</a>
                    @else
                        <a href="{{ $base_path }}/login" class="hover:text-indigo-600">로그인</a>
                    @endif
                </nav>
                <button @click="mobileMenuOpen = true" class="md:hidden text-gray-500 hover:text-indigo-600 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <div x-show="mobileMenuOpen" 
            @click="mobileMenuOpen = false"
            x-transition.opacity
            class="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"
            x-cloak>
    </div>

    <div x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 z-50 w-64 bg-white shadow-2xl overflow-y-auto md:hidden flex flex-col"
            x-cloak>

        <div class="p-4 border-b flex justify-between items-center shrink-0">
            <span class="font-bold text-gray-700">메뉴</span>
            <button @click="mobileMenuOpen = false" class="text-gray-500 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="p-4 flex-1 flex flex-col">
            
            <div class="mobile-menu-container flex flex-col font-medium flex-1 overflow-y-auto">
                @if(isset($group))
                    @hc_menu($group->slug)
                @endif
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 shrink-0">
                @if(isset($_SESSION['user_id']))
                    <div class="flex items-center justify-between mb-3">
                        <span class="font-bold text-gray-800 text-lg">{{ $_SESSION['nickname'] }}님</span>
                    </div>
                    <a href="{{ $base_path }}/logout" class="block w-full text-center py-2 border border-red-200 text-red-500 rounded hover:bg-red-50 text-sm font-bold">
                        로그아웃
                    </a>
                @else
                    <a href="{{ $base_path }}/login" class="block w-full text-center py-3 bg-indigo-600 text-white rounded hover:bg-indigo-700 font-bold shadow-md">
                        로그인 / 회원가입
                    </a>
                @endif
            </div>

        </div>
        
        <div class="p-4 bg-gray-50 text-center text-xs text-gray-400 shrink-0">
            &copy; {{ $group->name ?? 'HOCHO' }}
        </div>
    </div>

    {{-- [Basic 테마] 메인 컨텐츠 영역 --}}
    <main class="max-w-5xl mx-auto px-4 py-8 flex-grow w-full">
        @yield('content')
    </main>
    
    {{-- [Basic 테마] 푸터 --}}
    <footer class="bg-gray-50 border-t py-8 text-center text-gray-400 text-sm mt-auto">
        &copy; 2026 {{ $group->name ?? '' }}. Basic Theme by LH커뮤공사.
    </footer>

</div>
@endsection